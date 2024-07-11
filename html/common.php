<?php

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

        return $valid ? $secret : false;
    }

    return false;
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
        $remote_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
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

echo get_template('header');
