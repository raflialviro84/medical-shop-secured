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
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $proof = $request->header('DPoP');

        if (!$proof) {
            return response()->json([
                'message' => 'Cryptographic proof is required.',
            ], 403);
        }

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

        return $next($request);
    }
}