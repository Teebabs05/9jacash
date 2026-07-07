<?php
/**
 * Returns PublicKeyCredentialRequestOptions (JSON) for a usernameless
 * biometric login attempt - the browser prompts the user to pick a
 * registered account/authenticator itself, no username needed first.
 * Public endpoint (no login required - this IS how you log in).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

echo json_encode(['success' => true, 'options' => webauthn_login_options()]);
