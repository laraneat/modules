<?php

namespace Modules\Author\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Author\Models\Author;
use Modules\User\Models\User;

class AuthorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-author');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Author $author): bool
    {
        return $user->can('view-author');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create-author');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Author $author): bool
    {
        return $user->can('update-author');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Author $author): bool
    {
        return $user->can('delete-author');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Author $author): bool
    {
        return $user->can('delete-author');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Author $author): bool
    {
        return $user->can('force-delete-author');
    }
}
