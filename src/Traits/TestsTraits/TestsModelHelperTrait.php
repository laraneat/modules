<?php

namespace Laraneat\Modules\Traits\TestsTraits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Laraneat\Modules\Exceptions\InvalidSubject;

/**
 * @mixin \Illuminate\Foundation\Testing\TestCase
 */
trait TestsModelHelperTrait
{
    public function assertExistsModelWhereColumns($subject, array $columns): void
    {
        $this->assertTrue(
            $this->makeQueryWhereColumns($subject, $columns)->exists(),
            sprintf('Model where columns (%s) not exists', http_build_query($columns))
        );
    }

    public function getModelsWhereColumns($subject, array $columns): Collection
    {
        return $this->makeQueryWhereColumns($subject, $columns)->get();
    }

    public function makeQueryWhereColumns($subject, array $columns): Builder|Relation
    {
        if (is_subclass_of($subject, Model::class)) {
            $subject = $subject::query();
        }

        throw_unless(
            $subject instanceof Builder || $subject instanceof Relation,
            InvalidSubject::make($subject)
        );

        foreach ($columns as $column => $value) {
            $subject->where($column, $value);
        }

        return $subject;
    }
}
