<?php

namespace Wovosoft\LaravelTypescript\Types;


use Wovosoft\LaravelTypescript\Helpers\Column;
use Wovosoft\LaravelTypescript\Helpers\Types;
use Wovosoft\LaravelTypescript\Types\Type as GenericType;

class ColumnType {
    public static function toType(Type|Column|string $type): GenericType {
        if ($type instanceof Type) {
            $type = $type->getName();
        }

        return Types::getGenericType($type);
    }
}
