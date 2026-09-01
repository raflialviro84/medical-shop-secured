(function () {
    'use strict';

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

        console.log('[CSB] ECDSA P-256 key pair berhasil dibuat.');

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

        return keyPair;
    }

    window.CryptographicSessionBinding = {
        generateKeyPair
    };
})();