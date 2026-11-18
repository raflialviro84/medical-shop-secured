<?php

namespace CryptographicSession\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionRevoked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $sessionId
    ) {
    }
}

