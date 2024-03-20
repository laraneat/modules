<?php

namespace Modules\Author\Notifications;

use Illuminate\Notifications\Notification;

class PlainAuthorNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}
