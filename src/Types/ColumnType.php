<?php

namespace Wovosoft\LaravelTypescript\Types;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Wovosoft\LaravelTypescript\Types\Type as GenericType;

class ColumnType
{
    /**
     * @throws Exception
     */
    public static function toType(Type|Column|string $type): GenericType
    {
        if ($type instanceof Column) {
            $type = Type::getTypeRegistry()->lookupName($type->getType());
        } elseif ($type instanceof Type) {
            $type = Type::getTypeRegistry()->lookupName($type);
        }

        return match ($type) {
            Types::ARRAY,
            Types::SIMPLE_ARRAY         => GenericType::array(),

            Types::ASCII_STRING         => GenericType::string(comment: 'ASCII String'),
            Types::DATE_MUTABLE         => GenericType::mutableDate(),
            Types::DATE_IMMUTABLE       => GenericType::immutableDate(),
            Types::DATEINTERVAL         => GenericType::string(comment: 'date interval'),
            Types::DATETIME_MUTABLE     => GenericType::mutableDatetime(),
            Types::DATETIME_IMMUTABLE   => GenericType::immutableDatetime(),
            Types::DATETIMETZ_MUTABLE   => GenericType::mutableDatetimeZ(),
            Types::DATETIMETZ_IMMUTABLE => GenericType::immutableDatetimeZ(),
            Types::GUID                 => GenericType::string(comment: 'GUID'),
            Types::STRING,
            Types::TEXT                 => GenericType::string(),
            Types::TIME_MUTABLE         => GenericType::mutableTime(),
            Types::TIME_IMMUTABLE       => GenericType::immutableTime(),

            Types::BIGINT,
            Types::DECIMAL,
            Types::FLOAT,
            Types::INTEGER,
            Types::SMALLINT             => GenericType::number(),

            Types::BINARY,
            Types::BLOB                 => GenericType::unknown(),
            Types::BOOLEAN              => GenericType::boolean(),

            /*
             * JSON/OBJECT/ARRAY should be checked in more detail.
             * It's schema can be retried to generate more accurate
             * interface in upcoming versions.
             */
            Types::JSON,
            Types::OBJECT               => GenericType::object(),
            default                     => GenericType::any()
        };
    }
}
