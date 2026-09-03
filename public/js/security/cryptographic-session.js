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

    const BINDING_ENDPOINT =
        '/security/session-binding';


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
            '[CSB] Public Key:',
            {
                type: keyPair.publicKey.type,
                algorithm:
                    keyPair.publicKey.algorithm,
                extractable:
                    keyPair.publicKey.extractable,
                usages:
                    keyPair.publicKey.usages
            }
        );

        console.log(
            '[CSB] Private Key:',
            {
                type: keyPair.privateKey.type,
                algorithm:
                    keyPair.privateKey.algorithm,
                extractable:
                    keyPair.privateKey.extractable,
                usages:
                    keyPair.privateKey.usages
            }
        );

        console.log(
            '[CSB] Key pair berhasil disimpan ke IndexedDB.'
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
            binary += String.fromCharCode(
                byte
            );
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
    // Register Public Key
    // =====================================================

    async function registerPublicKey() {
        const publicKey =
            await exportPublicKeyJwk();

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

        const response = await fetch(
            BINDING_ENDPOINT,
            {
                method: 'POST',

                headers: {
                    'Content-Type':
                        'application/json',

                    'Accept':
                        'application/json',

                    'X-CSRF-TOKEN':
                        csrfToken
                },

                credentials: 'same-origin',

                body: JSON.stringify({
                    public_key: publicKey
                })
            }
        );

        let data;

        try {
            data = await response.json();
        } catch (error) {
            throw new Error(
                'Response register public key bukan JSON.'
            );
        }

        if (!response.ok) {
            throw new Error(
                data.message ||
                'Gagal mendaftarkan public key.'
            );
        }

        await saveValue(
            BINDING_ID_NAME,
            data.binding_id
        );

        console.log(
            '[CSB] Public key berhasil di-bind.',
            data
        );

        console.log(
            '[CSB] Binding ID disimpan:',
            data.binding_id
        );

        return data;
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

        console.log(
            '[CSB] Message berhasil ditandatangani.'
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

        const valid =
            await window.crypto.subtle.verify(
                {
                    name: 'ECDSA',
                    hash: 'SHA-256'
                },
                publicKey,
                signature,
                data
            );

        console.log(
            '[CSB] Signature verification:',
            valid
        );

        return valid;
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

        const valid =
            await window.crypto.subtle.verify(
                {
                    name: 'ECDSA',
                    hash: 'SHA-256'
                },
                publicKey,
                signature,
                data
            );

        console.log(
            '[CSB] Local key pair verification:',
            valid
        );

        return valid;
    }


    // =====================================================
    // Cryptographic Proof
    // =====================================================

    async function createCryptographicProof() {
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
            htm: 'POST',
            htu: '/security/session-proof',
            binding_id: bindingId
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

        const proof =
            `${signingInput}.${encodedSignature}`;

        console.log(
            '[CSB] Cryptographic proof berhasil dibuat.'
        );

        return {
            header,
            payload,
            proof
        };
    }


    // =====================================================
    // Send Cryptographic Proof
    // =====================================================

    async function sendCryptographicProof() {
        const result =
            await createCryptographicProof();

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
                            csrfToken
                    },

                    credentials:
                        'same-origin',

                    body: JSON.stringify({
                        proof: result.proof
                    })
                }
            );

        let data;

        try {
            data =
                await response.json();
        } catch (error) {
            throw new Error(
                'Response server bukan JSON.'
            );
        }

        console.log(
            '[CSB] Server verification response:',
            data
        );

        if (!response.ok) {
            throw new Error(
                data.message ||
                'Cryptographic proof ditolak.'
            );
        }

        return data;
    }


    // =====================================================
    // Generic Request Proof
    // =====================================================

    async function createRequestProof(
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
            ).pathname;

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

            htm: normalizedMethod,

            htu: normalizedUrl,

            binding_id: bindingId
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
    // Request With Cryptographic Proof
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

        const requestUrl =
            absoluteUrl.pathname;

        const proofResult =
            await createRequestProof(
                method,
                requestUrl
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
         *
         * POST, PUT, PATCH, DELETE
         * membutuhkan X-CSRF-TOKEN.
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
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
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

            /*
             * -------------------------------------------------
             * Guest
             * -------------------------------------------------
             */
            if (
                !status.authenticated
            ) {
                return null;
            }


            /*
             * -------------------------------------------------
             * CASE 1
             *
             * Session authenticated,
             * tetapi server belum memiliki binding.
             *
             * Ini biasanya terjadi setelah:
             * - login baru
             * - session baru
             * - session rotation
             * -------------------------------------------------
             */
            if (!status.bound) {

                console.log(
                    '[CSB] Session belum memiliki binding. Membuat binding baru...'
                );

                /*
                 * State browser dari session sebelumnya
                 * harus dibersihkan.
                 */
                await clearCryptographicState();

                /*
                 * Generate key pair baru.
                 */
                await generateKeyPair();

                /*
                 * Register public key baru.
                 */
                const result =
                    await registerPublicKey();

                console.log(
                    '[CSB] Automatic enrollment berhasil.',
                    result
                );

                return {
                    authenticated: true,
                    bound: true,
                    binding_id:
                        result.binding_id,
                    reused: false
                };
            }


            /*
             * -------------------------------------------------
             * CASE 2
             *
             * Server sudah memiliki binding.
             *
             * Periksa apakah browser mempunyai
             * binding ID dan private key yang sesuai.
             * -------------------------------------------------
             */

            const localBindingId =
                await getBindingId();

            const privateKey =
                await getPrivateKey();

            if (
                privateKey &&
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


            /*
             * -------------------------------------------------
             * CASE 3
             *
             * Server memiliki binding,
             * tetapi browser tidak mempunyai key
             * yang sesuai.
             *
             * JANGAN membuat key baru.
             *
             * Fail closed.
             * -------------------------------------------------
             */

            console.error(
                '[CSB] Local cryptographic key tidak sesuai dengan server binding.',
                {
                    serverBindingId:
                        status.binding_id,

                    localBindingId
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
                '[CSB] Automatic initialization gagal:',
                error
            );

            return null;
        }
    }

    async function navigateWithCryptographicProof(url) {
        const targetUrl = new URL(url, window.location.origin);

        /*
        * Pastikan target masih berada pada origin aplikasi
        */
        if (targetUrl.origin !== window.location.origin) {
            throw new Error('Invalid navigation target.');
        }

        /*
        * Ambil path target.
        *
        * Contoh:
        * /transactions
        */
        const targetPath = targetUrl.pathname;

        /*
        * Buat proof untuk REQUEST TUJUAN.
        *
        * Penting:
        * proof ini adalah:
        *
        * GET /transactions
        *
        * bukan:
        *
        * POST /security/navigation-grant
        */
        const proofData = await createRequestProof(
            'GET',
            targetUrl.href
        );

        /*
        * CSRF token Laravel
        */
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        if (!csrfToken) {
            throw new Error('CSRF token tidak ditemukan.');
        }

        /*
        * Kirim proof ke endpoint navigation grant.
        */
        const response = await fetch(
            '/security/navigation-grant',
            {
                method: 'POST',

                credentials: 'same-origin',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',

                    /*
                    * Proof untuk:
                    * GET /transactions
                    */
                    'DPoP': proofData.proof,

                    /*
                    * CSRF Laravel
                    */
                    'X-CSRF-TOKEN': csrfToken,
                },

                body: JSON.stringify({
                    /*
                    * Proof yang sama dikirim sebagai body
                    * karena controller navigation grant
                    * membacanya dari request JSON.
                    */
                    proof: proofData.proof,

                    /*
                    * Target resource yang ingin dibuka.
                    */
                    path: targetPath,
                }),
            }
        );

        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error(
                'Server mengembalikan response yang tidak valid.'
            );
        }

        if (!response.ok) {
            throw new Error(
                data.message ||
                'Gagal membuat cryptographic navigation grant.'
            );
        }

        if (!data.url) {
            throw new Error(
                'Navigation grant tidak mengembalikan URL.'
            );
        }

        /*
        * Grant berhasil dibuat.
        *
        * Browser sekarang melakukan navigasi normal
        * menggunakan URL yang memiliki one-time token.
        */
        window.location.assign(data.url);
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

        registerPublicKey,

        getBindingId,

        signTestMessage,

        verifyTestSignature,

        testLocalSignature,

        createCryptographicProof,

        sendCryptographicProof,

        createRequestProof,

        requestWithCryptographicProof,

        deleteValue,

        clearCryptographicState,

        getCurrentBindingStatus,

        initializeCryptographicSession
    };


    // =====================================================
    // Automatic Initialization
    // =====================================================

    document.addEventListener(
        'DOMContentLoaded',
        async () => {
            try {
                await initializeCryptographicSession();
            } catch (error) {
                console.error(
                    '[CSB] Automatic initialization gagal:',
                    error
                );
            }
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
             * Hentikan submit pertama
             * sampai local state selesai dibersihkan.
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
             * Submit ulang.
             *
             * form.submit() tidak memicu
             * event submit lagi.
             */
            form.submit();
        }
    );

    document.addEventListener('click', async (event) => {
        const link = event.target.closest(
            'a[data-cryptographic-navigation]'
        );

        /*
        * Bukan link yang menggunakan CSB navigation.
        */
        if (!link) {
            return;
        }

        /*
        * Biarkan browser menangani:
        *
        * Ctrl + click
        * Cmd + click
        * Shift + click
        * Alt + click
        */
        if (
            event.ctrlKey ||
            event.metaKey ||
            event.shiftKey ||
            event.altKey
        ) {
            return;
        }

        /*
        * Jika link membuka tab baru, jangan intercept.
        */
        if (
            link.target &&
            link.target !== '_self'
        ) {
            return;
        }

        const href = link.href;

        if (!href) {
            return;
        }

        const targetUrl = new URL(
            href,
            window.location.origin
        );

        /*
        * Hanya intercept URL dari aplikasi sendiri.
        */
        if (
            targetUrl.origin !== window.location.origin
        ) {
            return;
        }

        /*
        * Cegah browser langsung melakukan:
        *
        * GET /transactions
        *
        * karena belum ada DPoP / navigation grant.
        */
        event.preventDefault();

        /*
        * Optional: cegah double click selama request berjalan.
        */
        if (link.dataset.navigationLoading === 'true') {
            return;
        }

        link.dataset.navigationLoading = 'true';

        try {
            await navigateWithCryptographicProof(
                targetUrl.href
            );
        } catch (error) {
            console.error(
                'Cryptographic navigation failed:',
                error
            );

            alert(
                error?.message ||
                'Navigasi gagal karena cryptographic proof tidak valid.'
            );

            link.dataset.navigationLoading = 'false';
        }
    });

})();