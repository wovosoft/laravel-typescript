<?php

namespace Wovosoft\LaravelTypescript\Types;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Types\Type;

class ColumnType
{
    /**
     * @throws Exception
     */
    public static function toTypescript(Type $type): string
    {
        return match (Type::getTypeRegistry()->lookupName($type)) {
            Types::ARRAY,
            Types::SIMPLE_ARRAY   => "any[]",

            Types::ASCII_STRING,
            Types::DATE_MUTABLE,
            Types::DATE_IMMUTABLE,
            Types::DATEINTERVAL,
            Types::DATETIME_MUTABLE,
            Types::DATETIME_IMMUTABLE,
            Types::DATETIMETZ_MUTABLE,
            Types::DATETIMETZ_IMMUTABLE,
            Types::GUID,
            Types::STRING,
            Types::TEXT,
            Types::TIME_MUTABLE,
            Types::TIME_IMMUTABLE => "string",

            Types::BIGINT,
            Types::DECIMAL,
            Types::FLOAT,
            Types::INTEGER,
            Types::SMALLINT       => "number",

            Types::BINARY,
            Types::BLOB           => "unknown",
            Types::BOOLEAN        => "boolean",

            /*
             * JSON/OBJECT/ARRAY should be checked in more detail.
             * It's schema can be retried to generate more accurate
             * interface in upcoming versions.
             */
            Types::JSON,
            Types::OBJECT         => "{[key:string]:any}",
            default               => "any"
        };
    }
}
