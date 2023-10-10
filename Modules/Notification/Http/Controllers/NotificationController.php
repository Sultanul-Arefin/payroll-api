<?php

namespace Modules\Notification\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Entities\Notification;
use Modules\Notification\Http\Resources\NotificationResource;

class NotificationController extends Controller
{
    function index() {
        $notifications = auth()
            ->user()
            ?->unreadNotifications();
        return apiResponse([
            'notifications' => $notifications
                ->limit(5)
                ->get(['id', 'type', 'data', 'read_at', 'created_at']),
            'notifications_count' => $notifications->count()
        ]);
    }
}
