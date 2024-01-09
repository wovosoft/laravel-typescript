<?php

namespace Wovosoft\LaravelTypescript;

use Doctrine\DBAL\Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionException;
use Wovosoft\LaravelTypescript\Helpers\ModelInspector;

class LaravelTypescript
{
    public static function new(): static
    {
        return new static();
    }

    /**
     * @param string|array $sourceDir
     * @param string       $outputPath
     *
     * @return array<string,string> {path,contents}
     * @throws ReflectionException
     *
     * @throws Exception
     */
    public function generate(
        string|array $sourceDir,
        string       $outputPath,
    ): array
    {
        File::ensureDirectoryExists(dirname($outputPath));

        $contents = $this->toTypescript($sourceDir);

        File::put(path: $outputPath, contents: $contents);

        return [
            $outputPath,
            $contents,
        ];
    }

    /**
     * @param string|array $sourceDir
     *
     * @return string
     * @throws ReflectionException
     *
     * @throws Exception
     */
    public function toTypescript(string|array $sourceDir): string
    {
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
                $modelTypes = $modelClasses
                ->map(
                    fn(string $modelClass) => (string)ModelInspector::new($modelClass)
                        ->getInspectionResult()
                        ->getGenerator()
                )
                ->implode(fn(string $content) => $content, PHP_EOL . PHP_EOL);
                if (config('laravel-typescript.declare_namespace')) {
                    return "declare namespace $namespace {" . PHP_EOL
                        . $modelTypes
                        . PHP_EOL . '}' . PHP_EOL;
                }

                return $modelTypes . PHP_EOL;
            })
            ->implode(fn(string $content) => $content, PHP_EOL . PHP_EOL);
    }
}
