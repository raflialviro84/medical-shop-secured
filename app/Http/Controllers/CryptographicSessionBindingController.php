<?php

namespace App\Http\Controllers;

use App\Models\CryptographicSessionBinding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CryptographicSessionBindingController extends Controller
{
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

        try {
            $proof = $validated['proof'];

            $parts = explode('.', $proof);

            if (count($parts) !== 3) {
                return response()->json([
                    'message' => 'Invalid proof format.',
                ], 400);
            }

            [
                $encodedHeader,
                $encodedPayload,
                $encodedSignature
            ] = $parts;

            $header = json_decode(
                $this->base64UrlDecode($encodedHeader),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $payload = json_decode(
                $this->base64UrlDecode($encodedPayload),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $signature = $this->base64UrlDecode(
                $encodedSignature
            );

            /*
             * 1. Validate header
             */

            if (($header['typ'] ?? null) !== 'csb+jwt') {
                return response()->json([
                    'message' => 'Invalid proof type.',
                ], 400);
            }

            if (($header['alg'] ?? null) !== 'ES256') {
                return response()->json([
                    'message' => 'Invalid proof algorithm.',
                ], 400);
            }

            if (
                !isset($header['jwk']) ||
                !is_array($header['jwk'])
            ) {
                return response()->json([
                    'message' => 'Proof public key is missing.',
                ], 400);
            }

            /*
             * 2. Validate payload
             */

            foreach ([
                'jti',
                'iat',
                'htm',
                'htu',
                'binding_id',
            ] as $field) {
                if (!array_key_exists($field, $payload)) {
                    return response()->json([
                        'message' => "Missing proof field: {$field}.",
                    ], 400);
                }
            }

            /*
             * 3. Validate algorithm / curve
             */

            if (($header['jwk']['kty'] ?? null) !== 'EC') {
                return response()->json([
                    'message' => 'Invalid public key type.',
                ], 400);
            }

            if (($header['jwk']['crv'] ?? null) !== 'P-256') {
                return response()->json([
                    'message' => 'Invalid public key curve.',
                ], 400);
            }

            if (
                !isset($header['jwk']['x']) ||
                !isset($header['jwk']['y'])
            ) {
                return response()->json([
                    'message' => 'Invalid EC public key.',
                ], 400);
            }

            /*
             * 4. Validate current session binding
             */

            $binding = CryptographicSessionBinding::query()
                ->where('id', $payload['binding_id'])
                ->where(
                    'session_id',
                    $request->session()->getId()
                )
                ->where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->first();

            if (!$binding) {
                return response()->json([
                    'message' => 'Cryptographic session binding tidak valid.',
                ], 403);
            }

            /*
             * 5. Ensure proof public key == bound public key
             */

            $boundPublicKey = $binding->public_key;
            $proofPublicKey = $header['jwk'];

            $sameKey =
                ($boundPublicKey['kty'] ?? null) === ($proofPublicKey['kty'] ?? null) &&
                ($boundPublicKey['crv'] ?? null) === ($proofPublicKey['crv'] ?? null) &&
                ($boundPublicKey['x'] ?? null) === ($proofPublicKey['x'] ?? null) &&
                ($boundPublicKey['y'] ?? null) === ($proofPublicKey['y'] ?? null);

            if (!$sameKey) {
                return response()->json([
                    'message' => 'Proof public key tidak sesuai dengan session binding.',
                ], 403);
            }

            /*
             * 6. Validate HTTP request context
             */

            if ($payload['htm'] !== 'POST') {
                return response()->json([
                    'message' => 'Invalid HTTP method binding.',
                ], 403);
            }

            if ($payload['htu'] !== '/security/session-proof') {
                return response()->json([
                    'message' => 'Invalid HTTP URI binding.',
                ], 403);
            }

            /*
             * 7. Validate proof timestamp
             */

            $currentTime = time();
            $issuedAt = (int) $payload['iat'];

            $allowedClockSkew = 60;

            if (
                $issuedAt < ($currentTime - $allowedClockSkew) ||
                $issuedAt > ($currentTime + $allowedClockSkew)
            ) {
                return response()->json([
                    'message' => 'Proof timestamp is outside the allowed window.',
                ], 403);
            }

            /*
             * 8. Convert bound JWK → PEM
             */

            $publicKeyPem = $this->ecJwkToPem(
                $boundPublicKey
            );

            $publicKey = openssl_pkey_get_public(
                $publicKeyPem
            );

            if ($publicKey === false) {
                return response()->json([
                    'message' => 'Unable to load public key.',
                ], 500);
            }

            /*
             * 9. Reconstruct exact signed data
             *
             * IMPORTANT:
             * We verify the exact header.payload string.
             */

            $signingInput =
                $encodedHeader . '.' . $encodedPayload;

            /*
             * 10. Convert Web Crypto raw ECDSA signature
             *     (r || s) → DER
             */

            if (strlen($signature) !== 64) {
                return response()->json([
                    'message' => 'Invalid ECDSA P-256 signature length.',
                ], 400);
            }

            $derSignature =
                $this->ecdsaRawSignatureToDer($signature);

            /*
             * 11. Verify ECDSA signature
             */

            $verificationResult = openssl_verify(
                $signingInput,
                $derSignature,
                $publicKey,
                OPENSSL_ALGO_SHA256
            );

            if ($verificationResult === 1) {
                return response()->json([
                    'message' => 'Cryptographic proof valid.',
                    'proof_valid' => true,
                    'binding_id' => $binding->id,
                ], 200);
            }

            if ($verificationResult === 0) {
                return response()->json([
                    'message' => 'Cryptographic proof invalid.',
                    'proof_valid' => false,
                ], 403);
            }

            return response()->json([
                'message' => 'Cryptographic verification error.',
                'proof_valid' => false,
            ], 500);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Unable to process cryptographic proof.',
            ], 400);
        }
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;

        if ($padding !== 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(
            strtr($value, '-_', '+/'),
            true
        );

        if ($decoded === false) {
            throw new \RuntimeException(
                'Invalid base64url data.'
            );
        }

        return $decoded;
    }

    private function ecJwkToPem(array $jwk): string
    {
        $x = $this->base64UrlDecode($jwk['x']);
        $y = $this->base64UrlDecode($jwk['y']);

        if (strlen($x) !== 32 || strlen($y) !== 32) {
            throw new \RuntimeException(
                'Invalid P-256 coordinate length.'
            );
        }

        /*
         * SubjectPublicKeyInfo prefix for:
         * id-ecPublicKey + prime256v1
         */

        $prefix = hex2bin(
            '3059301306072A8648CE3D020106082A8648CE3D030107034200'
        );

        $der = $prefix
            . "\x04"
            . $x
            . $y;

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(
                base64_encode($der),
                64,
                "\n"
            )
            . "-----END PUBLIC KEY-----\n";
    }

    private function ecdsaRawSignatureToDer(
        string $rawSignature
    ): string {
        $r = substr($rawSignature, 0, 32);
        $s = substr($rawSignature, 32, 32);

        $encodeInteger = function (string $value): string {
            $value = ltrim($value, "\x00");

            if ($value === '') {
                $value = "\x00";
            }

            if ((ord($value[0]) & 0x80) !== 0) {
                $value = "\x00" . $value;
            }

            return "\x02"
                . $this->derLength(strlen($value))
                . $value;
        };

        $derR = $encodeInteger($r);
        $derS = $encodeInteger($s);

        $sequence = $derR . $derS;

        return "\x30"
            . $this->derLength(strlen($sequence))
            . $sequence;
    }

    private function derLength(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $bytes = '';

        while ($length > 0) {
            $bytes = chr($length & 0xff) . $bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes))
            . $bytes;
    }
}