<?php

namespace Wovosoft\LaravelTypescript\Helpers;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

class Attributes
{
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
    public static function isAttribute(ReflectionMethod $reflectionMethod): bool
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
    public static function isRelation(ReflectionMethod $method): bool
    {
        return $method->hasReturnType()
            && $method->getReturnType() instanceof ReflectionNamedType
            && is_subclass_of($method->getReturnType()->getName(), Relation::class);
    }

    /**
     * @description Determines of the attribute if of new style or old style
     * @param ReflectionMethod $method
     * @return bool
     */
    public static function isNewStyled(ReflectionMethod $method): bool
    {
        if (is_null($method->getReturnType())) {
            return false;
        }

        if ($method->getReturnType() instanceof ReflectionUnionType) {
            return false;
        }

        $name = str($method->getName());

        if (
            $name->startsWith("get")
            && $name->endsWith("Attribute")
            && $name->value() !== 'getAttribute'
        ) {
            return false;
        }

        return $method->getReturnType()->getName() === Attribute::class;
    }

    /**
     * @description Get Reflection of new style attributes 'get' method
     *              This method should be called for new style attributes only.
     * @param ReflectionMethod $method
     * @return ReflectionFunction
     * @throws ReflectionException
     */
    public static function getReflectionOfNewStyleAttribute(ReflectionMethod $method): ReflectionFunction
    {
        $modelClass = $method->getDeclaringClass()->getName();
        $model = new $modelClass;

        return new ReflectionFunction($model->{$method->getName()}()->get);
    }
}
