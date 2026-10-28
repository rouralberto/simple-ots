/**
 * Simple OTS - Page behaviour
 *
 * Kept out of the HTML so the Content-Security-Policy can forbid inline scripts.
 */

(function () {
    'use strict';

    // UI strings in the visitor's language, rendered by the server into <html data-i18n>.
    const STRINGS = JSON.parse(document.documentElement.dataset.i18n || '{}');

    function t(key, vars = {}) {
        return Object.entries(vars).reduce(
            (text, [name, value]) => text.replaceAll(`{{${name}}}`, value),
            STRINGS[key] || key
        );
    }

    function showError(element, message) {
        element.textContent = message;
        element.hidden = false;
    }

    async function postJson(url, data, headers = {}) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', ...headers },
            body: JSON.stringify(data),
            credentials: 'same-origin',
        });
        let body = {};
        try {
            body = await response.json();
        } catch (e) {
            // Non-JSON error page (e.g. proxy failure); fall through with a generic message.
        }
        if (!response.ok) {
            throw new Error(body.error || t('js.request_failed', { STATUS: response.status }));
        }
        return body;
    }

    /**
     * Copy buttons: <button data-copy-target="element-id">…<span>Label</span></button>
     */
    function initCopyButtons() {
        document.querySelectorAll('[data-copy-target]').forEach((button) => {
            const label = button.querySelector('span');
            const icon = button.querySelector('i');
            const originalLabel = label.textContent;
            let resetTimer;

            button.addEventListener('click', async () => {
                const target = document.getElementById(button.dataset.copyTarget);
                let copied = false;
                try {
                    await navigator.clipboard.writeText(target.value);
                    copied = true;
                } catch (e) {
                    // Clipboard API is missing on insecure origins; select the text so the user can copy manually.
                    target.focus();
                    target.select();
                }

                label.textContent = t(copied ? 'js.copied' : 'js.copy_manually');
                icon.className = copied ? 'bi bi-check-lg me-2' : 'bi bi-cursor-text me-2';
                clearTimeout(resetTimer);
                resetTimer = setTimeout(() => {
                    label.textContent = originalLabel;
                    icon.className = 'bi bi-copy me-2';
                }, 2000);
            });
        });
    }

    /**
     * Language picker: the choice goes in a cookie the server reads, then the page reloads in that language.
     * A reload (unlike following a link) keeps the #key fragment, so it is safe on a reveal link.
     */
    function initLanguagePicker() {
        const select = document.getElementById('language');
        select.value = select.dataset.current;
        document.getElementById('language-picker').hidden = false;

        select.addEventListener('change', () => {
            const secure = window.location.protocol === 'https:' ? '; secure' : '';
            document.cookie = `lang=${encodeURIComponent(select.value)}; path=/; max-age=31536000; samesite=lax${secure}`;
            window.location.reload();
        });
    }

    // Reloading would throw away a link or secret that can't be shown again, so switching waits until it's gone.
    function lockLanguagePicker(locked) {
        document.getElementById('language').disabled = locked;
    }

    function initCreatePage() {
        const form = document.getElementById('secret-form');
        if (!form) return;

        const secretInput = document.getElementById('secret');
        const expiryInput = document.getElementById('expiry');
        const submitBtn = document.getElementById('submit-btn');
        const errorBox = document.getElementById('create-error');
        const createSection = document.getElementById('create');
        const createdSection = document.getElementById('created');
        const sharingUrl = document.getElementById('sharing-url');
        const submitLabel = submitBtn.innerHTML;
        let csrfToken = form.dataset.csrfToken;

        if (!OTSCrypto.isSupported()) {
            showError(errorBox, t('js.insecure_context'));
            submitBtn.disabled = true;
            return;
        }

        secretInput.addEventListener('input', () => secretInput.classList.remove('is-invalid'));

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorBox.hidden = true;

            if (!secretInput.value.trim()) {
                secretInput.classList.add('is-invalid');
                secretInput.focus();
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + t('js.encrypting');

            try {
                const key = await OTSCrypto.generateKey();
                const keyString = await OTSCrypto.exportKey(key);
                const encryptedData = await OTSCrypto.encrypt(secretInput.value, key);

                const result = await postJson(
                    '/write/',
                    { secret: encryptedData, expiry: parseInt(expiryInput.value, 10) },
                    { 'X-CSRF-Token': csrfToken }
                );

                csrfToken = result.csrfToken;
                const url = new URL('/', window.location.origin);
                url.searchParams.set('read', result.uuid);
                url.hash = keyString;

                sharingUrl.value = url.href;
                document.getElementById('created-expiry').textContent = result.expiresIn;
                secretInput.value = '';
                createSection.hidden = true;
                createdSection.hidden = false;
                lockLanguagePicker(true);
                sharingUrl.focus();
                sharingUrl.select();
            } catch (err) {
                showError(errorBox, err.message || t('js.generic_error'));
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitLabel;
            }
        });

        document.getElementById('create-another').addEventListener('click', () => {
            sharingUrl.value = '';
            createdSection.hidden = true;
            createSection.hidden = false;
            lockLanguagePicker(false);
            secretInput.focus();
        });
    }

    function initRevealPage() {
        const container = document.getElementById('reveal');
        if (!container) return;

        const revealBtn = document.getElementById('reveal-button');
        const intro = document.getElementById('reveal-intro');
        const errorBox = document.getElementById('reveal-error');
        const result = document.getElementById('reveal-result');
        const output = document.getElementById('secret-output');
        const keyMissing = document.getElementById('reveal-key-missing');
        const revealLabel = revealBtn.innerHTML;
        let keyString;
        let revealing = false;

        if (!OTSCrypto.isSupported()) {
            showError(errorBox, t('js.insecure_context_reveal'));
            revealBtn.disabled = true;
            return;
        }

        // Check the key *before* the server deletes the secret.
        function checkKey() {
            keyString = OTSCrypto.getKeyFromFragment();
            const valid = OTSCrypto.isValidKeyString(keyString);
            keyMissing.hidden = valid;
            revealBtn.disabled = !valid;
        }
        checkKey();
        // Pasting the full link over an incomplete one in the same tab only changes the fragment, without reloading.
        window.addEventListener('hashchange', () => {
            if (!revealing && !intro.hidden) checkKey();
        });

        revealBtn.addEventListener('click', async () => {
            revealing = true;
            revealBtn.disabled = true;
            revealBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + t('js.decrypting');

            errorBox.hidden = true;
            let response;
            try {
                response = await fetch('/read/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ uuid: container.dataset.uuid }),
                });
            } catch (err) {
                response = null;
            }

            if (!response || !response.ok) {
                const gone = response && response.status === 404;
                const body = response ? await response.json().catch(() => ({})) : {};
                showError(errorBox, body.error || t('js.network_error'));
                if (gone) {
                    intro.hidden = true;
                } else {
                    // Nothing was consumed, so let the user retry.
                    revealing = false;
                    revealBtn.disabled = false;
                    revealBtn.innerHTML = revealLabel;
                }
                return;
            }

            // The secret is now deleted server-side, so the key is useless; keep it out of history and bookmarks.
            history.replaceState(null, '', window.location.pathname + window.location.search);
            intro.hidden = true;

            try {
                const key = await OTSCrypto.importKey(keyString);
                output.value = await OTSCrypto.decrypt((await response.json()).secret, key);
            } catch (err) {
                showError(errorBox, t('js.decrypt_failed'));
                return;
            }

            result.hidden = false;
            lockLanguagePicker(true);
            output.rows = Math.min(Math.max(output.value.split('\n').length, 4), 20);
            output.focus();
            output.select();

            if (typeof JSConfetti !== 'undefined') {
                new JSConfetti().addConfetti({ emojis: ['💥', '🦵', '💣', '🤯', '🔥'] });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initLanguagePicker();
        initCopyButtons();
        initCreatePage();
        initRevealPage();
    });
})();
