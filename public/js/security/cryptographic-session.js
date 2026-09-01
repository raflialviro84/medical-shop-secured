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

    console.log('[CSB] Public key berhasil di-bind.', data);

    return data;
}

    window.CryptographicSessionBinding = {
        generateKeyPair,
        getPrivateKey,
        getPublicKey,
        exportPublicKeyJwk,
        registerPublicKey,
    };
})();