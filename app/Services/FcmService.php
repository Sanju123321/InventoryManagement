<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Firebase Cloud Messaging Service (HTTP v1 API)
 *
 * Responsibilities (Single Responsibility):
 *  - Build and sign the service-account JWT
 *  - Exchange JWT for a short-lived OAuth 2.0 access token (cached)
 *  - Send push notifications to one or more FCM tokens
 *
 * Setup requirements:
 *  1. Create a Firebase project and register a Web app.
 *  2. Download the service-account JSON: Firebase Console →
 *     Project Settings → Service Accounts → Generate new private key.
 *  3. Place the file at the path defined by FIREBASE_CREDENTIALS in .env.
 *  4. Add the .env variables shown in config/firebase.php.
 */
class FcmService
{
    private readonly string $projectId;
    private readonly array  $credentials;

    /**
     * @throws RuntimeException when the credentials file is missing / invalid.
     */
    public function __construct()
    {
        $this->projectId = (string) config('firebase.project_id');
        $path = base_path((string) config('firebase.credentials_path'));

        if (! file_exists($path)) {
            throw new RuntimeException(
                "Firebase service-account file not found: {$path}. " .
                'Download it from Firebase Console → Project Settings → Service Accounts.'
            );
        }

        $decoded = json_decode(file_get_contents($path), true);

        if (! is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
            throw new RuntimeException('Firebase credentials file is invalid or missing required fields.');
        }

        $this->credentials = $decoded;
    }

    /**
     * Send a push notification to a single FCM token.
     *
     * @param  string  $token   FCM registration token of the target device.
     * @param  string  $title   Notification title.
     * @param  string  $body    Notification body.
     * @param  array   $data    Optional key-value data payload (all values must be strings).
     * @param  int     $retries Number of retry attempts on transient failures.
     * @return bool             True on success, false after all retries exhausted.
     */
    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array  $data    = [],
        int    $retries = 3
    ): bool {
        $payload = $this->buildPayload($token, $title, $body, $data);

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $accessToken = $this->getAccessToken();

                $response = Http::withToken($accessToken)
                    ->timeout(10)
                    ->post(
                        "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                        $payload
                    );

                if ($response->successful()) {
                    return true;
                }

                // 400 / 404 → bad token, no point retrying
                if (in_array($response->status(), [400, 404], true)) {
                    Log::warning('FCM: invalid token or request', [
                        'token'  => substr($token, 0, 20) . '…',
                        'status' => $response->status(),
                        'body'   => $response->body(),
                    ]);
                    return false;
                }

                Log::warning("FCM: attempt {$attempt}/{$retries} failed", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

            } catch (Throwable $e) {
                Log::error("FCM: attempt {$attempt}/{$retries} threw exception: {$e->getMessage()}");
            }

            // Exponential back-off before next attempt
            if ($attempt < $retries) {
                sleep($attempt);
            }
        }

        return false;
    }

    /**
     * Send the same notification to multiple FCM tokens.
     *
     * Returns an array keyed by token → bool (success/failure).
     *
     * @param  string[] $tokens
     */
    public function sendToMultiple(
        array  $tokens,
        string $title,
        string $body,
        array  $data = []
    ): array {
        $results = [];
        foreach ($tokens as $token) {
            $results[$token] = $this->sendToToken($token, $title, $body, $data);
        }
        return $results;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** Build the FCM v1 message payload. */
    private function buildPayload(string $token, string $title, string $body, array $data): array
    {
        return [
            'message' => [
                'token'        => $token,
                'notification' => compact('title', 'body'),
                'data'         => array_map('strval', $data),
                'webpush'      => [
                    'fcm_options' => ['link' => config('app.url')],
                    'notification' => [
                        'icon'  => asset('assets/img/logo.png'),
                        'badge' => asset('assets/img/badge.png'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Retrieve (or refresh) a short-lived OAuth 2.0 access token.
     * The token is cached for 58 minutes (tokens expire in 60 minutes).
     */
    private function getAccessToken(): string
    {
        return Cache::remember('firebase_oauth_token', 3480, function () {
            $jwtAssertion = $this->buildJwt();

            $response = Http::asForm()
                ->timeout(10)
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwtAssertion,
                ]);

            if (! $response->successful()) {
                // Bust the cache so the next request retries
                Cache::forget('firebase_oauth_token');
                throw new RuntimeException('Failed to obtain Firebase access token: ' . $response->body());
            }

            return (string) $response->json('access_token');
        });
    }

    /**
     * Build a signed RS256 JWT for the service-account grant flow.
     *
     * Uses only PHP built-in functions (openssl) — no extra packages needed.
     */
    private function buildJwt(): string
    {
        $urlSafeB64 = static fn(string $data): string =>
            rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

        $now = time();

        $header  = $urlSafeB64((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $urlSafeB64((string) json_encode([
            'iss'   => $this->credentials['client_email'],
            'sub'   => $this->credentials['client_email'],
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ]));

        $signingInput = "{$header}.{$payload}";
        $privateKey   = openssl_pkey_get_private($this->credentials['private_key']);

        if ($privateKey === false) {
            throw new RuntimeException('Failed to load Firebase private key from credentials file.');
        }

        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return "{$signingInput}.{$urlSafeB64($signature)}";
    }
}
