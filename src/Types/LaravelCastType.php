<?php

namespace Wovosoft\LaravelTypescript\Types;

class LaravelCastType
{
    public static function isBuiltIn(string $castType): bool
    {
        return in_array(str($castType)->before(':')->value(), [
            'int', 'integer', 'real', 'float', 'double', 'decimal', 'string',
            'bool', 'boolean', 'object', 'array', 'json', 'collection', 'date',
            'datetime', 'custom_datetime', 'immutable_date', 'immutable_custom_datetime',
            'immutable_datetime', 'timestamp',
        ]);
    }

    /**
     * @param string $castType
     *
     * @return Type
     *
     * @link  vendor/laravel/framework/src/Illuminate/Database/Eloquent/Concerns/HasAttributes.php
     */
    public static function getType(string $castType): Type
    {
        return match (str($castType)->before(':')->value()) {
            'int',
            'integer',
            'real',
            'float',
            'double',
            'decimal'                   => Type::number(),
            'string'                    => Type::string(),
            'bool',
            'boolean'                   => Type::boolean(),
            'object'                    => Type::object(),
            'array'                     => Type::array(),
            'json',
            'collection'                => Type::json(),
            'date'                      => Type::string(comment: 'date string'),
            'datetime'                  => Type::string(comment: 'datetime string'),
            'custom_datetime'           => Type::string(comment: 'custom_datetime string'),
            'immutable_date'            => Type::string(comment: 'immutable_date string'),
            'immutable_custom_datetime' => Type::string(comment: 'immutable_custom_datetime string'),
            'immutable_datetime'        => Type::string(comment: 'immutable_datetime string'),
            'timestamp'                 => Type::string(comment: 'timestamp string'),
            default                     => Type::string(comment: 'no specific type')
        };
    }
}
