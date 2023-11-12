<?php

use Carbon\Carbon as CarbonMutable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Wovosoft\LaravelTypescript\Types\Type;

return [
    'output_path'       => resource_path('js/types/models.d.ts'),
    'source_dir'        => app_path('Models'),
    /**
     * Custom attributes should have return types defined.
     * But if it is not, then the return type should be this type.
     * And this value should be php supported return types.
     * like primitive types or any other classes.
     */
    'custom_attributes' => [
        'fallback_return_type' => 'string',
        /*
         * Return type resolver for the new style attribute's accessor method.
         * eg. prlDate():Attribute => Attribute::get(fn():return_type=>return_value)
         */
        'accessor_resolvers'   => fn(string $type) => match ($type) {
            CarbonImmutable::class => Type::immutableDatetime(),
            Carbon::class,
            CarbonMutable::class   => Type::mutableDatetime(),
            default                => Type::any()
        },
    ],
    "custom_relations"  => [
        "resolvers" => []
    ]
];
