<?php

namespace App\Modules\Recipe\Repositories\Cache;

use App\Modules\Core\Repositories\Cache\BaseCacheDecorator;
use App\Modules\Recipe\Repositories\RecipeRepository;

class CacheRecipeDecorator extends BaseCacheDecorator implements RecipeRepository
{
    public function __construct(RecipeRepository $recipe)
    {
        parent::__construct();
        $this->entityName = 'recipe.recipes';
        $this->repository = $recipe;
    }
}
