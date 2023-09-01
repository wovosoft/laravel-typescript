<?php

namespace Wovosoft\LaravelTypescript\Console\Commands;

use Illuminate\Console\Command;
use Wovosoft\LaravelTypescript\Facades\LaravelTypescript;

class LaravelTypescriptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-typescript:transform';

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
        LaravelTypescript::generate(
            sourceDir : config("laravel-typescript.source_dir"),
            outputPath: config("laravel-typescript.output_path"),
        );

        $this->info("Successfully Generated Typescript Model Interfaces");
    }
}
