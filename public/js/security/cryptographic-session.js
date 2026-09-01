(function () {
    'use strict';

    const DB_NAME = 'medical-shop-security';
    const DB_VERSION = 1;
    const STORE_NAME = 'keys';

    function openDatabase() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = function (event) {
                const db = event.target.result;

                if (!db.objectStoreNames.contains(STORE_NAME)) {
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

    async function saveKey(name, key) {
        const db = await openDatabase();

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(
                STORE_NAME,
                'readwrite'
            );

            const store = transaction.objectStore(STORE_NAME);

            const request = store.put(key, name);

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

        const store = transaction.objectStore(STORE_NAME);

        const request = store.put(value, name);

        request.onsuccess = function () {
            resolve();
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

        const store = transaction.objectStore(STORE_NAME);

        const request = store.get(name);

        request.onsuccess = function () {
            resolve(request.result ?? null);
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

            const store = transaction.objectStore(STORE_NAME);

            const request = store.get(name);

            request.onsuccess = function () {
                resolve(request.result || null);
            };

            request.onerror = function () {
                reject(request.error);
            };
        });
    }

    async function generateKeyPair() {
        if (!window.crypto || !window.crypto.subtle) {
            throw new Error('Web Crypto API tidak tersedia.');
        }

        const keyPair = await window.crypto.subtle.generateKey(
            {
                name: 'ECDSA',
                namedCurve: 'P-256'
            },
            false,
            ['sign', 'verify']
        );

        await saveKey(
            'session-private-key',
            keyPair.privateKey
        );

        await saveKey(
            'session-public-key',
            keyPair.publicKey
        );

        console.log(
            '[CSB] ECDSA P-256 key pair berhasil dibuat.'
        );

        console.log('[CSB] Public Key:', {
            type: keyPair.publicKey.type,
            algorithm: keyPair.publicKey.algorithm,
            extractable: keyPair.publicKey.extractable,
            usages: keyPair.publicKey.usages
        });

        console.log('[CSB] Private Key:', {
            type: keyPair.privateKey.type,
            algorithm: keyPair.privateKey.algorithm,
            extractable: keyPair.privateKey.extractable,
            usages: keyPair.privateKey.usages
        });

        console.log(
            '[CSB] Key pair berhasil disimpan ke IndexedDB.'
        );

        return keyPair;
    }

    async function getPrivateKey() {
        return await getKey('session-private-key');
    }

    async function getPublicKey() {
        return await getKey('session-public-key');
    }

    async function exportPublicKeyJwk() {
        const publicKey = await getPublicKey();

        if (!publicKey) {
            throw new Error('Public key tidak ditemukan.');
        }

        return await window.crypto.subtle.exportKey(
            'jwk',
            publicKey
        );
    }

    async function registerPublicKey() {
    const publicKey = await exportPublicKeyJwk();

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    if (!csrfToken) {
        throw new Error('CSRF token tidak ditemukan.');
    }

    const response = await fetch('/security/session-binding', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            public_key: publicKey,
        }),
    });

    const data = await response.json();

    if (!response.ok) {
        throw new Error(
            data.message || 'Gagal mendaftarkan public key.'
        );
    }

    await saveValue(
        'binding-id',
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

    async function getPublicKeyJwk() {
    return await exportPublicKeyJwk();
}

    async function getBindingId() {
    return await getValue('binding-id');
}

    async function signTestMessage(message = 'CSB_TEST_MESSAGE') {
    const privateKey = await getPrivateKey();

    if (!privateKey) {
        throw new Error('Private key tidak ditemukan di IndexedDB.');
    }

    const data = new TextEncoder().encode(message);

    const signature = await window.crypto.subtle.sign(
        {
            name: 'ECDSA',
            hash: 'SHA-256'
        },
        privateKey,
        data
    );

    console.log('[CSB] Message berhasil ditandatangani.');

    return {
        message,
        signature
    };
}

    async function verifyTestSignature(message, signature) {
    const publicKey = await getPublicKey();

    if (!publicKey) {
        throw new Error('Public key tidak ditemukan di IndexedDB.');
    }

    const data = new TextEncoder().encode(message);

    const valid = await window.crypto.subtle.verify(
        {
            name: 'ECDSA',
            hash: 'SHA-256'
        },
        publicKey,
        signature,
        data
    );

    console.log('[CSB] Signature verification:', valid);

    return valid;
}

    async function testLocalSignature() {
    const privateKey = await getPrivateKey();
    const publicKey = await getPublicKey();

    if (!privateKey) {
        throw new Error('Private key tidak ditemukan.');
    }

    if (!publicKey) {
        throw new Error('Public key tidak ditemukan.');
    }

    const message = 'CSB_LOCAL_TEST';
    const data = new TextEncoder().encode(message);

    const signature = await window.crypto.subtle.sign(
        {
            name: 'ECDSA',
            hash: 'SHA-256'
        },
        privateKey,
        data
    );

    const valid = await window.crypto.subtle.verify(
        {
            name: 'ECDSA',
            hash: 'SHA-256'
        },
        publicKey,
        signature,
        data
    );

    console.log('[CSB] Local key pair verification:', valid);

    return valid;
}

    function generateJti() {
    if (window.crypto.randomUUID) {
        return window.crypto.randomUUID();
    }

    const bytes = new Uint8Array(16);
    window.crypto.getRandomValues(bytes);

    return Array.from(bytes)
        .map(byte => byte.toString(16).padStart(2, '0'))
        .join('');
}

function canonicalizeProof(proof) {
    return [
        proof.jti,
        proof.iat,
        proof.htm,
        proof.htu,
        proof.sid
    ].join('\n');
}

    async function createSessionProof() {
    const privateKey = await getPrivateKey();

    if (!privateKey) {
        throw new Error('Private key tidak ditemukan.');
    }

    const proof = {
        jti: generateJti(),
        iat: Math.floor(Date.now() / 1000),
        htm: 'POST',
        htu: '/security/session-proof',
        sid: 'current-session-binding'
    };

    const canonical = canonicalizeProof(proof);

    const data = new TextEncoder().encode(canonical);

    const signature = await window.crypto.subtle.sign(
        {
            name: 'ECDSA',
            hash: 'SHA-256'
        },
        privateKey,
        data
    );

    const signatureBase64 = btoa(
        String.fromCharCode(...new Uint8Array(signature))
    );

    console.log('[CSB] Session proof berhasil dibuat.');

    return {
        ...proof,
        signature: signatureBase64
    };
}

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

function generateJti() {
    if (window.crypto.randomUUID) {
        return window.crypto.randomUUID();
    }

    const bytes = new Uint8Array(16);

    window.crypto.getRandomValues(bytes);

    return Array.from(bytes)
        .map(byte => byte.toString(16).padStart(2, '0'))
        .join('');
}

    async function createCryptographicProof() {
    const privateKey = await getPrivateKey();
    const bindingId = await getBindingId();
    const publicKeyJwk = await getPublicKeyJwk();

    if (!privateKey) {
        throw new Error('Private key tidak ditemukan.');
    }

    if (!bindingId) {
        throw new Error('Binding ID tidak ditemukan.');
    }

    const header = {
        typ: 'csb+jwt',
        alg: 'ES256',
        jwk: publicKeyJwk
    };

    const payload = {
        jti: generateJti(),
        iat: Math.floor(Date.now() / 1000),
        htm: 'POST',
        htu: '/security/session-proof',
        binding_id: bindingId
    };

    const encodedHeader = stringToBase64Url(
        JSON.stringify(header)
    );

    const encodedPayload = stringToBase64Url(
        JSON.stringify(payload)
    );

    const signingInput =
        `${encodedHeader}.${encodedPayload}`;

    const signature = await window.crypto.subtle.sign(
        {
            name: 'ECDSA',
            hash: 'SHA-256'
        },
        privateKey,
        new TextEncoder().encode(signingInput)
    );

    const encodedSignature = base64UrlEncode(
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

    async function sendCryptographicProof() {
    const result = await createCryptographicProof();

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    if (!csrfToken) {
        throw new Error('CSRF token tidak ditemukan.');
    }

    const response = await fetch(
        '/security/session-proof',
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                proof: result.proof,
            }),
        }
    );

    const data = await response.json();

    console.log(
        '[CSB] Server verification response:',
        data
    );

    if (!response.ok) {
        throw new Error(
            data.message || 'Cryptographic proof ditolak.'
        );
    }

    return data;
}

    async function createRequestProof(method, url) {
        const privateKey = await getPrivateKey();
        const bindingId = await getBindingId();
        const publicKeyJwk = await getPublicKeyJwk();

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
            iat: Math.floor(Date.now() / 1000),
            htm: method.toUpperCase(),
            htu: url,
            binding_id: bindingId
        };

        const encodedHeader =
            stringToBase64Url(JSON.stringify(header));

        const encodedPayload =
            stringToBase64Url(JSON.stringify(payload));

        const signingInput =
            `${encodedHeader}.${encodedPayload}`;

        const signature =
            await window.crypto.subtle.sign(
                {
                    name: 'ECDSA',
                    hash: 'SHA-256'
                },
                privateKey,
                new TextEncoder().encode(signingInput)
            );

        const encodedSignature =
            base64UrlEncode(new Uint8Array(signature));

        return {
            header,
            payload,
            proof:
                `${signingInput}.${encodedSignature}`
        };
    }

    window.CryptographicSessionBinding = {
        generateKeyPair,
        getPrivateKey,
        getPublicKey,
        exportPublicKeyJwk,
        registerPublicKey,
        getBindingId,
        testLocalSignature,
        signTestMessage,
        verifyTestSignature,
        createCryptographicProof,
        sendCryptographicProof,
        createRequestProof
    };
})();