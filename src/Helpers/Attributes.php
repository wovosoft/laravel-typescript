<?php

namespace Wovosoft\LaravelTypescript\Helpers;

use Exception;
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
     *              1. get{PropName}Attribute():type
     *              2. propName():Attribute
     *              If the method starts with get and ends with Attribute, then the
     *              middle part would be the prop name.
     *
     *              and if the method's return type is Attribute then the method's name
     *              is prop name.
     *
     * @param ReflectionMethod $reflectionMethod
     *
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
        return $reflectionMethod->getReturnType()?->getName() === Attribute::class;
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
     * @description Determines if the attribute is of new style or old style
     *
     * @param ReflectionMethod $method
     *
     * @return bool
     */
    public static function isNewStyled(ReflectionMethod $method): bool
    {
        if (is_null($method->getReturnType())) {
            return false;
        }

        $name = str($method->getName());

        /*
         * First we check if it is of old style attribute
         * old style attributes are defined like, 'get{PropName}Attribute()'
         */
        if (
            $name->startsWith('get')
            && $name->endsWith('Attribute')
            && $name->value() !== 'getAttribute'
        ) {
            return false;
        }

        /**
         * New style attributes doesn't return multiple values,
         * it returns only one, and it is of type.
         *
         * @link \Illuminate\Database\Eloquent\Casts\Attribute::class
         */
        if ($method->getReturnType() instanceof ReflectionUnionType) {
            return false;
        }

        /*
         * If return type is exactly of Illuminate\Database\Eloquent\Casts\Attribute::class,
         * then it is of new style attribute
         */
        return $method->getReturnType()->getName() === Attribute::class;
    }

    /**
     * @description Get Reflection of new style attributes 'get' method
     *              This method should be called for new style attributes only.
     *
     * @note In most cases, it is safe to retrieve types in this way,
     *      because, calling new styled attribute doesn't make any database/php operations,
     *      it just returns an instance of Attribute::class
     * @note It is safe to check if the method defines new style attribute before calling this function
     *
     * @param ReflectionMethod $method
     *
     * @throws ReflectionException
     * @throws Exception
     *
     * @return ReflectionFunction
     */
    public static function getReflectionOfNewStyleAttribute(ReflectionMethod $method): ReflectionFunction
    {
        $model = ModelInspector::parseModel($method->getDeclaringClass()->getName());

        return new ReflectionFunction($model->{$method->getName()}()->get);
    }
}
