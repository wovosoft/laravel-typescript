<?php

namespace Wovosoft\LaravelTypescript\Helpers;

use Doctrine\DBAL\Schema\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionUnionType;
use Wovosoft\LaravelTypescript\Enums\RelationMultipleEnum;
use Wovosoft\LaravelTypescript\Transformers\Casts;
use Wovosoft\LaravelTypescript\Transformers\DatabaseType;
use Wovosoft\LaravelTypescript\Transformers\EnumType;
use Wovosoft\LaravelTypescript\Transformers\PhpType;
use Wovosoft\LaravelTypescript\Transformers\Typescript;

class Transformer
{
    /**
     * Main Entry Point of full operation
     * @param Collection<int,class-string<Model>> $modelsClasses
     *
     * @return string
     */
    public static function generate(Collection $modelsClasses): string
    {

        /**
         * Global Imports shouldn't be done,
         * cause same short name can be used for different models in different namespace.
         * which can conflict model namespaces.
         */

        return static::getTypes($modelsClasses)
            ->groupBy('namespace')
            ->implode(function (Collection $types, string $namespace) {
                $namespace = str($namespace)->replace('\\', '.')->value();

                $typescriptNamespace = PHP_EOL .
                    $types->implode(
                        fn(Typescript $typescriptType) => $typescriptType->generate(),
                        PHP_EOL . PHP_EOL
                    )
                    . PHP_EOL;

                return "declare namespace $namespace{ $typescriptNamespace}";
            }, PHP_EOL);
    }

    /**
     * @param Collection<int,class-string<Model>> $modelsClasses
     *
     * @return Collection<int,Typescript>
     */
    public static function getTypes(Collection $modelsClasses): Collection
    {
        return $modelsClasses->map(function (string $modelClass) {
            $reflection = (new \ReflectionClass($modelClass));

            $model = ModelInspector::parseModel($modelClass);

            return new Typescript(
                namespace: $reflection->getNamespaceName(),
                model: $reflection->getName(),
                shortName: $reflection->getShortName(),
                types: static::getFieldsOfModel($model)
                    ->merge(static::getRelationsOfModel($model))
                    ->merge(static::getCustomAttributesOfModel($model))
                    ->mapWithKeys(fn($value, $key) => [$key => $value])
            );
        });
    }

    /**
     * Returns Database columns to be typescript compatible
     * @throws \Exception
     */
    public static function getFieldsOfModel(string|Model $model)
    {
        $model = ModelInspector::parseModel($model);

        return ModelInspector::getColumns($model)->mapWithKeys(fn(Column $column) => [
            $column->getName() => static::transformData(item: $model, column: $column),
        ]);
    }

    /**
     * @param class-string<Model>|Model $model
     *
     * @throws ReflectionException
     */
    public static function getRelationsOfModel(string|Model $model): Collection
    {
        if (is_string($model)) {
            $model = new $model();
        }

        return ModelInspector::getRelationMethods($model)
            ->mapWithKeys(function (ReflectionMethod $reflectionMethod) use ($model) {
                $relatedModel = $model->{$reflectionMethod->getName()}();
                $modelReflection = new ReflectionObject($model);
                $relatedModelReflection = new ReflectionObject($relatedModel->getRelated());

                return [
                    str($reflectionMethod->getName())->snake()->value() => [
                        'isUndefinable'      => true,
                        'value'              => static::transformData($relatedModel),
                        'return_count_type'  => static::getReturnCountType($relatedModel),
                        'namespace'          => $modelReflection->getNamespaceName(),
                        'related_namespace'  => $relatedModelReflection->getNamespaceName(),
                        'related_class'      => $relatedModelReflection->getName(),
                        'related_class_name' => $relatedModelReflection->getShortName(),
                    ],
                ];
            });
    }

    private static function isOldStyleAttribute(ReflectionMethod $reflectionMethod): bool
    {
        $methodName = str($reflectionMethod->getName());

        return $methodName->startsWith('get') && $methodName->endsWith('Attribute');
    }

    private static function getCustomAttributeName(ReflectionMethod $reflectionMethod): string
    {
        $methodName = str($reflectionMethod->getName());

        if (static::isOldStyleAttribute($reflectionMethod)) {
            return $methodName->after('get')->beforeLast('Attribute')->snake()->value();
        }

        return $methodName->snake()->value();
    }

    /**
     * @description Attributes which returns Illuminate\Database\Eloquent\Casts\Attribute, that means new attribute format,
     * the callback of get should be explicitly defined. Otherwise, type will be unknown
     *
     * @throws ReflectionException
     * @throws \Exception
     */
    public static function getCustomAttributesOfModel(string|Model $model): Collection
    {
        $model = ModelInspector::parseModel($model);

        return ModelInspector::getCustomAttributes($model)->mapWithKeys(fn(ReflectionMethod $reflectionMethod) => [
            static::getCustomAttributeName($reflectionMethod) => static::transformData(
                static::isOldStyleAttribute($reflectionMethod) ? $reflectionMethod->getReturnType()
                    : (new ReflectionFunction($model->{$reflectionMethod->getName()}()->get))->getReturnType()
            ),
        ]);
    }

    public static function getReturnCountType(
        ReflectionUnionType|ReflectionNamedType|Model|Relation|null $item = null
    ): RelationMultipleEnum
    {
        if ($item instanceof Relation) {
            return match (get_class($item)) {
                HasMany::class, HasManyThrough::class,
                BelongsToMany::class, MorphMany::class, MorphToMany::class => RelationMultipleEnum::Multiple,
                MorphOneOrMany::class                                      => RelationMultipleEnum::Multiple_Or_Single,
                default                                                    => RelationMultipleEnum::Single
            };
        }

        return RelationMultipleEnum::Single;
    }

    public static function transformData(
        ReflectionUnionType|ReflectionNamedType|Model|Relation|null $item = null,
        Column|null                                                 $column = null
    ): string
    {
        if ($item instanceof Relation) {
            $reflection = (new ReflectionObject($item->getRelated()));
            $shorName = $reflection->getShortName();

            return match (get_class($item)) {
                HasOne::class, HasOneThrough::class, BelongsTo::class, MorphOne::class => "$shorName | null",
                HasMany::class, HasManyThrough::class,
                BelongsToMany::class, MorphMany::class, MorphToMany::class             => "{$shorName}[] | null",
                MorphOneOrMany::class                                                  => "$shorName | {$shorName}[] | null",
                default                                                                => 'any'
            };
        }

        if ($item instanceof Model) {
            $casts = $item->getCasts();

            if (in_array($column->getName(), array_keys($casts))) {
                /** @var class-string<\BackedEnum>|class-string<\UnitEnum> $castType */
                $castType = $casts[$column->getName()];

                if (enum_exists($castType)) {
                    return EnumType::toTypescript($castType);
                }

                return DatabaseType::toTypescript(Casts::type($castType));
            }

            return DatabaseType::toTypescript($column->getType());
        }

        if ($item instanceof ReflectionUnionType) {
            return collect($item->getTypes())
                ->implode(function (ReflectionNamedType $namedType) {
                    return PhpType::toTypescript($namedType->getName());
                }, ' | ');
        }

        return PhpType::toTypescript($item?->getName());
    }
}
