<?php

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src https://cdn.jsdelivr.net; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
    ]);
}

$pdo = new PDO('sqlite:/var/www/db/db.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('
    CREATE TABLE IF NOT EXISTS secrets (
        uuid      TEXT PRIMARY KEY,
        createdAt TEXT DEFAULT CURRENT_TIMESTAMP,
        expiry    INTEGER,
        value     TEXT
    ) STRICT
');

$pdo->exec('
    CREATE TABLE IF NOT EXISTS rate_limits (
        ip        TEXT PRIMARY KEY,
        attempts  INTEGER DEFAULT 1,
        window_start TEXT DEFAULT CURRENT_TIMESTAMP
    ) STRICT
');

cleanup_expired_secrets();

function cleanup_expired_secrets(): void
{
    global $pdo;

    $stmt = $pdo->prepare("
        DELETE FROM secrets
        WHERE (strftime('%s', 'now') - strftime('%s', createdAt)) > expiry
    ");
    $stmt->execute();

    $stmt = $pdo->prepare("
        DELETE FROM rate_limits
        WHERE (strftime('%s', 'now') - strftime('%s', window_start)) > 3600
    ");
    $stmt->execute();
}

const RATE_LIMIT_WINDOW = 300; // 5 minutes
const RATE_LIMIT_MAX_ATTEMPTS = 30;

function check_rate_limit(): bool
{
    global $pdo;

    $ip = get_remote_ip();

    $stmt = $pdo->prepare('SELECT attempts, window_start FROM rate_limits WHERE ip = ?');
    $stmt->execute([$ip]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        $window_start = strtotime($record['window_start']);
        $now = time();

        if (($now - $window_start) < RATE_LIMIT_WINDOW) {
            if ($record['attempts'] >= RATE_LIMIT_MAX_ATTEMPTS) {
                return false;
            }

            $stmt = $pdo->prepare('UPDATE rate_limits SET attempts = attempts + 1 WHERE ip = ?');
            $stmt->execute([$ip]);
        } else {
            $stmt = $pdo->prepare('UPDATE rate_limits SET attempts = 1, window_start = CURRENT_TIMESTAMP WHERE ip = ?');
            $stmt->execute([$ip]);
        }
    } else {
        $stmt = $pdo->prepare('INSERT INTO rate_limits (ip) VALUES (?)');
        $stmt->execute([$ip]);
    }

    return true;
}

function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function regenerate_csrf_token(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * @throws Exception
 */
function uuidv4(): string
{
    $data = random_bytes(16);
    assert(strlen($data) == 16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function is_valid_uuidv4(string $uuid): bool
{
    return preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $uuid) === 1;
}

function get_template(string $name): string
{
    return file_get_contents("/var/www/html/templates/$name.html");
}

function get_secret(string $uuid, bool $delete = false): mixed
{
    global $pdo;

    try {
        $pdo->exec('BEGIN IMMEDIATE');

        $stmt = $pdo->prepare('SELECT value, createdAt, expiry FROM secrets WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $secret = $stmt->fetch();

        if ($secret) {
            $createdAt = strtotime($secret['createdAt']);
            $expiry    = $secret['expiry'];
            $remaining = $createdAt + $expiry - date('U');
            $valid     = $remaining > 0;

            if ($delete || !$valid) {
                $stmt = $pdo->prepare('DELETE FROM secrets WHERE uuid = ?');
                $stmt->execute([$uuid]);
            }

            $pdo->exec('COMMIT');
            return $valid ? $secret : false;
        }

        $pdo->exec('COMMIT');
        return false;
    } catch (PDOException $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
}

function get_host(): string
{
    $host = $_SERVER['HTTP_HOST'];
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    } elseif (isset($_ENV['VIRTUAL_HOST'])) {
        $host = $_ENV['VIRTUAL_HOST'];
    }

    return $host;
}

function get_schema(): string
{
    $isSecure = false;
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $isSecure = true;
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        $isSecure = true;
    }

    return $isSecure ? 'https' : 'http';
}

function get_url(string $uri): string
{
    $host   = get_host();
    $schema = get_schema();

    return "$schema://$host/$uri";
}

function get_remaining_time($date, $expiry)
{
    $start_date   = strtotime($date);
    $expiry_time  = $start_date + $expiry;
    $current_time = time();
    $timediff     = $expiry_time - $current_time;

    $hours   = sprintf("%02d", floor($timediff / 3600));
    $minutes = sprintf("%02d", floor(($timediff % 3600) / 60));

    return $timediff > 0 ? "{$hours}h {$minutes}min" : false;
}

function get_remote_ip(): string
{
    $remote_ip = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $remote_ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }

    return $remote_ip;
}

function is_allowed(): bool
{
    $is_allowed = true;

    if (isset($_ENV['AUTH_IPS'])) {
        $auth_ips   = explode(',', $_ENV['AUTH_IPS']);
        $is_allowed = in_array(get_remote_ip(), $auth_ips);
    }

    return $is_allowed;
}

function show_rate_limit_error(): void
{
    http_response_code(429);
    echo get_template('header');
    echo '<h1>Too Many Requests</h1>';
    echo '<div class="alert alert-danger" role="alert">';
    echo 'You have made too many requests. Please wait a few minutes before trying again. ';
    echo '<a href="/">Go back</a>.';
    echo '</div>';
    echo get_template('footer');
    exit;
}
