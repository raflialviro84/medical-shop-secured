<?php

namespace App\Http\Middleware;

use CryptographicSession\Http\Middleware\VerifyCryptographicSession as BaseVerifyCryptographicSession;

/**
 * Backward compatibility alias for the modular VerifyCryptographicSession middleware.
 */
class VerifyCryptographicSession extends BaseVerifyCryptographicSession
{
}
