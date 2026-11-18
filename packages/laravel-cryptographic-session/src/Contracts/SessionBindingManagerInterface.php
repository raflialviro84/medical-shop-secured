<?php

namespace CryptographicSession\Contracts;

use CryptographicSession\Models\CryptographicSessionBinding;

interface SessionBindingManagerInterface
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
    ): CryptographicSessionBinding;

    /**
     * Revoke session binding berdasarkan session ID.
     */
    public function revoke(string $sessionId): int;

    /**
     * Dapatkan binding aktif untuk session dan user tertentu.
     */
    public function getActiveBinding(string $sessionId, mixed $userId): ?CryptographicSessionBinding;

    /**
     * Validasi format JWK EC P-256 public key.
     */
    public function isValidPublicKey(mixed $publicKey): bool;
}

