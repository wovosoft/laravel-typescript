<?php

namespace Wovosoft\LaravelTypescript\Helpers;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Doctrine\DBAL\Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

class ModelInspector
{
    /**
     * @var class-string<Model>|Model
     */
    private string|Model $model;

    public static function getModelsIn(string $directory): Collection
    {
        /**
         * @link https://github.com/composer/class-map-generator
         */

        return collect(array_keys(ClassMapGenerator::createMap($directory)))
            ->filter(fn($class) => is_subclass_of($class, Model::class));
    }

    public static function new(): static
    {
        return new static();
    }

    /**
     * @param class-string<Model>|Model $model
     * @return $this
     */
    public function inspectionFor(string|Model $model): static
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @return ModelInspectionResult
     * @throws Exception
     * @throws ReflectionException
     */
    public function getInspectionResult(): ModelInspectionResult
    {
        return new ModelInspectionResult(
            columns          : $this->getColumns(),
            custom_attributes: $this->getCustomAttributes(),
            relations        : $this->getRelationMethods()
        );
    }

    /**
     * @return Collection
     * @throws Exception
     * @throws \Exception
     */
    public function getColumns(): Collection
    {
        $model = $this->parseModel();

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

    /**
     * @description Methods of model which are defined to describe custom attributes,
     *              should be added in props of the generating typescript interface
     * @throws ReflectionException
     * @throws \Exception
     */
    private function getCustomAttributes(): Collection
    {
        $reflectionMethods = (new ReflectionClass($this->model))->getMethods();
        return collect($reflectionMethods)->filter(
            fn(ReflectionMethod $rf) => $this->isMethodDefinesModelAttribute($rf)
        );
    }

    /**
     * @description Returns methods of a given model, which are used to define relations
     * @return Collection
     * @throws ReflectionException
     */
    private function getRelationMethods(): Collection
    {
        return collect((new ReflectionClass($this->model))->getMethods())
            ->filter(fn(ReflectionMethod $rf) => $this->isMethodDefinesRelation($rf));
    }

    /**
     * @description Determines here a model method defines custom attribute or not.
     *              Custom attributes can be defined in two ways.
     *              1. get{PropName}Attribute()
     *              2. propName():Attribute
     *              If the method starts with get and ends with Attribute, then the
     *              middle part would be the prop name.
     *
     *              and if the method's return type is Attribute then the method's name
     *              is prop name.
     * @param ReflectionMethod $reflectionMethod
     * @return bool
     */
    private function isMethodDefinesModelAttribute(ReflectionMethod $reflectionMethod): bool
    {
        $methodName = str($reflectionMethod->getName());
        /**
         * In old style, get{Prop}Attribute() method's returns type can be
         * Enum Type, we do not need to care about it.
         */
        if (
            $methodName->startsWith('get')
            && $methodName->endsWith('Attribute')
            && $methodName->value() !== 'getAttribute'
        ) {
            return true;
        }

        /*
         * in new style, custom attribute defining method always returns Attribute::class,
         * so, when it returns other than Attribute::class it is not a custom attribute
         */
        if ($reflectionMethod->getReturnType() instanceof ReflectionUnionType) {
            return false;
        }

        /*
         * If it returns Attribute::class it is custom attribute
         */
        return ($reflectionMethod->getReturnType()?->getName() === Attribute::class);
    }


    /*
     * Determines if a method is used to define model relation or not
     */
    private function isMethodDefinesRelation(ReflectionMethod $reflectionMethod): bool
    {
        return $reflectionMethod->hasReturnType()
            && $reflectionMethod->getReturnType() instanceof ReflectionNamedType
            && is_subclass_of($reflectionMethod->getReturnType()->getName(), Relation::class);
    }

    /**
     * @throws \Exception
     */
    private function parseModel(): Model
    {
        if (is_string($this->model)) {
            if (!is_subclass_of($this->model, Model::class)) {
                throw new \Exception("$this->model is not a valid Model Class");
            }
            return new $this->model();
        }

        return $this->model;
    }
}
