<?php

namespace Wovosoft\LaravelTypescript\Transformers;

use Doctrine\DBAL\Types\Type as DoctrineType;

/**
 * Default built-in types provided by Doctrine DBAL.
 * @source Doctrine\DBAL\Types\Types;
 */
enum DatabaseType: string
{
    case ARRAY                = 'array';
    case ASCII_STRING         = 'ascii_string';
    case BIGINT               = 'bigint';
    case BINARY               = 'binary';
    case BLOB                 = 'blob';
    case BOOLEAN              = 'boolean';
    case DATE_MUTABLE         = 'date';
    case DATE_IMMUTABLE       = 'date_immutable';
    case DATEINTERVAL         = 'dateinterval';
    case DATETIME_MUTABLE     = 'datetime';
    case DATETIME_IMMUTABLE   = 'datetime_immutable';
    case DATETIMETZ_MUTABLE   = 'datetimetz';
    case DATETIMETZ_IMMUTABLE = 'datetimetz_immutable';
    case DECIMAL              = 'decimal';
    case FLOAT                = 'float';
    case GUID                 = 'guid';
    case INTEGER              = 'integer';
    case JSON                 = 'json';
    case OBJECT               = 'object';
    case SIMPLE_ARRAY         = 'simple_array';
    case SMALLINT             = 'smallint';
    case STRING               = 'string';
    case TEXT                 = 'text';
    case TIME_MUTABLE         = 'time';
    case TIME_IMMUTABLE       = 'time_immutable';

    public static function toTypescript(string|DatabaseType|DoctrineType $type): string
    {
        if (is_string($type)) {
            $type = self::tryFrom($type);
        } elseif ($type instanceof DoctrineType) {
            $type = self::tryFrom($type->getName());
        }

        return match ($type) {
            self::ARRAY, self::SIMPLE_ARRAY => "any[]",
            self::BIGINT, self::BINARY, self::SMALLINT, self::INTEGER, self::FLOAT, self::DECIMAL, self::DATEINTERVAL => "number",
            self::BLOB => "Blob",
            self::BOOLEAN => "boolean",
            self::OBJECT => "any",
//            self::ASCII_STRING => "string",
//            self::DATE_MUTABLE => "string",
//            self::DATE_IMMUTABLE => "string",
//            self::DATETIME_MUTABLE => "string",
//            self::DATETIME_IMMUTABLE => "string",
//            self::DATETIMETZ_MUTABLE => "string",
//            self::DATETIMETZ_IMMUTABLE => "string",
//            self::GUID => "string",
//            self::JSON => "string",
//            self::STRING => "string",
//            self::TEXT => "string",
//            self::TIME_MUTABLE => "string",
//            self::TIME_IMMUTABLE => "string",
            default => "string"
        };
    }
}
