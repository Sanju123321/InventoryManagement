<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles saving the browser's FCM registration token to the authenticated user.
 */
class FcmController extends Controller
{
    public function saveToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string|min:50',
        ]);

        $user = auth()->user();

        // Avoid a write if the token hasn't changed
        if ($user->fcm_token !== $request->fcm_token) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        return response()->json(['message' => 'FCM token saved.'], 200);
    }
}
