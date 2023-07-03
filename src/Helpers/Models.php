<?php

namespace Wovosoft\LaravelTypescript\Helpers;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Doctrine\DBAL\Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use ReflectionMethod;

class Models
{
    public static function in(string $directory): Collection
    {
        /**
         * @link https://github.com/composer/class-map-generator
         */

        return collect(array_keys(ClassMapGenerator::createMap($directory)))
            ->filter(fn ($class) => is_subclass_of($class, Model::class))
            ->flatten();
    }

    /**
     * @throws \ReflectionException
     */
    public static function getCustomAttributesOf(string|Model $model): Collection
    {
        if (is_string($model)) {
            $model = new $model();
        }

        return collect((new \ReflectionClass($model))->getMethods())
            ->filter(
                fn (ReflectionMethod $reflectionMethod) => static::isMethodIsModelAttribute($reflectionMethod)
            );
    }

    private static function isMethodIsModelAttribute(ReflectionMethod $reflectionMethod): bool
    {
        $methodName = str($reflectionMethod->getName());

        return (
            $methodName->startsWith('get')
            && $methodName->endsWith('Attribute')
            && $methodName->value() !== 'getAttribute'
        ) || ($reflectionMethod->getReturnType()?->getName() === Attribute::class);
    }

    /**
     * @param class-string<Model>|Model $model
     *
     * @throws \ReflectionException
     */
    public static function getRelatedModelsOf(string|Model $model): Collection
    {
        if (is_string($model)) {
            $model = new $model();
        }

        return collect((new \ReflectionClass($model))->getMethods())
            ->filter(fn (ReflectionMethod $method) => static::isRelation($method));
    }

    private static function isRelation(ReflectionMethod $method): bool
    {
        return $method->hasReturnType()
            && $method->getReturnType() instanceof \ReflectionNamedType
            && is_subclass_of($method->getReturnType()->getName(), Relation::class);
    }

    /**
     * @throws Exception
     */
    public static function getFieldsOf(string|Model $model): Collection
    {
        if (is_string($model)) {
            $model = new $model();
        }

        /**
         * Model fields name should be exact like column name.
         */
        return collect(
            $model
                ->getConnection()
                ->getDoctrineConnection()
                ->createSchemaManager()
                ->listTableColumns($model->getTable())
        );
    }
}
