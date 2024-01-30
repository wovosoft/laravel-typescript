<?php

namespace Wovosoft\LaravelTypescript\Util;

use Wovosoft\LaravelTypescript\Types\RelationType;

class Counter
{
    /**
     * Custom relations should have return types defined.
     * But if it is not, then the return type should be this type.
     * And this value should be php supported return types.
     * like primitive types or any other classes.
     * @see https://github.com/staudenmeir/eloquent-has-many-deep
     */

    public function run(string $relationClass): RelationType
    {
        return match ($relationClass) {
            //"\Staudenmeir\EloquentHasManyDeep\HasOneDeep"  => RelationType::One,
            "Staudenmeir\EloquentHasManyDeep\HasManyDeep" => RelationType::Many,
            default                                       => RelationType::One
        };
    }
}
