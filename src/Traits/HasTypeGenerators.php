<?php

namespace Wovosoft\LaravelTypescript\Traits;

use Wovosoft\LaravelTypescript\Helpers\ModelInspector;

trait HasTypeGenerators {
    public static function model(string $name, bool $isMultiple = false, ?string $comment = null): static {
        return new static(
            name      : ModelInspector::getQualifiedNamespace($name),
            isMultiple: $isMultiple,
            comment   : $comment
        );
    }

    public static function string(bool $isMultiple = false, ?string $comment = null): static {
        return new static(
            name      : 'string',
            isMultiple: $isMultiple,
            comment   : $comment
        );
    }

    public static function mutableDate(bool $isMultiple = false): static {
        return static::string(
            isMultiple: $isMultiple,
            comment   : 'date mutable'
        );
    }

    public static function immutableDate(bool $isMultiple = false): static {
        return static::string(
            isMultiple: $isMultiple,
            comment   : 'date immutable'
        );
    }

    public static function mutableDatetimeZ(bool $isMultiple = false): static {
        return static::string(
            isMultiple: $isMultiple,
            comment   : 'datetimez mutable'
        );
    }

    public static function immutableDatetimeZ(bool $isMultiple = false): static {
        return static::string(
            isMultiple: $isMultiple,
            comment   : 'datetimez immutable'
        );
    }

    public static function mutableDatetime(bool $isMultiple = false): static {
        return static::string(
            isMultiple: $isMultiple,
            comment   : 'datetime mutable'
        );
    }

    public static function immutableTime(bool $isMultiple = false): static {
        return static::string(
            isMultiple: $isMultiple,
            comment   : 'time immutable'
        );
    }

    public static function mutableTime(bool $isMultiple = false): static {
        return static::string(
            isMultiple: $isMultiple,
            comment   : 'time mutable'
        );
    }

    public static function immutableDatetime(bool $isMultiple = false): static {
        return static::string(
            isMultiple: $isMultiple,
            comment   : 'datetime immutable'
        );
    }

    public static function mutableTimestamp(bool $isMultiple = false): static {
        return static::string(
            isMultiple: $isMultiple,
            comment   : 'timestamp mutable'
        );
    }

    public static function immutableTimestamp(bool $isMultiple = false): static {
        return static::string(
            isMultiple: $isMultiple,
            comment   : 'timestamp immutable'
        );
    }

    public static function number(bool $isMultiple = false, ?string $comment = null): static {
        return new static(
            name      : 'number',
            isMultiple: $isMultiple,
            comment   : $comment
        );
    }

    public static function boolean(bool $isMultiple = false, ?string $comment = null): static {
        return new static(
            name      : 'boolean',
            isMultiple: $isMultiple,
            comment   : $comment
        );
    }

    public static function object(bool $isMultiple = false, ?string $comment = "Object/Record"): static {
        return new static(
            name      : '{[key:string]:any}',
            isMultiple: $isMultiple,
            comment   : $comment
        );
    }

    public static function any(bool $isMultiple = false, ?string $comment = null): static {
        return new static(
            name      : 'any',
            isMultiple: $isMultiple,
            comment   : $comment
        );
    }

    public static function array(bool $isMultiple = false, ?string $comment = "Array"): static {
        return static::object(
            isMultiple: $isMultiple,
            comment   : $comment
        );
    }

    public static function json(bool $isMultiple = false, ?string $comment = "JSON"): static {
        return static::object(
            isMultiple: $isMultiple,
            comment   : $comment
        );
    }

    public static function unknown(bool $isMultiple = false, ?string $comment = null): static {
        return new static(
            name      : 'unknown',
            isMultiple: $isMultiple,
            comment   : $comment
        );
    }

    public static function never(bool $isMultiple = false, ?string $comment = null): static {
        return new static(
            name      : 'never',
            isMultiple: $isMultiple,
            comment   : $comment
        );
    }

    public static function decimal(bool $isMultiple = false, ?string $comment = "Decimal"): static {
        return static::number(
            isMultiple: $isMultiple,
            comment   : $comment
        );
    }

    public static function float(bool $isMultiple = false, ?string $comment = "Float"): static {
        return static::number(
            isMultiple: $isMultiple,
            comment   : $comment
        );
    }

    public static function integer(bool $isMultiple = false, ?string $comment = "Integer"): static {
        return static::number(
            isMultiple: $isMultiple,
            comment   : $comment
        );
    }
}
