<?php

namespace Wovosoft\LaravelTypescript\Types;

/**
 * @description Converts PHP Enum types to Typescript Union Type
 */
class EnumType
{
    /**
     * @param class-string<\BackedEnum> $enum
     *
     * @return Type
     */
    public static function toType(string $enum): Type
    {
        if (enum_exists($enum)) {
            return Type::new(
                name: collect($enum::cases())->implode(fn($option) => "\"$option->value\"", ' | ')
            );
        }

        return Type::any();
    }
}
