<?php

namespace Wovosoft\LaravelTypescript\Commands;

use Illuminate\Console\Command;
use Wovosoft\LaravelTypescript\LaravelTypescript;

class TypescriptModelTransformer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'typescript:transform-models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Model to Typescript Transfer Command';

    /**
     * Execute the console command.
     *
     * @throws \Exception
     */
    public function handle(): void
    {
        $transformer = new LaravelTypescript(
            outputPath: config('laravel-typescript.output_path'),
            sourceDir: config('laravel-typescript.source_dir')
        );
        $transformer->run();
    }
}
