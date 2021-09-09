<?php

namespace App\Modules\Recipe\Repositories\Eloquent;

use App\Modules\Core\Repositories\Eloquent\EloquentBaseRepository;
use App\Modules\Recipe\Events\RecipeWasCreated;
use App\Modules\Recipe\Repositories\RecipeRepository;

class EloquentRecipeRepository extends EloquentBaseRepository implements RecipeRepository
{
    public function create($data)
    {
        $recipe = $this->model->create($data);

        event(new RecipeWasCreated($recipe, $data));

        return $recipe;
    }
}
