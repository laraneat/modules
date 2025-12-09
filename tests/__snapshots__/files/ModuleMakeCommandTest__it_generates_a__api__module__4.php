<?php

namespace Modules\ArticleComment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ArticleComment\Models\ArticleComment;

/**
 * @method \Illuminate\Support\Collection<int, ArticleComment>|ArticleComment create($attributes = [], ?ArticleComment $parent = null)
 * @method \Illuminate\Support\Collection<int, ArticleComment> createMany(iterable $records)
 * @method ArticleComment createOne($attributes = [])
 * @method \Illuminate\Support\Collection<int, ArticleComment>|ArticleComment make($attributes = [], ?ArticleComment $parent = null)
 * @method ArticleComment makeOne($attributes = [])
 */
class ArticleCommentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string
     */
    protected $model = ArticleComment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            // TODO: add fields here
        ];
    }
}
