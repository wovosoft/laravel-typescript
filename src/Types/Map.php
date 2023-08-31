<?php

namespace Wovosoft\LaravelTypescript\Types;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Types\Type;

class Map
{
    /**
     * @throws Exception
     */
    public static function dbToTypescript(Type $type): string
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
            Types::JSON,
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
            Types::OBJECT         => "{[key:string]:any}",
            default               => "any"
        };
    }

    /**
     * @param class-string<\BackedEnum> $enum
     * @return string
     */
    public function enumToTypescript(string $enum): string
    {
        if (enum_exists($enum)) {
            return collect($enum::cases())->map(fn($option) => "\"$option->value\"")->implode(' | ');
        }

        return 'any';
    }
}
