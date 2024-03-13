<?php

namespace Wovosoft\LaravelTypescript\Helpers;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionMethod;

readonly class ModelInspectionResult {
    /**
     * @param  class-string<Model>|Model  $model
     * @param  Collection<int,Column>  $columns
     * @param  Collection<int,ReflectionMethod>  $custom_attributes
     * @param  Collection<int,ReflectionMethod>  $relations
     */
    public function __construct(
        private string|Model $model,
        private Collection $columns,
        private Collection $custom_attributes,
        private Collection $relations
    ) {
    }

    /**
     * @description Generates the model interface
     *
     * @return string
     */
    public function __toString(): string {
        return $this->getGenerator();
    }

    /**
     * @return Model|string
     */
    public function getModel(): Model|string {
        return $this->model;
    }

    /**
     * @return Collection<int,Column>
     */
    public function getColumns(): Collection {
        return $this->columns;
    }

    /**
     * @return Collection<int,ReflectionMethod>
     */
    public function getCustomAttributes(): Collection {
        return $this->custom_attributes;
    }

    /**
     * @return Collection<int,ReflectionMethod>
     */
    public function getRelations(): Collection {
        return $this->relations;
    }

    public function getGenerator(): Generator {
        return new Generator($this);
    }

    public function toTypescript(): string {
        return (string) $this->getGenerator();
    }
}
