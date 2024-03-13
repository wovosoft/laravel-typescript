<?php

namespace Wovosoft\LaravelTypescript;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionException;
use Wovosoft\LaravelTypescript\Helpers\ModelInspector;

class LaravelTypescript {
    public static function new(): static {
        return new static();
    }

    /**
     * @param  string|array  $sourceDir
     * @param  string  $outputPath
     *
     * @return array<string,string> {path,contents}
     * @throws ReflectionException
     */
    public function generate(
        string|array $sourceDir,
        string $outputPath,
    ): array {
        File::ensureDirectoryExists(dirname($outputPath));

        $contents = $this->toTypescript($sourceDir);

        File::put(path: $outputPath, contents: $contents);

        return [
            $outputPath,
            $contents,
        ];
    }

    /**
     * @param  string|array  $sourceDir
     *
     * @return string
     * @throws ReflectionException
     */
    public function toTypescript(string|array $sourceDir): string {

        return ModelInspector::getModelsIn($sourceDir)
            ->map(fn(string $modelClass) => [
                'namespace' => (new ReflectionClass($modelClass))->getNamespaceName(),
                'model'     => $modelClass,
            ])
            ->groupBy('namespace')
            ->mapWithKeys(fn(Collection $models, string $namespace) => [
                $namespace => $models->pluck('model'),
            ])
            ->map(function (Collection $modelClasses, string $namespace) {
                $namespace = ModelInspector::getQualifiedNamespace($namespace);

                return "declare namespace $namespace {"
                       .PHP_EOL
                       .$modelClasses
                           ->map(
                               function (string $modelClass) {
                                   return ModelInspector::new($modelClass)
                                       ->getInspectionResult()
                                       ->getGenerator()
                                       ->toTypescript();
                               }
                           )
                           ->implode(fn(string $content) => $content, PHP_EOL.PHP_EOL)
                       .PHP_EOL
                       .'}'
                       .PHP_EOL;
            })
            ->implode(fn(string $content) => $content, PHP_EOL.PHP_EOL);
    }
}
