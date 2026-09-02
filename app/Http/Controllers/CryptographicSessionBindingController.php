<?php

namespace App\Http\Controllers;

use App\Models\CryptographicSessionBinding;
use App\Security\CryptographicSessionBinding\ProofVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\CryptographicNavigationGrant;
use Illuminate\Support\Str;

class CryptographicSessionBindingController extends Controller
{

    public function __construct(
        private ProofVerifier $proofVerifier
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'public_key' => ['required', 'array'],
            'public_key.kty' => ['required', 'string', 'in:EC'],
            'public_key.crv' => ['required', 'string', 'in:P-256'],
            'public_key.x' => ['required', 'string'],
            'public_key.y' => ['required', 'string'],
        ]);

        $sessionId = $request->session()->getId();

        $existing = CryptographicSessionBinding::query()
            ->where('session_id', $sessionId)
            ->first();

        if ($existing) {
            $existingKey = $existing->public_key;
            $incomingKey = $validated['public_key'];

            $sameKey =
                ($existingKey['kty'] ?? null) === ($incomingKey['kty'] ?? null) &&
                ($existingKey['crv'] ?? null) === ($incomingKey['crv'] ?? null) &&
                ($existingKey['x'] ?? null) === ($incomingKey['x'] ?? null) &&
                ($existingKey['y'] ?? null) === ($incomingKey['y'] ?? null);

            if (!$sameKey) {
                return response()->json([
                    'message' => 'Session sudah terikat dengan public key yang berbeda.',
                ], 409);
            }

            return response()->json([
                'message' => 'Cryptographic session binding sudah tersedia.',
                'binding_id' => $existing->id,
            ], 200);
        }

        $binding = CryptographicSessionBinding::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'public_key' => $validated['public_key'],
            'algorithm' => 'ECDSA',
            'curve' => 'P-256',
            'digest' => 'SHA-256',
        ]);

        return response()->json([
            'message' => 'Cryptographic session binding berhasil dibuat.',
            'binding_id' => $binding->id,
        ], 201);
    }

    public function verify(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'proof' => ['required', 'string'],
        ]);

        $result = $this->proofVerifier->verify(
            $request,
            $validated['proof']
        );

        if (!$result['valid']) {
            return response()->json([
                'message' => $result['message'],
                'proof_valid' => false,
            ], $result['status']);
        }

        return response()->json([
            'message' => 'Cryptographic proof valid.',
            'proof_valid' => true,
            'binding_id' => $result['binding_id'],
        ], 200);
    }

    public function createNavigationGrant(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'proof' => ['required', 'string'],
            'path' => ['required', 'string', 'max:2048'],
        ]);

        /*
        * Untuk tahap penelitian ini, hanya resource tertentu
        * yang boleh menggunakan navigation grant.
        */
        $allowedPaths = [
            '/transactions',
        ];

        if (!in_array($validated['path'], $allowedPaths, true)) {
            return response()->json([
                'message' => 'Navigation target is not allowed.',
            ], 403);
        }

        /*
        * Proof harus benar-benar dibuat untuk:
        *
        * GET /transactions
        *
        * bukan untuk:
        *
        * POST /security/navigation-grant
        */
        $result = $this->proofVerifier->verify(
            $request,
            $validated['proof'],
            'GET',
            $validated['path']
        );

        if (!$result['valid']) {
            return response()->json([
                'message' => $result['message'],
                'proof_valid' => false,
            ], $result['status']);
        }

        /*
        * Pastikan binding yang diverifikasi memang masih
        * terikat pada session dan user saat ini.
        */
        $sessionId = $request->session()->getId();

        $binding = CryptographicSessionBinding::query()
            ->where('id', $result['binding_id'])
            ->where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->first();

        if (!$binding) {
            return response()->json([
                'message' => 'Session binding not found.',
            ], 403);
        }

        /*
        * Token asli hanya dikirim ke browser.
        * Database hanya menyimpan hash token.
        */
        $plainToken = Str::random(64);

        CryptographicNavigationGrant::create([
            'token' => hash('sha256', $plainToken),
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'binding_id' => $binding->id,
            'method' => 'GET',
            'path' => $validated['path'],
            'expires_at' => now()->addSeconds(10),
            'used_at' => null,
        ]);

        return response()->json([
            'message' => 'Navigation grant created.',
            'url' => $validated['path']
                . '?nav_token='
                . urlencode($plainToken),
        ]);
    }

}