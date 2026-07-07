/**
 * Client-side helpers for biometric (WebAuthn) registration and login.
 * Requires window.CSRF_TOKEN to be set on the page.
 */

const SureCashWebAuthn = (function () {
    function supported() {
        return !!(window.PublicKeyCredential && navigator.credentials && navigator.credentials.create);
    }

    function base64urlToBuffer(value) {
        const padded = value.replace(/-/g, '+').replace(/_/g, '/') + '=='.slice(0, (4 - (value.length % 4)) % 4);
        const raw = atob(padded);
        const buffer = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; i++) buffer[i] = raw.charCodeAt(i);
        return buffer.buffer;
    }

    function bufferToBase64url(buffer) {
        const bytes = new Uint8Array(buffer);
        let str = '';
        for (let i = 0; i < bytes.byteLength; i++) str += String.fromCharCode(bytes[i]);
        return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    async function register() {
        const optionsRes = await fetch('/webauthn/register-options.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': window.CSRF_TOKEN },
            body: 'csrf_token=' + encodeURIComponent(window.CSRF_TOKEN),
        }).then((r) => r.json());

        if (!optionsRes.success) throw new Error(optionsRes.message || 'Could not start registration.');

        const opts = optionsRes.options;
        const publicKey = {
            challenge: base64urlToBuffer(opts.challenge),
            rp: opts.rp,
            user: {
                id: base64urlToBuffer(opts.user.id),
                name: opts.user.name,
                displayName: opts.user.displayName,
            },
            pubKeyCredParams: opts.pubKeyCredParams,
            excludeCredentials: opts.excludeCredentials.map((c) => ({ type: c.type, id: base64urlToBuffer(c.id) })),
            authenticatorSelection: opts.authenticatorSelection,
            timeout: opts.timeout,
            attestation: opts.attestation,
        };

        const credential = await navigator.credentials.create({ publicKey });

        const payload = {
            id: credential.id,
            rawId: bufferToBase64url(credential.rawId),
            type: credential.type,
            response: {
                attestationObject: bufferToBase64url(credential.response.attestationObject),
                clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
            },
        };

        const verifyRes = await fetch('/webauthn/register-verify.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf_token: window.CSRF_TOKEN, credential: payload }),
        }).then((r) => r.json());

        return verifyRes;
    }

    async function fetchLoginOptions() {
        return fetch('/webauthn/login-options.php', { method: 'POST' }).then((r) => r.json());
    }

    // navigator.credentials.get() must run inside (or very close to) the
    // click event's user activation - any await before it (like fetching
    // options over the network) can burn through that activation window
    // and leave the browser waiting on a gesture that already happened.
    // Callers should fetch options ahead of time (e.g. as soon as the
    // biometric button becomes visible) and pass them in here so get()
    // runs immediately when the user actually clicks.
    async function login(context, prefetchedOptionsRes) {
        const optionsRes = prefetchedOptionsRes || await fetchLoginOptions();

        if (!optionsRes.success) throw new Error(optionsRes.message || 'Could not start biometric login.');

        const opts = optionsRes.options;
        const publicKey = {
            challenge: base64urlToBuffer(opts.challenge),
            rpId: opts.rpId,
            userVerification: opts.userVerification,
            timeout: opts.timeout,
            allowCredentials: [],
        };

        const assertion = await navigator.credentials.get({ publicKey });

        const payload = {
            id: assertion.id,
            rawId: bufferToBase64url(assertion.rawId),
            type: assertion.type,
            response: {
                authenticatorData: bufferToBase64url(assertion.response.authenticatorData),
                clientDataJSON: bufferToBase64url(assertion.response.clientDataJSON),
                signature: bufferToBase64url(assertion.response.signature),
            },
        };

        const verifyRes = await fetch('/webauthn/login-verify.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf_token: window.CSRF_TOKEN, context, credential: payload }),
        }).then((r) => r.json());

        return verifyRes;
    }

    return { supported, register, login, fetchLoginOptions };
})();
