<?php

namespace Wovosoft\LaravelTypescript\Helpers;

use Doctrine\DBAL\Schema\Column;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;
use Wovosoft\LaravelTypescript\Types\ColumnType;
use Wovosoft\LaravelTypescript\Types\Definition;
use Wovosoft\LaravelTypescript\Types\EnumType;
use Wovosoft\LaravelTypescript\Types\LaravelCastType;
use Wovosoft\LaravelTypescript\Types\PhpType;
use Wovosoft\LaravelTypescript\Types\RelationType;
use Wovosoft\LaravelTypescript\Types\Type;

/**
 * @todo This can be used for date strings in future
 *      https://blog.logrocket.com/handling-date-strings-typescript/
 */
readonly class Generator
{
    private string $indent;
    
    public function __construct(private ModelInspectionResult $result)
    {
        $this->indent = config('laravel-typescript.declare_namespace') ? "\t" : "";
    }

    /**
     * @description Get all definitions
     *
     * @return Collection<int,Definition>
     * @throws ReflectionException
     *
     */
    public function getDefinitions(): Collection
    {
        return $this
            ->getColumnDefinitions()
            ->merge($this->getCustomAttributeDefinitions())
            ->merge($this->getRelationDefinitions());
    }

    /**
     * @description Generates interface
     *
     * @return string
     * @throws ReflectionException
     *
     */
    public function __toString(): string
    {
        return $this->toTypescript();
    }

    /**
     * @description Generates interface
     *
     * @throws ReflectionException
     */
    public function toTypescript(): string
    {
        $typings = $this
            ->getDefinitions()
            ->implode(function (Definition $def, string $key) {
                return $this->indent . "\t$key" . ($def->isUndefinable ? '?' : '') . ": $def;";
            }, PHP_EOL);

        $reflection = Reflection::model($this->result->getModel());

        return str($typings)
            ->prepend($this->indent . "export interface " . $reflection->getShortName() . ' {' . PHP_EOL)
            ->append(PHP_EOL . "$this->indent}");
    }

    /**
     * @description Returns database column definitions
     *
     * @return Collection<int,Definition>
     * @throws ReflectionException
     *
     * @throws Exception
     */
    private function getColumnDefinitions(): Collection
    {
        $modelReflection = Reflection::model($this->result->getModel());
        $model = ModelInspector::parseModel($this->result->getModel());

        return $this->result
            ->getColumns()
            ->map(function (Column $column, string $key) use ($model, $modelReflection) {
                /*
                 * Database columns can be cast in different format
                 */
                if ($model->hasCast($key)) {
                    /*
                     * When, the cast is of type Enum it should be rendered as union type
                     * @todo : Rendered union type can be stored in separate scope rather then being
                     *         rendered as values directly.
                     */
                    if (is_string($model->getCasts()[$key]) && enum_exists($model->getCasts()[$key])) {
                        $type = EnumType::toType($model->getCasts()[$key]);
                    } elseif (LaravelCastType::isBuiltIn($model->getCasts()[$key])) {
                        $type = LaravelCastType::getType($model->getCasts()[$key]);
                    } else {
                        $type = PhpType::toType($model->getCasts()[$key]);
                    }
                } else {
                    $type = ColumnType::toType($column->getType());
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
                $types = $this->getAttributeReturnTypes($method);

                /*
                 * When there is return type is not defined
                 */
                if ($types->isEmpty()) {
                    $types->add(
                        new Type(
                            name      : config('laravel-typescript.custom_attributes.fallback_return_type'),
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
                    ),
                ];
            });
    }

    /**
     * @description Returns definitions of relations
     *
     * @return Collection<int,Definition>
     * @throws ReflectionException
     *
     */
    private function getRelationDefinitions(): Collection
    {
        if ($this->result->getModel() instanceof Model) {
            $model = $this->result->getModel();
        } else {
            $modelClass = $this->result->getModel();
            $model = new $modelClass();
        }

        $modelReflection = Reflection::model($model);

        return $this->result
            ->getRelations()
            ->mapWithKeys(function (ReflectionMethod $method) use ($model, $modelReflection) {
                try {
                    $relation = $method->invoke($model);
                    $relatedModel = get_class($relation->getRelated());

                } catch (Throwable) {
                    return [];
                }

                $relatedModelReflection = Reflection::model($relatedModel);

                /**
                 * When Model and Related Morels are from the same namespace,
                 * only short name is enough
                 * When Model and Related Model are from different namespaces,
                 * full namespace name should be used.
                 */

                $typeName = Reflection::isSameNamespace($relatedModelReflection, $modelReflection)
                    ? $relatedModelReflection->getShortName()
                    : $relatedModelReflection->getName();

                return [
                    $this->qualifyAttributeName($method) => new Definition(
                        namespace     : $modelReflection->getNamespaceName(),
                        name          : $method->getName(),
                        model         : get_class($model),
                        modelShortName: $modelReflection->getShortName(),
                        types         : [
                            new Type(
                                name      : $typeName,
                                isMultiple: RelationType::getReturnCountType($method->getReturnType()->getName())
                            ),
                        ],
                        //model relations are not set by their method nemo,
                        //so in typescript it should be nullable (not required) and undefinable
                        isRequired    : false,
                        isUndefinable : true
                    )
                ];
            });
    }

    /**
     * @description Returns Collection of Return Types (Type)
     *
     * @param ReflectionMethod $method
     *
     * @return Collection<int,Type>
     * @throws ReflectionException
     *
     */
    private function getAttributeReturnTypes(ReflectionMethod $method): Collection
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
            ->map(function (ReflectionNamedType $type) {
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

                    return PhpType::toType($type->getName() ?: config('laravel-typescript.custom_attributes.fallback_return_type'));
                } elseif (enum_exists($type->getName())) {
                    return EnumType::toType($type->getName());
                } /**
                 * When the return type is of type Model, it's qualified namespace should be used.
                 * The returning model should be rendered in separate interface.
                 */
                elseif (ModelInspector::isOfModelType($type->getName())) {
                    return Type::model(name: $type->getName());
                }

                $resolver = config('laravel-typescript.custom_attributes.accessor_resolvers');
                if (is_callable($resolver)) {
                    return $resolver($type->getName());
                }

                return Type::any();
            });
    }

    /**
     * @description Returns Prop name to be generated
     *
     * @param ReflectionMethod $method
     *
     * @return string
     */
    private function qualifyAttributeName(ReflectionMethod $method): string
    {
        $name = str($method->getName());

        if (Attributes::isRelation($method) || Attributes::isNewStyled($method)) {
            return $name->snake()->value();
        }

        return $name->after('get')->before('Attribute')->snake()->value();
    }

    /**
     * @description Determines if a props value is required or nullable
     *
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
