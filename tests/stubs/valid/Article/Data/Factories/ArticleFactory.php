<?php

namespace App\Modules\Article\Data\Factories;

use App\Modules\Article\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @method \Illuminate\Support\Collection|Article create($attributes = [], ?Article $parent = null)
 * @method \Illuminate\Support\Collection createMany(iterable $records)
 * @method Article createOne($attributes = [])
 * @method \Illuminate\Support\Collection|Article make($attributes = [], ?Article $parent = null)
 * @method Article makeOne($attributes = [])
 */
class ArticleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            //
        ];
    }
}

