<?php

namespace Modules\Author\Observers;

use Modules\Author\Models\Author;

class SomeAuthorObserver
{
    /**
     * Handle the Author "created" event.
     */
    public function created(Author $author): void
    {
        //
    }

    /**
     * Handle the Author "updated" event.
     */
    public function updated(Author $author): void
    {
        //
    }

    /**
     * Handle the Author "deleted" event.
     */
    public function deleted(Author $author): void
    {
        //
    }

    /**
     * Handle the Author "restored" event.
     */
    public function restored(Author $author): void
    {
        //
    }

    /**
     * Handle the Author "force deleted" event.
     */
    public function forceDeleted(Author $author): void
    {
        //
    }
}
