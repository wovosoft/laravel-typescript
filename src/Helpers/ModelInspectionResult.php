<?php

namespace Wovosoft\LaravelTypescript\Helpers;

use Doctrine\DBAL\Schema\Column;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use ReflectionMethod;
use ReflectionNamedType;
use Wovosoft\LaravelTypescript\Transformers\PhpType;
use Wovosoft\LaravelTypescript\Types\Map;

class ModelInspectionResult
{
    public function __construct(
        private readonly Collection $columns,
        private readonly Collection $custom_attributes,
        private readonly Collection $relations
    )
    {
    }

    /**
     * @return Collection<int,Column>
     */
    public function getColumns(): Collection
    {
        return $this->columns;
    }

    /**
     * @return Collection<int,ReflectionMethod>
     */
    public function getCustomAttributes(): Collection
    {
        return $this->custom_attributes;
    }

    /**
     * @return Collection<int,ReflectionMethod>
     */
    public function getRelations(): Collection
    {
        return $this->relations;
    }

    public function generate()
    {
        return $this->generateCustomAttributes();
    }

    private function generateCustomAttributes()
    {
        return $this->custom_attributes->mapWithKeys(function (ReflectionMethod $method) {
            return [
                $this->qualifyAttributeName($method) => $this->generateCustomAttributeReturnType($method)
            ];
        });
    }

    private function generateCustomAttributeReturnType(ReflectionMethod $method)
    {
        $types = [];
        if ($method->getReturnType() instanceof ReflectionNamedType) {
            $types[] = $method->getReturnType();
        } else if ($method->getReturnType() instanceof \ReflectionUnionType) {
            $types = $method->getReturnType()->getTypes();
        }


        if ($this->isAttributeOfNewStyle($method)) {
            return [
                "is_nullable"    => false,
                "is_undefinable" => false,
                "new"            => true,
                "types"          => $method->getReturnType()
            ];
        }

        return [
            "is_nullable"    => false,
            "is_undefinable" => false,
            "types"          => collect($types)->map(function (ReflectionNamedType $type) {
                return $type->isBuiltin() ? PhpType::toTypescript($type->getName()) : $type->getName();
            })->toArray()
        ];
    }

    private function qualifyAttributeName(ReflectionMethod $method): string
    {
        $name = str($method->getName());

        if ($this->isAttributeOfNewStyle($method)) {
            return $name->snake()->value();
        }

        return $name->after("get")->before("Attribute")->snake()->value();
    }

    private function isAttributeOfNewStyle(ReflectionMethod $method): bool
    {
        if ($method->getReturnType() instanceof \ReflectionUnionType) {
            return false;
        }

        return $method->getReturnType()->getName() === Attribute::class;
    }
}
