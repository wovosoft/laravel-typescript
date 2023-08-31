<?php

namespace Wovosoft\LaravelTypescript\Helpers;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

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
            model: $this->model,
            columns: $this->getColumns(),
            custom_attributes: $this->getCustomAttributes(),
            relations: $this->getRelations()
        );
    }

    /**
     * @return Collection<int,Column>
     * @throws Exception
     * @throws \Exception
     */
    private function getColumns(): Collection
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
     * @return Collection<int,ReflectionMethod>
     * @throws ReflectionException
     */
    private function getCustomAttributes(): Collection
    {
        $reflectionMethods = (new ReflectionClass($this->model))->getMethods();

        return collect($reflectionMethods)->filter(
            fn(ReflectionMethod $rf) => Attributes::isAttribute($rf)
        );
    }

    /**
     * @description Returns methods of a given model, which are used to define relations
     * @return Collection<int,ReflectionMethod>
     * @throws ReflectionException
     */
    private function getRelations(): Collection
    {
        return collect((new ReflectionClass($this->model))->getMethods())
            ->filter(fn(ReflectionMethod $rf) => Attributes::isRelation($rf));
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
