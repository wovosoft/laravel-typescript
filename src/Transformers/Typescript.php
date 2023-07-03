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
            . $this->types?->implode(fn(string $value, string $key) => "\t\t$key: $value;", PHP_EOL)
            . PHP_EOL . "\t}";
    }
}
