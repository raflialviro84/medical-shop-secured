<?php

namespace App\Http\Middleware;

use App\Models\CryptographicNavigationGrant;
use App\Security\CryptographicSessionBinding\ProofVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCryptographicResource
{
    public function __construct(
        private ProofVerifier $proofVerifier
    ) {}

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        /*
         * ==================================================
         * 1. Pastikan user sudah authenticated
         * ==================================================
         */
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        /*
         * ==================================================
         * 2. Browser top-level navigation
         * ==================================================
         *
         * Browser tidak dapat mengirim custom header DPoP
         * secara langsung pada <a href="...">.
         *
         * Karena itu browser navigation menggunakan
         * one-time navigation grant.
         *
         * Contoh:
         *
         * /transactions?nav_token=xxxxx
         */
        $navigationToken = $request->query('nav_token');

        if ($navigationToken) {
            $hashedToken = hash(
                'sha256',
                $navigationToken
            );

            $grant = CryptographicNavigationGrant::query()
                ->where('token', $hashedToken)
                ->where(
                    'session_id',
                    $request->session()->getId()
                )
                ->where(
                    'user_id',
                    $request->user()->id
                )
                ->where(
                    'method',
                    strtoupper($request->method())
                )
                ->where(
                    'path',
                    $request->getPathInfo()
                )
                ->whereNull('used_at')
                ->where(
                    'expires_at',
                    '>',
                    now()
                )
                ->first();

            /*
             * Grant tidak ditemukan, sudah dipakai,
             * expired, session berbeda, user berbeda,
             * atau path berbeda.
             */
            if (!$grant) {
                return response()->json([
                    'message' =>
                        'Invalid or expired navigation grant.',
                ], 403);
            }

            /*
             * One-time use.
             *
             * Setelah berhasil digunakan,
             * grant langsung ditandai sebagai used.
             */
            $grant->update([
                'used_at' => now(),
            ]);

            /*
             * Lanjutkan ke controller.
             */
            return $next($request);
        }

        /*
         * ==================================================
         * 3. Request biasa menggunakan DPoP
         * ==================================================
         */
        $proof = $request->header('DPoP');

        if (!$proof) {
            return response()->json([
                'message' => 'Cryptographic proof is required.',
            ], 403);
        }

        /*
         * Verifikasi proof terhadap request aktual:
         *
         * - session
         * - user
         * - binding
         * - public key
         * - htm
         * - htu
         * - iat
         * - signature
         * - replay / jti
         */
        $result = $this->proofVerifier->verify(
            $request,
            $proof
        );

        if (!$result['valid']) {
            return response()->json([
                'message' => $result['message'],
                'proof_valid' => false,
            ], $result['status']);
        }

        /*
         * Proof valid.
         */
        return $next($request);
    }
}