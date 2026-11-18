<?php

namespace CryptographicSession\Http\Controllers;

use CryptographicSession\Contracts\ProofVerifierInterface;
use CryptographicSession\Contracts\SessionBindingManagerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CryptographicSessionController extends Controller
{
    public function __construct(
        protected ProofVerifierInterface $proofVerifier,
        protected SessionBindingManagerInterface $bindingManager
    ) {
    }

    /**
     * Memeriksa status binding session saat ini.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'authenticated' => false,
                'bound' => false,
                'binding_id' => null,
            ], 200);
        }

        $binding = $this->bindingManager->getActiveBinding(
            $request->session()->getId(),
            $user->getAuthIdentifier()
        );

        return response()->json([
            'authenticated' => true,
            'bound' => $binding !== null,
            'binding_id' => $binding?->id,
        ], 200);
    }

    /**
     * Endpoint untuk verifikasi proof secara langsung (test/manual verify).
     */
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

        $result = $this->proofVerifier->verify($request, $validated['proof']);

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

    /**
     * Menyajikan file Service Worker dengan header Service-Worker-Allowed.
     */
    public function serviceWorker()
    {
        $path = __DIR__ . '/../../../resources/js/csb-service-worker.js';

        if (!file_exists($path)) {
            abort(404, 'Service Worker file not found.');
        }

        $content = file_get_contents($path);

        return response($content, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}

