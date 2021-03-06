<?php

namespace App\Modules\Article\UI\API\QueryWizards;

use App\Ship\Abstracts\QueryWizards\ModelQueryWizard;

class ArticleQueryWizard extends ModelQueryWizard
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
     * @return \Jackardios\QueryWizard\Handlers\Eloquent\Includes\AbstractEloquentInclude[]|string[]
     */
    protected function allowedIncludes(): array
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
}
