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
         * User harus sudah terautentikasi
         * menggunakan Laravel session.
         */
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        /*
         * Ambil cryptographic proof dari header DPoP.
         */
        $proof = $request->header('DPoP');

        /*
         * Session saja tidak cukup.
         *
         * Jika request tidak membawa proof,
         * request ditolak.
         */
        if (!$proof) {
            return response()->json([
                'message' =>
                    'Cryptographic proof is required.',
                'proof_valid' => false,
            ], 403);
        }

        /*
         * Verifikasi proof.
         *
         * ProofVerifier melakukan pemeriksaan:
         *
         * - format proof
         * - header typ
         * - algoritma ES256
         * - JWK EC P-256
         * - binding_id
         * - session_id
         * - user_id
         * - public key binding
         * - HTTP method
         * - HTTP URI
         * - timestamp
         * - ECDSA signature
         * - replay / JTI
         */
        $result = $this->proofVerifier->verify(
            $request,
            $proof
        );

        /*
         * Cryptographic proof tidak valid.
         */
        if (!$result['valid']) {
            return response()->json([
                'message' =>
                    $result['message'],

                'proof_valid' =>
                    false,
            ], $result['status']);
        }

        /*
         * Session + cryptographic proof valid.
         *
         * Request boleh diteruskan ke controller.
         */
        return $next($request);
    }
}