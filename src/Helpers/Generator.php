<?php

namespace Wovosoft\LaravelTypescript\Helpers;

use Doctrine\DBAL\Schema\Column;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use Wovosoft\LaravelTypescript\RelationType;
use Wovosoft\LaravelTypescript\Types\Definition;
use Wovosoft\LaravelTypescript\Types\EnumType;
use Wovosoft\LaravelTypescript\Types\PhpType;
use Wovosoft\LaravelTypescript\Types\ColumnType;
use Wovosoft\LaravelTypescript\Types\Type;

class Generator
{
    public function __construct(private readonly ModelInspectionResult $result)
    {
    }


    /**
     * @return Collection<int,Definition>
     * @throws ReflectionException
     */
    public function getDefinitions(): Collection
    {
        return $this
            ->getColumnDefinitions()
            ->merge($this->getCustomAttributeDefinitions())
            ->merge($this->getRelationDefinitions());
    }

    /**
     * @return string
     * @throws ReflectionException
     */
    public function __toString(): string
    {
        return $this->toTypescript();
    }

    /**
     * @throws ReflectionException
     */
    public function toTypescript(): string
    {
        $typings = $this
            ->getDefinitions()
            ->implode(function (Definition $definition, string $key) {
                return "\t\t$key" . ($definition->isUndefinable ? '?' : '') . ": $definition;";
            }, PHP_EOL);

        $reflection = new ReflectionClass($this->result->getModel());

        return str($typings)
            ->prepend("\texport interface " . ($reflection->getShortName()) . " {" . PHP_EOL)
            ->append(PHP_EOL . "\t}");
    }

    /**
     * @return Collection<int,Definition>
     * @throws ReflectionException
     * @throws Exception
     */
    private function getColumnDefinitions(): Collection
    {
        $modelReflection = new ReflectionClass($this->result->getModel());
        $model = ModelInspector::parseModel($this->result->getModel());

        return $this->result
            ->getColumns()
            ->map(function (Column $column, string $key) use ($model, $modelReflection) {
                if ($model->hasCast($key)) {
                    if (is_string($model->getCasts()[$key]) && enum_exists($model->getCasts()[$key])) {
                        $type = new Type(
                            name      : EnumType::toTypescript($model->getCasts()[$key]),
                            isMultiple: false
                        );
                    } else {
                        $type = new Type(
                            name      : PhpType::toTypescript($model->getCasts()[$key]),
                            isMultiple: false
                        );
                    }
                } else {
                    $type = new Type(
                        name      : ColumnType::toTypescript($column->getType()),
                        isMultiple: false
                    );
                }

                return new Definition(
                    namespace     : $modelReflection->getNamespaceName(),
                    name          : $column->getName(),
                    model         : $modelReflection->getName(),
                    modelShortName: $modelReflection->getShortName(),
                    types         : [$type],
                    isRequired    : $column->getNotnull(),
                    isUndefinable : false
                );
            });
    }

    /**
     * @return Collection<int,Definition>
     */
    private function getCustomAttributeDefinitions(): Collection
    {
        return $this->result
            ->getCustomAttributes()
            ->mapWithKeys(function (ReflectionMethod $method) {
                $decClass = $method->getDeclaringClass();
                $types = $this->getReturnTypes($method);

                if ($types->isEmpty()) {
                    $types->add(
                        new Type(
                            name      : config("laravel-typescript.custom_attributes.fallback_return_type"),
                            isMultiple: false
                        )
                    );
                }

                return [
                    $this->qualifyAttributeName($method) => new Definition(
                        namespace     : $decClass->getNamespaceName(),
                        name          : $this->qualifyAttributeName($method),
                        model         : $decClass->getName(),
                        modelShortName: $decClass->getShortName(),
                        types         : $types,
                        isRequired    : $this->isRequiredReturnType($method),
                        isUndefinable : true
                    )
                ];
            });
    }

