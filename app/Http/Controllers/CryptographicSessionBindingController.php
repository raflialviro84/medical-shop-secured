<?php

namespace App\Http\Controllers;

use App\Security\CryptographicSessionBinding\ProofVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CryptographicSessionBindingController extends Controller
{
    public function __construct(
        private ProofVerifier $proofVerifier
    ) {
    }

    /**
     * Verifikasi cryptographic proof.
     *
     * Endpoint ini digunakan untuk pengujian mekanisme
     * cryptographic proof secara langsung.
     *
     * Validasi utama dilakukan oleh ProofVerifier.
     */
    public function verify(Request $request): JsonResponse
    {
        /*
         * User harus sudah authenticated oleh
         * Laravel session.
         */
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        /*
         * Proof wajib dikirim melalui request body.
         */
        $validated = $request->validate([
            'proof' => [
                'required',
                'string',
            ],
        ]);

        /*
         * Delegasikan seluruh proses verifikasi
         * kepada ProofVerifier.
         *
         * ProofVerifier melakukan:
         *
         * - parsing proof
         * - validasi header
         * - validasi payload
         * - pencarian session binding
         * - pencocokan public key
         * - binding HTTP method
         * - binding URI
         * - validasi timestamp
         * - verifikasi ECDSA P-256
         * - replay protection
         */
        $result = $this->proofVerifier->verify(
            $request,
            $validated['proof']
        );

        /*
         * Proof tidak valid.
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
         * Proof valid.
         */
        return response()->json([
            'message' =>
                'Cryptographic proof valid.',

            'proof_valid' =>
                true,

            'binding_id' =>
                $result['binding_id'],
        ], 200);
    }
}