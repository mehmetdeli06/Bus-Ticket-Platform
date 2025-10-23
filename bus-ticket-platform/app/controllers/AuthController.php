<?php
declare(strict_types=1);

class AuthController {
    public function __construct(private PDO $db) {}

    public function login(): void {
        if (is_logged_in()) {
            $this->redirectByRole(current_user()['role'] ?? ROLE_USER);
            return;
        }
        render('auth/login');
    }

    public function loginPost(): void {
        csrf_verify_or_die();
       
        $emailParam = mb_strtolower((string)($_POST['email'] ?? ''));
        if (function_exists('rate_limit_check')) {
            if (!rate_limit_check('login|' . $emailParam, 5, 300)) {
                render('auth/login', ['error' => 'Çok fazla deneme. Lütfen biraz sonra tekrar deneyin.']);
                return;
            }
            if (!rate_limit_check('login', 10, 60)) {
                render('auth/login', ['error' => 'Çok fazla deneme. Lütfen biraz sonra tekrar deneyin.']);
                return;
            }
        }

        $emailRaw = trim((string)($_POST['email'] ?? ''));
        $passRaw  = (string)($_POST['password'] ?? '');

        $email = mb_strtolower($emailRaw);
        if ($email === '' || $passRaw === '') {
            render('auth/login', ['error' => 'E-posta ve şifre zorunlu.']);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            render('auth/login', ['error' => 'Geçerli bir e-posta giriniz.']);
            return;
        }

        $stmt = $this->db->prepare('
            SELECT 
                id,
                full_name AS name,
                email,
                password  AS password_hash,
                role,
                company_id
            FROM "User"
            WHERE LOWER(email) = ?
            LIMIT 1
        ');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !verify_password($passRaw, (string)$user['password_hash'])) {
            render('auth/login', ['error' => 'Geçersiz kimlik bilgileri.']);
            return;
        }


        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'         => (string)$user['id'],
            'name'       => (string)$user['name'],
            'full_name'  => (string)$user['name'],
            'email'      => (string)$user['email'],
            'role'       => (string)$user['role'],    
            'company_id' => $user['company_id'] ?? null,
        ];

        $this->redirectByRole($_SESSION['user']['role']);
    }

    public function register(): void {
        if (is_logged_in()) {
            $this->redirectByRole(current_user()['role'] ?? ROLE_USER);
            return;
        }
        render('auth/register');
    }

    public function registerPost(): void {
        csrf_verify_or_die();

        $emailParam = mb_strtolower((string)($_POST['email'] ?? ''));
        if (function_exists('rate_limit_check')) {
            if (!rate_limit_check('register|' . $emailParam, 3, 300)) {
                render('auth/register', ['error' => 'Çok fazla istek. Lütfen biraz sonra tekrar deneyin.']);
                return;
            }
            if (!rate_limit_check('register', 5, 60)) {
                render('auth/register', ['error' => 'Çok fazla istek. Lütfen biraz sonra tekrar deneyin.']);
                return;
            }
        }

        $nameRaw  = trim((string)($_POST['name'] ?? ''));
        $emailRaw = trim((string)($_POST['email'] ?? ''));
        $pass1    = (string)($_POST['password'] ?? '');
        $pass2    = (string)($_POST['password2'] ?? '');

        $name  = mb_substr($nameRaw, 0, 120);
        $email = mb_strtolower($emailRaw);

        if ($name === '' || $email === '' || $pass1 === '' || $pass2 === '') {
            render('auth/register', ['error' => 'Tüm alanlar zorunlu.']);
            return;
        }
        if (mb_strlen($name) < 2) {
            render('auth/register', ['error' => 'Ad en az 2 karakter olmalı.']);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            render('auth/register', ['error' => 'Geçerli bir e-posta giriniz.']);
            return;
        }
        if ($pass1 !== $pass2) {
            render('auth/register', ['error' => 'Parolalar uyuşmuyor.']);
            return;
        }
        if (mb_strlen($pass1) < 8) {
            render('auth/register', ['error' => 'Parola en az 8 karakter olmalı.']);
            return;
        }

        $check = $this->db->prepare('SELECT 1 FROM "User" WHERE LOWER(email) = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            render('auth/register', ['error' => 'Bu e-posta zaten kayıtlı.']);
            return;
        }

        $id   = bin2hex(random_bytes(16));
        $hash = hash_password($pass1); 

        $ins = $this->db->prepare('
            INSERT INTO "User" (id, full_name, email, role, password, company_id, balance, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 0, ?)
        ');
        $ins->execute([$id, $name, $email, ROLE_USER, $hash, null, now_str_tr()]);

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'         => (string)$id,
            'name'       => $name,
            'full_name'  => $name,
            'email'      => $email,
            'role'       => ROLE_USER,
            'company_id' => null,
        ];

        $this->redirectByRole(ROLE_USER);
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                true
            );
        }
        session_destroy();
        redirect('home/index');
    }

    private function redirectByRole(string $role): void {
        switch ($role) {
            case ROLE_ADMIN:
                redirect('admin/panel');
                break;
            case ROLE_COMPANY:
                redirect('company/panel');
                break;
            default: 
                redirect('home/index');
                break;
        }
    }
}
