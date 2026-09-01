<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCryptographicSession
{
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

        return $next($request);
    }
}