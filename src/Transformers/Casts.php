<?php

namespace Wovosoft\LaravelTypescript\Transformers;

use Illuminate\Database\Eloquent\Casts\AsStringable;

class Casts
{
    public static array $options = [
        "int"                => DatabaseType::INTEGER,
        "integer"            => DatabaseType::INTEGER,
        "array"              => DatabaseType::ARRAY,
        AsStringable::class  => DatabaseType::STRING,
        "boolean"            => DatabaseType::BOOLEAN,
        "collection"         => DatabaseType::ARRAY,
        "date"               => DatabaseType::DATE_MUTABLE,
        "datetime"           => DatabaseType::DATETIME_MUTABLE,
        "immutable_date"     => DatabaseType::DATE_IMMUTABLE,
        "immutable_datetime" => DatabaseType::DATETIME_IMMUTABLE,
        "double"             => DatabaseType::FLOAT,
        "float"              => DatabaseType::FLOAT,
        "hashed"             => DatabaseType::STRING,
        "object"             => DatabaseType::OBJECT,
        "real"               => DatabaseType::FLOAT,
        "string"             => DatabaseType::STRING,
        "timestamp;"         => DatabaseType::STRING,
    ];

    public static function type(string $type): DatabaseType|string
    {
        if (str($type)->startsWith("decimal")) {
            return DatabaseType::FLOAT;
        }
        return self::$options[$type] ?? $type;
    }
}
