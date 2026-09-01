const DB_NAME = 'medical-shop-security';
const DB_VERSION = 1;
const STORE_NAME = 'keys';

function openKeyDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;

            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME);
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function savePrivateKey(key) {
    const db = await openKeyDatabase();

    return new Promise((resolve, reject) => {
        const transaction = db.transaction(STORE_NAME, 'readwrite');
        const store = transaction.objectStore(STORE_NAME);

        const request = store.put(key, 'session-private-key');

        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

async function generateSessionKeyPair() {
    const keyPair = await crypto.subtle.generateKey(
        {
            name: 'ECDSA',
            namedCurve: 'P-256',
        },
        false,
        ['sign', 'verify'],
    );

    await savePrivateKey(keyPair.privateKey);

    console.log('[CSB] ECDSA P-256 key pair generated');

    console.log('[CSB] Public Key:', {
        type: keyPair.publicKey.type,
        algorithm: keyPair.publicKey.algorithm,
        extractable: keyPair.publicKey.extractable,
        usages: keyPair.publicKey.usages,
    });

    console.log('[CSB] Private Key:', {
        type: keyPair.privateKey.type,
        algorithm: keyPair.privateKey.algorithm,
        extractable: keyPair.privateKey.extractable,
        usages: keyPair.privateKey.usages,
    });

    return keyPair;
}

window.CryptographicSessionBinding = {
    generateSessionKeyPair,
};