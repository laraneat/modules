<?php

namespace App\Modules\Author\Data\Factories;

use App\Modules\Author\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @method \Illuminate\Support\Collection<int, Author>|Author create($attributes = [], ?Author $parent = null)
 * @method \Illuminate\Support\Collection<int, Author> createMany(iterable $records)
 * @method Author createOne($attributes = [])
 * @method \Illuminate\Support\Collection<int, Author>|Author make($attributes = [], ?Author $parent = null)
 * @method Author makeOne($attributes = [])
 */
class SomeAuthorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string
     */
    protected $model = Author::class;

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

