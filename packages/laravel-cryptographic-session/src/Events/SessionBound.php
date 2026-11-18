<?php

namespace CryptographicSession\Events;

use CryptographicSession\Models\CryptographicSessionBinding;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionBound
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CryptographicSessionBinding $binding
    ) {
    }
}

