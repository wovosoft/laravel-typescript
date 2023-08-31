<?php

namespace Wovosoft\LaravelTypescript\Types;

class Definition
{
    /**
     * @param string      $namespace
     * @param string      $name
     * @param string      $model
     * @param array<Type> $types
     * @param bool        $isRequired
     * @param bool        $isUndefinable
     */
    public function __construct(
        public string $namespace,
        public string $name,
        public string $model,
        public array  $types,
        public bool   $isRequired,
        public bool   $isUndefinable
    )
    {
    }

    public function __toString(): string
    {
        return (collect($this->types)->implode(fn(Type $type) => (string)$type, " | "))
            . (!$this->isRequired ? ' | null' : '');
    }
}
