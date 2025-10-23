<?php
declare(strict_types=1);

class HomeController {
    public function __construct(private PDO $db) {}

    public function index(): void {
        if (function_exists('require_method')) {
            require_method('GET');
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        if (is_logged_in()) {
            $cu = current_user();
            $uid  = (string)($cu['id'] ?? '');
            $role = (string)($cu['role'] ?? '');

            if ($uid !== '') {
                try {
                    $st = $this->db->prepare('SELECT id, role, company_id, full_name, email FROM "User" WHERE id = ? LIMIT 1');
                    $st->execute([$uid]);
                    $row = $st->fetch(PDO::FETCH_ASSOC);

                    if (!$row) {
                        $_SESSION = [];
                        session_destroy();
                        redirect('auth/login');
                        return;
                    }

                    if (!empty($row['role']) && $row['role'] !== $role) {
                        $_SESSION['user']['role']       = $row['role'];
                        $_SESSION['user']['company_id'] = $row['company_id'] ?? null;
                        $_SESSION['user']['id']         = (string)$row['id'];
                    }

                    $role = (string)$row['role'];
                    if ($role === ROLE_COMPANY && empty($row['company_id'])) {
                        flash('err', 'Hesabınız bir firmaya bağlı değil.');
                        redirect('account/index');
                        return;
                    }
                } catch (Throwable) {
                }
            }

            switch ($role) {
                case ROLE_ADMIN:
                    redirect('admin/panel');
                    return;
                case ROLE_COMPANY:
                    redirect('company/panel');
                    return;
                default: 
                    break;
            }
        }

        render('home/index');
    }
}
