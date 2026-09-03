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
         * Endpoint yang tidak membutuhkan cryptographic proof
         * =====================================================
         *
         * Endpoint ini merupakan bagian dari lifecycle
         * authentication/session dan harus tetap dapat
         * digunakan tanpa DPoP.
         */
        $excludedPaths = [
            '/logout',
            '/login',
            '/register',
            '/security/session-binding/status',
            '/security/session-proof',
        ];

        if (
            in_array(
                $request->getPathInfo(),
                $excludedPaths,
                true
            )
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
                'message' => 'Unauthenticated.',
                'proof_valid' => false,
            ], 401);
        }


        /*
         * =====================================================
         * Cryptographic Proof
         * =====================================================
         */

        $proof = $request->header('DPoP');

        if (!$proof) {
            return response()->json([
                'message' =>
                    'Cryptographic proof is required.',
                'proof_valid' => false,
            ], 403);
        }


        /*
         * =====================================================
         * Verify Proof
         * =====================================================
         */

        $result = $this->proofVerifier->verify(
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
         * Access Granted
         * =====================================================
         */

        return $next($request);
    }
}