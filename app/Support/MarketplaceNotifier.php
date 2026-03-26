<?php

namespace App\Support;

use App\Models\MarketplaceNotification;

class MarketplaceNotifier
{
    public static function send(int $userId, string $title, string $message, string $type = 'info', ?string $actionUrl = null): void
    {
        MarketplaceNotification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'action_url' => $actionUrl,
            'is_read' => false,
        ]);
    }
}
