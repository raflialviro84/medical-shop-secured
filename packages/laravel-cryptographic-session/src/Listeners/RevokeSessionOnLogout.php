<?php

namespace CryptographicSession\Listeners;

use CryptographicSession\Contracts\SessionBindingManagerInterface;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;

class RevokeSessionOnLogout
{
    public function __construct(
        protected SessionBindingManagerInterface $bindingManager
    ) {
    }

    public function handle(Logout $event): void
    {
        if (!config('cryptographic-session.auto_revoke_on_logout', true)) {
            return;
        }

        $request = request();
        if (!$request) {
            return;
        }

        try {
            $sessionId = $request->session()->getId();
            $this->bindingManager->revoke($sessionId);
        } catch (\Throwable $e) {
            Log::warning('[CSB] Gagal mencabut session binding saat logout: ' . $e->getMessage());
        }
    }
}

