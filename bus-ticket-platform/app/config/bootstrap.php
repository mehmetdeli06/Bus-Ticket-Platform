<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,   
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$__now = time();
if (!empty($_SESSION['__last_touch']) && ($__now - (int)$_SESSION['__last_touch']) > 300) {
    $_SESSION = [];
    session_destroy();
    redirect('auth/login');
}
$_SESSION['__last_touch'] = $__now;


if (!isset($GLOBALS['CSP_NONCE'])) {
    $GLOBALS['CSP_NONCE'] = base64_encode(random_bytes(16));
}
$cspNonce = $GLOBALS['CSP_NONCE'];

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
//header("Referrer-Policy: no-referrer-when-downgrade");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
header("Cross-Origin-Opener-Policy: same-origin");
header("Cross-Origin-Resource-Policy: same-origin");


$cdn = "https://cdn.jsdelivr.net";


$csp = [
    "default-src 'self' data: blob:",
    "script-src 'self' {$cdn} 'nonce-{$cspNonce}'",
    "style-src  'self' {$cdn} 'unsafe-inline'",
    "img-src 'self' data:",
    "font-src 'self' {$cdn} data:",
    "connect-src 'self'",
    "frame-ancestors 'none'",
    "object-src 'none'",
    "base-uri 'self'",
    "form-action 'self'",
];
header("Content-Security-Policy: " . implode('; ', $csp));

try {
    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $pdo->exec("PRAGMA foreign_keys = ON;");
    $pdo->exec("PRAGMA journal_mode = WAL;");
    $pdo->exec("PRAGMA synchronous = NORMAL;");
    $pdo->exec("PRAGMA busy_timeout = 5000;");


    $GLOBALS['pdo'] = $pdo;
} catch (PDOException $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die("Veritabanı bağlantı hatası: " . $e->getMessage());
    }
    http_response_code(500);
    exit('Veritabanı hatası.');
}



if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', BASE_PATH . '/app/lib/font/');
}
require_once BASE_PATH . '/app/lib/fpdf.php';


require_once BASE_PATH . '/app/controllers/HomeController.php';
require_once BASE_PATH . '/app/controllers/AuthController.php';
require_once BASE_PATH . '/app/controllers/TripController.php';
require_once BASE_PATH . '/app/controllers/TicketController.php';
require_once BASE_PATH . '/app/controllers/AdminController.php';
require_once BASE_PATH . '/app/controllers/AccountController.php';


