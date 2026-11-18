<?php

namespace CryptographicSession\Listeners;

use CryptographicSession\Contracts\SessionBindingManagerInterface;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class BindSessionOnLogin
{
    public function __construct(
        protected SessionBindingManagerInterface $bindingManager
    ) {
    }

    public function handle(Login $event): void
    {
        if (!config('cryptographic-session.auto_bind_on_login', true)) {
            return;
        }

        $request = request();
        if (!$request) {
            return;
        }

        $publicKeyRaw = null;

        // 1. Cek Header HTTP
        $headerName = config('cryptographic-session.public_key_header', 'X-Client-Public-Key');
        if ($request->hasHeader($headerName)) {
            $publicKeyRaw = $request->header($headerName);
        }

        // 2. Cek Request Inputs
        if (!$publicKeyRaw) {
            $inputs = config('cryptographic-session.public_key_inputs', ['publicKey', 'public_key', 'csb_public_key']);
            foreach ($inputs as $inputName) {
                if ($request->filled($inputName)) {
                    $publicKeyRaw = $request->input($inputName);
                    break;
                }
            }
        }

        if (!$publicKeyRaw) {
            return;
        }

        $publicKey = is_array($publicKeyRaw) ? $publicKeyRaw : json_decode($publicKeyRaw, true);
        if (!is_array($publicKey)) {
            return;
        }

        try {
            $sessionId = $request->session()->getId();
            $userId = $event->user->getAuthIdentifier();

            $binding = $this->bindingManager->bind(
                $sessionId,
                $userId,
                $publicKey
            );

            // Simpan info ID ke server session untuk referensi cepat
            $request->session()->put('cryptographic_binding_id', $binding->id);
        } catch (\Throwable $e) {
            Log::warning('[CSB] Gagal mengikat session secara otomatis saat login: ' . $e->getMessage());
        }
    }
}

