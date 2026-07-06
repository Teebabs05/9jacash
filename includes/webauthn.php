<?php
/**
 * Minimal WebAuthn (FIDO2) relying-party implementation for biometric
 * login (Windows Hello / Touch ID / Android biometric unlock) for both
 * users and admins. No composer dependency beyond PHPMailer is allowed
 * in this project, so this is a small, purpose-built implementation
 * rather than a full spec-complete WebAuthn library:
 *
 * - Only ES256 (COSE alg -7, P-256 ECDSA) credentials are supported.
 *   Every mainstream platform authenticator (Windows Hello, Touch ID,
 *   Android's biometric APIs) defaults to ES256 when it's offered, so
 *   this covers the "biometric login" use case without needing RSA/PEM
 *   plumbing for a format that in practice won't be used here.
 * - Attestation statements ("attStmt") are accepted but not
 *   cryptographically verified against a manufacturer root - this
 *   relying party only cares that the user's own device can later
 *   prove possession of the private key (verified rigorously at login
 *   via the assertion signature), not chain-of-trust to a vendor CA.
 *   This is a standard, documented simplification used by most
 *   self-hosted WebAuthn relying parties that aren't doing enterprise
 *   device attestation.
 */

declare(strict_types=1);

const WEBAUTHN_CHALLENGE_TTL = 120; // seconds

if (!function_exists('base64url_encode')) {
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('base64url_decode')) {
    function base64url_decode(string $data): string
    {
        $padded = str_pad(strtr($data, '-_', '+/'), strlen($data) % 4 === 0 ? strlen($data) : strlen($data) + (4 - strlen($data) % 4), '=');
        return (string) base64_decode($padded, true);
    }
}

// ---------------------------------------------------------------
// Minimal CBOR decoder - only the subset WebAuthn actually produces:
// unsigned/negative integers, byte strings, text strings, arrays and
// maps, all with definite (non-streaming) lengths.
// ---------------------------------------------------------------
if (!function_exists('cbor_decode')) {
    function cbor_decode(string $data)
    {
        $offset = 0;
        $value = cbor_decode_at($data, $offset);
        return $value;
    }
}

if (!function_exists('cbor_read_length')) {
    function cbor_read_length(string $data, int &$offset, int $additionalInfo): int
    {
        if ($additionalInfo < 24) {
            return $additionalInfo;
        }

        if ($additionalInfo === 24) {
            $len = ord($data[$offset]);
            $offset += 1;
            return $len;
        }

        if ($additionalInfo === 25) {
            $len = (ord($data[$offset]) << 8) | ord($data[$offset + 1]);
            $offset += 2;
            return $len;
        }

        if ($additionalInfo === 26) {
            $unpacked = unpack('N', substr($data, $offset, 4));
            $offset += 4;
            return (int) $unpacked[1];
        }

        if ($additionalInfo === 27) {
            $unpacked = unpack('J', substr($data, $offset, 8));
            $offset += 8;
            return (int) $unpacked[1];
        }

        throw new RuntimeException('Unsupported CBOR length encoding.');
    }
}

if (!function_exists('cbor_decode_at')) {
    function cbor_decode_at(string $data, int &$offset)
    {
        $initialByte = ord($data[$offset]);
        $offset += 1;

        $majorType = $initialByte >> 5;
        $additionalInfo = $initialByte & 0x1F;

        switch ($majorType) {
            case 0: // unsigned integer
                return cbor_read_length($data, $offset, $additionalInfo);

            case 1: // negative integer
                return -1 - cbor_read_length($data, $offset, $additionalInfo);

            case 2: // byte string
                $len = cbor_read_length($data, $offset, $additionalInfo);
                $bytes = substr($data, $offset, $len);
                $offset += $len;
                return $bytes;

            case 3: // text string
                $len = cbor_read_length($data, $offset, $additionalInfo);
                $str = substr($data, $offset, $len);
                $offset += $len;
                return $str;

            case 4: // array
                $count = cbor_read_length($data, $offset, $additionalInfo);
                $items = [];
                for ($i = 0; $i < $count; $i++) {
                    $items[] = cbor_decode_at($data, $offset);
                }
                return $items;

            case 5: // map
                $count = cbor_read_length($data, $offset, $additionalInfo);
                $map = [];
                for ($i = 0; $i < $count; $i++) {
                    $key = cbor_decode_at($data, $offset);
                    $val = cbor_decode_at($data, $offset);
                    $map[$key] = $val;
                }
                return $map;

            default:
                throw new RuntimeException('Unsupported CBOR major type: ' . $majorType);
        }
    }
}

