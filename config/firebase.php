<?php

return [
    // Absolute path to the Firebase service account JSON. Accepts either:
    //  - a `storage/app/...` relative path (resolved against the app base dir), or
    //  - a bare `app/...` path (resolved against the storage dir).
    'credentials' => env('FIREBASE_CREDENTIALS')
        ? (str_starts_with(env('FIREBASE_CREDENTIALS'), 'storage/')
            ? base_path(env('FIREBASE_CREDENTIALS'))
            : storage_path(env('FIREBASE_CREDENTIALS')))
        : storage_path('app/firebase-service-account.json'),
    'project_id' => env('FIREBASE_PROJECT_ID', ''),
];