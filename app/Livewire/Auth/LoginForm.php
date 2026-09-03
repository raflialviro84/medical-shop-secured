<?php

namespace App\Livewire\Auth;

use App\Models\CryptographicSessionBinding;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LoginForm extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /*
     * Public key ECDSA P-256 yang dikirim
     * oleh browser saat proses login.
     *
     * Isinya berupa JSON JWK.
     */
    public string $publicKey = '';

    public function login()
    {
        /*
         * ==================================================
         * 1. Validasi input login
         * ==================================================
         */
        $validated = $this->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
            ],

            'remember' => [
                'boolean',
            ],

            'publicKey' => [
                'required',
                'string',
            ],
        ]);

        /*
         * ==================================================
         * 2. Decode public key dari browser
         * ==================================================
         */
        try {
            $publicKey = json_decode(
                $validated['publicKey'],
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\Throwable) {
            $this->addError(
                'publicKey',
                'Cryptographic public key tidak valid.'
            );

            return;
        }

        /*
         * ==================================================
         * 3. Validasi struktur public key
         * ==================================================
         *
         * Sistem kita menggunakan:
         *
         * EC
         * P-256
         * X coordinate
         * Y coordinate
         */
        if (!$this->isValidPublicKey($publicKey)) {
            $this->addError(
                'publicKey',
                'Cryptographic public key tidak valid.'
            );

            return;
        }

        /*
         * ==================================================
         * 4. Authentication Laravel
         * ==================================================
         */
        $authenticated = Auth::attempt(
            [
                'email' =>
                    $validated['email'],

                'password' =>
                    $validated['password'],
            ],
            $validated['remember'] ?? false
        );

        /*
         * Kredensial salah.
         */
        if (!$authenticated) {
            $this->addError(
                'email',
                'The provided credentials do not match our records.'
            );

            return;
        }

        /*
         * ==================================================
         * 5. Regenerate Laravel session
         * ==================================================
         *
         * Ini dilakukan SEBELUM cryptographic binding
         * supaya binding menggunakan session ID final.
         */
        request()
            ->session()
            ->regenerate();

        $user = Auth::user();

        /*
         * Pastikan user berhasil didapatkan.
         */
        if (!$user) {
            Auth::logout();

            request()
                ->session()
                ->invalidate();

            request()
                ->session()
                ->regenerateToken();

            $this->addError(
                'email',
                'Authentication failed.'
            );

            return;
        }

        /*
         * ==================================================
         * 6. Hapus binding lama pada session ID ini
         * ==================================================
         *
         * Normalnya session baru belum mempunyai binding.
         *
         * Bagian ini hanya sebagai perlindungan tambahan
         * apabila terdapat data binding yang tertinggal.
         */
        CryptographicSessionBinding::query()
            ->where(
                'session_id',
                request()->session()->getId()
            )
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
            ]);

        /*
         * ==================================================
         * 7. Buat cryptographic session binding
         * ==================================================
         *
         * Binding menghubungkan:
         *
         * session Laravel
         * +
         * user
         * +
         * public key browser
         */
        try {
            $binding =
                CryptographicSessionBinding::create([
                    'session_id' =>
                        request()
                            ->session()
                            ->getId(),

                    'user_id' =>
                        $user->id,

                    'public_key' =>
                        $publicKey,

                    'algorithm' =>
                        'ECDSA',

                    'curve' =>
                        'P-256',

                    'digest' =>
                        'SHA-256',

                    'revoked_at' =>
                        null,
                ]);
        } catch (\Throwable $e) {
            /*
             * ==================================================
             * FAIL CLOSED
             * ==================================================
             *
             * Jangan biarkan user menjadi authenticated
             * jika cryptographic session binding gagal dibuat.
             */
            Auth::logout();

            request()
                ->session()
                ->invalidate();

            request()
                ->session()
                ->regenerateToken();

            report($e);

            $this->addError(
                'email',
                'Cryptographic session binding gagal dibuat.'
            );

            return;
        }

        /*
         * ==================================================
         * 8. Simpan binding ID pada session
         * ==================================================
         *
         * Ini bukan pengganti cryptographic verification.
         * Ini hanya informasi referensi server-side.
         */
        request()
            ->session()
            ->put(
                'cryptographic_binding_id',
                $binding->id
            );

        /*
         * ==================================================
         * 9. Login berhasil
         * ==================================================
         */
        return redirect()->intended(
            route('home')
        );
    }

    /**
     * Validasi JWK public key ECDSA P-256.
     */
    private function isValidPublicKey(
        mixed $publicKey
    ): bool {
        if (!is_array($publicKey)) {
            return false;
        }

        /*
         * Key type harus EC.
         */
        if (
            ($publicKey['kty'] ?? null)
            !== 'EC'
        ) {
            return false;
        }

        /*
         * Curve harus P-256.
         */
        if (
            ($publicKey['crv'] ?? null)
            !== 'P-256'
        ) {
            return false;
        }

        /*
         * X dan Y wajib ada.
         */
        if (
            !isset($publicKey['x']) ||
            !isset($publicKey['y'])
        ) {
            return false;
        }

        if (
            !is_string($publicKey['x']) ||
            !is_string($publicKey['y'])
        ) {
            return false;
        }

        /*
         * Decode Base64URL coordinate.
         */
        try {
            $x = base64_decode(
                strtr(
                    $publicKey['x'],
                    '-_',
                    '+/'
                ) .
                str_repeat(
                    '=',
                    (
                        4 -
                        strlen(
                            $publicKey['x']
                        ) % 4
                    ) % 4
                ),
                true
            );

            $y = base64_decode(
                strtr(
                    $publicKey['y'],
                    '-_',
                    '+/'
                ) .
                str_repeat(
                    '=',
                    (
                        4 -
                        strlen(
                            $publicKey['y']
                        ) % 4
                    ) % 4
                ),
                true
            );

            if (
                $x === false ||
                $y === false
            ) {
                return false;
            }

            /*
             * P-256 mempunyai coordinate
             * X dan Y masing-masing 32 byte.
             */
            if (
                strlen($x) !== 32 ||
                strlen($y) !== 32
            ) {
                return false;
            }

            return true;

        } catch (\Throwable) {
            return false;
        }
    }

    public function render()
    {
        return view(
            'livewire.auth.login-form'
        );
    }
}