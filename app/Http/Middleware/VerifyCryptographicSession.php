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
            return response()->json([
                'message' =>
                    'Unauthenticated.',

                'proof_valid' =>
                    false,
            ], 401);
        }


        /*
         * =====================================================
         * Cryptographic Proof Required
         * =====================================================
         */

        $proof =
            $request->header('DPoP');

        if (!$proof) {
            return response()->json([
                'message' =>
                    'Cryptographic proof is required.',

                'proof_valid' =>
                    false,
            ], 403);
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
            return response()->json([
                'message' =>
                    $result['message'],

                'proof_valid' =>
                    false,
            ], $result['status']);
        }


        /*
         * =====================================================
         * Cryptographic Verification Successful
         * =====================================================
         */

        return $next($request);
    }
}