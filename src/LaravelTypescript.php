<?php

namespace Wovosoft\LaravelTypescript;

use Illuminate\Support\Facades\File;
use Wovosoft\LaravelTypescript\Helpers\Models;
use Wovosoft\LaravelTypescript\Helpers\Transformer;

class LaravelTypescript
{
    public function __construct(
        private ?string $outputPath = null,
        private ?string $sourceDir = null,
    )
    {
        if (!$this->outputPath) {
            $this->outputPath = resource_path("js/types/models.d.ts");
        }

        if (!$this->sourceDir) {
            $this->sourceDir = app_path("Models");
        }
    }

    public function run(): void
    {
        File::ensureDirectoryExists(dirname($this->outputPath));

        File::put(
            path: $this->outputPath,
            contents: Transformer::generate(Models::in($this->sourceDir))
        );
    }
}
