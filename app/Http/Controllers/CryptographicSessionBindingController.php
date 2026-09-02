<?php

namespace App\Http\Controllers;

use App\Models\CryptographicSessionBinding;
use App\Security\CryptographicSessionBinding\ProofVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

}