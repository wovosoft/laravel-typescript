<?php

namespace Wovosoft\LaravelTypescript\Types;

use Illuminate\Support\Collection;

/**
 * @description Simplified Definition of each
 *              property/attribute, which will be directly
 *              used to generate typescript key=>value pairs
 */
class Definition
{
    /**
     * @param string                           $namespace
     * @param string                           $name
     * @param string                           $model
     * @param string                           $modelShortName
     * @param array<Type>|Collection<int,Type> $types
     * @param bool                             $isRequired
     * @param bool                             $isUndefinable
     */
    public function __construct(
        public string $namespace,
        public string $name,
        public string $model,
        public string $modelShortName,
        public array|Collection $types,
        public bool $isRequired,
        public bool $isUndefinable
    ) {
    }

    public function __toString(): string
    {
        return
            collect($this->types)->implode(fn (Type $type) => (string) $type, ' | ')
            .(!$this->isRequired ? ' | null' : '');
    }
}