/**
 * Parse the raw authenticatorData byte structure (not CBOR - a fixed
 * binary layout per the WebAuthn spec).
 */
if (!function_exists('webauthn_parse_auth_data')) {
    function webauthn_parse_auth_data(string $authData): array
    {
        $rpIdHash = substr($authData, 0, 32);
        $flags = ord($authData[32]);
        $signCountUnpacked = unpack('N', substr($authData, 33, 4));
        $signCount = (int) $signCountUnpacked[1];

        $result = [
            'rpIdHash' => $rpIdHash,
            'flags' => $flags,
            'userPresent' => (bool) ($flags & 0x01),
            'userVerified' => (bool) ($flags & 0x04),
            'attestedCredentialDataIncluded' => (bool) ($flags & 0x40),
            'signCount' => $signCount,
            'credentialId' => null,
            'credentialPublicKey' => null,
        ];

        $offset = 37;

        if ($result['attestedCredentialDataIncluded']) {
            $offset += 16; // aaguid, unused
            $credIdLenUnpacked = unpack('n', substr($authData, $offset, 2));
            $credIdLen = (int) $credIdLenUnpacked[1];
            $offset += 2;
            $result['credentialId'] = substr($authData, $offset, $credIdLen);
            $offset += $credIdLen;
            $result['credentialPublicKey'] = cbor_decode_at($authData, $offset);
        }

        return $result;
    }
}

/**
 * Build a PEM-encoded SubjectPublicKeyInfo from a COSE EC2/P-256 key
 * map (kty=2, crv=1, alg=-7 / ES256). Returns null for any other key
 * type - the caller should reject registration in that case.
 */
if (!function_exists('webauthn_cose_key_to_pem')) {
    function webauthn_cose_key_to_pem(array $coseKey): ?string
    {
        $kty = $coseKey[1] ?? null;
        $crv = $coseKey[-1] ?? null;
        $x = $coseKey[-2] ?? null;
        $y = $coseKey[-3] ?? null;

        if ($kty !== 2 || $crv !== 1 || !is_string($x) || !is_string($y) || strlen($x) !== 32 || strlen($y) !== 32) {
            return null;
        }

        // Fixed DER prefix for a P-256 SubjectPublicKeyInfo, up to (but
        // not including) the raw uncompressed EC point that follows.
        $derPrefix = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200');
        $point = "\x04" . $x . $y;
        $der = $derPrefix . $point;

        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";

        return $pem;
    }
}

if (!function_exists('webauthn_rp_id')) {
    function webauthn_rp_id(): string
    {
        return (string) parse_url(rtrim(APP_URL, '/'), PHP_URL_HOST);
    }
}

if (!function_exists('webauthn_expected_origin')) {
    function webauthn_expected_origin(): string
    {
        $parts = parse_url(rtrim(APP_URL, '/'));
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return "{$scheme}://{$host}{$port}";
    }
}

/**
 * Registration options (server -> client). $ownerType/$ownerId identify
 * the account enabling biometric login; the challenge is stashed in the
 * session so register-verify.php can confirm the same ceremony round-tripped.
 */
