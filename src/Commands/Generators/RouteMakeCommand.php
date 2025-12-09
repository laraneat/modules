<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class RouteMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:route
                            {name : The name of the route}
                            {module? : The name or package name of the app module}
                            {--ui= : The UI for which the route will be created}
                            {--action= : The class name of the action to be used in the route}
                            {--method= : HTTP request method}
                            {--url= : Route URL}
                            {--name= : Route name}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new route for the specified module.';

    /**
     * The UI for which the route will be created.
     * ('web' or 'api')
     */
    protected string $ui;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the route class name',
        ];
    }

    protected function beforeGenerate(): void
    {
        $this->ui = $this->getOptionOrChoice(
            'ui',
            question: 'Enter the UI for which the route will be created',
            choices: ['api', 'web'],
            default: 'api'
        );
        $this->componentType = $this->ui === 'api'
            ? ModuleComponentType::ApiRoute
            : ModuleComponentType::WebRoute;
    }

    protected function getContents(): string
    {
        $url = $this->getOptionOrAsk(
            'url',
            'Enter the route URL',
        );
        $method = $this->getOptionOrChoice(
            'method',
            'Select the HTTP request method',
            ['get', 'post', 'put', 'patch', 'delete', 'options'],
            'get'
        );
        $name = $this->getOptionOrAsk(
            'name',
            'Enter the route name',
            $this->generateDefaultRouteName($url, $method),
        );
        $actionClass = $this->getFullClassFromOptionOrAsk(
            optionName: 'action',
            question: 'Enter the class name of the action to be used in the route',
            componentType: ModuleComponentType::Action,
            module: $this->module
        );
        $stubReplaces = [
            'method' => $method,
            'url' => $url,
            'name' => $name,
        ];
        $stubReplaces['action'] = class_basename($actionClass);
        $stubReplaces['actionNamespace'] = $this->getNamespaceOfClass($actionClass);

        return Stub::create("route.stub", $stubReplaces)->render();
    }

    protected function generateDefaultActionName(string $url, string $method): string
    {
        $verb = $this->recognizeActionVerbByMethod($url, $method);
        $studlyVerb = Str::studly($verb);
        $resource = Str::studly($this->recognizeResourceByUrl($url));

        if ($verb === 'list') {
            $resource = Str::plural($resource);
        } else {
            $resource = Str::singular($resource);
        }

        if ($verb === 'options') {
            return $resource . $studlyVerb . 'Action';
        }

        return $studlyVerb . $resource . 'Action';
    }

    protected function generateDefaultRouteName(string $url, string $method): string
    {
        $verb = $this->recognizeActionVerbByMethod($url, $method);
        $resource = Str::snake($this->recognizeResourceByUrl($url));

        return Str::lower($this->ui) . '.' . $resource . '.' . $verb;
    }

    protected function recognizeActionVerbByMethod(string $url, string $method): string
    {
        if ($method === 'get') {
            $lastUrlPart = basename($url);

            if ($this->urlHasParameters($lastUrlPart)) {
                return 'view';
            }

            return 'list';
        }

        return in_array($method, ['post', 'put', 'patch']) ? 'update' : $method;
    }

    protected function recognizeResourceByUrl(string $url): string
    {
        $urlParts = explode('/', $url);
        $urlPartsNumber = count($urlParts);
        $lastUrlPart = $urlParts[$urlPartsNumber - 1];

        if (! $this->urlHasParameters($lastUrlPart)) {
            return $lastUrlPart;
        }

        if ($urlPartsNumber > 1) {
            $resource = $urlParts[$urlPartsNumber - 2];
        } else {
            $resource = $lastUrlPart;
        }

        return str_replace(['{', '}'], '', $resource);
    }

    protected function urlHasParameters(string $url): bool
    {
        return Str::contains($url, '{');
    }
}
