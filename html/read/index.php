<?php

declare(strict_types=1);

require_once '../common.php';

// Revealing deletes the secret, so it must be an explicit POST: link previews,
// crawlers and browser prefetching only issue GETs and can't burn the secret.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    json_response(['error' => 'Method not allowed.'], 405);
}

if (!check_rate_limit()) {
    json_response(['error' => 'Too many requests. Please wait a few minutes before trying again.'], 429);
}

$uuid   = json_decode(file_get_contents('php://input'), true)['uuid'] ?? null;
$secret = is_valid_uuidv4($uuid) ? take_secret($uuid) : null;

if (!$secret) {
    json_response(['error' => 'This secret has already been viewed or has expired.'], 404);
}

json_response(['secret' => $secret->value]);