if (!function_exists('webauthn_registration_options')) {
    function webauthn_registration_options(string $ownerType, int $ownerId, string $ownerName, string $ownerDisplayName): array
    {
        $challenge = random_bytes(32);
        $_SESSION['webauthn_reg'] = [
            'challenge' => base64url_encode($challenge),
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'expires' => time() + WEBAUTHN_CHALLENGE_TTL,
        ];

        $stmt = db()->prepare('SELECT credential_id FROM webauthn_credentials WHERE owner_type = ? AND owner_id = ?');
        $stmt->execute([$ownerType, $ownerId]);
        $existing = $stmt->fetchAll();

        return [
            'challenge' => base64url_encode($challenge),
            'rp' => ['id' => webauthn_rp_id(), 'name' => (string) get_setting('site_name', 'SURECASH MINING')],
            'user' => [
                'id' => base64url_encode((string) $ownerId),
                'name' => $ownerName,
                'displayName' => $ownerDisplayName,
            ],
            'pubKeyCredParams' => [['type' => 'public-key', 'alg' => -7]],
            'excludeCredentials' => array_map(
                static fn (array $row) => ['type' => 'public-key', 'id' => $row['credential_id']],
                $existing
            ),
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform',
                'residentKey' => 'required',
                'userVerification' => 'required',
            ],
            'timeout' => WEBAUTHN_CHALLENGE_TTL * 1000,
            'attestation' => 'none',
        ];
    }
}

/**
 * Verify a registration ceremony and store the resulting credential.
 * $credential is the decoded JSON body the client posted back
 * (id, rawId, response.attestationObject, response.clientDataJSON - all base64url strings).
 */
