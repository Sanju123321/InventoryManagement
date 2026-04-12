<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class AppNotificationController extends Controller
{
    /** Show the notifications centre for the current company. */
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $notifications = AppNotification::where('company_id', $companyId)
            ->latest()
            ->paginate(20);

        // Mark all unread as read when the user visits the page
        AppNotification::where('company_id', $companyId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('notifications.index', compact('notifications'));
    }

    /** Mark a single notification as read (AJAX or redirect). */
    public function markRead(AppNotification $notification)
    {
        abort_unless($notification->company_id === auth()->user()->company_id, 403);

        $notification->update(['is_read' => true]);

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Marked as read.']);
        }

        return back();
    }

    /** Mark ALL unread notifications as read. */
    public function markAllRead()
    {
        AppNotification::where('company_id', auth()->user()->company_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /** Delete a single notification. */
    public function destroy(AppNotification $notification)
    {
        abort_unless($notification->company_id === auth()->user()->company_id, 403);
        $notification->delete();

        return back()->with('success', 'Notification deleted.');
    }

    /**
     * Return unread count (used by header bell via fetch/polling).
     */
    public function unreadCount()
    {
        $count = AppNotification::where('company_id', auth()->user()->company_id)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }
}
