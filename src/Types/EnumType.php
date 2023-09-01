<?php

namespace Wovosoft\LaravelTypescript\Types;

/**
 * @description Converts PHP Enum types to Typescript Union Type
 */
enum EnumType
{
    /**
     * @param class-string<\BackedEnum> $enum
     *
     * @return string
     */
    public static function toTypescript(string $enum): string
    {
        if (enum_exists($enum)) {
            return collect($enum::cases())
                ->implode(fn ($option) => "\"$option->value\"", ' | ');
        }

        return 'any';
    }
}
