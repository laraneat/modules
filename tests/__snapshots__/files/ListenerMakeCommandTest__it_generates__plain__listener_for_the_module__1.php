<?php

namespace Modules\Author\Listeners;

use Modules\Author\Events\SomeAuthorEvent;

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
