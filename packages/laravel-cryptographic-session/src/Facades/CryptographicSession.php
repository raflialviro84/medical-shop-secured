<?php

namespace CryptographicSession\Facades;

use CryptographicSession\Contracts\SessionBindingManagerInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \CryptographicSession\Models\CryptographicSessionBinding bind(string $sessionId, mixed $userId, array $publicKey, string $algorithm = 'ECDSA', string $curve = 'P-256', string $digest = 'SHA-256')
 * @method static int revoke(string $sessionId)
 * @method static \CryptographicSession\Models\CryptographicSessionBinding|null getActiveBinding(string $sessionId, mixed $userId)
 * @method static bool isValidPublicKey(mixed $publicKey)
 *
 * @see \CryptographicSession\Services\SessionBindingManager
 */
class CryptographicSession extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SessionBindingManagerInterface::class;
    }
}

