<?php

namespace App\Modules\Author\Listeners;

use App\Modules\Author\Events\SomeAuthorEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
