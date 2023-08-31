<?php

namespace Wovosoft\LaravelTypescript\Helpers;

use Doctrine\DBAL\Schema\Column;
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
use Wovosoft\LaravelTypescript\Types\PhpType;
use Wovosoft\LaravelTypescript\Types\ColumnType;
use Wovosoft\LaravelTypescript\Types\Type;

class Generator
{
    public function __construct(private readonly ModelInspectionResult $result)
    {
    }

    /**
     * @throws ReflectionException
     */
    public function generate()
    {
        return $this
            ->getColumnDefinitions()
            ->merge($this->getCustomAttributeDefinitions())
            ->merge($this->getRelationDefinitions())
            ->map(function (Definition $definition, string $key) {
                return (string)$definition;
            });
    }

    public function __toString(): string
    {
        return $this->generate()->toJson();
    }

    /**
     * @return Collection<int,Definition>
     * @throws ReflectionException
     */
    private function getColumnDefinitions(): Collection
    {
        $modelReflection = new ReflectionClass($this->result->getModel());

        return $this->result
            ->getColumns()
            ->map(function (Column $column) use ($modelReflection) {
                return new Definition(
                    namespace: $modelReflection->getNamespaceName(),
                    name: $column->getName(),
                    model: $modelReflection->getName(),
                    types: [
                        new Type(
                            name: ColumnType::toTypescript($column->getType()),
                            isMultiple: false
                        )
                    ],
                    isRequired: $column->getNotnull(),
                    isUndefinable: false
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
                return [
                    $this->qualifyAttributeName($method) => new Definition(
                        namespace: $method->getDeclaringClass()->getNamespaceName(),
                        name: $this->qualifyAttributeName($method),
                        model: $method->getDeclaringClass()?->getName(),
                        types: $this->getReturnTypes($method),
                        isRequired: $this->isRequiredReturnType($method),
                        isUndefinable: true
                    )
                ];
            });
    }

    private function getRelationDefinitions()
    {
        if ($this->result->getModel() instanceof Model) {
            $model = $this->result->getModel();
        } else {
            $modelClass = $this->result->getModel();
            $model = new $modelClass;
        }

        return $this->result
            ->getRelations()
            ->mapWithKeys(function (ReflectionMethod $method) use ($model) {
                /* @var Model $relatedModel */
                $relatedModel = $model->{$method->getName()}()->getRelated();

                dump($method->getReturnType()->getName());

                return [
                    $this->qualifyAttributeName($method) => new Definition(
                        namespace: $method->getDeclaringClass()->getNamespaceName(),
                        name: $method->getName(),
                        model: $method->getDeclaringClass()->getName(),
                        types: [
                            new Type(
                                name: get_class($relatedModel),
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
                        isRequired: false,
                        isUndefinable: true
                    )
                ];
            });
    }

    /**
     * @throws ReflectionException
     */
    private function getReturnTypes(ReflectionMethod $method): array
    {
        if (Attributes::isNewStyled($method)) {
            $type = Attributes::getReflectionOfNewStyleAttribute($method)->getReturnType();
        } else {
            $type = $method->getReturnType();
        }

        if ($type instanceof ReflectionNamedType) {
            $types = [$type];
        } else {
            $types = $type->getTypes();
        }

        return collect($types)
            ->map(function (ReflectionNamedType $type) use ($method) {
                if ($type->isBuiltin()) {
                    /**
                     * @todo In php it is not possible to define array's member type
                     *       but in docblock it is possible. So, do it later.
                     */
//                    if ($type->getName() === "array") {
//                        $doc = new DocBlock($method->getDocComment());
//                        if ($doc->hasTag('return')) {
//                            /** @var DocBlock\Tag\ReturnTag $returnTag */
//                            $returnTag = $doc->getTagsByName('return')[0];
//                            dump($returnTag->getTypes());
//                        }
//                    }

                    $name = PhpType::toTypescript($type->getName() ?: null);
                } else {
                    $name = $type->getName() ?: 'any';
                }

                return new Type(
                    name: $name,
                    isMultiple: false
                );
            })
            ->toArray();
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

            return !Attributes::getReflectionOfNewStyleAttribute($method)->getReturnType()->allowsNull();
        }

        return !$method->getReturnType()->allowsNull();
    }
}











