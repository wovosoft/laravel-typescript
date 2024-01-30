<?php

namespace Wovosoft\LaravelTypescript\Util;


use Carbon\Carbon as CarbonMutable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Wovosoft\LaravelTypescript\Types\Type;

class AccessorResolver
{
    public function run(string $type): Type
    {
        return match ($type) {
            CarbonImmutable::class => Type::immutableDatetime(),
            Carbon::class,
            CarbonMutable::class   => Type::mutableDatetime(),
            default                => Type::any()
        };
    }
}
