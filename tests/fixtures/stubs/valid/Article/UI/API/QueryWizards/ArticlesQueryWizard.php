<?php

namespace App\Modules\Article\UI\API\QueryWizards;

use App\Ship\Abstracts\QueryWizards\EloquentQueryWizard;

class ArticlesQueryWizard extends EloquentQueryWizard
{
    /**
     * @return string[]
     */
    protected function allowedAppends(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    protected function allowedFields(): array
    {
        return [];
    }

    /**
     * @return \Jackardios\QueryWizard\Handlers\Eloquent\Filters\AbstractEloquentFilter[]|string[]
     */
    protected function allowedFilters(): array
    {
        return [];
    }

    /**
     * @return \Jackardios\QueryWizard\Handlers\Eloquent\Includes\AbstractEloquentInclude[]|string[]
     */
    protected function allowedIncludes(): array
    {
        return [];
    }

    /**
     * @return \Jackardios\QueryWizard\Handlers\Eloquent\Sorts\AbstractEloquentSort[]|string[]
     */
    protected function allowedSorts(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    protected function defaultAppends(): array
    {
        return [];
    }

    /**
     * @return \Jackardios\QueryWizard\Handlers\Eloquent\Includes\AbstractEloquentInclude[]|string[]
     */
    protected function defaultIncludes(): array
    {
        return [];
    }

    /**
     * @return \Jackardios\QueryWizard\Handlers\Eloquent\Sorts\AbstractEloquentSort[]|string[]
     */
    protected function defaultSorts(): array
    {
        return [];
    }
}
