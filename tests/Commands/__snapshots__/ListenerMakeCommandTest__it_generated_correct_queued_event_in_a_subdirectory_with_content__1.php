<?php return '<?php

namespace App\\Modules\\Blog\\Listeners;

use App\\Modules\\Blog\\Events\\User\\WasCreated;
use Illuminate\\Queue\\InteractsWithQueue;
use Illuminate\\Contracts\\Queue\\ShouldQueue;

class NotifyUsersOfANewPost implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  WasCreated  $event
     * @return void
     */
    public function handle(WasCreated $event)
    {
        //
    }
}
';
