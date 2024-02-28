<?php

namespace Modules\ArticleComment\UI\API\QueryWizards;

use Jackardios\QueryWizard\Eloquent\EloquentFilter;
use Jackardios\QueryWizard\Eloquent\EloquentInclude;
use Jackardios\QueryWizard\Eloquent\EloquentQueryWizard;
use Jackardios\QueryWizard\Eloquent\EloquentSort;

class ArticleCommentsQueryWizard extends EloquentQueryWizard
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
     * @return array<int, string|EloquentFilter>
     */
    protected function allowedFilters(): array
    {
        return [];
    }

    /**
     * @return array<int, string|EloquentInclude>
     */
    protected function allowedIncludes(): array
    {
        return [];
    }

    /**
     * @return array<int, string|EloquentInclude>
     */
    protected function defaultIncludes(): array
    {
        return [];
    }

    /**
     * @return array<int, string|EloquentSort>
     */
    protected function allowedSorts(): array
    {
        return [];
    }

    /**
     * @return array<int, string|EloquentSort>
     */
    protected function defaultSorts(): array
    {
        return [];
    }
}
