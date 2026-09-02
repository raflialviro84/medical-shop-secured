<?php

namespace App\Security\CryptographicSessionBinding;

use App\Models\CryptographicProofReplay;
use App\Models\CryptographicSessionBinding;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProofVerifier
{
    /**
     * Verifikasi cryptographic proof terhadap request Laravel saat ini.
     *
     * Untuk request biasa:
     *
     *     verify($request, $proof)
     *
     * maka method dan URI proof akan dibandingkan dengan request aktual.
     *
     * Untuk kebutuhan seperti navigation grant:
     *
     *     verify(
     *         $request,
     *         $proof,
     *         'GET',
     *         '/transactions'
     *     )
     *
     * maka method dan URI proof akan dibandingkan dengan nilai
     * expectedMethod dan expectedUri.
     *
     * Return sukses:
     *
     * [
     *     'valid' => true,
     *     'binding_id' => ...
     * ]
     *
     * Return gagal:
     *
     * [
     *     'valid' => false,
     *     'status' => 403,
     *     'message' => '...'
     * ]
     */
    public function verify(
        Request $request,
        string $proof,
        ?string $expectedMethod = null,
        ?string $expectedUri = null
    ): array {
        /*
         * 1. Pecah proof menjadi:
         *    header.payload.signature
         */
        $parts = explode('.', $proof);

        if (count($parts) !== 3) {
            return $this->invalid(
                400,
                'Invalid proof format.'
            );
        }

        [
            $encodedHeader,
            $encodedPayload,
            $encodedSignature
        ] = $parts;

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

            $signature = $this->base64UrlDecode(
                $encodedSignature
            );
        } catch (\Throwable) {
            return $this->invalid(
                400,
                'Invalid proof encoding.'
            );
        }

        /*
         * Pastikan header dan payload benar-benar object JSON
         * yang berubah menjadi associative array.
         */
        if (!is_array($header) || !is_array($payload)) {
            return $this->invalid(
                400,
                'Invalid proof JSON structure.'
            );
        }

        /*
         * 2. Validasi header
         */
        if (($header['typ'] ?? null) !== 'csb+jwt') {
            return $this->invalid(
                400,
                'Invalid proof type.'
            );
        }

        if (($header['alg'] ?? null) !== 'ES256') {
            return $this->invalid(
                400,
                'Invalid proof algorithm.'
            );
        }

        if (
            !isset($header['jwk']) ||
            !is_array($header['jwk'])
        ) {
            return $this->invalid(
                400,
                'Proof public key is missing.'
            );
        }

        /*
         * 3. Validasi payload
         */
        $requiredFields = [
            'jti',
            'iat',
            'htm',
            'htu',
            'binding_id',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $payload)) {
                return $this->invalid(
                    400,
                    "Missing proof field: {$field}."
                );
            }
        }

        if (
            !is_string($payload['jti']) ||
            $payload['jti'] === ''
        ) {
            return $this->invalid(
                400,
                'Invalid jti.'
            );
        }

        if (!is_numeric($payload['iat'])) {
            return $this->invalid(
                400,
                'Invalid iat.'
            );
        }

        if (
            !is_string($payload['htm']) ||
            $payload['htm'] === ''
        ) {
            return $this->invalid(
                400,
                'Invalid htm.'
            );
        }

        if (
            !is_string($payload['htu']) ||
            $payload['htu'] === ''
        ) {
            return $this->invalid(
                400,
                'Invalid htu.'
            );
        }

        /*
         * 4. Validasi tipe dan parameter EC
         */
        if (($header['jwk']['kty'] ?? null) !== 'EC') {
            return $this->invalid(
                400,
                'Invalid public key type.'
            );
        }

        if (($header['jwk']['crv'] ?? null) !== 'P-256') {
            return $this->invalid(
                400,
                'Invalid public key curve.'
            );
        }

        if (
            !isset($header['jwk']['x']) ||
            !isset($header['jwk']['y'])
        ) {
            return $this->invalid(
                400,
                'Invalid EC public key.'
            );
        }

        if (
            !is_string($header['jwk']['x']) ||
            !is_string($header['jwk']['y'])
        ) {
            return $this->invalid(
                400,
                'Invalid EC public key coordinates.'
            );
        }

        /*
         * 5. Cari binding berdasarkan:
         *
         * binding_id
         * current session
         * current authenticated user
         *
         * binding_id yang digunakan client adalah ID database
         * CryptographicSessionBinding.
         */
        $binding = CryptographicSessionBinding::query()
            ->where('id', $payload['binding_id'])
            ->where(
                'session_id',
                $request->session()->getId()
            )
            ->where(
                'user_id',
                $request->user()->id
            )
            ->whereNull('revoked_at')
            ->first();

        if (!$binding) {
            return $this->invalid(
                403,
                'Cryptographic session binding tidak valid.'
            );
        }

        /*
         * 6. Pastikan public key di proof
         *    sama dengan public key yang dibind
         */
        $boundPublicKey = $binding->public_key;
        $proofPublicKey = $header['jwk'];

        if (!is_array($boundPublicKey)) {
            return $this->invalid(
                500,
                'Stored public key is invalid.'
            );
        }

        $sameKey =
            ($boundPublicKey['kty'] ?? null)
                === ($proofPublicKey['kty'] ?? null)
            &&
            ($boundPublicKey['crv'] ?? null)
                === ($proofPublicKey['crv'] ?? null)
            &&
            ($boundPublicKey['x'] ?? null)
                === ($proofPublicKey['x'] ?? null)
            &&
            ($boundPublicKey['y'] ?? null)
                === ($proofPublicKey['y'] ?? null);

        if (!$sameKey) {
            return $this->invalid(
                403,
                'Proof public key tidak sesuai dengan session binding.'
            );
        }

        /*
         * 7. Bind proof terhadap HTTP request.
         *
         * Mode normal:
         *   expectedMethod = null
         *   expectedUri    = null
         *
         * Maka gunakan request aktual.
         *
         * Mode navigation grant:
         *   expectedMethod = 'GET'
         *   expectedUri    = '/transactions'
         *
         * Maka proof wajib memang diterbitkan untuk
         * GET /transactions.
         */
        $expectedMethod = strtoupper(
            $expectedMethod ?? $request->method()
        );

        $expectedUri =
            $expectedUri ?? $request->getPathInfo();

        $proofMethod = strtoupper(
            (string) $payload['htm']
        );

        $proofUri = (string) $payload['htu'];

        if ($proofMethod !== $expectedMethod) {
            return $this->invalid(
                403,
                'Invalid HTTP method binding.'
            );
        }

        if ($proofUri !== $expectedUri) {
            return $this->invalid(
                403,
                'Invalid HTTP URI binding.'
            );
        }

        /*
         * 8. Freshness / timestamp
         *
         * Proof hanya valid dalam window ±60 detik.
         */
        $currentTime = time();
        $issuedAt = (int) $payload['iat'];

        $allowedClockSkew = 60;

        if (
            $issuedAt < ($currentTime - $allowedClockSkew)
            ||
            $issuedAt > ($currentTime + $allowedClockSkew)
        ) {
            return $this->invalid(
                403,
                'Proof timestamp is outside the allowed window.'
            );
        }

        /*
         * 9. Signature harus tepat 64 byte:
         *
         * r = 32 byte
         * s = 32 byte
         *
         * Web Crypto ECDSA menghasilkan signature
         * dalam bentuk raw r || s.
         */
        if (strlen($signature) !== 64) {
            return $this->invalid(
                400,
                'Invalid ECDSA P-256 signature length.'
            );
        }

        /*
         * 10. JWK → PEM
         */
        try {
            $publicKeyPem = $this->ecJwkToPem(
                $boundPublicKey
            );
        } catch (\Throwable) {
            return $this->invalid(
                400,
                'Invalid EC public key.'
            );
        }

        $publicKey = openssl_pkey_get_public(
            $publicKeyPem
        );

        if ($publicKey === false) {
            return $this->invalid(
                500,
                'Unable to load public key.'
            );
        }

        /*
         * 11. Reconstruct exact signing input
         *
         * Signature dibuat terhadap:
         *
         * base64url(header) . "." . base64url(payload)
         */
        $signingInput =
            $encodedHeader . '.' . $encodedPayload;

        /*
         * 12. Web Crypto raw signature
         *     r || s
         *
         * →
         *
         * DER ECDSA signature
         */
        try {
            $derSignature =
                $this->ecdsaRawSignatureToDer(
                    $signature
                );
        } catch (\Throwable) {
            return $this->invalid(
                400,
                'Unable to process ECDSA signature.'
            );
        }

        /*
         * 13. Verify ECDSA signature
         */
        $verificationResult = openssl_verify(
            $signingInput,
            $derSignature,
            $publicKey,
            OPENSSL_ALGO_SHA256
        );

        if ($verificationResult === 1) {
            /*
             * 14. Replay protection
             *
             * JTI hanya boleh digunakan satu kali.
             */
            $existingReplay = CryptographicProofReplay::query()
                ->where('jti', $payload['jti'])
                ->first();

            if ($existingReplay) {
                return $this->invalid(
                    403,
                    'Proof sudah pernah digunakan.'
                );
            }

            /*
             * Simpan JTI setelah signature valid.
             *
             * Unique constraint pada kolom jti di database
             * menjadi perlindungan tambahan jika ada dua request
             * concurrent yang membawa proof dengan JTI sama.
             */
            try {
                CryptographicProofReplay::create([
                    'jti' => $payload['jti'],
                    'binding_id' => $binding->id,
                    'issued_at' => Carbon::createFromTimestamp(
                        $issuedAt
                    ),
                    'expires_at' => Carbon::createFromTimestamp(
                        $issuedAt
                    )->addSeconds($allowedClockSkew),
                ]);
            } catch (\Throwable) {
                return $this->invalid(
                    403,
                    'Proof sudah pernah digunakan.'
                );
            }

            return [
                'valid' => true,
                'binding_id' => $binding->id,
            ];
        }

        if ($verificationResult === 0) {
            return $this->invalid(
                403,
                'Cryptographic proof invalid.'
            );
        }

        return $this->invalid(
            500,
            'Cryptographic verification error.'
        );
    }

    /**
     * Membuat response error standar.
     */
    private function invalid(
        int $status,
        string $message
    ): array {
        return [
            'valid' => false,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * Base64URL decode tanpa padding wajib.
     */
    private function base64UrlDecode(
        string $value
    ): string {
        $padding = strlen($value) % 4;

        if ($padding !== 0) {
            $value .= str_repeat(
                '=',
                4 - $padding
            );
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

    /**
     * Konversi EC JWK P-256 menjadi PEM SubjectPublicKeyInfo.
     */
    private function ecJwkToPem(array $jwk): string
    {
        $x = $this->base64UrlDecode(
            $jwk['x']
        );

        $y = $this->base64UrlDecode(
            $jwk['y']
        );

        if (
            strlen($x) !== 32 ||
            strlen($y) !== 32
        ) {
            throw new \RuntimeException(
                'Invalid P-256 coordinate length.'
            );
        }

        /*
         * SubjectPublicKeyInfo:
         *
         * id-ecPublicKey
         * prime256v1 / P-256
         */
        $prefix = hex2bin(
            '3059301306072A8648CE3D020106082A8648CE3D030107034200'
        );

        /*
         * EC uncompressed point:
         *
         * 04 || X || Y
         */
        $der = $prefix
            . "\x04"
            . $x
            . $y;

        return
            "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(
                base64_encode($der),
                64,
                "\n"
            )
            . "-----END PUBLIC KEY-----\n";
    }

    /**
     * Konversi signature Web Crypto:
     *
     *     r || s
     *
     * menjadi DER ECDSA:
     *
     *     SEQUENCE {
     *         INTEGER r
     *         INTEGER s
     *     }
     */
    private function ecdsaRawSignatureToDer(
        string $rawSignature
    ): string {
        if (strlen($rawSignature) !== 64) {
            throw new \RuntimeException(
                'Invalid raw signature length.'
            );
        }

        /*
         * 32 byte pertama = r
         */
        $r = substr(
            $rawSignature,
            0,
            32
        );

        /*
         * 32 byte berikutnya = s
         */
        $s = substr(
            $rawSignature,
            32,
            32
        );

        $encodeInteger = function (
            string $value
        ): string {
            /*
             * Buang leading zero.
             */
            $value = ltrim(
                $value,
                "\x00"
            );

            /*
             * INTEGER kosong dianggap 0.
             */
            if ($value === '') {
                $value = "\x00";
            }

            /*
             * DER INTEGER bersifat signed.
             *
             * Jika bit tertinggi 1,
             * tambahkan 00 agar tetap positif.
             */
            if (
                (ord($value[0]) & 0x80) !== 0
            ) {
                $value = "\x00" . $value;
            }

            return "\x02"
                . $this->derLength(
                    strlen($value)
                )
                . $value;
        };

        $derR = $encodeInteger($r);
        $derS = $encodeInteger($s);

        $sequence = $derR . $derS;

        return "\x30"
            . $this->derLength(
                strlen($sequence)
            )
            . $sequence;
    }

    /**
     * Encode panjang data untuk DER.
     */
    private function derLength(
        int $length
    ): string {
        if ($length < 128) {
            return chr($length);
        }

        $bytes = '';

        while ($length > 0) {
            $bytes =
                chr($length & 0xff) . $bytes;

            $length >>= 8;
        }

        return chr(
            0x80 | strlen($bytes)
        ) . $bytes;
    }
}