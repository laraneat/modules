<?php

namespace Modules\Author\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SomeAuthorRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //
    }
}