    /**
     * @description Returns definitions of relations
     * @return Collection<int,Definition>
     * @throws ReflectionException
     */
    private function getRelationDefinitions(): Collection
    {
        if ($this->result->getModel() instanceof Model) {
            $model = $this->result->getModel();
        } else {
            $modelClass = $this->result->getModel();
            $model = new $modelClass;
        }

        $modelReflection = new ReflectionClass($model);

        return $this->result
            ->getRelations()
            ->mapWithKeys(function (ReflectionMethod $method) use ($model, $modelReflection) {
                /* @var Model $relatedModel */
                $relatedModel = $model->{$method->getName()}()->getRelated();
                $decClass = $method->getDeclaringClass();

                $relatedModelReflection = new ReflectionClass($relatedModel);

                if ($relatedModelReflection->getNamespaceName() === $modelReflection->getNamespaceName()) {
                    $typeName = $relatedModelReflection->getShortName();
                } else {
                    $typeName = $relatedModelReflection->getName();
                }

                return [
                    $this->qualifyAttributeName($method) => new Definition(
                        namespace     : $decClass->getNamespaceName(),
                        name          : $method->getName(),
                        model         : $decClass->getName(),
                        modelShortName: $decClass->getShortName(),
                        types         : [
                            new Type(
                                name      : $typeName,
                                isMultiple: match ($method->getReturnType()->getName()) {
                                    //HasOne::class,
                                    //HasOneThrough::class,
                                    //BelongsTo::class,
                                    //MorphOne::class,
                                    //MorphTo::class,
                                    //MorphPivot::class     => RelationType::One,

                                    HasManyThrough::class,
                                    HasMany::class,
                                    BelongsToMany::class,
                                    MorphMany::class,
                                    MorphToMany::class    => RelationType::Many,

                                    HasOneOrMany::class,
                                    MorphOneOrMany::class => RelationType::OneOrMany,

                                    default               => RelationType::One
                                }
                            )
                        ],
                        isRequired    : false,
                        isUndefinable : true
                    )
                ];
            });
    }

    /**
     * @param ReflectionMethod $method
     * @return Collection<int,Type>
     * @throws ReflectionException
     */
    private function getReturnTypes(ReflectionMethod $method): Collection
    {
        if (Attributes::isNewStyled($method)) {
            $type = Attributes::getReflectionOfNewStyleAttribute($method)->getReturnType();
        } else {
            $type = $method->getReturnType();
        }

        if ($type instanceof ReflectionNamedType) {
            $types = [$type];
        } else {
            $types = $type?->getTypes();
        }

        return collect($types)
            ->map(function (ReflectionNamedType $type) use ($method) {
                if ($type->isBuiltin()) {
                    /**
                     * @todo In php it is not possible to define array's member type
                     *       but in docblock it is possible. So, do it later.
                     */
                    //if ($type->getName() === "array") {
                    //    $doc = new DocBlock($method->getDocComment());
                    //    if ($doc->hasTag('return')) {
                    //        /** @var DocBlock\Tag\ReturnTag $returnTag */
                    //        $returnTag = $doc->getTagsByName('return')[0];
                    //        dump($returnTag->getTypes());
                    //    }
                    //}

                    $name = PhpType::toTypescript($type->getName() ?: config("laravel-typescript.custom_attributes.fallback_return_type"));
                } else {
                    $name = $type->getName() ?: 'any';
                }

                return new Type(
                    name      : $name,
                    isMultiple: false
                );
            });
    }

    private function qualifyAttributeName(ReflectionMethod $method): string
    {
        $name = str($method->getName());

        if (Attributes::isRelation($method) || Attributes::isNewStyled($method)) {
            return $name->snake()->value();
        }

        return $name->after("get")->before("Attribute")->snake()->value();
    }


    /**
     * @description Determines if a props value is required or nullable
     * @throws ReflectionException
     */
    private function isRequiredReturnType(ReflectionMethod $method): bool
    {
        /*
         * The New style attribute returns an instance of Attribute,
         * which has two methods get and set. We only need to care about the
         * return types of get method.
         * Because it is set dynamically as a Closure and the returning type is
         * added in the Closure, the attribute method should be called to
         * achieve the instance and then get the returning type from that
         * instance of get method.
         *
         * And according to the implementation logic, the method (prop():Attribute) should only return
         * the instance of Attribute, it shouldn't perform any other action.
         * So, it is safe to call it to have the instance of Attribute.
         */
        if (Attributes::isNewStyled($method)) {

            /*
             * NOTE: When $model->newStyleAttribute or $model->new_style_attribute
             * is called, it is being resolved directly by the model itself.
             * So, we have to call it as a function to get the return type of the
             * callback named get.
             */

            return !Attributes::getReflectionOfNewStyleAttribute($method)->getReturnType()?->allowsNull();
        }

        return !$method->getReturnType()?->allowsNull();
    }
}











