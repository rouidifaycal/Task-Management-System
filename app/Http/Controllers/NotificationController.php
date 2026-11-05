<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('notifications.index', [
            'notifications' => $user->notifications()->latest()->paginate(20),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $userNotification = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $userNotification->markAsRead();

        return to_route('notifications.index')
            ->with('status', __('Notification marked as read.'));
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $now = now();

        $request->user()
            ->unreadNotifications()
            ->update([
                'read_at' => $now,
                'updated_at' => $now,
            ]);

        return to_route('notifications.index')
            ->with('status', __('All notifications marked as read.'));
    }
}
