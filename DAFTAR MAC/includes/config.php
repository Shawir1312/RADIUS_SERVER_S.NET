<?php
// ================================================================
// MAC Registration — Core Config & Auth
// ================================================================

// PHP 7.4 Polyfills
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $h, string $n): bool { return strncmp($h, $n, strlen($n)) === 0; }
}
if (!function_exists('str_contains')) {
    function str_contains(string $h, string $n): bool { return $n === '' || strpos($h, $n) !== false; }
}

define('APP_BASE', dirname(__DIR__));
define('DATA_FILE', APP_BASE . '/data/config.json');

date_default_timezone_set('Asia/Jayapura');
if (session_status() === PHP_SESSION_NONE) session_start();

// Load .env
$envFile = APP_BASE . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($val);
        }
    }
}

define('APP_NAME', $_ENV['APP_NAME'] ?? 'MAC Registration');

// ── Data Store (JSON) ────────────────────────────────────────────
function loadData(): array {
    if (!file_exists(DATA_FILE)) {
        return ['routers' => [], 'users' => []];
    }
    $data = json_decode(file_get_contents(DATA_FILE), true);
    return $data ?: ['routers' => [], 'users' => []];
}

function saveData(array $data): bool {
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

// ── Auth ─────────────────────────────────────────────────────────
function isLoggedIn(): bool { return !empty($_SESSION['mac_user']); }

function currentUser(): array {
    return $_SESSION['mac_user'] ?? [];
}

function isAdmin(): bool {
    return ($_SESSION['mac_user']['role'] ?? '') === 'admin';
}

function isTeknisi(): bool {
    return ($_SESSION['mac_user']['role'] ?? '') === 'teknisi';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

function doLogin(string $username, string $password): bool {
    $data = loadData();
    foreach ($data['users'] as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            $_SESSION['mac_user'] = [
                'username'  => $user['username'],
                'full_name' => $user['full_name'],
                'role'      => $user['role'],
            ];
            return true;
        }
    }
    return false;
}

function doLogout() {
    unset($_SESSION['mac_user']);
    session_destroy();
}

// ── Router Helpers ───────────────────────────────────────────────
function getRouters(): array {
    $data = loadData();
    return $data['routers'] ?? [];
}

function getRouterById(string $id): ?array {
    foreach (getRouters() as $r) {
        if ($r['id'] === $id) return $r;
    }
    return null;
}

function getSelectedRouter(): ?array {
    $id = $_SESSION['selected_router'] ?? '';
    if (!$id) {
        $routers = getRouters();
        if (!empty($routers)) {
            $_SESSION['selected_router'] = $routers[0]['id'];
            return $routers[0];
        }
        return null;
    }
    return getRouterById($id);
}

// ── Helpers ──────────────────────────────────────────────────────
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitizeMac(string $mac): string {
    $mac = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $mac));
    if (strlen($mac) !== 12) return '';
    return implode(':', str_split($mac, 2));
}

function isValidMac(string $mac): bool {
    return preg_match('/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/', $mac) === 1;
}

// Include MikroTik API
require_once __DIR__ . '/MikrotikAPI.php';

/**
 * Get connected MikroTik API instance for the selected router
 */
function getMikrotik(?array $router = null): ?MikrotikAPI {
    if (!$router) $router = getSelectedRouter();
    if (!$router) return null;

    $host = $router['host'];
    $port = (int)($router['port'] ?? 8728);
    
    // Extract port from host string if user typed "host:port"
    if (strpos($host, ':') !== false) {
        $parts = explode(':', $host, 2);
        $host = $parts[0];
        $port = (int)$parts[1];
    }

    $mt = new MikrotikAPI(
        $host,
        $port,
        $router['username'],
        $router['password'] ?? ''
    );
    if (!$mt->connect()) {
        return null;
    }
    return $mt;
}
