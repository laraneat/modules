<?php

namespace Modules\Author\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class AuthorPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        //
    }
}
