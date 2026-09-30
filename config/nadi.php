<?php

/*
|--------------------------------------------------------------------------
| Nadi Qurban OSI — app-specific configuration
|--------------------------------------------------------------------------
| Read through config() (not env()) so values survive `config:cache`.
*/

return [

    // First Super Admin (SuperAdminSeeder). Must change the password on first login.
    'superadmin' => [
        'name' => env('SUPERADMIN_NAME', 'Super Admin'),
        'email' => env('SUPERADMIN_EMAIL'),
        'password' => env('SUPERADMIN_PASSWORD'),
    ],

    // Password for DemoUserSeeder users (local/staging only).
    'seed_user_password' => env('SEED_USER_PASSWORD'),

];
