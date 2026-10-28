<?php

declare(strict_types=1);

require_once '../common.php';

// Revealing deletes the secret, so it must be an explicit POST: link previews,
// crawlers and browser prefetching only issue GETs and can't burn the secret.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    json_response(['error' => t('api.method_not_allowed')], 405);
}

if (!check_rate_limit()) {
    json_response(['error' => t('api.rate_limit')], 429);
}

$uuid   = json_decode(file_get_contents('php://input'), true)['uuid'] ?? null;
$secret = is_valid_uuidv4($uuid) ? take_secret($uuid) : null;

if (!$secret) {
    json_response(['error' => t('api.secret_gone')], 404);
}

json_response(['secret' => $secret->value]);
