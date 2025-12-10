/**
 * Simple OTS - Client-Side Encryption Module
 *
 * Uses Web Crypto API (SubtleCrypto) for zero-knowledge encryption.
 * - AES-256-GCM for authenticated encryption
 * - Random 256-bit keys
 * - 96-bit random IVs (as recommended for GCM)
 * - Key transmitted via URL fragment (never sent to server)
 */

const OTSCrypto = {
    /**
     * Generate a random 256-bit encryption key
     * @returns {Promise<CryptoKey>}
     */
    async generateKey() {
        return await crypto.subtle.generateKey(
            { name: 'AES-GCM', length: 256 },
            true, // extractable
            ['encrypt', 'decrypt']
        );
    },

    /**
     * Export key to base64url string (for URL fragment)
     * @param {CryptoKey} key
     * @returns {Promise<string>}
     */
    async exportKey(key) {
        const rawKey = await crypto.subtle.exportKey('raw', key);
        return this.arrayBufferToBase64Url(rawKey);
    },

    /**
     * Import key from base64url string
     * @param {string} keyString
     * @returns {Promise<CryptoKey>}
     */
    async importKey(keyString) {
        const rawKey = this.base64UrlToArrayBuffer(keyString);
        return await crypto.subtle.importKey(
            'raw',
            rawKey,
            { name: 'AES-GCM', length: 256 },
            false,
            ['decrypt']
        );
    },

    /**
     * Encrypt plaintext with AES-256-GCM
     * @param {string} plaintext
     * @param {CryptoKey} key
     * @returns {Promise<string>} Base64-encoded IV + ciphertext
     */
    async encrypt(plaintext, key) {
        const encoder = new TextEncoder();
        const data = encoder.encode(plaintext);

        // Generate random 96-bit IV (recommended for GCM)
        const iv = crypto.getRandomValues(new Uint8Array(12));

        const ciphertext = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv: iv },
            key,
            data
        );

        // Prepend IV to ciphertext
        const combined = new Uint8Array(iv.length + ciphertext.byteLength);
        combined.set(iv);
        combined.set(new Uint8Array(ciphertext), iv.length);

        return this.arrayBufferToBase64(combined.buffer);
    },

    /**
     * Decrypt ciphertext with AES-256-GCM
     * @param {string} encryptedData Base64-encoded IV + ciphertext
     * @param {CryptoKey} key
     * @returns {Promise<string>} Decrypted plaintext
     */
    async decrypt(encryptedData, key) {
        const combined = this.base64ToArrayBuffer(encryptedData);
        const combinedArray = new Uint8Array(combined);

        // Extract IV (first 12 bytes) and ciphertext
        const iv = combinedArray.slice(0, 12);
        const ciphertext = combinedArray.slice(12);

        const decrypted = await crypto.subtle.decrypt(
            { name: 'AES-GCM', iv: iv },
            key,
            ciphertext
        );

        const decoder = new TextDecoder();
        return decoder.decode(decrypted);
    },

    /**
     * Convert ArrayBuffer to standard Base64 string
     * @param {ArrayBuffer} buffer
     * @returns {string}
     */
    arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    },

    /**
     * Convert standard Base64 string to ArrayBuffer
     * @param {string} base64
     * @returns {ArrayBuffer}
     */
    base64ToArrayBuffer(base64) {
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    },

    /**
     * Convert ArrayBuffer to URL-safe Base64 string
     * @param {ArrayBuffer} buffer
     * @returns {string}
     */
    arrayBufferToBase64Url(buffer) {
        return this.arrayBufferToBase64(buffer)
            .replace(/\+/g, '-')
            .replace(/\//g, '_')
            .replace(/=+$/, '');
    },

    /**
     * Convert URL-safe Base64 string to ArrayBuffer
     * @param {string} base64url
     * @returns {ArrayBuffer}
     */
    base64UrlToArrayBuffer(base64url) {
        // Convert base64url to standard base64
        let base64 = base64url
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        // Add padding if necessary
        while (base64.length % 4) {
            base64 += '=';
        }

        return this.base64ToArrayBuffer(base64);
    },

    /**
     * Extract key from URL fragment
     * @returns {string|null}
     */
    getKeyFromFragment() {
        const fragment = window.location.hash.slice(1);
        return fragment || null;
    },

    /**
     * Check if Web Crypto API is available
     * @returns {boolean}
     */
    isSupported() {
        return !!(crypto && crypto.subtle && crypto.subtle.generateKey);
    }
};

// Freeze the object to prevent modifications
Object.freeze(OTSCrypto);
