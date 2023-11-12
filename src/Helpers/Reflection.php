<?php

namespace Wovosoft\LaravelTypescript\Helpers;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use ReflectionObject;

class Reflection
{
    /**
     * @throws \ReflectionException
     */
    public static function model(string|Model $model): ReflectionObject|ReflectionClass
    {
        if (is_string($model)) {
            return new ReflectionClass($model);
        }

        return new ReflectionObject($model);
    }

    public function getNamespace()
    {

    }

    public static function isSameNamespace(ReflectionObject|ReflectionClass $ro1, ReflectionObject|ReflectionClass $ro2): bool
    {
        return $ro1->getNamespaceName() === $ro2->getNamespaceName();
    }
}
