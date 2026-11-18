<?php

use CryptographicSession\Http\Controllers\CryptographicSessionController;
use Illuminate\Support\Facades\Route;

$prefix = config('cryptographic-session.route_prefix', 'security');

Route::middleware(['web'])->group(function () use ($prefix) {
    // Status endpoint (bisa diakses guest maupun authenticated)
    Route::get("{$prefix}/session-binding/status", [CryptographicSessionController::class, 'status'])
        ->name('security.session-binding.status');

    // Verification endpoint (auth-only)
    Route::middleware(['auth'])->group(function () use ($prefix) {
        Route::post("{$prefix}/session-proof", [CryptographicSessionController::class, 'verify'])
            ->name('security.session-proof.verify');
    });

    // Fallback direct service worker route jika file di public belum di-publish
    Route::get('/csb-service-worker.js', [CryptographicSessionController::class, 'serviceWorker'])
        ->name('csb.service-worker');
});

