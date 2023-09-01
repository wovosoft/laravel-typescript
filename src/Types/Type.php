<?php

namespace Wovosoft\LaravelTypescript\Types;

use Wovosoft\LaravelTypescript\RelationType;

class Type
{
    public function __construct(
        private readonly string            $name,
        private readonly bool|RelationType $isMultiple
    )
    {
    }

    private function getQualifiedName(): string
    {
        return str($this->name)->replace("\\", ".")->value();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIsMultiple(): bool|RelationType
    {
        return $this->isMultiple;
    }

    public function __toString(): string
    {
        $name = $this->getQualifiedName();

        if ($this->isMultiple instanceof RelationType) {
            return match ($this->isMultiple) {
                //RelationType::One       => $this->name,
                RelationType::Many      => $name . "[]",
                RelationType::OneOrMany => implode(" | ", [$name, $name . "[]"]),
                default                 => $name
            };
        }

        return $name . ($this->isMultiple ? '[]' : '');
    }
}
