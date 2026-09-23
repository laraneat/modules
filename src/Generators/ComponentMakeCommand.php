<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Illuminate\Foundation\Console\ComponentMakeCommand as BaseComponentMakeCommand;
use Illuminate\Support\Facades\Config;
use Override;

/**
 * The view of a component is named after the class relative to the components namespace,
 * so "Modules\Blog\View\Components\Forms\Input" renders "blog::components.forms.input".
 *
 * @internal
 */
final class ComponentMakeCommand extends BaseComponentMakeCommand
{
    use NamespacesViews {
        getView as private namespacedView;
    }

    #[Override]
    protected function getView()
    {
        $module = $this->currentModule();
        $name = $this->argument('name');

        if ($module === null || ! is_string($name)) {
            return $this->namespacedView();
        }

        $components = $module->namespace.'\\'.trim(Config::string('modules.generators.make:component', 'View\\Components'), '\\').'\\';
        $class = str_replace('/', '\\', ltrim($name, '\\/'));

        if (! str_starts_with($class, $components)) {
            return $this->namespacedView();
        }

        $this->input->setArgument('name', substr($class, strlen($components)));

        try {
            return $this->namespacedView();
        } finally {
            $this->input->setArgument('name', $name);
        }
    }
}
