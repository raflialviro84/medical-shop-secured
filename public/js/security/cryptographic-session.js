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

    async function savePrivateKey(privateKey) {
        const db = await openDatabase();

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(
                STORE_NAME,
                'readwrite'
            );

            const store = transaction.objectStore(STORE_NAME);

            const request = store.put(
                privateKey,
                'session-private-key'
            );

            request.onsuccess = function () {
                resolve();
            };

            request.onerror = function () {
                reject(request.error);
            };
        });
    }

    async function getPrivateKey() {
        const db = await openDatabase();

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(
                STORE_NAME,
                'readonly'
            );

            const store = transaction.objectStore(STORE_NAME);

            const request = store.get(
                'session-private-key'
            );

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
            throw new Error(
                'Web Crypto API tidak tersedia.'
            );
        }

        const keyPair = await window.crypto.subtle.generateKey(
            {
                name: 'ECDSA',
                namedCurve: 'P-256'
            },
            false,
            ['sign', 'verify']
        );

        await savePrivateKey(keyPair.privateKey);

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
            '[CSB] Private Key berhasil disimpan ke IndexedDB.'
        );

        return keyPair;
    }

    window.CryptographicSessionBinding = {
        generateKeyPair,
        getPrivateKey
    };
})();