if (!function_exists('webauthn_verify_registration')) {
    function webauthn_verify_registration(string $ownerType, int $ownerId, array $credential, string $deviceLabel = ''): array
    {
        $session = $_SESSION['webauthn_reg'] ?? null;
        unset($_SESSION['webauthn_reg']);

        if (!$session || $session['owner_type'] !== $ownerType || $session['owner_id'] !== $ownerId || $session['expires'] < time()) {
            return ['success' => false, 'message' => 'Registration session expired. Please try again.'];
        }

        try {
            $clientDataRaw = base64url_decode($credential['response']['clientDataJSON'] ?? '');
            $clientData = json_decode($clientDataRaw, true);

            if (!is_array($clientData) || ($clientData['type'] ?? '') !== 'webauthn.create') {
                return ['success' => false, 'message' => 'Unexpected registration response type.'];
            }

            if (($clientData['challenge'] ?? '') !== $session['challenge']) {
                return ['success' => false, 'message' => 'Registration challenge mismatch.'];
            }

            if (rtrim((string) ($clientData['origin'] ?? ''), '/') !== webauthn_expected_origin()) {
                return ['success' => false, 'message' => 'Registration origin mismatch.'];
            }

            $attestationObjectRaw = base64url_decode($credential['response']['attestationObject'] ?? '');
            $attestationObject = cbor_decode($attestationObjectRaw);

            if (!is_array($attestationObject) || !isset($attestationObject['authData'])) {
                return ['success' => false, 'message' => 'Could not read authenticator response.'];
            }

            $authData = webauthn_parse_auth_data($attestationObject['authData']);

            if (!$authData['attestedCredentialDataIncluded'] || !$authData['credentialId'] || !is_array($authData['credentialPublicKey'])) {
                return ['success' => false, 'message' => 'Authenticator did not return credential data.'];
            }

            $pem = webauthn_cose_key_to_pem($authData['credentialPublicKey']);

            if (!$pem) {
                return ['success' => false, 'message' => 'This authenticator type is not supported. Please use your device\'s built-in fingerprint/face unlock.'];
            }

            $credentialIdB64 = base64url_encode($authData['credentialId']);

            $stmt = db()->prepare(
                'INSERT INTO webauthn_credentials (owner_type, owner_id, credential_id, public_key, sign_count, device_label, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$ownerType, $ownerId, $credentialIdB64, $pem, $authData['signCount'], $deviceLabel !== '' ? $deviceLabel : null]);

            return ['success' => true, 'message' => 'Biometric login enabled successfully.'];
        } catch (Throwable $e) {
            app_log('error', 'WebAuthn registration failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not process this authenticator. Please try again.'];
        }
    }
}

/**
 * Login (assertion) options for the usernameless/discoverable-credential
 * flow - the browser itself prompts the user to pick which registered
 * account/authenticator to use, so no username needs to be typed first.
 */
if (!function_exists('webauthn_login_options')) {
    function webauthn_login_options(): array
    {
        $challenge = random_bytes(32);
        $_SESSION['webauthn_login'] = [
            'challenge' => base64url_encode($challenge),
            'expires' => time() + WEBAUTHN_CHALLENGE_TTL,
        ];

        return [
            'challenge' => base64url_encode($challenge),
            'rpId' => webauthn_rp_id(),
            'userVerification' => 'required',
            'timeout' => WEBAUTHN_CHALLENGE_TTL * 1000,
            'allowCredentials' => [],
        ];
    }
}

/**
 * Verify a login (assertion) ceremony. $expectedOwnerType scopes the
 * credential lookup to 'user' or 'admin' so a credential registered on
 * one login page can never authenticate the other.
 */
if (!function_exists('webauthn_verify_login')) {
    function webauthn_verify_login(string $expectedOwnerType, array $credential): array
    {
        $session = $_SESSION['webauthn_login'] ?? null;
        unset($_SESSION['webauthn_login']);

        if (!$session || $session['expires'] < time()) {
            return ['success' => false, 'message' => 'Login session expired. Please try again.'];
        }

        try {
            $clientDataRaw = base64url_decode($credential['response']['clientDataJSON'] ?? '');
            $clientData = json_decode($clientDataRaw, true);

            if (!is_array($clientData) || ($clientData['type'] ?? '') !== 'webauthn.get') {
                return ['success' => false, 'message' => 'Unexpected login response type.'];
            }

            if (($clientData['challenge'] ?? '') !== $session['challenge']) {
                return ['success' => false, 'message' => 'Login challenge mismatch.'];
            }

            if (rtrim((string) ($clientData['origin'] ?? ''), '/') !== webauthn_expected_origin()) {
                return ['success' => false, 'message' => 'Login origin mismatch.'];
            }

            $credentialIdB64 = (string) ($credential['id'] ?? '');

            $stmt = db()->prepare('SELECT * FROM webauthn_credentials WHERE credential_id = ? AND owner_type = ? LIMIT 1');
            $stmt->execute([$credentialIdB64, $expectedOwnerType]);
            $stored = $stmt->fetch();

            if (!$stored) {
                return ['success' => false, 'message' => 'This biometric credential is not registered here.'];
            }

            $authDataRaw = base64url_decode($credential['response']['authenticatorData'] ?? '');
            $signatureRaw = base64url_decode($credential['response']['signature'] ?? '');
            $authData = webauthn_parse_auth_data($authDataRaw);

            if (!$authData['userPresent'] || !$authData['userVerified']) {
                return ['success' => false, 'message' => 'Biometric verification was not completed.'];
            }

            $signedData = $authDataRaw . hash('sha256', $clientDataRaw, true);
            $verified = openssl_verify($signedData, $signatureRaw, $stored['public_key'], OPENSSL_ALGO_SHA256);

            if ($verified !== 1) {
                return ['success' => false, 'message' => 'Biometric signature verification failed.'];
            }

            // Clone/replay heuristic: a nonzero counter that doesn't
            // strictly increase means either a replayed assertion or a
            // cloned authenticator. Many resident-key platform
            // authenticators always report 0, which is normal and not
            // flagged here.
            if ($authData['signCount'] !== 0 && $stored['sign_count'] !== 0 && $authData['signCount'] <= (int) $stored['sign_count']) {
                app_log('warning', 'WebAuthn sign counter did not increase - possible cloned authenticator.', ['credential_id' => $credentialIdB64]);
                return ['success' => false, 'message' => 'Biometric verification failed. Please log in with your password.'];
            }

            db()->prepare('UPDATE webauthn_credentials SET sign_count = ?, last_used_at = NOW() WHERE id = ?')
                ->execute([$authData['signCount'], $stored['id']]);

            return ['success' => true, 'message' => 'Login successful.', 'owner_id' => (int) $stored['owner_id']];
        } catch (Throwable $e) {
            app_log('error', 'WebAuthn login failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not verify biometric login. Please try again.'];
        }
    }
}

if (!function_exists('webauthn_credential_count')) {
    function webauthn_credential_count(string $ownerType, int $ownerId): int
    {
        $stmt = db()->prepare('SELECT COUNT(*) AS c FROM webauthn_credentials WHERE owner_type = ? AND owner_id = ?');
        $stmt->execute([$ownerType, $ownerId]);

        return (int) $stmt->fetch()['c'];
    }
}
