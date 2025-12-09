<?php

namespace Modules\Author\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SomeAuthorEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct()
    {
        //
    }

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return [];
    }
}
