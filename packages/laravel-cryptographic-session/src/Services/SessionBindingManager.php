<?php

namespace CryptographicSession\Services;

use CryptographicSession\Contracts\SessionBindingManagerInterface;
use CryptographicSession\Events\SessionBound;
use CryptographicSession\Events\SessionRevoked;
use CryptographicSession\Models\CryptographicSessionBinding;
use InvalidArgumentException;

class SessionBindingManager implements SessionBindingManagerInterface
{
    /**
     * Bind session saat ini dengan user dan browser public key.
     */
    public function bind(
        string $sessionId,
        mixed $userId,
        array $publicKey,
        string $algorithm = 'ECDSA',
        string $curve = 'P-256',
        string $digest = 'SHA-256'
    ): CryptographicSessionBinding {
        if (!$this->isValidPublicKey($publicKey)) {
            throw new InvalidArgumentException('Public key format is invalid for ECDSA P-256.');
        }

        $bindingModel = config('cryptographic-session.models.binding', CryptographicSessionBinding::class);

        // Revoke binding lama pada session ID ini jika ada
        $bindingModel::query()
            ->where('session_id', $sessionId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
            ]);

        $binding = $bindingModel::create([
            'session_id' => $sessionId,
            'user_id' => (string) $userId,
            'public_key' => $publicKey,
            'algorithm' => $algorithm,
            'curve' => $curve,
            'digest' => $digest,
            'revoked_at' => null,
        ]);

        event(new SessionBound($binding));

        return $binding;
    }

    /**
     * Revoke session binding berdasarkan session ID.
     */
    public function revoke(string $sessionId): int
    {
        $bindingModel = config('cryptographic-session.models.binding', CryptographicSessionBinding::class);

        $affected = $bindingModel::query()
            ->where('session_id', $sessionId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
            ]);

        if ($affected > 0) {
            event(new SessionRevoked($sessionId));
        }

        return $affected;
    }

    /**
     * Dapatkan binding aktif untuk session dan user tertentu.
     */
    public function getActiveBinding(string $sessionId, mixed $userId): ?CryptographicSessionBinding
    {
        $bindingModel = config('cryptographic-session.models.binding', CryptographicSessionBinding::class);

        return $bindingModel::query()
            ->where('session_id', $sessionId)
            ->where('user_id', (string) $userId)
            ->whereNull('revoked_at')
            ->first();
    }

    /**
     * Validasi JWK public key ECDSA P-256.
     */
    public function isValidPublicKey(mixed $publicKey): bool
    {
        if (!is_array($publicKey)) {
            return false;
        }

        if (($publicKey['kty'] ?? null) !== 'EC') {
            return false;
        }

        if (($publicKey['crv'] ?? null) !== 'P-256') {
            return false;
        }

        if (!isset($publicKey['x']) || !isset($publicKey['y'])) {
            return false;
        }

        if (!is_string($publicKey['x']) || !is_string($publicKey['y'])) {
            return false;
        }

        try {
            $x = $this->base64UrlDecode($publicKey['x']);
            $y = $this->base64UrlDecode($publicKey['y']);

            return strlen($x) === 32 && strlen($y) === 32;
        } catch (\Throwable) {
            return false;
        }
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
}

