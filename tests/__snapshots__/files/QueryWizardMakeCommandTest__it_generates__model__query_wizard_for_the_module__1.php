<?php

namespace Modules\Author\UI\API\QueryWizards;

use Jackardios\QueryWizard\Model\ModelInclude;
use Jackardios\QueryWizard\Model\ModelQueryWizard;

class AuthorQueryWizard extends ModelQueryWizard
{
    /**
     * @return array<int, string>
     */
    protected function allowedAppends(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    protected function defaultAppends(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    protected function allowedFields(): array
    {
        return [
            // TODO: add fields here
        ];
    }

    /**
     * @return array<int, string|ModelInclude>
     */
    protected function allowedIncludes(): array
    {
        return [];
    }

    /**
     * @return array<int, string|ModelInclude>
     */
    protected function defaultIncludes(): array
    {
        return [];
    }
}
