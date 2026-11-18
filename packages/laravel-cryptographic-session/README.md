# Laravel Cryptographic Session Binding (CSB)

Package Laravel modular untuk mengamankan session web dari serangan **Session Hijacking** menggunakan mekanisme **Cryptographic Session Binding (DPoP / Web Crypto ECDSA P-256)**.

---

## 🚀 Fitur Utama

- **ECDSA P-256 Cryptographic Binding**: Setiap sesi web diikat secara kriptografis dengan pasangan kunci asimetris yang dihasilkan di browser klien melalui Web Crypto API.
- **Hardware-Isolated / IndexedDB Private Key**: Private key tersimpan di browser klien dan tidak pernah dikirim ke server. Penyerang yang mencuri session cookie (via XSS atau sniffing) tidak dapat menggunakan sesi tersebut tanpa kunci kriptografi.
- **DPoP HTTP Request Binding**: Memverifikasi Method HTTP, URI, timestamp window (anti-clock-skew), dan signature digital pada setiap permintaan yang dilindungi.
- **Replay Protection**: Token JTI dicatat dalam database untuk mencegah *replay attack*.
- **Otomatisasi Lifecycle Auth**:
  - Otomatis membuat binding saat event `Illuminate\Auth\Events\Login` terpicu.
  - Otomatis mencabut (revoke) binding saat event `Illuminate\Auth\Events\Logout` terpicu.
- **Plug-and-Play**: Cukup pasang directive Blade `@cryptographicSession` dan beri middleware `cryptographic.session` pada route yang ingin diamankan.

---

## 📦 Instalasi di Proyek Baru

### 1. Install via Composer

Tambahkan dependensi paket ke `composer.json` proyek Anda:

```bash
composer require csb/laravel-cryptographic-session
```

*(Jika menggunakan repositori lokal / path repository:)*
```json
"repositories": [
    {
        "type": "path",
        "url": "packages/laravel-cryptographic-session"
    }
]
```

### 2. Jalankan Perintah Instalasi

```bash
php artisan csb:install
```

Perintah di atas akan secara otomatis:
1. Mempublikasikan file konfigurasi `config/cryptographic-session.php`.
2. Mempublikasikan aset frontend ke `public/vendor/cryptographic-session/`.
3. Menjalankan migrasi tabel `cryptographic_session_bindings` dan `cryptographic_proof_replays`.

---

## 🛠️ Konfigurasi (`config/cryptographic-session.php`)

Anda dapat menyesuaikan konfigurasi berikut:

```php
return [
    // Header proof yang diharapkan dari client (default: DPoP)
    'header_name' => env('CSB_HEADER_NAME', 'DPoP'),

    // Toleransi perbedaan waktu client-server dalam detik (default: 60)
    'clock_skew_seconds' => env('CSB_CLOCK_SKEW', 60),

    // Algoritma ECDSA
    'algorithm' => 'ES256',
    'curve' => 'P-256',

    // Otomatisasi binding & revocation pada lifecycle login/logout
    'auto_bind_on_login' => true,
    'auto_revoke_on_logout' => true,

    // Rute yang dikecualikan dari verifikasi proof
    'excluded_routes' => [
        'login',
        'register',
        'logout',
        'security/session-binding/*',
        'security/session-proof',
    ],
];
```

---

## 💻 Cara Penggunaan

### 1. Pasang Script di Layout Utama (`layouts/app.blade.php`)

Tambahkan directive Blade di dalam tag `<head>` layout aplikasi Anda:

```blade
<head>
    <!-- Meta & Styles lainnya -->
    
    <!-- Cryptographic Session Binding Script -->
    @cryptographicSession
</head>
```

### 2. Lindungi Route dengan Middleware

Gunakan middleware alias `cryptographic.session`:

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cryptographic.session'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::post('/transfer', [WalletController::class, 'transfer']);
});
```

### 3. Integrasi Form Login

Script client secara otomatis mendeteksi form login. Jika Anda menggunakan Blade form konvensional atau Livewire, sediakan input hidden untuk public key:

```blade
<form method="POST" action="{{ route('login') }}">
    @csrf
    
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    
    <!-- Input ini akan diisi otomatis oleh script CSB -->
    <input type="hidden" id="publicKey" name="publicKey">
    
    <button type="submit">Login</button>
</form>
```

Atau jika menggunakan API/Fetch:
Kirimkan header `X-Client-Public-Key` berisi JSON JWK dari `CryptographicSession.exportPublicKeyJwk()`.

---

## 🧪 Testing Endpoint

Paket ini menyediakan endpoint bawaan:
- `GET /security/session-binding/status`: Memeriksa apakah sesi saat ini terikat secara kriptografis.
- `POST /security/session-proof`: Endpoint verifikasi manual proof signature.

---

## 📄 Lisensi

MIT License.

