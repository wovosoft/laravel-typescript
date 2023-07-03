<?php

namespace Wovosoft\LaravelTypescript\Transformers;

use Illuminate\Support\Collection;

class Typescript
{
    public function __construct(
        public string      $namespace,
        public string      $model,
        public string      $shortName,
        public ?Collection $types = null
    )
    {
    }

    public function generate(): string
    {
        return "\texport interface $this->shortName {" . PHP_EOL
            . $this->types?->implode(function (string|array $value, string $key) {
                if (is_string($value)) {
                    $value = [
                        "value" => $value
                    ];
                }

                if (isset($value['isUndefinable'])) {
                    return "\t\t$key?: {$value['value']};";
                }

                return "\t\t$key: {$value['value']};";
            }, PHP_EOL)
            . PHP_EOL . "\t}";
    }
}
