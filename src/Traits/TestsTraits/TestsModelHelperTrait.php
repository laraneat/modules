<?php

namespace Laraneat\Modules\Traits\TestsTraits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Laraneat\Modules\Exceptions\InvalidSubject;
use Throwable;

/**
 * @mixin \Illuminate\Foundation\Testing\TestCase
 */
trait TestsModelHelperTrait
{
    /**
     * @throws Throwable
     */
    public function assertExistsModelWithAttributes($subject, array $attributes): void
    {
        if (is_subclass_of($subject, Model::class)) {
            $subject = $subject::query();
        }

        throw_unless(
            $subject instanceof Builder || $subject instanceof Relation,
            InvalidSubject::make($subject)
        );

        foreach ($attributes as $key => $value) {
            $subject->where($key, $value);
        }

        $this->assertTrue($subject->exists());
    }
}
