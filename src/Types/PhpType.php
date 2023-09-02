<?php

namespace Wovosoft\LaravelTypescript\Types;

enum PhpType: string
{
    case INT = 'int';
    case FLOAT = 'float';
    case DOUBLE = 'double';
    case ARRAY = 'array';
    case OBJECT = 'object';
    case STRING = 'string';
    case BOOL = 'bool';
    case BOOLEAN = 'boolean';
    case NULL = 'null';

    public static function toType(string|PhpType|null $type = null): Type
    {
        if (is_string($type)) {
            $type = self::tryFrom($type);
        }

        return match ($type) {
            self::INT,
            self::FLOAT,
            self::DOUBLE              => Type::number(),

            /*
             * @todo : docblock should be checked to have exact array of type
             */
            self::ARRAY               => Type::any(isMultiple: true),
            /*
             * @todo :  Rather than just generating a generic object interface,
             * more detailed interface can be generated in future versions.
             */
            self::OBJECT              => Type::object(),
            self::STRING              => Type::string(),
            self::BOOLEAN, self::BOOL => Type::boolean(),
            //self::NULL                => Type::any(),
            default                   => Type::any()
        };
    }
}
