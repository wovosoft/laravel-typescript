<?php

namespace Wovosoft\LaravelTypescript\Types;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Wovosoft\LaravelTypescript\Helpers\ModelInspector;

enum RelationType
{
    case One;
    case Many;
    case OneOrMany;

    public static function getReturnCountType(string $relationClass): RelationType
    {
        if (!ModelInspector::isDefaultRelation($relationClass)) {
            if (is_subclass_of($relationClass, HasOneOrMany::class)) {
                return RelationType::OneOrMany;
            }

            $counterClass = config("laravel-typescript.counter");
            $counter = new $counterClass();

            return $counter->run($relationClass);
        }

        return match ($relationClass) {
            //HasOne::class,
            //HasOneThrough::class,
            //BelongsTo::class,
            //MorphOne::class,
            //MorphTo::class,
            //MorphPivot::class     => RelationType::One,

            HasManyThrough::class,
            HasMany::class,
            BelongsToMany::class,
            MorphMany::class,
            MorphToMany::class    => RelationType::Many,

            HasOneOrMany::class,
            MorphOneOrMany::class => RelationType::OneOrMany,

            default               => RelationType::One
        };
    }

    /**
     * @param string $relationClass
     * @return RelationType
     * @todo : Implement Custom relations
     */
    public static function getCustomReturnCountType(string $relationClass): RelationType
    {
        return match ($relationClass) {
            default => RelationType::One
        };
    }
}
