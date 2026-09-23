<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Illuminate\Routing\Console\ControllerMakeCommand as BaseControllerMakeCommand;
use Illuminate\Support\Facades\Config;
use Override;

/**
 * With "--requests", the controller imports the form requests from the namespace
 * make:request creates them in, instead of "App\Http\Requests".
 *
 * @internal
 */
final class ControllerMakeCommand extends BaseControllerMakeCommand
{
    use ResolvesModule;

    private const array REQUEST_REPLACEMENTS = [
        '{{ namespacedStoreRequest }}', '{{namespacedStoreRequest}}',
        '{{ namespacedUpdateRequest }}', '{{namespacedUpdateRequest}}',
        '{{ namespacedRequests }}', '{{namespacedRequests}}',
    ];

    /**
     * @param  array<array-key, mixed>  $replace
     * @param  string  $modelClass
     * @return array<array-key, mixed>
     */
    #[Override]
    protected function buildFormRequestReplacements(array $replace, $modelClass)
    {
        $replace = parent::buildFormRequestReplacements($replace, $modelClass);
        $module = $this->currentModule();

        if ($module === null) {
            return $replace;
        }

        $namespace = $module->namespace.'\\'.trim(Config::string('modules.generators.make:request', 'Http\\Requests'), '\\').'\\';

        foreach (self::REQUEST_REPLACEMENTS as $key) {
            if (is_string($replace[$key] ?? null)) {
                $replace[$key] = str_replace('App\\Http\\Requests\\', $namespace, $replace[$key]);
            }
        }

        return $replace;
    }
}
