<?php

namespace Wovosoft\LaravelTypescript\Transformers;

use Illuminate\Support\Collection;
use Wovosoft\LaravelTypescript\Enums\RelationMultipleEnum;

class Typescript
{
    private Collection $imports;

    public function __construct(
        public string                $namespace,
        public string                $model,
        public string                $shortName,
        private readonly ?Collection $types = null
    )
    {
        $this->imports = $types
            ?->filter(fn($value, $key) => !is_string($value))
            ->filter(fn($value) => $value['namespace'] !== $value['related_namespace'])
            ->mapWithKeys(function (string|array $value, string $key) {
                return [
                    $value['related_class_name'] => str($value['related_class'])->replace("\\", ".")->value()
                ];
            });
    }


    /**
     * After being called this function,
     * external imports will be resolved.
     * So, this method must be called first
     * @return string
     */
    public function generate(): string
    {
        return "\texport interface $this->shortName {" . PHP_EOL . $this->renderContents() . PHP_EOL . "\t}";
    }

    public function getImports(): Collection
    {
        return $this->imports;
    }

    public function renderImports(): string
    {
        if ($this->imports->isNotEmpty()) {
            return $this->imports->implode(function (string $namespace, string $key) {
                return "import $key = " . $namespace;
            }, ";" . PHP_EOL);
        }
        return '';
    }

    private function renderContents(): ?string
    {
        return $this->types?->implode(function (string|array $value, string $key) {
            if (is_string($value)) {
                return "\t\t$key: $value;";
            }

            $theValue = $value['value'];
            if ($value['namespace'] !== $value['related_namespace']) {
                $stringable = str($value['related_class'])->replace('\\', '.')->value();

                if ($value['return_count_type'] === RelationMultipleEnum::Multiple) {
                    $stringable .= "[]";
                }

                $theValue = $stringable . ' | null';
            }

            if (isset($value['isUndefinable'])) {
                return "\t\t$key?: {$theValue};";
            }

            return "\t\t$key: {$theValue};";
        }, PHP_EOL);
    }
}
