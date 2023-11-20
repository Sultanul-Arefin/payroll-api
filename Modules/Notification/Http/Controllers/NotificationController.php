<?php

namespace Modules\Notification\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Notification\Http\Resources\NotificationResource;

class NotificationController extends Controller
{
    public function all_notifications(): AnonymousResourceCollection
    {
        $rows = request()?->has('rows') ? request('rows') : 15;

        return NotificationResource::collection(
            auth()
                ->user()
                ?->notifications()
                ->when(! is_null(request('search')), function ($query) {
                    $query->where(
                        'data',
                        'LIKE',
                        '%'.request('search').'%'
                    );
                })
                ->latest('created_at')
                ->cursorPaginate($rows)
        )->additional([
            'meta' => [
                'total' => auth()
                    ->user()
                    ?->notifications()
                    ->when(! is_null(request('search')), function ($query) {
                        $query->where(
                            'data',
                            'LIKE',
                            '%'.request('search').'%'
                        );
                    })
                    ->count(),
                'range' => $this->calculateRangeForsCursor($rows),
            ],
        ]);
    }

    public function calculateRangeForsCursor($rows): array
    {
        $totalRecordsCount = auth()
            ->user()
            ?->notifications()
            ->when(! is_null(request('search')), function ($query) {
                $query->where('data', 'LIKE', '%'.request('search').'%');
            })
            ->count();
        if (request()?->has('cursor')) {
            $cursor = json_decode(base64_decode(request('cursor')));
            if (is_null($cursor)) {
                return abort(500, 'Cursor value tempered');
            }
            if ($cursor->_pointsToNextItems === true) {
                $totalBeforeCursor = auth()
                    ->user()
                    ?->notifications()
                    ->when(! is_null(request('search')), function ($query) {
                        $query->where(
                            'data',
                            'LIKE',
                            '%'.request('search').'%'
                        );
                    })
                    ->where('created_at', '>=', $cursor->created_at)
                    ->count();

                return [
                    'from' => $totalBeforeCursor + 1,
                    'to' => min($totalRecordsCount, $rows + $totalBeforeCursor),
                    'total' => $totalRecordsCount,
                ];
            }

            $totalAfterCursor = auth()
                ->user()
                ?->notifications()
                ->when(! is_null(request('search')), function ($query) {
                    $query->where(
                        'data',
                        'LIKE',
                        '%'.request('search').'%'
                    );
                })
                ->where('created_at', '<=', $cursor->created_at)
                ->count();

            return [
                'from' => $totalRecordsCount - ($rows + $totalAfterCursor - 1),
                'to' => $totalRecordsCount - $totalAfterCursor,
                'total' => $totalRecordsCount,
            ];
        }

        return [
            'from' => 1,
            'to' => min($totalRecordsCount, $rows),
            'total' => $totalRecordsCount,
        ];
    }

    public function un_read_notifications(): JsonResponse
    {
        $notifications = auth()
            ->user()
            ?->unreadNotifications();

        return apiResponse([
            'notifications' => $notifications
                ->limit(5)
                ->get(['id', 'type', 'data', 'read_at', 'created_at']),
            'notifications_count' => $notifications->count(),
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function mark_all_as_read()
    {
        auth()
            ->user()
            ?->unreadNotifications->markAsRead();

        return apiResponse(
            data: [],
            message: 'Notifications marked as read'
        );
    }

    /**
     * @return JsonResponse
     */
    public function mark_single_as_read($id)
    {
        $notifications = auth()
            ->user()
            ?->unreadNotifications()
            ->where('id', $id)
            ->where('notifiable_type', User::class)
            ->get();
        if ($notifications->count() > 0) {
            $notifications->markAsRead();

            return apiResponse([], 'Notification marked as read');
        }

        return apiResponse([], 'No Notification exists');
    }
}
