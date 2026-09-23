<?php

declare(strict_types=1);

namespace Laraneat\Modules\Scaffold;

use FilesystemIterator;
use Illuminate\Support\Str;
use Laraneat\Modules\Exceptions\InvalidTemplate;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Renders a module template directory. "*.stub" files are rendered to files without the
 * extension, other files are copied as they are. Placeholders work in paths and in stubs:
 * "{{ name }}", "{{ name|plural|snake }}". Unknown variables and filters are errors.
 *
 * @internal
 */
final class TemplateRenderer
{
    private const string PLACEHOLDER = '/\{\{\s*([A-Za-z_]\w*)((?:\s*\|\s*[A-Za-z_]\w*)*)\s*\}\}/';

    /**
     * Render the template into memory, so nothing is written when it is invalid.
     *
     * @param  array<string, string>  $variables
     * @return array<string, string> File contents by their path relative to the module.
     */
    public function render(string $template, array $variables): array
    {
        $template = rtrim(str_replace('\\', '/', $template), '/');

        if (! is_dir($template)) {
            throw InvalidTemplate::at($template, 'the directory does not exist.');
        }

        $files = [];
        $sources = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($template, FilesystemIterator::SKIP_DOTS)) as $file) {
            $source = substr(str_replace('\\', '/', $file->getPathname()), strlen($template) + 1);
            $isStub = str_ends_with($source, '.stub');
            $path = $this->renderPath($isStub ? substr($source, 0, -5) : $source, $source, $variables);

            if (isset($files[$path])) {
                throw InvalidTemplate::at($source, "another file is also rendered to [{$path}].");
            }

            $contents = (string) file_get_contents($file->getPathname());
            $files[$path] = $isStub ? $this->renderString($contents, $source, $variables) : $contents;
            $sources[$path] = $source;
        }

        foreach ($sources as $path => $source) {
            for ($directory = dirname($path); $directory !== '.'; $directory = dirname($directory)) {
                if (isset($files[$directory])) {
                    throw InvalidTemplate::at($sources[$directory], "the file is rendered to [{$directory}], which is also the directory of [{$path}].");
                }
            }
        }

        ksort($files, SORT_STRING);

        return $files;
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function renderPath(string $path, string $source, array $variables): string
    {
        return implode('/', array_map(function (string $segment) use ($source, $variables): string {
            $segment = $this->renderString($segment, $source, $variables);

            if (in_array($segment, ['', '.', '..'], true) || strpbrk($segment, "/\\\0:") !== false) {
                throw InvalidTemplate::at($source, "the path renders to an unsafe name [{$segment}].");
            }

            return $segment;
        }, explode('/', $path)));
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function renderString(string $string, string $source, array $variables): string
    {
        return (string) preg_replace_callback(self::PLACEHOLDER, function (array $matches) use ($source, $variables): string {
            if (! array_key_exists($matches[1], $variables)) {
                throw InvalidTemplate::at($source, "unknown variable [{$matches[1]}].");
            }

            $value = $variables[$matches[1]];

            foreach (array_filter(array_map(trim(...), explode('|', $matches[2]))) as $filter) {
                $value = $this->filter($filter, $value, $source);
            }

            return $value;
        }, $string);
    }

    private function filter(string $filter, string $value, string $source): string
    {
        return match ($filter) {
            'studly' => Str::studly($value),
            'camel' => Str::camel($value),
            'snake' => Str::snake(Str::camel($value)),
            'kebab' => Str::kebab(Str::camel($value)),
            'plural' => Str::plural($value),
            'singular' => Str::singular($value),
            'lower' => Str::lower($value),
            'upper' => Str::upper($value),
            'title' => Str::title($value),
            'json' => substr(json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 1, -1),
            default => throw InvalidTemplate::at($source, "unknown filter [{$filter}]."),
        };
    }
}
