<?php

namespace App\Http\Middleware;

use App\Security\CryptographicSessionBinding\ProofVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCryptographicSession
{
    public function __construct(
        private ProofVerifier $proofVerifier
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {

        /*
         * =====================================================
         * Authentication / Session Lifecycle Exclusions
         * =====================================================
         *
         * Endpoint berikut tidak membutuhkan cryptographic
         * proof karena merupakan bagian dari lifecycle session
         * atau endpoint CSB itu sendiri.
         */

        if (
            $request->isMethod('POST') &&
            $request->is('logout')
        ) {
            return $next($request);
        }

        if (
            $request->routeIs('logout')
        ) {
            return $next($request);
        }

        if (
            $request->is('login') ||
            $request->is('register')
        ) {
            return $next($request);
        }

        if (
            $request->is('security/session-binding/status')
        ) {
            return $next($request);
        }

        if (
            $request->is('security/session-proof')
        ) {
            return $next($request);
        }


        /*
         * =====================================================
         * Authentication
         * =====================================================
         */

        if (!$request->user()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'proof_valid' => false,
                ], 401);
            }
            abort(401, 'Unauthenticated.');
        }


        /*
         * =====================================================
         * Cryptographic Proof Required
         * =====================================================
         */

        $proof =
            $request->header('DPoP');

        if (!$proof) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Cryptographic proof is required.',
                    'proof_valid' => false,
                ], 403);
            }
            abort(403, 'Cryptographic proof is required.');
        }


        /*
         * =====================================================
         * Verify Cryptographic Proof
         * =====================================================
         */

        $result =
            $this->proofVerifier->verify(
                $request,
                $proof
            );


        if (!$result['valid']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $result['message'],
                    'proof_valid' => false,
                ], $result['status']);
            }
            abort($result['status'], $result['message']);
        }


        /*
         * =====================================================
         * Cryptographic Verification Successful
         * =====================================================
         */

        return $next($request);
    }
}