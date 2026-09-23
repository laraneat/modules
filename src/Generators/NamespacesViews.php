<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Override;

/**
 * Module views are registered under the module name, so the view a generated class
 * renders is "blog::mail.post", while the view file is still created as "mail/post".
 *
 * @internal
 */
trait NamespacesViews
{
    use ResolvesModule;

    private bool $buildingClass = false;

    #[Override]
    protected function buildClass($name)
    {
        $module = $this->currentModule();
        $markdown = $this->hasOption('markdown') ? $this->option('markdown') : null;

        // Before Laravel 13.33, make:notification renders the --markdown option without getView().
        $namespaced = $module !== null && is_string($markdown) && $markdown !== '' && ! str_contains($markdown, '::');

        if ($namespaced) {
            $this->input->setOption('markdown', $module->name.'::'.$markdown);
        }

        $this->buildingClass = true;

        try {
            return parent::buildClass($name);
        } finally {
            $this->buildingClass = false;

            if ($namespaced) {
                $this->input->setOption('markdown', $markdown);
            }
        }
    }

    /**
     * Not marked as an override: make:notification has getView() only since Laravel 13.33.
     *
     * @return string
     */
    protected function getView()
    {
        $view = parent::getView();
        $module = $this->currentModule();

        return $this->buildingClass && $module !== null && ! str_contains($view, '::') ? $module->name.'::'.$view : $view;
    }
}
