<?php

namespace Modules\Author\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Author\Events\SomeAuthorEvent;

class QueuedAuthorListener implements ShouldQueue
{
    use InteractsWithQueue;

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
