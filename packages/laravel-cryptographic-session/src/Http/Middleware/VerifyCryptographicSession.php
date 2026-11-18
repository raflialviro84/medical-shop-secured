<?php

namespace CryptographicSession\Http\Middleware;

use Closure;
use CryptographicSession\Contracts\ProofVerifierInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCryptographicSession
{
    public function __construct(
        protected ProofVerifierInterface $proofVerifier
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Pengecualian rute dari verifikasi proof
        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }

        // 2. User harus terautentikasi di Laravel
        if (!$request->user()) {
            abort(401, 'Unauthenticated.');
        }

        // 3. Header DPoP / Cryptographic proof wajib ada
        $headerName = config('cryptographic-session.header_name', 'DPoP');
        $proof = $request->header($headerName);

        if (!$proof) {
            abort(403, 'Cryptographic proof is required.');
        }

        // 4. Verifikasi keaslian proof
        $result = $this->proofVerifier->verify($request, $proof);

        if (!$result['valid']) {
            abort($result['status'] ?? 403, $result['message'] ?? 'Cryptographic proof invalid.');
        }

        return $next($request);
    }

    /**
     * Tentukan apakah request saat ini masuk dalam daftar pengecualian.
     */
    protected function shouldPassThrough(Request $request): bool
    {
        // Logout & Auth lifecycle
        if ($request->isMethod('POST') && $request->is('logout')) {
            return true;
        }

        if ($request->routeIs('logout')) {
            return true;
        }

        $excluded = config('cryptographic-session.excluded_routes', [
            'login',
            'register',
            'logout',
            'security/session-binding/*',
            'security/session-proof',
        ]);

        foreach ($excluded as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}

