<?php

namespace Wovosoft\LaravelTypescript;

use Doctrine\DBAL\Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Wovosoft\LaravelTypescript\Helpers\ModelInspector;

class LaravelTypescript
{
    /**
     * @param string|null $sourceDir
     * @param string|null $outputPath
     * @return void
     * @throws Exception
     * @throws \ReflectionException
     */
    public function generate(
        ?string $sourceDir = null,
        ?string $outputPath = null,
    )
    {
        if (!$outputPath) {
            $outputPath = resource_path('js/types/models2.d.ts');
        }

        if (!$sourceDir) {
            $sourceDir = app_path('Models');
        }

        File::ensureDirectoryExists(dirname($outputPath));

        $contents = ModelInspector::getModelsIn($sourceDir)
            ->map(fn(string $modelClass) => [
                "namespace" => (new \ReflectionClass($modelClass))->getNamespaceName(),
                "model"     => $modelClass
            ])
            ->groupBy("namespace")
            ->mapWithKeys(fn(Collection $models, string $namespace) => [
                $namespace => $models->pluck('model')
            ])
            ->map(function (Collection $modelClasses, string $namespace) {
                $namespace = str($namespace)->replace("\\", ".")->value();

                return "declare namespace $namespace {" . PHP_EOL
                    . $modelClasses
                        ->map(
                            fn(string $modelClass) => (string)ModelInspector::new()
                                ->inspectionFor($modelClass)
                                ->getInspectionResult()
                                ->getGenerator()
                        )
                        ->implode(
                            fn(string $content) => $content, PHP_EOL . PHP_EOL
                        )
                    . "}" . PHP_EOL;
            })
            ->implode(fn(string $content) => $content, PHP_EOL . PHP_EOL);

        File::put(path: $outputPath, contents: $contents);
    }
}
