<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Support\Str;
use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Stub;
use Laraneat\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputOption;

/**
 * @group generator
 */
class RouteMakeCommand extends ComponentGeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:route';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new route for the specified module.';

    /**
     * Module instance.
     *
     * @var Module
     */
    protected Module $module;

    /**
     * Component type.
     *
     * @var string
     */
    protected string $componentType;

    /**
     * The UI for which the request will be created.
     *
     * @var string
     */
    protected string $ui = 'api';

    /**
     * Prepared 'name' argument.
     *
     * @var string
     */
    protected string $nameArgument;

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['ui', null, InputOption::VALUE_REQUIRED, 'The UI for which the route will be created.'],
            ['action', null, InputOption::VALUE_REQUIRED, 'The class name of the action to be used in the route.'],
            ['method', 'm', InputOption::VALUE_REQUIRED, 'HTTP request method.'],
            ['url', 'u', InputOption::VALUE_REQUIRED, 'Route URL.'],
            ['name', null, InputOption::VALUE_REQUIRED, 'Route name.'],
        ];
    }

    protected function prepare()
    {
        $this->module = $this->getModule();
        $this->ui = $this->getOptionOrChoice(
            'ui',
            'Select the UI for which the request will be created',
            ['api', 'web'],
            'api'
        );
        $this->componentType = "{$this->ui}-route";
        $this->nameArgument = $this->getTrimmedArgument('name');
    }

    protected function getDestinationFilePath(): string
    {
        return $this->getComponentPath($this->module, $this->nameArgument, $this->componentType);
    }

    protected function getTemplateContents(): string
    {
        $url = $this->getOptionOrAsk(
            'url',
            'Enter the route URL',
            '',
            true
        );
        $method = $this->getOptionOrChoice(
            'method',
            'Select the HTTP request method',
            ['get', 'post', 'put', 'patch', 'delete', 'options'],
            'get'
        );
        $action = $this->getOptionOrAsk(
            'action',
            'Enter the class name of the action to be used in the route',
            $this->generateDefaultActionName($url, $method),
            true
        );
        $name = $this->getOptionOrAsk(
            'name',
            'Enter the route name',
            $this->generateDefaultRouteName($url, $method),
            true
        );
        $stubReplaces = [
            'actionNamespace' => $this->getComponentNamespace($this->module, $action, 'action'),
            'action' => $this->getClass($action),
            'method' => $method,
            'url' => $url,
            'name' => $name,
        ];

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
