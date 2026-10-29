<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Notifications\Models\Notification as InAppNotification;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = InAppNotification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return ApiResponse::success($notifications->through(function ($notification) {
            $payload = $notification->payload ?? [];

            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $payload['title'] ?? null,
                'body' => $payload['body'] ?? null,
                'data' => $payload['data'] ?? [],
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at,
            ];
        }));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = InAppNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return ApiResponse::success(['count' => $count]);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = InAppNotification::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return ApiResponse::error('Notification not found', 404);
        }

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return ApiResponse::success(null, 'Notification marked as read');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        InAppNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponse::success(null, 'All notifications marked as read');
    }
}