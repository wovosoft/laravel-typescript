<?php

namespace Wovosoft\LaravelTypescript\Transformers;

class EnumType
{
    /**
     * @param class-string<\BackedEnum|\UnitEnum> $enum
     *
     * @return string
     */
    public static function toTypescript(string $enum): string
    {
        if (enum_exists($enum)) {
            return collect($enum::cases())->map(fn ($option) => "\"$option->value\"")->implode(' | ');
        }

        return 'any';
    }
}
