<?php

namespace Wovosoft\LaravelTypescript\Helpers;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class ModelInspector
{
    /**
     * @param  class-string<Model>|Model|null  $model
     */
    public function __construct(
        private string|Model|null $model = null
    ) {
    }

    private static array $defaultRelations = [
        BelongsTo::class,
        BelongsToMany::class,
        HasMany::class,
        HasManyThrough::class,
        HasOne::class,
        HasOneOrMany::class,
        HasOneThrough::class,
        MorphMany::class,
        MorphOne::class,
        MorphOneOrMany::class,
        MorphPivot::class,
        MorphTo::class,
        MorphToMany::class,
        Pivot::class,
        Relation::class
    ];

    /**
     * @description Returns the list of model-classes in a directory
     *
     * @param  string|array  $directories
     *
     * @return Collection<int,class-string<Model>>
     *
     * @link        https://github.com/composer/class-map-generator
     */
    public static function getModelsIn(string|array $directories): Collection
    {
        if (is_string($directories)) {
            $directories = [$directories];
        }

        return collect($directories)
            ->map(fn(string $dir) => array_keys(ClassMapGenerator::createMap($dir)))
            ->collapse()
            ->filter(fn($class) => static::isOfModelType($class));
    }

    /**
     * @description Checks if the provided class/object is of type Model
     *
     * @note        the parameter $model's type is set to be string|Model, because
     *       we need to check any kind of object/class to be checked if it
     *       is of type Model or not. If string|Model is used, strings of any
     *      type will be passed, but objects other than Model won't be passed
     *      for testing whether it is of type Model or not.
     *
     * @param  mixed  $model
     *
     * @return bool
     */
    public static function isOfModelType(mixed $model): bool
    {
        if (is_string($model) && class_exists($model)) {
            return is_subclass_of($model, Model::class);
        }

        return $model instanceof Model;
    }

    /**
     * @description Returns new instance of the Model Inspector class.
     *
     * @note        If value for $model is provided @method inspectionFor() doesn't need to be used
     *
     * @param  class-string<Model>|Model|null  $model
     *
     * @return static
     */
    public static function new(string|Model|null $model = null): static
    {
        return new static($model);
    }

    /**
     * @description Used to set Model class for inspection
     *
     * @param  class-string<Model>|Model  $model
     *
     * @return $this
     */
    public function inspectionFor(string|Model $model): static
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @description Return model inspection result which contains
     *              list of database columns, custom attributes, and relations.
     *
     * @return ModelInspectionResult
     * @throws \Exception
     *
     * @throws ReflectionException
     */
    public function getInspectionResult(): ModelInspectionResult
    {
        $this->isModelSet();

        return new ModelInspectionResult(
            model            : $this->model,
            columns          : $this->getColumns(),
            custom_attributes: $this->getCustomAttributes(),
            relations        : $this->getRelations()
        );
    }

    /**
     * @description Returns Collection of Database columns
     *
     * @return Collection<int,Column>
     *
     * @throws \Exception
     */
    private function getColumns(): Collection
    {
        $this->isModelSet();

        $model = static::parseModel($this->model);

        $columns = Schema::getColumns($model->getTable());

        /**
         * Model fields name should be exact like column name.
         * Fields which are marked as hidden, should not be generated.
         * So, those fields are being forgotten (omitted) from the collection.
         */
        return collect($columns)
            ->when(!empty($model->getHidden()), fn(Collection $cols) => $cols->forget($model->getHidden()))
            ->map(fn($col) => Column::fromArray($col));
    }

    /**
     * @description Returns methods which are used to define Custom Attributes
     *
     * @return Collection<int,ReflectionMethod>
     * @throws ReflectionException
     *
     */
    private function getCustomAttributes(): Collection
    {
        return $this->getMethods()->filter(fn(ReflectionMethod $rf) => Attributes::isAttribute($rf));
    }

    /**
     * @description Returns methods of a given model, which are used to define relations
     *
     * @return Collection<int,ReflectionMethod>
     * @throws ReflectionException
     *
     * @throws \Exception
     */
    private function getRelations(): Collection
    {
        $this->isModelSet();

        return $this->getMethods()
            ->filter(fn(ReflectionMethod $rf) => Attributes::isRelation($rf));
    }


    /**
     * @throws ReflectionException
     */
    private function getMethods(): Collection
    {
        return collect((new ReflectionClass($this->model))->getMethods(ReflectionMethod::IS_PUBLIC));
    }

    /**
     * @param  class-string<Model>|Model  $model
     *
     * @return Model
     * @throws \Exception
     *
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

    /**
     * @throws \Exception
     */
    private function isModelSet(): bool
    {
        if (!isset($this->model)) {
            throw new \Exception('Model Not Set');
        }

        return true;
    }

    public static function getQualifiedNamespace(string $name): string
    {
        return str($name)->replace('\\', '.')->value();
    }

    public static function getDefaultRelations(): array
    {
        return static::$defaultRelations;
    }

    public static function isDefaultRelation(string $relation): bool
    {
        return in_array($relation, static::$defaultRelations);
    }
}
