<?php

namespace Wovosoft\LaravelTypescript\Transformers;

class ObjectType
{
    public static function toTypescript(object $obj)
    {
        $reflection = new \ReflectionObject($obj);
        dump($reflection->getProperties());
    }
}
