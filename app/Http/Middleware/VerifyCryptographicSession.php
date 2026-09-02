<?php

namespace App\Http\Middleware;

use App\Security\CryptographicSessionBinding\ProofVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CryptographicNavigationGrant;
use Illuminate\Support\Str;

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
            'method' => ['required', 'string', 'in:GET'],
            'path' => ['required', 'string', 'max:2048'],
        ]);

        /*
        * Pastikan proof yang dikirim memang dibuat
        * untuk method/path tujuan navigasi.
        *
        * ProofVerifier sendiri akan memvalidasi htm/htu
        * terhadap request yang sedang masuk, jadi untuk
        * navigation grant kita perlukan request virtual
        * terhadap target GET.
        *
        * Untuk tahap pertama, grant endpoint akan memverifikasi
        * binding dan signature menggunakan proof yang diterima.
        */

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

        /*
        * Proof endpoint adalah POST /security/navigation-grant.
        * Karena ProofVerifier memeriksa HTM/HTU berdasarkan request
        * aktual, kita membutuhkan proof khusus untuk endpoint ini.
        *
        * Setelah proof valid, path tujuan tetap divalidasi
        * terhadap daftar halaman yang memang boleh dinavigasikan.
        */

        $allowedPaths = [
            '/transactions',
        ];

        if (!in_array($validated['path'], $allowedPaths, true)) {
            return response()->json([
                'message' => 'Navigation target is not allowed.',
            ], 403);
        }

        $sessionId = $request->session()->getId();

        $binding = CryptographicSessionBinding::query()
            ->where('binding_id', $result['binding_id'])
            ->where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->first();

        if (!$binding) {
            return response()->json([
                'message' => 'Session binding not found.',
            ], 403);
        }

        $token = Str::random(64);

        CryptographicNavigationGrant::create([
            'token' => hash('sha256', $token),
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'binding_id' => $binding->binding_id,
            'method' => 'GET',
            'path' => $validated['path'],
            'expires_at' => now()->addSeconds(10),
        ]);

        return response()->json([
            'message' => 'Navigation grant created.',
            'url' => $validated['path'] . '?nav_token=' . urlencode($token),
        ]);
    }
}