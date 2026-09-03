(function () {
    'use strict';

    // =====================================================
    // Configuration
    // =====================================================

    const DB_NAME = 'medical-shop-security';
    const DB_VERSION = 1;
    const STORE_NAME = 'keys';

    const PRIVATE_KEY_NAME = 'session-private-key';
    const PUBLIC_KEY_NAME = 'session-public-key';
    const BINDING_ID_NAME = 'binding-id';

    const STATUS_ENDPOINT =
        '/security/session-binding/status';

    const SERVICE_WORKER_PATH =
        '/csb-service-worker.js';


    // =====================================================
    // IndexedDB
    // =====================================================

    function openDatabase() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(
                DB_NAME,
                DB_VERSION
            );

            request.onupgradeneeded = function (event) {
                const db = event.target.result;

                if (
                    !db.objectStoreNames.contains(
                        STORE_NAME
                    )
                ) {
                    db.createObjectStore(STORE_NAME);
                }
            };

            request.onsuccess = function (event) {
                resolve(event.target.result);
            };

            request.onerror = function () {
                reject(request.error);
            };
        });
    }


    // =====================================================
    // IndexedDB - Save
    // =====================================================

    async function saveKey(name, key) {
        const db = await openDatabase();

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(
                STORE_NAME,
                'readwrite'
            );

            const store =
                transaction.objectStore(STORE_NAME);

            const request = store.put(
                key,
                name
            );

            request.onsuccess = function () {
                resolve();
            };

            request.onerror = function () {
                reject(request.error);
            };
        });
    }


    async function saveValue(name, value) {
        const db = await openDatabase();

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(
                STORE_NAME,
                'readwrite'
            );

            const store =
                transaction.objectStore(STORE_NAME);

            const request = store.put(
                value,
                name
            );

            request.onsuccess = function () {
                resolve();
            };

            request.onerror = function () {
                reject(request.error);
            };
        });
    }


    // =====================================================
    // IndexedDB - Read
    // =====================================================

    async function getValue(name) {
        const db = await openDatabase();

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(
                STORE_NAME,
                'readonly'
            );

            const store =
                transaction.objectStore(STORE_NAME);

            const request = store.get(name);

            request.onsuccess = function () {
                resolve(
                    request.result ?? null
                );
            };

            request.onerror = function () {
                reject(request.error);
            };
        });
    }


    async function getKey(name) {
        return await getValue(name);
    }


    // =====================================================
    // IndexedDB - Delete
    // =====================================================

    async function deleteValue(name) {
        const db = await openDatabase();

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(
                STORE_NAME,
                'readwrite'
            );

            const store =
                transaction.objectStore(STORE_NAME);

            const request = store.delete(name);

            request.onsuccess = function () {
                resolve();
            };

            request.onerror = function () {
                reject(request.error);
            };
        });
    }


    async function clearCryptographicState() {
        await deleteValue(PRIVATE_KEY_NAME);
        await deleteValue(PUBLIC_KEY_NAME);
        await deleteValue(BINDING_ID_NAME);

        console.log(
            '[CSB] Cryptographic session state berhasil dibersihkan.'
        );
    }


    // =====================================================
    // Key Pair Generation
    // =====================================================

    async function generateKeyPair() {
        if (
            !window.crypto ||
            !window.crypto.subtle
        ) {
            throw new Error(
                'Web Crypto API tidak tersedia.'
            );
        }

        const keyPair =
            await window.crypto.subtle.generateKey(
                {
                    name: 'ECDSA',
                    namedCurve: 'P-256'
                },
                false,
                [
                    'sign',
                    'verify'
                ]
            );

        await saveKey(
            PRIVATE_KEY_NAME,
            keyPair.privateKey
        );

        await saveKey(
            PUBLIC_KEY_NAME,
            keyPair.publicKey
        );

        console.log(
            '[CSB] ECDSA P-256 key pair berhasil dibuat.'
        );

        console.log(
            '[CSB] Private key disimpan sebagai non-extractable key:',
            {
                type:
                    keyPair.privateKey.type,

                algorithm:
                    keyPair.privateKey.algorithm,

                extractable:
                    keyPair.privateKey.extractable,

                usages:
                    keyPair.privateKey.usages
            }
        );

        return keyPair;
    }


    // =====================================================
    // Key Access
    // =====================================================

    async function getPrivateKey() {
        return await getKey(
            PRIVATE_KEY_NAME
        );
    }


    async function getPublicKey() {
        return await getKey(
            PUBLIC_KEY_NAME
        );
    }


    // =====================================================
    // Public Key → JWK
    // =====================================================

    async function exportPublicKeyJwk() {
        const publicKey =
            await getPublicKey();

        if (!publicKey) {
            throw new Error(
                'Public key tidak ditemukan.'
            );
        }

        return await window.crypto.subtle.exportKey(
            'jwk',
            publicKey
        );
    }


    async function getPublicKeyJwk() {
        return await exportPublicKeyJwk();
    }


    // =====================================================
    // Binding ID
    // =====================================================

    async function getBindingId() {
        return await getValue(
            BINDING_ID_NAME
        );
    }


    // =====================================================
    // JTI
    // =====================================================

    function generateJti() {
        if (
            window.crypto &&
            typeof window.crypto.randomUUID === 'function'
        ) {
            return window.crypto.randomUUID();
        }

        const bytes =
            new Uint8Array(16);

        window.crypto.getRandomValues(
            bytes
        );

        return Array.from(bytes)
            .map(
                byte =>
                    byte
                        .toString(16)
                        .padStart(2, '0')
            )
            .join('');
    }


    // =====================================================
    // Base64URL
    // =====================================================

    function base64UrlEncode(bytes) {
        let binary = '';

        for (const byte of bytes) {
            binary += String.fromCharCode(byte);
        }

        return btoa(binary)
            .replace(/\+/g, '-')
            .replace(/\//g, '_')
            .replace(/=+$/g, '');
    }


    function stringToBase64Url(value) {
        return base64UrlEncode(
            new TextEncoder().encode(value)
        );
    }


    // =====================================================
    // Login Public Key Preparation
    // =====================================================

    async function prepareLoginPublicKey() {
        const input =
            document.getElementById('publicKey');

        /*
         * Hanya bekerja pada halaman yang
         * memang memiliki field publicKey.
         */
        if (!input) {
            return;
        }

        try {
            let publicKey =
                await getPublicKey();

            let privateKey =
                await getPrivateKey();

            /*
             * Jika belum memiliki key pair,
             * buat key pair baru.
             */
            if (!publicKey || !privateKey) {
                await clearCryptographicState();

                await generateKeyPair();

                publicKey =
                    await getPublicKey();

                privateKey =
                    await getPrivateKey();
            }

            if (!publicKey || !privateKey) {
                throw new Error(
                    'Cryptographic key pair gagal disiapkan.'
                );
            }

            const publicKeyJwk =
                await exportPublicKeyJwk();

            input.value =
                JSON.stringify(publicKeyJwk);

            /*
             * Trigger event supaya Livewire
             * menerima nilai publicKey.
             */
            input.dispatchEvent(
                new Event('input', {
                    bubbles: true
                })
            );

            console.log(
                '[CSB] Public key berhasil disiapkan untuk login.'
            );

        } catch (error) {
            console.error(
                '[CSB] Gagal menyiapkan public key login:',
                error
            );
        }
    }


    // =====================================================
    // Local Signature Test
    // =====================================================

    async function signTestMessage(
        message = 'CSB_TEST_MESSAGE'
    ) {
        const privateKey =
            await getPrivateKey();

        if (!privateKey) {
            throw new Error(
                'Private key tidak ditemukan di IndexedDB.'
            );
        }

        const data =
            new TextEncoder().encode(
                message
            );

        const signature =
            await window.crypto.subtle.sign(
                {
                    name: 'ECDSA',
                    hash: 'SHA-256'
                },
                privateKey,
                data
            );

        return {
            message,
            signature
        };
    }


    async function verifyTestSignature(
        message,
        signature
    ) {
        const publicKey =
            await getPublicKey();

        if (!publicKey) {
            throw new Error(
                'Public key tidak ditemukan di IndexedDB.'
            );
        }

        const data =
            new TextEncoder().encode(
                message
            );

        return await window.crypto.subtle.verify(
            {
                name: 'ECDSA',
                hash: 'SHA-256'
            },
            publicKey,
            signature,
            data
        );
    }


    async function testLocalSignature() {
        const privateKey =
            await getPrivateKey();

        const publicKey =
            await getPublicKey();

        if (!privateKey) {
            throw new Error(
                'Private key tidak ditemukan.'
            );
        }

        if (!publicKey) {
            throw new Error(
                'Public key tidak ditemukan.'
            );
        }

        const message =
            'CSB_LOCAL_TEST';

        const data =
            new TextEncoder().encode(
                message
            );

        const signature =
            await window.crypto.subtle.sign(
                {
                    name: 'ECDSA',
                    hash: 'SHA-256'
                },
                privateKey,
                data
            );

        return await window.crypto.subtle.verify(
            {
                name: 'ECDSA',
                hash: 'SHA-256'
            },
            publicKey,
            signature,
            data
        );
    }


    // =====================================================
    // Cryptographic Proof
    // =====================================================

    async function createCryptographicProof(
        method,
        url
    ) {
        const privateKey =
            await getPrivateKey();

        const bindingId =
            await getBindingId();

        const publicKeyJwk =
            await getPublicKeyJwk();

        if (!privateKey) {
            throw new Error(
                'Private key tidak ditemukan.'
            );
        }

        if (!bindingId) {
            throw new Error(
                'Binding ID tidak ditemukan.'
            );
        }

        const normalizedMethod =
            String(method).toUpperCase();

        const normalizedUrl =
            new URL(
                url,
                window.location.origin
            );

        /*
         * Proof hanya boleh dibuat
         * untuk origin aplikasi sendiri.
         */
        if (
            normalizedUrl.origin !==
            window.location.origin
        ) {
            throw new Error(
                'Request harus berasal dari origin aplikasi sendiri.'
            );
        }

        const header = {
            typ: 'csb+jwt',
            alg: 'ES256',
            jwk: publicKeyJwk
        };

        const payload = {
            jti: generateJti(),

            iat: Math.floor(
                Date.now() / 1000
            ),

            htm:
                normalizedMethod,

            htu:
                normalizedUrl.pathname,

            binding_id:
                bindingId
        };

        const encodedHeader =
            stringToBase64Url(
                JSON.stringify(header)
            );

        const encodedPayload =
            stringToBase64Url(
                JSON.stringify(payload)
            );

        const signingInput =
            `${encodedHeader}.${encodedPayload}`;

        const signature =
            await window.crypto.subtle.sign(
                {
                    name: 'ECDSA',
                    hash: 'SHA-256'
                },
                privateKey,
                new TextEncoder().encode(
                    signingInput
                )
            );

        const encodedSignature =
            base64UrlEncode(
                new Uint8Array(signature)
            );

        return {
            header,
            payload,
            proof:
                `${signingInput}.${encodedSignature}`
        };
    }


    // =====================================================
    // Send Cryptographic Proof
    // =====================================================

    async function sendCryptographicProof() {
        const result =
            await createCryptographicProof(
                'POST',
                '/security/session-proof'
            );

        const csrfToken =
            document
                .querySelector(
                    'meta[name="csrf-token"]'
                )
                ?.getAttribute('content');

        if (!csrfToken) {
            throw new Error(
                'CSRF token tidak ditemukan.'
            );
        }

        const response =
            await fetch(
                '/security/session-proof',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/json',

                        'Accept':
                            'application/json',

                        'X-CSRF-TOKEN':
                            csrfToken,

                        'DPoP':
                            result.proof
                    },

                    credentials:
                        'same-origin',

                    body: JSON.stringify({
                        proof:
                            result.proof
                    })
                }
            );

        let data;

        try {
            data =
                await response.json();
        } catch {
            throw new Error(
                'Response server bukan JSON.'
            );
        }

        if (!response.ok) {
            throw new Error(
                data.message ||
                'Cryptographic proof ditolak.'
            );
        }

        console.log(
            '[CSB] Server verification response:',
            data
        );

        return data;
    }


    // =====================================================
    // Generic Request With Cryptographic Proof
    // =====================================================

    async function requestWithCryptographicProof(
        url,
        options = {}
    ) {
        const method = (
            options.method || 'GET'
        ).toUpperCase();

        const absoluteUrl =
            new URL(
                url,
                window.location.origin
            );

        /*
         * Hanya izinkan origin aplikasi sendiri.
         */
        if (
            absoluteUrl.origin !==
            window.location.origin
        ) {
            throw new Error(
                'Request harus berasal dari origin aplikasi sendiri.'
            );
        }

        const proofResult =
            await createCryptographicProof(
                method,
                absoluteUrl.href
            );

        const headers =
            new Headers(
                options.headers || {}
            );

        headers.set(
            'DPoP',
            proofResult.proof
        );

        /*
         * Laravel CSRF protection.
         */
        if (
            [
                'POST',
                'PUT',
                'PATCH',
                'DELETE'
            ].includes(method)
        ) {
            const csrfToken =
                document
                    .querySelector(
                        'meta[name="csrf-token"]'
                    )
                    ?.getAttribute('content');

            if (!csrfToken) {
                throw new Error(
                    'CSRF token tidak ditemukan.'
                );
            }

            headers.set(
                'X-CSRF-TOKEN',
                csrfToken
            );
        }

        return fetch(
            absoluteUrl.pathname +
            absoluteUrl.search,
            {
                ...options,

                method,

                headers,

                credentials:
                    options.credentials ||
                    'same-origin'
            }
        );
    }


    // =====================================================
    // Current Binding Status
    // =====================================================

    async function getCurrentBindingStatus() {
        const response = await fetch(
            STATUS_ENDPOINT,
            {
                method: 'GET',

                credentials:
                    'same-origin',

                headers: {
                    'Accept':
                        'application/json'
                }
            }
        );

        if (!response.ok) {
            throw new Error(
                'Gagal memeriksa status cryptographic session binding.'
            );
        }

        return await response.json();
    }


    // =====================================================
    // Initialize Cryptographic Session
    // =====================================================

    async function initializeCryptographicSession() {
        try {
            const status =
                await getCurrentBindingStatus();


            // -------------------------------------------------
            // Guest
            // -------------------------------------------------

            if (!status.authenticated) {
                return null;
            }


            // -------------------------------------------------
            // Authenticated tetapi belum memiliki binding
            // -------------------------------------------------
            //
            // Binding dibuat oleh LoginForm setelah
            // Auth::attempt() berhasil.
            //

            if (!status.bound) {
                console.error(
                    '[CSB] Session authenticated tetapi belum memiliki binding.'
                );

                return {
                    authenticated: true,
                    bound: false,
                    keyMismatch: true
                };
            }


            // -------------------------------------------------
            // Ambil state lokal
            // -------------------------------------------------

            const localBindingId =
                await getBindingId();

            const privateKey =
                await getPrivateKey();

            const publicKey =
                await getPublicKey();


            // -------------------------------------------------
            // CASE 1
            //
            // Login berhasil.
            // Server sudah mempunyai binding.
            // Browser mempunyai key pair.
            // Binding ID belum tersimpan lokal.
            // -------------------------------------------------

            if (
                privateKey &&
                publicKey &&
                (
                    localBindingId === null ||
                    localBindingId === undefined
                )
            ) {
                await saveValue(
                    BINDING_ID_NAME,
                    status.binding_id
                );

                console.log(
                    '[CSB] Binding ID dari server berhasil disimpan ke IndexedDB:',
                    status.binding_id
                );

                return {
                    authenticated: true,
                    bound: true,
                    binding_id:
                        status.binding_id,
                    reused: false
                };
            }


            // -------------------------------------------------
            // CASE 2
            //
            // Binding lokal cocok dengan server.
            // -------------------------------------------------

            if (
                privateKey &&
                publicKey &&
                localBindingId !== null &&
                Number(localBindingId) ===
                    Number(status.binding_id)
            ) {
                console.log(
                    '[CSB] Cryptographic session binding aktif:',
                    status.binding_id
                );

                return {
                    authenticated: true,
                    bound: true,
                    binding_id:
                        status.binding_id,
                    reused: true
                };
            }


            // -------------------------------------------------
            // CASE 3
            //
            // Binding server ada,
            // tetapi key/state lokal tidak cocok.
            //
            // Fail closed.
            // -------------------------------------------------

            console.error(
                '[CSB] Local cryptographic state tidak sesuai dengan server binding.',
                {
                    serverBindingId:
                        status.binding_id,

                    localBindingId,

                    hasPrivateKey:
                        !!privateKey,

                    hasPublicKey:
                        !!publicKey
                }
            );

            return {
                authenticated: true,
                bound: true,
                binding_id:
                    status.binding_id,
                reused: false,
                keyMismatch: true
            };

        } catch (error) {

            console.error(
                '[CSB] Initialization gagal:',
                error
            );

            return null;
        }
    }


    // =====================================================
    // Register Service Worker
    // =====================================================

    async function registerCryptographicServiceWorker() {

        /*
         * Browser harus mendukung Service Worker.
         */
        if (
            !('serviceWorker' in navigator)
        ) {
            console.error(
                '[CSB] Service Worker tidak didukung oleh browser.'
            );

            return false;
        }


        try {

            const registration =
                await navigator.serviceWorker.register(
                    SERVICE_WORKER_PATH,
                    {
                        scope: '/',

                        /*
                         * Jangan menggunakan cache lama
                         * saat melakukan update Service Worker.
                         */
                        updateViaCache: 'none'
                    }
                );

            console.log(
                '[CSB] Service Worker berhasil didaftarkan.',
                {
                    scope:
                        registration.scope
                }
            );


            /*
             * Tunggu sampai Service Worker
             * mempunyai status ready.
             */
            await navigator.serviceWorker.ready;

            console.log(
                '[CSB] Service Worker sudah aktif.'
            );

            return true;

        } catch (error) {

            console.error(
                '[CSB] Gagal mendaftarkan Service Worker:',
                error
            );

            return false;
        }
    }


    // =====================================================
    // Expose Public API
    // =====================================================

    window.CryptographicSessionBinding = {

        generateKeyPair,

        getPrivateKey,

        getPublicKey,

        exportPublicKeyJwk,

        getPublicKeyJwk,

        getBindingId,

        signTestMessage,

        verifyTestSignature,

        testLocalSignature,

        createCryptographicProof,

        sendCryptographicProof,

        createRequestProof:
            createCryptographicProof,

        requestWithCryptographicProof,

        deleteValue,

        clearCryptographicState,

        getCurrentBindingStatus,

        initializeCryptographicSession,

        prepareLoginPublicKey,

        registerCryptographicServiceWorker
    };


    // =====================================================
    // Automatic Initialization
    // =====================================================

    document.addEventListener(
        'DOMContentLoaded',
        async () => {

            /*
             * 1. Register Service Worker.
             */
            await registerCryptographicServiceWorker();


            /*
             * 2. Persiapkan public key untuk
             *    halaman login.
             */
            await prepareLoginPublicKey();


            /*
             * 3. Periksa status cryptographic
             *    session binding.
             */
            await initializeCryptographicSession();
        }
    );


    // =====================================================
    // Automatic Logout Cleanup
    // =====================================================

    document.addEventListener(
        'submit',
        async (event) => {

            const form =
                event.target;

            if (
                !(form instanceof HTMLFormElement)
            ) {
                return;
            }

            const action =
                form.getAttribute('action');

            if (!action) {
                return;
            }

            const url =
                new URL(
                    action,
                    window.location.origin
                );

            if (
                url.pathname !== '/logout'
            ) {
                return;
            }


            /*
             * Jangan kirim state cryptographic
             * lama setelah logout.
             */
            event.preventDefault();

            try {

                await clearCryptographicState();

                console.log(
                    '[CSB] Cryptographic state dibersihkan sebelum logout.'
                );

            } catch (error) {

                console.error(
                    '[CSB] Gagal membersihkan cryptographic state:',
                    error
                );
            }


            /*
             * Submit ulang setelah state lokal
             * selesai dibersihkan.
             */
            form.submit();
        }
    );

})();