<?php

namespace App\Modules\Author\Listeners;

use App\Modules\Author\Events\SomeAuthorEvent;

class PlainAuthorListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SomeAuthorEvent $event): void
    {
        //
    }
}
