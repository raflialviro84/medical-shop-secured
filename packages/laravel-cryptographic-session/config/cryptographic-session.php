<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cryptographic Header Name
    |--------------------------------------------------------------------------
    |
    | Header HTTP yang digunakan untuk membawa proof signature ECDSA dari browser.
    | Default menggunakan format DPoP (Demonstrating Proof-of-Possession).
    |
    */
    'header_name' => env('CSB_HEADER_NAME', 'DPoP'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Clock Skew (Detik)
    |--------------------------------------------------------------------------
    |
    | Toleransi selisih waktu (timestamp window iat) antara client dan server.
    |
    */
    'clock_skew_seconds' => env('CSB_CLOCK_SKEW', 60),

    /*
    |--------------------------------------------------------------------------
    | Kriptografi Algoritma & Kurva
    |--------------------------------------------------------------------------
    |
    | Algoritma ECDSA yang didukung oleh Web Crypto API pada browser modern.
    |
    */
    'algorithm' => 'ES256',
    'curve' => 'P-256',
    'digest' => 'SHA-256',

    /*
    |--------------------------------------------------------------------------
    | Otomatisasi Lifecycle Event
    |--------------------------------------------------------------------------
    |
    | Menghubungkan secara otomatis ke event bawaan Laravel Auth:
    | - Illuminate\Auth\Events\Login (Otomatis bind session dengan public key)
    | - Illuminate\Auth\Events\Logout (Otomatis revoke session binding)
    |
    */
    'auto_bind_on_login' => env('CSB_AUTO_BIND_ON_LOGIN', true),
    'auto_revoke_on_logout' => env('CSB_AUTO_REVOKE_ON_LOGOUT', true),

    /*
    |--------------------------------------------------------------------------
    | Nama Field / Header Public Key saat Login
    |--------------------------------------------------------------------------
    |
    | Nama parameter di form login (request body / input) atau HTTP Header
    | yang memuat JWK public key dari browser.
    |
    */
    'public_key_inputs' => [
        'publicKey',
        'public_key',
        'csb_public_key',
    ],
    'public_key_header' => 'X-Client-Public-Key',

    /*
    |--------------------------------------------------------------------------
    | Route Pengecualian (Bypass)
    |--------------------------------------------------------------------------
    |
    | Rute yang tidak membutuhkan verifikasi cryptographic proof pada middleware.
    |
    */
    'excluded_routes' => [
        'login',
        'register',
        'logout',
        'security/session-binding/status',
        'security/session-proof',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Package Bawaan
    |--------------------------------------------------------------------------
    |
    | Apakah package harus mendaftarkan rute otomatis untuk status binding,
    | proof verifier testing, dan service worker.
    |
    */
    'register_routes' => true,
    'route_prefix' => 'security',

    /*
    |--------------------------------------------------------------------------
    | Model Mapping
    |--------------------------------------------------------------------------
    |
    | Anda dapat memperluas (extend) Eloquent Model berikut jika ingin
    | menyesuaikan nama tabel atau relasi database.
    |
    */
    'models' => [
        'binding' => \CryptographicSession\Models\CryptographicSessionBinding::class,
        'replay' => \CryptographicSession\Models\CryptographicProofReplay::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Configuration
    |--------------------------------------------------------------------------
    |
    | Pengaturan default yang dikirim ke browser client script.
    |
    */
    'frontend' => [
        'db_name' => env('CSB_DB_NAME', 'medical-shop-security'),
        'service_worker_path' => env('CSB_SW_PATH', '/csb-service-worker.js'),
        'status_endpoint' => env('CSB_STATUS_ENDPOINT', '/security/session-binding/status'),
    ],
];

