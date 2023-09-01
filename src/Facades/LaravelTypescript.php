<?php

namespace Wovosoft\LaravelTypescript\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method \Wovosoft\LaravelTypescript\LaravelTypescript new()
 * @method array generate(string|array $sourceDir, string $outputPath)
 * @method string toTypescript(string|array $sourceDir)
 */
class LaravelTypescript extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-typescript';
    }
}
