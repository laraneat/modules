<?php

namespace Modules\ArticleComment\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\ArticleComment\Models\ArticleComment;
use Modules\User\Models\User;

class ArticleCommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-article-comment');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ArticleComment $articleComment): bool
    {
        return $user->can('view-article-comment');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create-article-comment');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ArticleComment $articleComment): bool
    {
        return $user->can('update-article-comment');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ArticleComment $articleComment): bool
    {
        return $user->can('delete-article-comment');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ArticleComment $articleComment): bool
    {
        return $user->can('delete-article-comment');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ArticleComment $articleComment): bool
    {
        return $user->can('force-delete-article-comment');
    }
}
