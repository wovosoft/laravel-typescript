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

    /**
     * @description Returns the list of model-classes in a directory
     * @param string|array $directories
     * @return Collection<int,class-string<Model>>
     * @link https://github.com/composer/class-map-generator
     */
    public static function getModelsIn(string|array $directories): Collection
    {
        if (is_string($directories)) {
            $directories = [$directories];
        }

        return collect($directories)
            ->map(fn(string $dir) => array_keys(ClassMapGenerator::createMap($dir)))
            ->collapse()
            ->filter(fn($class) => is_subclass_of($class, Model::class));
    }

    /**
     * @description Returns new instance of the Model Inspector class
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }

    /**
     * @description Used to set Model class for inspection
     * @param class-string<Model>|Model $model
     * @return $this
     */
    public function inspectionFor(string|Model $model): static
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @description Returns model inspection result which contains
     *              list of database columns, custom attributes and relations.
     * @return ModelInspectionResult
     * @throws Exception
     * @throws ReflectionException
     */
    public function getInspectionResult(): ModelInspectionResult
    {
        return new ModelInspectionResult(
            model            : $this->model,
            columns          : $this->getColumns(),
            custom_attributes: $this->getCustomAttributes(),
            relations        : $this->getRelations()
        );
    }

    /**
     * @description Returns Collection of Database columns
     * @return Collection<int,Column>
     * @throws Exception
     * @throws \Exception
     */
    private function getColumns(): Collection
    {
        $model = static::parseModel($this->model);

        $columns = $model
            ->getConnection()
            ->getDoctrineConnection()
            ->createSchemaManager()
            ->listTableColumns($model->getTable());

        /**
         * Model fields name should be exact like column name.
         */
        return collect($columns)
            ->when(!empty($model->getHidden()), fn(Collection $cols) => $cols->forget($model->getHidden()));
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
     * @param class-string<Model>|Model $model
     * @return Model
     * @throws \Exception
     */
    public static function parseModel(string|Model $model): Model
    {
        if (is_string($model)) {
            if (!is_subclass_of($model, Model::class)) {
                throw new \Exception("$model is not a valid Model Class");
            }
            return new $model();
        }

        return $model;
    }
}
