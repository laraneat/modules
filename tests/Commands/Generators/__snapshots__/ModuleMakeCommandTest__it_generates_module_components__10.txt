<?php

namespace App\Modules\Article\Policies;

use App\Modules\Article\Models\Article;
use App\Modules\User\Models\User;
use App\Ship\Abstracts\Policies\Policy;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArticlePolicy extends Policy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param User $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('view-article');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param User $user
     * @param Article $article
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Article $article)
    {
        return $user->can('view-article');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param User $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('create-article');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @param Article $article
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Article $article)
    {
        return $user->can('update-article');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param Article $article
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Article $article)
    {
        return $user->can('delete-article');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param User $user
     * @param Article $article
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Article $article)
    {
        return $user->can('delete-article');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param User $user
     * @param Article $article
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Article $article)
    {
        return $user->can('force-delete-article');
    }
}
