<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function poll(Request $request): JsonResponse
    {
        $notifications = $request->user()->unreadNotifications()->latest()->limit(20)->get();

        $notifications->each->markAsRead();

        return response()->json([
            'notifications' => $notifications->map(fn ($n) => [
                'message' => $n->data['message'] ?? '',
                'level'   => $n->data['level'] ?? 'info',
            ])->values(),
        ]);
    }
}