<?php

declare(strict_types=1);

require_once '../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    json_response(['error' => t('api.method_not_allowed')], 405);
}

if (!is_allowed()) {
    json_response(['error' => t('api.not_authorised')], 403);
}

if (!check_rate_limit()) {
    json_response(['error' => t('api.rate_limit')], 429);
}

if (!validate_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    json_response(['error' => t('api.session_expired')], 403);
}

$input  = json_decode(file_get_contents('php://input'), true);
$secret = $input['secret'] ?? null;
$expiry = is_int($input['expiry'] ?? null) ? Expiry::tryFrom($input['expiry']) : null;

if (!is_valid_ciphertext($secret)) {
    json_response(['error' => t('api.invalid_secret')], 400);
}

if (!$expiry) {
    json_response(['error' => t('api.invalid_expiry')], 400);
}

$uuid = uuidv4();
db()->prepare('INSERT INTO secrets (uuid, expiry, value) VALUES (?, ?, ?)')
    ->execute([$uuid, $expiry->value, $secret]);

json_response([
    'uuid'      => $uuid,
    'expiresIn' => format_duration($expiry->value),
    'csrfToken' => regenerate_csrf_token(),
], 201);
