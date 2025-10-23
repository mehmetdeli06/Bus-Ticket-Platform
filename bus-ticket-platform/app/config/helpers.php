<?php
declare(strict_types=1);


function e(mixed $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return true;
  
    $proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
    return $proto === 'https';
}

function require_https_or_redirect(): void {
    if (!is_https()) {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: https://' . $host . $uri, true, 301);
        exit;
    }
}


function current_route(): string {
    $r = $_GET['r'] ?? 'home/index';
  
    if (!preg_match('#^[A-Za-z0-9/_-]+$#', $r)) {
        return 'home/index';
    }
    return $r;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user']);
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function current_user_id(): ?string {
    return isset($_SESSION['user']['id']) ? (string)$_SESSION['user']['id'] : null;
}

function require_login(): void {
    if (is_logged_in()) return;
    if (current_route() !== 'auth/login') {
        redirect('auth/login');
    }
}


function has_role(string $role): bool {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === $role;
}


function has_any_role(array $roles): bool {
    $r = $_SESSION['user']['role'] ?? null;
    return $r !== null && in_array($r, $roles, true);
}


function require_role(string|array $roles): void {
    if (!is_logged_in()) {
        if (current_route() !== 'auth/login') {
            redirect('auth/login');
        }
        return;
    }
    $ok = is_string($roles) ? has_role($roles) : has_any_role($roles);
    if (!$ok) {
        http_response_code(403);
        exit('403 - Yetkisiz erişim');
    }
}


function render(string $view, array $data = []): void {
    extract($data, EXTR_SKIP);
    $viewFile   = BASE_PATH . '/app/views/' . $view . '.php';
    $layoutFile = BASE_PATH . '/app/views/layout.php';

    if (!file_exists($viewFile)) {
        http_response_code(404);
        echo "View bulunamadı: " . e($view);
        return;
    }

    ob_start();
    include $viewFile;
    $content = ob_get_clean();
    include $layoutFile;
}

function redirect(string $route): void {

    $r = preg_replace("/[\r\n]+/", '', $route); 
    if (!preg_match('#^[A-Za-z0-9/_-]+$#', $r)) {
        $r = 'home/index';
    }
    header('Location: /?r=' . $r, true, 302);
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string {
    $t = csrf_token();
    return '<input type="hidden" name="_csrf" value="' . e($t) . '">';
}

function csrf_verify_or_die(): void {
    $ok = isset($_POST['_csrf'], $_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], (string)$_POST['_csrf']);
    if (!$ok) {
        http_response_code(419);
        exit('CSRF doğrulama başarısız.');
    }
}

function flash(string $key, string $message): void {
    $_SESSION['_flash'][$key] = $message;
}
function flash_get(string $key): ?string {
    if (empty($_SESSION['_flash'][$key])) return null;
    $msg = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

function now_tr(): DateTimeImmutable {
    return new DateTimeImmutable('now', new DateTimeZone('Europe/Istanbul'));
}
function now_str_tr(): string {
    return now_tr()->format('Y-m-d H:i:s'); 
}

function parse_dt_tr(string $s): ?DateTimeImmutable {
    $s = str_replace('T', ' ', trim($s));
    foreach (['Y-m-d H:i:s', 'Y-m-d H:i'] as $fmt) {
        $dt = DateTimeImmutable::createFromFormat($fmt, $s, new DateTimeZone('Europe/Istanbul'));
        if ($dt !== false) return $dt;
    }
    return null;
}

function is_past_departure(string $departure): bool {
    $dt = parse_dt_tr($departure);
    return $dt ? (now_tr() >= $dt) : false;
}

function assertTripNotDeparted(array $trip): void {
    $dt = isset($trip['departure_time']) ? parse_dt_tr((string)$trip['departure_time']) : null;
    if (!$dt) {
        http_response_code(400);
        flash('err', 'Sefer tarihi geçersiz.');
        redirect('home/index');
    }
    if (now_tr() >= $dt) {
        http_response_code(400);
        flash('err', 'Kalkış saati geçmiş bir sefer için işlem yapılamaz.');
        redirect('home/index');
    }
}

function require_user(): void {
    require_role(ROLE_USER);
}

function can_download_ticket_pdf(PDO $db, string $ticketId): bool {
    if (!is_logged_in()) return false;
    $u = current_user();
    if (($u['role'] ?? null) !== ROLE_USER) return false;

    $q = $db->prepare("
        SELECT 1
          FROM Tickets
         WHERE id = ? AND user_id = ? AND status = 'active'
         LIMIT 1
    ");
    $q->execute([$ticketId, $u['id']]);
    return (bool)$q->fetchColumn();
}


function hash_password(string $plain): string {
 
    if (defined('PASSWORD_ARGON2ID')) {
        return password_hash($plain, PASSWORD_ARGON2ID);
    }
    return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
}
function verify_password(string $plain, string $hash): bool {
    return password_verify($plain, $hash);
}

function syncExpiredTickets(PDO $db): int {
    $now = now_str_tr();
    $q = $db->prepare("
        UPDATE Tickets
           SET status = 'expired'
         WHERE status = 'active'
           AND departure_time IS NOT NULL
           AND departure_time < :now
    ");
    $q->execute([':now' => $now]);
    return $q->rowCount();
}


function pdf_txt(string $s): string {
    $out = @iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $s);
    return $out !== false ? $out : $s;
}

function client_ip(): string {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (str_contains($ip, ',')) { $ip = trim(explode(',', $ip)[0]); }
    return (string)$ip;
}

function rate_limit_ensure_table(PDO $db): void {
    static $done = false;
    if ($done) return;
    $db->exec("
        CREATE TABLE IF NOT EXISTS Rate_Limits (
            key TEXT NOT NULL,
            ip TEXT NOT NULL,
            window_start INTEGER NOT NULL,
            count INTEGER NOT NULL,
            PRIMARY KEY (key, ip)
        )
    ");
    $done = true;
}

function rate_limit_check(string $key, int $limit = 10, int $perSeconds = 60): bool {
    /** @var PDO $pdo */
    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo instanceof PDO) {
        return true; 
    }

    rate_limit_ensure_table($pdo);

    $ip = client_ip();
    $now = time();

    $pdo->beginTransaction();
    try {
        $sel = $pdo->prepare("SELECT window_start, count FROM Rate_Limits WHERE key = ? AND ip = ? LIMIT 1");
        $sel->execute([$key, $ip]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $ins = $pdo->prepare("INSERT INTO Rate_Limits (key, ip, window_start, count) VALUES (?, ?, ?, 1)");
            $ins->execute([$key, $ip, $now]);
            $pdo->commit();
            return true;
        }

        $windowStart = (int)$row['window_start'];
        $count       = (int)$row['count'];

        if ($windowStart + $perSeconds <= $now) {
            $upd = $pdo->prepare("UPDATE Rate_Limits SET window_start = ?, count = 1 WHERE key = ? AND ip = ?");
            $upd->execute([$now, $key, $ip]);
            $pdo->commit();
            return true;
        }

        if ($count < $limit) {
            $upd = $pdo->prepare("UPDATE Rate_Limits SET count = count + 1 WHERE key = ? AND ip = ?");
            $upd->execute([$key, $ip]);
            $pdo->commit();
            return true;
        }

        $pdo->commit();
        return false;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return true; 
    }
}


function require_method(string|array $methods): void {
    $req = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $allow = array_map('strtoupper', (array)$methods);
    if (!in_array($req, $allow, true)) {
        header('Allow: '.implode(', ', $allow));
        http_response_code(405);
        exit('405 Method Not Allowed');
    }
}

if (!function_exists('csrf_verify_or_die')) {
    function csrf_verify_or_die(): void {
        if (!function_exists('csrf_verify_or_die')) {
            http_response_code(419);
            exit('CSRF doğrulama fonksiyonu eksik.');
        }
        csrf_verify_or_die();
    }
}

if (!function_exists('audit')) {
    function audit(string $action, array $meta = []): void {
        try {
            /** @var PDO|null $pdo */
            $pdo = $GLOBALS['pdo'] ?? null;
            if (!$pdo instanceof PDO) return;

            $pdo->exec('CREATE TABLE IF NOT EXISTS Audit_Log (
                id TEXT PRIMARY KEY,
                at TEXT NOT NULL,
                user_id TEXT,
                action TEXT NOT NULL,
                meta TEXT
            )');

            $id = bin2hex(random_bytes(16));
            $at = (new DateTimeImmutable('now', new DateTimeZone('Europe/Istanbul')))->format('Y-m-d H:i:s');
            $uid = $_SESSION['user']['id'] ?? null;

            $stmt = $pdo->prepare('INSERT INTO Audit_Log (id, at, user_id, action, meta) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$id, $at, $uid, $action, json_encode($meta, JSON_UNESCAPED_UNICODE)]);
        } catch (Throwable) {
        }
    }
}

if (!function_exists('expire_past_tickets')) {
    function expire_past_tickets(PDO $db): void {
        $now = (new DateTimeImmutable('now', new DateTimeZone('Europe/Istanbul')))->format('Y-m-d H:i:s');
        $q = $db->prepare("
            UPDATE Tickets
               SET status = 'expired'
             WHERE status = 'active'
               AND trip_id IN (
                   SELECT id FROM Trips
                    WHERE DATETIME(departure_time) <= DATETIME(:now)
               )
        ");
        $q->execute([':now' => $now]);
    }
}
