'use strict';

/*
|--------------------------------------------------------------------------
| Cryptographic Session Binding - Service Worker
|--------------------------------------------------------------------------
|
| Tugas:
| - Menambahkan DPoP proof pada protected top-level navigation.
| - Mengambil private key dari IndexedDB.
| - Tidak menggunakan navigation grant.
| - Tidak melakukan bypass ketika key tidak tersedia.
|
*/


// ============================================================
// Configuration
// ============================================================

const DB_NAME = 'medical-shop-security';
const DB_VERSION = 1;
const STORE_NAME = 'keys';

const PRIVATE_KEY_NAME = 'session-private-key';
const PUBLIC_KEY_NAME = 'session-public-key';
const BINDING_ID_NAME = 'binding-id';


// ============================================================
// Public navigation paths
// ============================================================

const PUBLIC_PATHS = [
    '/',
    '/products',
    '/search',
    '/login',
    '/register',
    '/security/session-binding/status'
];


// ============================================================
// IndexedDB
// ============================================================

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


// ============================================================
// Base64URL
// ============================================================

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


// ============================================================
// JTI
// ============================================================

function generateJti() {
    if (
        crypto &&
        typeof crypto.randomUUID === 'function'
    ) {
        return crypto.randomUUID();
    }

    const bytes =
        new Uint8Array(16);

    crypto.getRandomValues(bytes);

    return Array.from(bytes)
        .map(
            byte =>
                byte
                    .toString(16)
                    .padStart(2, '0')
        )
        .join('');
}


// ============================================================
// Determine protected navigation
// ============================================================

function isPublicNavigation(pathname) {

    if (
        PUBLIC_PATHS.includes(pathname)
    ) {
        return true;
    }

    /*
     * Product detail:
     *
     * /products
     * /products/123
     */
    if (
        pathname === '/products' ||
        pathname.startsWith('/products/')
    ) {
        return true;
    }

    return false;
}


// ============================================================
// Create DPoP Proof
// ============================================================

async function createNavigationProof(
    request
) {
    const privateKey =
        await getKey(
            PRIVATE_KEY_NAME
        );

    const publicKey =
        await getKey(
            PUBLIC_KEY_NAME
        );

    const bindingId =
        await getValue(
            BINDING_ID_NAME
        );

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

    if (!bindingId) {
        throw new Error(
            'Binding ID tidak ditemukan.'
        );
    }


    // --------------------------------------------------------
    // Export public key
    // --------------------------------------------------------

    const publicKeyJwk =
        await crypto.subtle.exportKey(
            'jwk',
            publicKey
        );


    // --------------------------------------------------------
    // Request URL
    // --------------------------------------------------------

    const requestUrl =
        new URL(request.url);


    // --------------------------------------------------------
    // Header
    // --------------------------------------------------------

    const header = {
        typ: 'csb+jwt',
        alg: 'ES256',
        jwk: publicKeyJwk
    };


    // --------------------------------------------------------
    // Payload
    // --------------------------------------------------------

    const payload = {
        jti: generateJti(),

        iat: Math.floor(
            Date.now() / 1000
        ),

        htm: request.method.toUpperCase(),

        htu:
            requestUrl.pathname,

        binding_id: bindingId
    };


    // --------------------------------------------------------
    // Encode
    // --------------------------------------------------------

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


    // --------------------------------------------------------
    // Sign
    // --------------------------------------------------------

    const signature =
        await crypto.subtle.sign(
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


    return (
        `${signingInput}.${encodedSignature}`
    );
}


// ============================================================
// Synthetic failure response
// ============================================================

function cryptographicFailureResponse(
    message
) {
    return new Response(
        JSON.stringify({
            message,
            proof_valid: false
        }),
        {
            status: 403,

            headers: {
                'Content-Type':
                    'application/json'
            }
        }
    );
}


// ============================================================
// Navigation interception
// ============================================================

self.addEventListener(
    'fetch',
    event => {

        const request =
            event.request;

        /*
         * Hanya intercept top-level navigation.
         *
         * AJAX/fetch/API tidak diubah di sini.
         */
        if (
            request.mode !== 'navigate'
        ) {
            return;
        }


        const url =
            new URL(request.url);


        /*
         * Hanya origin aplikasi sendiri.
         */
        if (
            url.origin !== self.location.origin
        ) {
            return;
        }


        /*
         * Public page tidak membutuhkan CSB.
         */
        if (
            isPublicNavigation(
                url.pathname
            )
        ) {
            return;
        }


        /*
         * Protected navigation.
         */
        event.respondWith(
            (async () => {

                try {

                    const proof =
                        await createNavigationProof(
                            request
                        );


                    /*
                     * Clone header request.
                     */
                    const headers =
                        new Headers(
                            request.headers
                        );


                    headers.set(
                        'DPoP',
                        proof
                    );


                    /*
                     * Buat request baru dengan
                     * header DPoP.
                     */
                    const protectedRequest =
                        new Request(
                            request,
                            {
                                headers
                            }
                        );


                    return await fetch(
                        protectedRequest
                    );

                } catch (error) {

                    console.error(
                        '[CSB SW] Navigation proof gagal:',
                        error
                    );

                    /*
                     * Fail closed.
                     *
                     * Jangan pernah meneruskan
                     * request protected tanpa proof.
                     */
                    return cryptographicFailureResponse(
                        'Cryptographic proof is required.'
                    );
                }
            })()
        );
    }
);


// ============================================================
// Install
// ============================================================

self.addEventListener(
    'install',
    () => {
        self.skipWaiting();
    }
);


// ============================================================
// Activate
// ============================================================

self.addEventListener(
    'activate',
    event => {

        event.waitUntil(
            self.clients.claim()
        );
    }
);