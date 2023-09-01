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

    public static function toTypescript(string|PhpType|null $type = null): string
    {
        if (is_string($type)) {
            $type = self::tryFrom($type);
        }

        return match ($type) {
            self::INT,
            self::FLOAT,
            self::DOUBLE              => 'number',

            /*
             * @todo : docblock should be checked to have exact array of type
             */
            self::ARRAY               => 'any[]',
            /*
             * @todo :  Rather than just generating a generic object interface,
             * more detailed interface can be generated in future versions.
             */
            self::OBJECT              => '{[key:string]:any}',
            self::STRING              => 'string',
            self::NULL                => 'null',
            self::BOOLEAN, self::BOOL => 'boolean',
            default                   => 'any'
        };
    }
}
