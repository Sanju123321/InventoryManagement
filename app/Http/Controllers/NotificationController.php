<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\Request;

/**
 * Example controller demonstrating how to trigger FCM notifications.
 *
 * Usage:
 *   Route::post('/notify/user/{user}', [NotificationController::class, 'notifyUser']);
 */
class NotificationController extends Controller
{
    public function __construct(private readonly FcmService $fcm) {}

    /**
     * Send a push notification to a specific user.
     *
     * POST /notify/user/{user}
     * Body: { "title": "...", "message": "..." }
     */
    public function notifyUser(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->company_id === $user->company_id, 403);

        $request->validate([
            'title'   => 'required|string|max:100',
            'message' => 'required|string|max:300',
        ]);

        if (empty($user->fcm_token)) {
            return response()->json(['message' => 'User has no FCM token registered.'], 422);
        }

        $sent = $this->fcm->sendToToken(
            token : $user->fcm_token,
            title : $request->title,
            body  : $request->message,
            data  : ['user_id' => (string) $user->id],
        );

        return $sent
            ? response()->json(['message' => 'Notification sent successfully.'])
            : response()->json(['message' => 'Failed to send notification. Check logs for details.'], 500);
    }

    /**
     * Broadcast a notification to all users in the authenticated company.
     *
     * POST /notify/broadcast
     * Body: { "title": "...", "message": "..." }
     */
    public function broadcast(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'title'   => 'required|string|max:100',
            'message' => 'required|string|max:300',
        ]);

        $companyId = auth()->user()->company_id;

        $tokens = User::where('company_id', $companyId)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json(['message' => 'No users have registered FCM tokens.'], 422);
        }

        $results = $this->fcm->sendToMultiple($tokens, $request->title, $request->message);
        $success  = count(array_filter($results));
        $failed   = count($results) - $success;

        return response()->json([
            'message' => "Notification sent to {$success} user(s). {$failed} failed.",
            'results' => $results,
        ]);
    }
}
