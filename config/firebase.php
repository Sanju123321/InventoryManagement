<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    | Your Firebase project ID, found in the Firebase Console → Project Settings.
    |
    */
    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Service Account Credentials Path
    |--------------------------------------------------------------------------
    | Path (relative to project root) to your Firebase service account JSON key.
    | Download from Firebase Console → Project Settings → Service Accounts.
    |
    | Example: storage/app/firebase/service-account.json
    |
    */
    'credentials_path' => env('FIREBASE_CREDENTIALS', 'storage/app/firebase/service-account.json'),

    /*
    |--------------------------------------------------------------------------
    | Web Push (Firebase JS SDK) Configuration
    |--------------------------------------------------------------------------
    | These values come from Firebase Console → Project Settings → General
    | → Your apps → Web app config.
    |
    */
    'web' => [
        'api_key'            => env('FIREBASE_WEB_API_KEY'),
        'auth_domain'        => env('FIREBASE_AUTH_DOMAIN'),
        'project_id'         => env('FIREBASE_PROJECT_ID'),
        'storage_bucket'     => env('FIREBASE_STORAGE_BUCKET'),
        'messaging_sender_id'=> env('FIREBASE_MESSAGING_SENDER_ID'),
        'app_id'             => env('FIREBASE_APP_ID'),
        'measurement_id'     => env('FIREBASE_MEASUREMENT_ID'),
        'vapid_key'          => env('FIREBASE_VAPID_KEY'),
    ],

];
