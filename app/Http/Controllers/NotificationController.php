<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Display a listing of notifications.
     */
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->with('auction')
            ->latest()
            ->paginate(20);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Get unread count for badge.
     */
    public function unreadCount(Request $request)
    {
        $count = $request->user()
            ->notifications()
            ->whereNull('last_seen_at')
            ->sum('unread_count');

        return response()->json(['count' => $count]);
    }

    /**
     * Get latest notifications for dropdown.
     */
    public function latest(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->with('auction')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json($notifications);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, int $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return back();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $this->notificationService->markAllAsRead($request->user());

        return back();
    }
}
