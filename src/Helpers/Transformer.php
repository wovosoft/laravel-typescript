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
use Wovosoft\LaravelTypescript\Transformers\Casts;
use Wovosoft\LaravelTypescript\Transformers\DatabaseType;
use Wovosoft\LaravelTypescript\Transformers\EnumType;
use Wovosoft\LaravelTypescript\Transformers\PhpType;
use Wovosoft\LaravelTypescript\Transformers\Typescript;

class Transformer
{
    /**
     * @param Collection<int,class-string<Model>> $classes
     *
     * @return string
     */
    public static function generate(Collection $classes): string
    {
        return static::getTypes($classes)
            ->groupBy('namespace')
            ->implode(function (Collection $types, string $namespace) {
                $namespace = str($namespace)->replace('\\', '.')->value();
                $typescript = PHP_EOL . $types->implode(fn(Typescript $typescriptType) => $typescriptType->generate(), PHP_EOL . PHP_EOL) . PHP_EOL;

                return "declare namespace $namespace{ $typescript}";
            }, PHP_EOL);
    }

    /**
     * @param Collection<int,class-string<Model>> $classes
     *
     * @return Collection<int,Typescript>
     */
    public static function getTypes(Collection $classes): Collection
    {
        return $classes->map(function (string $modelClass) {
            $reflection = (new \ReflectionClass($modelClass));

            $model = Models::parseModel($modelClass);

            $contents = static::modelFields($model)
                ->merge(static::modelRelations($model))
                ->merge(static::customAttributes($model))
                ->mapWithKeys(fn($value, $key) => [$key => $value]);

            return new Typescript(
                namespace: $reflection->getNamespaceName(),
                model: $reflection->getName(),
                shortName: $reflection->getShortName(),
                types: $contents
            );
        });
    }

    /**
     * @throws \Exception
     */
    public static function modelFields(string|Model $model)
    {

        $model = Models::parseModel($model);


        return Models::getFieldsOf($model)->mapWithKeys(fn(Column $column) => [
            $column->getName() => static::transform(item: $model, column: $column),
        ]);
    }

    /**
     * @param class-string<Model>|Model $model
     *
     * @throws ReflectionException
     */
    public static function modelRelations(string|Model $model): Collection
    {
        if (is_string($model)) {
            $model = new $model();
        }

        return Models::getRelatedModelsOf($model)->mapWithKeys(fn(ReflectionMethod $reflectionMethod) => [
            str($reflectionMethod->getName())->snake()->value() => [
                "isUndefinable" => true,
                "value"         => static::transform($model->{$reflectionMethod->getName()}())
            ],
        ]);
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
    public static function customAttributes(string|Model $model): Collection
    {
        $model = Models::parseModel($model);

        return Models::getCustomAttributesOf($model)->mapWithKeys(fn(ReflectionMethod $reflectionMethod) => [
            static::getCustomAttributeName($reflectionMethod) => static::transform(
                static::isOldStyleAttribute($reflectionMethod) ? $reflectionMethod->getReturnType()
                    : (new ReflectionFunction($model->{$reflectionMethod->getName()}()->get))->getReturnType()
            ),
        ]);
    }

    public static function transform(
        ReflectionUnionType|ReflectionNamedType|Model|Relation|null $item = null,
        Column|null                                                 $column = null
    ): string
    {
        if ($item instanceof Relation) {
            $shorName = (new ReflectionObject($item->getRelated()))->getShortName();

            return match (get_class($item)) {
                HasOne::class, HasOneThrough::class, BelongsTo::class, MorphOne::class => "{$shorName} | null",
                HasMany::class, HasManyThrough::class,
                BelongsToMany::class, MorphMany::class, MorphToMany::class => "{$shorName}[] | null",
                MorphOneOrMany::class => "{$shorName} | {$shorName}[] | null",
                default => 'any'
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
                ->implode(fn(ReflectionNamedType $namedType) => PhpType::toTypescript($namedType->getName()), ' | ');
        }

        return PhpType::toTypescript($item?->getName());
    }
}
