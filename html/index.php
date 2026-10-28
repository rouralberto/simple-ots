<?php

declare(strict_types=1);

require_once 'common.php';

if (!check_rate_limit()) {
    render_error(429, t('error.rate_limit.title'), t('error.rate_limit.message'));
}

// Legacy direct-view links: send them to the reveal page. Browsers keep the #key fragment across redirects.
if (isset($_GET['uuid'])) {
    header('Location: /?read=' . (is_valid_uuidv4($_GET['uuid']) ? $_GET['uuid'] : ''), true, 302);
    exit;
}

if (isset($_GET['read'])) {
    $uuid   = $_GET['read'];
    $secret = is_valid_uuidv4($uuid) ? find_secret($uuid) : null;

    if (!$secret) {
        render_page(get_template('not-found'), 404);
    }

    render_page(get_template('read-secret', [
        'UUID'      => $uuid,
        'REMAINING' => format_duration($secret->remainingSeconds),
    ]));
}

if (!is_allowed()) {
    render_page(get_template('not-authorised'), 403);
}

render_page(get_template('new-secret', ['CSRF_TOKEN' => generate_csrf_token()]));
