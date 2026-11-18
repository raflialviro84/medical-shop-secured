<?php

namespace CryptographicSession\Services;

use CryptographicSession\Contracts\ProofVerifierInterface;
use CryptographicSession\Models\CryptographicProofReplay;
use CryptographicSession\Models\CryptographicSessionBinding;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProofVerifier implements ProofVerifierInterface
{
    public function __construct(
        protected int $clockSkew = 60
    ) {
    }

    /**
     * Verifikasi cryptographic proof terhadap request Laravel saat ini.
     *
     * @param Request $request
     * @param string $proof
     * @param string|null $expectedMethod
     * @param string|null $expectedUri
     * @return array{valid: bool, status?: int, message?: string, binding_id?: int}
     */
    public function verify(
        Request $request,
        string $proof,
        ?string $expectedMethod = null,
        ?string $expectedUri = null
    ): array {
        // 1. Pecah proof menjadi: header.payload.signature
        $parts = explode('.', $proof);

        if (count($parts) !== 3) {
            return $this->invalid(400, 'Invalid proof format.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        try {
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

            $signature = $this->base64UrlDecode($encodedSignature);
        } catch (\Throwable) {
            return $this->invalid(400, 'Invalid proof encoding.');
        }

        if (!is_array($header) || !is_array($payload)) {
            return $this->invalid(400, 'Invalid proof JSON structure.');
        }

        // 2. Validasi header
        if (($header['typ'] ?? null) !== 'csb+jwt') {
            return $this->invalid(400, 'Invalid proof type.');
        }

        $expectedAlg = config('cryptographic-session.algorithm', 'ES256');
        if (($header['alg'] ?? null) !== $expectedAlg) {
            return $this->invalid(400, 'Invalid proof algorithm.');
        }

        if (!isset($header['jwk']) || !is_array($header['jwk'])) {
            return $this->invalid(400, 'Proof public key is missing.');
        }

        // 3. Validasi payload
        $requiredFields = ['jti', 'iat', 'htm', 'htu', 'binding_id'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $payload)) {
                return $this->invalid(400, "Missing proof field: {$field}.");
            }
        }

        if (!is_string($payload['jti']) || $payload['jti'] === '') {
            return $this->invalid(400, 'Invalid jti.');
        }

        if (!is_numeric($payload['iat'])) {
            return $this->invalid(400, 'Invalid iat.');
        }

        if (!is_string($payload['htm']) || $payload['htm'] === '') {
            return $this->invalid(400, 'Invalid htm.');
        }

        if (!is_string($payload['htu']) || $payload['htu'] === '') {
            return $this->invalid(400, 'Invalid htu.');
        }

        // 4. Validasi tipe dan parameter EC
        if (($header['jwk']['kty'] ?? null) !== 'EC') {
            return $this->invalid(400, 'Invalid public key type.');
        }

        $expectedCurve = config('cryptographic-session.curve', 'P-256');
        if (($header['jwk']['crv'] ?? null) !== $expectedCurve) {
            return $this->invalid(400, 'Invalid public key curve.');
        }

        if (!isset($header['jwk']['x']) || !isset($header['jwk']['y'])) {
            return $this->invalid(400, 'Invalid EC public key.');
        }

        if (!is_string($header['jwk']['x']) || !is_string($header['jwk']['y'])) {
            return $this->invalid(400, 'Invalid EC public key coordinates.');
        }

        // 5. Cari binding aktif
        $user = $request->user();
        if (!$user) {
            return $this->invalid(401, 'Unauthenticated.');
        }

        $bindingModel = config('cryptographic-session.models.binding', CryptographicSessionBinding::class);
        $binding = $bindingModel::query()
            ->where('id', $payload['binding_id'])
            ->where('session_id', $request->session()->getId())
            ->where('user_id', (string) $user->getAuthIdentifier())
            ->whereNull('revoked_at')
            ->first();

        if (!$binding) {
            return $this->invalid(403, 'Cryptographic session binding tidak valid.');
        }

        // 6. Cocokkan public key proof dengan public key binding di DB
        $boundPublicKey = $binding->public_key;
        $proofPublicKey = $header['jwk'];

        if (!is_array($boundPublicKey)) {
            return $this->invalid(500, 'Stored public key is invalid.');
        }

        $sameKey =
            ($boundPublicKey['kty'] ?? null) === ($proofPublicKey['kty'] ?? null)
            && ($boundPublicKey['crv'] ?? null) === ($proofPublicKey['crv'] ?? null)
            && ($boundPublicKey['x'] ?? null) === ($proofPublicKey['x'] ?? null)
            && ($boundPublicKey['y'] ?? null) === ($proofPublicKey['y'] ?? null);

        if (!$sameKey) {
            return $this->invalid(403, 'Proof public key tidak sesuai dengan session binding.');
        }

        // 7. Bind proof terhadap HTTP request
        $expectedMethod = strtoupper($expectedMethod ?? $request->method());
        $expectedUri = $expectedUri ?? $request->getPathInfo();

        $proofMethod = strtoupper((string) $payload['htm']);
        $proofUri = (string) $payload['htu'];

        if ($proofMethod !== $expectedMethod) {
            return $this->invalid(403, 'Invalid HTTP method binding.');
        }

        if ($proofUri !== $expectedUri) {
            return $this->invalid(403, 'Invalid HTTP URI binding.');
        }

        // 8. Validasi Timestamp window / Clock Skew
        $currentTime = time();
        $issuedAt = (int) $payload['iat'];
        $allowedClockSkew = config('cryptographic-session.clock_skew_seconds', $this->clockSkew);

        if (
            $issuedAt < ($currentTime - $allowedClockSkew)
            || $issuedAt > ($currentTime + $allowedClockSkew)
        ) {
            return $this->invalid(403, 'Proof timestamp is outside the allowed window.');
        }

        // 9. Validasi panjang raw signature ECDSA Web Crypto (64 byte: r=32, s=32)
        if (strlen($signature) !== 64) {
            return $this->invalid(400, 'Invalid ECDSA P-256 signature length.');
        }

        // 10. Konversi JWK -> PEM
        try {
            $publicKeyPem = $this->ecJwkToPem($boundPublicKey);
        } catch (\Throwable) {
            return $this->invalid(400, 'Invalid EC public key.');
        }

        $publicKey = openssl_pkey_get_public($publicKeyPem);
        if ($publicKey === false) {
            return $this->invalid(500, 'Unable to load public key.');
        }

        // 11. Reconstruct signing input: base64url(header) . "." . base64url(payload)
        $signingInput = $encodedHeader . '.' . $encodedPayload;

        // 12. Konversi raw Web Crypto signature (r||s) -> DER format untuk OpenSSL
        try {
            $derSignature = $this->ecdsaRawSignatureToDer($signature);
        } catch (\Throwable) {
            return $this->invalid(400, 'Unable to process ECDSA signature.');
        }

        // 13. Verify ECDSA signature
        $verificationResult = openssl_verify(
            $signingInput,
            $derSignature,
            $publicKey,
            OPENSSL_ALGO_SHA256
        );

        if ($verificationResult === 1) {
            // 14. Replay protection (JTI)
            $replayModel = config('cryptographic-session.models.replay', CryptographicProofReplay::class);

            $existingReplay = $replayModel::query()
                ->where('jti', $payload['jti'])
                ->first();

            if ($existingReplay) {
                return $this->invalid(403, 'Proof sudah pernah digunakan.');
            }

            try {
                $replayModel::create([
                    'jti' => $payload['jti'],
                    'binding_id' => $binding->id,
                    'issued_at' => Carbon::createFromTimestamp($issuedAt),
                    'expires_at' => Carbon::createFromTimestamp($issuedAt)->addSeconds($allowedClockSkew),
                ]);
            } catch (\Throwable) {
                return $this->invalid(403, 'Proof sudah pernah digunakan.');
            }

            return [
                'valid' => true,
                'binding_id' => $binding->id,
            ];
        }

        if ($verificationResult === 0) {
            return $this->invalid(403, 'Cryptographic proof invalid.');
        }

        return $this->invalid(500, 'Cryptographic verification error.');
    }

    /**
     * Response error standar.
     */
    protected function invalid(int $status, string $message): array
    {
        return [
            'valid' => false,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * Base64URL decode helper.
     */
    protected function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding !== 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) {
            throw new \RuntimeException('Invalid base64url data.');
        }

        return $decoded;
    }

    /**
     * Konversi EC JWK P-256 menjadi PEM SubjectPublicKeyInfo.
     */
    protected function ecJwkToPem(array $jwk): string
    {
        $x = $this->base64UrlDecode($jwk['x']);
        $y = $this->base64UrlDecode($jwk['y']);

        if (strlen($x) !== 32 || strlen($y) !== 32) {
            throw new \RuntimeException('Invalid P-256 coordinate length.');
        }

        $prefix = hex2bin('3059301306072A8648CE3D020106082A8648CE3D030107034200');
        $der = $prefix . "\x04" . $x . $y;

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    /**
     * Konversi signature Web Crypto (r || s) menjadi DER ECDSA SEQUENCE.
     */
    protected function ecdsaRawSignatureToDer(string $rawSignature): string
    {
        if (strlen($rawSignature) !== 64) {
            throw new \RuntimeException('Invalid raw signature length.');
        }

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

            return "\x02" . $this->derLength(strlen($value)) . $value;
        };

        $derR = $encodeInteger($r);
        $derS = $encodeInteger($s);
        $sequence = $derR . $derS;

        return "\x30" . $this->derLength(strlen($sequence)) . $sequence;
    }

    /**
     * Encode panjang data untuk DER.
     */
    protected function derLength(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xff) . $bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)) . $bytes;
    }
}

