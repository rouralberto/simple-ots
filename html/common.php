<?php

declare(strict_types=1);

header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; font-src https://cdn.jsdelivr.net; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("Cache-Control: no-store");
header("Vary: Accept-Language, Cookie");

const RATE_LIMIT_WINDOW = 300; // 5 minutes
const RATE_LIMIT_MAX_ATTEMPTS = 30;
const MAX_SECRET_LENGTH = 131072; // Base64 ciphertext, roughly 96KB of plaintext
const LOCALES = ['en', 'es', 'zh']; // Each has a translation file in lang/; the first is the fallback

enum Expiry: int
{
    case Hour = 3600;
    case Day  = 86400;
    case Week = 604800;
}

final class Secret
{
    public int $remainingSeconds {
        get => $this->expiresAt - time();
    }

    public function __construct(
        public readonly string $value,
        public readonly int $expiresAt,
    ) {
    }
}

function db(): Pdo\Sqlite
{
    static $db = open_database();
    return $db;
}

function open_database(): Pdo\Sqlite
{
    $db = new Pdo\Sqlite('sqlite:' . __DIR__ . '/../db/db.sqlite', options: [
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $db->exec('
        CREATE TABLE IF NOT EXISTS secrets (
            uuid      TEXT PRIMARY KEY,
            createdAt TEXT DEFAULT CURRENT_TIMESTAMP,
            expiry    INTEGER,
            value     TEXT
        ) STRICT
    ');
    $db->exec('
        CREATE TABLE IF NOT EXISTS rate_limits (
            ip        TEXT PRIMARY KEY,
            attempts  INTEGER DEFAULT 1,
            window_start TEXT DEFAULT CURRENT_TIMESTAMP
        ) STRICT
    ');
    return $db;
}

cleanup_expired_secrets();

function cleanup_expired_secrets(): void
{
    db()->exec('DELETE FROM secrets WHERE unixepoch() >= unixepoch(createdAt) + expiry');
    db()->exec('DELETE FROM rate_limits WHERE unixepoch() - unixepoch(window_start) > 3600');
}

/**
 * Counts this request against the client's window in a single atomic upsert,
 * so concurrent requests can't slip past the limit.
 */
#[\NoDiscard('a request over the rate limit must be rejected')]
function check_rate_limit(): bool
{
    $stmt = db()->prepare('
        INSERT INTO rate_limits (ip) VALUES (:ip)
        ON CONFLICT (ip) DO UPDATE SET
            attempts     = IIF(unixepoch() - unixepoch(window_start) < :window, attempts + 1, 1),
            window_start = IIF(unixepoch() - unixepoch(window_start) < :window, window_start, CURRENT_TIMESTAMP)
        RETURNING attempts
    ');
    $stmt->bindValue('ip', get_remote_ip());
    // Bound explicitly as an integer: SQLite compares any number as less than a TEXT value.
    $stmt->bindValue('window', RATE_LIMIT_WINDOW, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchColumn() <= RATE_LIMIT_MAX_ATTEMPTS;
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => is_https(),
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true,
        ]);
    }
}

function generate_csrf_token(): string
{
    start_session();
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

#[\NoDiscard('a request with an invalid CSRF token must be rejected')]
function validate_csrf_token(#[\SensitiveParameter] ?string $token): bool
{
    start_session();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function regenerate_csrf_token(): string
{
    start_session();
    return $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function uuidv4(): string
{
    $data = random_bytes(16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function is_valid_uuidv4(mixed $uuid): bool
{
    return is_string($uuid) && preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $uuid) === 1;
}

/**
 * Ciphertext must be Base64 holding at least a 12-byte IV and a 16-byte GCM tag.
 * Rejecting anything else guarantees plaintext is never stored by mistake.
 */
function is_valid_ciphertext(mixed $value): bool
{
    if (!is_string($value) || strlen($value) > MAX_SECRET_LENGTH) {
        return false;
    }
    $decoded = base64_decode($value, true);
    return $decoded !== false && strlen($decoded) > 28;
}

function locale(): string
{
    static $locale = detect_locale();
    return $locale;
}

/**
 * The language picked in the navbar (the "lang" cookie), otherwise the supported language the
 * browser prefers most, from its Accept-Language header (e.g. "es-ES,es;q=0.9,en;q=0.8").
 * Regional variants match their base language.
 */
function detect_locale(): string
{
    $chosen = $_COOKIE['lang'] ?? null;
    if (in_array($chosen, LOCALES, true)) {
        return $chosen;
    }

    $preferences = [];
    foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') as $entry) {
        if (!preg_match('/^\s*([a-z]{2,3})(?:[-_][\w-]*)?\s*(?:;\s*q\s*=\s*([\d.]+))?/i', $entry, $match)) {
            continue;
        }
        $locale  = strtolower($match[1]);
        $quality = isset($match[2]) ? (float)$match[2] : 1.0;
        if (in_array($locale, LOCALES, true) && $quality > 0) {
            $preferences[$locale] = max($preferences[$locale] ?? 0, $quality);
        }
    }
    // The sort is stable, so equally weighted languages keep the browser's order.
    arsort($preferences);

    return array_key_first($preferences) ?? LOCALES[0];
}

function translations(): array
{
    static $strings = require __DIR__ . '/lang/' . locale() . '.php';
    return $strings;
}

/**
 * A UI string in the visitor's language, with its {{NAME}} placeholders filled in.
 */
function t(string $key, array $vars = []): string
{
    $placeholders = array_map(fn(string $name) => '{{' . $name . '}}', array_keys($vars));
    return strtr(translations()[$key] ?? $key, array_combine($placeholders, $vars));
}

function get_template(string $name, array $vars = []): string
{
    $placeholders = array_map(fn(string $key) => '{{' . $key . '}}', array_keys($vars));
    $values       = array_map(htmlspecialchars(...), $vars);

    // Translations are trusted markup and may hold {{NAME}} placeholders themselves, so they go in first.
    return file_get_contents(__DIR__ . "/templates/$name.html")
        |> (fn(string $html) => preg_replace_callback('/\{\{t:([\w.]+)\}\}/', fn(array $match) => t($match[1]), $html))
        |> (fn(string $html) => strtr($html, array_combine($placeholders, $values)));
}

/**
 * A static file's URL tagged with a hash of its contents, so browsers and CDNs that cache it
 * for a long time still fetch the new version as soon as it is deployed.
 */
function asset_url(string $path): string
{
    return "/$path?v=" . hash_file('xxh3', __DIR__ . "/$path");
}

function render_page(string $body, int $status = 200): never
{
    $js_strings = array_filter(translations(), fn(string $key) => str_starts_with($key, 'js.'), ARRAY_FILTER_USE_KEY);

    http_response_code($status);
    echo get_template('header', [
        'LOCALE'    => locale(),
        'I18N'      => json_encode($js_strings, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        'CRYPTO_JS' => asset_url('crypto.js'),
        'APP_JS'    => asset_url('app.js'),
    ]);
    echo $body;
    echo get_template('footer');
    exit;
}

function render_error(int $status, string $title, string $message): never
{
    render_page(get_template('error', ['TITLE' => $title, 'MESSAGE' => $message]), $status);
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

/**
 * Look up a live secret without consuming it.
 */
function find_secret(string $uuid): ?Secret
{
    $stmt = db()->prepare('
        SELECT value, unixepoch(createdAt) + expiry AS expiresAt
        FROM secrets
        WHERE uuid = ? AND unixepoch() < unixepoch(createdAt) + expiry
    ');
    $stmt->execute([$uuid]);
    $row = $stmt->fetch();

    return $row ? new Secret(...$row) : null;
}

/**
 * Atomically read and delete a live secret: a single DELETE … RETURNING
 * means two concurrent requests can never both receive it.
 */
#[\NoDiscard('the secret is deleted by this call, so its value must be used')]
function take_secret(string $uuid): ?Secret
{
    $stmt = db()->prepare('
        DELETE FROM secrets
        WHERE uuid = ? AND unixepoch() < unixepoch(createdAt) + expiry
        RETURNING value, unixepoch(createdAt) + expiry AS expiresAt
    ');
    $stmt->execute([$uuid]);
    $row = array_first($stmt->fetchAll());

    return $row ? new Secret(...$row) : null;
}

function format_duration(int $seconds): string
{
    $days    = intdiv($seconds, 86400);
    $hours   = intdiv($seconds % 86400, 3600);
    $minutes = intdiv($seconds % 3600, 60);

    $vars    = ['D' => $days, 'H' => $hours, 'M' => max(1, $minutes)];

    if ($days > 0) {
        return t($hours > 0 ? 'duration.days_hours' : 'duration.days', $vars);
    }
    if ($hours > 0) {
        return t($minutes > 0 ? 'duration.hours_minutes' : 'duration.hours', $vars);
    }
    return t('duration.minutes', $vars);
}

function is_https(): bool
{
    return ($_SERVER['HTTPS'] ?? '') === 'on'
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on';
}

/**
 * Read a comma-separated environment variable as a list of trimmed, non-empty values.
 */
function get_env_list(string $name): array
{
    return (string)getenv($name)
        |> (fn(string $value) => explode(',', $value))
        |> (fn(array $items) => array_map(trim(...), $items))
        |> array_filter(...)
        |> array_values(...);
}

/**
 * Check whether an IP matches any entry in a list of IPs or CIDR ranges (IPv4 and IPv6).
 */
function ip_matches(string $ip, array $ranges): bool
{
    return filter_var($ip, FILTER_VALIDATE_IP) !== false
        && array_any($ranges, fn(string $range) => ip_in_range(inet_pton($ip), $range));
}

function ip_in_range(string $ip_bin, string $range): bool
{
    [$subnet, $bits] = array_pad(explode('/', $range, 2), 2, null);
    if (filter_var($subnet, FILTER_VALIDATE_IP) === false) {
        return false;
    }

    $subnet_bin = inet_pton($subnet);
    if (strlen($subnet_bin) !== strlen($ip_bin)) {
        return false;
    }

    $bits      = $bits === null ? strlen($ip_bin) * 8 : (int)$bits;
    $bytes     = intdiv($bits, 8);
    $remainder = $bits % 8;

    if (substr($ip_bin, 0, $bytes) !== substr($subnet_bin, 0, $bytes)) {
        return false;
    }
    if ($remainder === 0) {
        return true;
    }

    $mask = 0xff << (8 - $remainder) & 0xff;
    return (ord($ip_bin[$bytes]) & $mask) === (ord($subnet_bin[$bytes]) & $mask);
}

/**
 * The client IP. X-Forwarded-For is only honoured when the request comes from a proxy listed
 * in TRUSTED_PROXIES, otherwise anyone could spoof it to bypass AUTH_IPS and rate limiting.
 * The proxy chain is walked from the nearest hop outwards: the first untrusted hop is the client.
 */
function get_remote_ip(): string
{
    $remote_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $trusted   = get_env_list('TRUSTED_PROXIES');

    if (!$trusted || !ip_matches($remote_ip, $trusted) || empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $remote_ip;
    }

    $chain = $_SERVER['HTTP_X_FORWARDED_FOR']
        |> (fn(string $header) => explode(',', $header))
        |> (fn(array $hops) => array_map(trim(...), $hops))
        |> array_reverse(...)
        |> (fn(array $hops) => [$remote_ip, ...$hops]);

    $client = array_find_key($chain, fn(string $hop) => !ip_matches($hop, $trusted));
    if ($client === null) {
        return array_last($chain);
    }

    // A malformed hop can't be the client; fall back to the last proxy that vouched for it.
    return filter_var($chain[$client], FILTER_VALIDATE_IP) !== false ? $chain[$client] : $chain[$client - 1];
}

#[\NoDiscard('a client that is not allowed must be rejected')]
function is_allowed(): bool
{
    $auth_ips = get_env_list('AUTH_IPS');

    return !$auth_ips || ip_matches(get_remote_ip(), $auth_ips);
}
