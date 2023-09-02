<?php

namespace Wovosoft\LaravelTypescript\Types;

use Wovosoft\LaravelTypescript\RelationType;
use Wovosoft\LaravelTypescript\Traits\HasTypeGenerators;

/**
 * @description Contains Information about each type
 */
class Type
{
    use HasTypeGenerators;

    public function __construct(
        private readonly string            $name,
        private readonly bool|RelationType $isMultiple,
        private readonly ?string           $comment = null
    )
    {
    }

    private function getQualifiedName(): string
    {
        return str($this->name)->replace('\\', '.')->value();
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

        /**
         * When the type is of Relation, it can be of one, multiple or one_many in number.
         */
        if ($this->isMultiple instanceof RelationType) {
            return match ($this->getIsMultiple()) {
                    RelationType::Many      => $name . '[]',
                    RelationType::OneOrMany => implode(' | ', [$name, $name . '[]']),
                    //RelationType::One       => $name,
                    default                 => $name
                } . ($this->comment ? " /** $this->comment **/" : "");
        }

        return $name . ($this->getIsMultiple() ? '[]' : '') . ($this->comment ? " /** $this->comment **/" : "");
    }

    public static function new(string $name, bool $isMultiple = false, ?string $comment = null): static
    {
        return new static(
            name      : $name,
            isMultiple: $isMultiple,
            comment   : $comment
        );
    }
}
