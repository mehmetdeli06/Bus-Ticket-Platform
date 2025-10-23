<?php
declare(strict_types=1);

class AccountController {
    public function __construct(private PDO $db) {}

    public function index(): void {
        require_login();

        if (has_role(ROLE_ADMIN)) {
            http_response_code(403);
            exit('403 - Yetkisiz erişim');
        }

        $cu = current_user();
        if (!$cu || ($cu['id'] ?? '') === '') {
            http_response_code(401);
            redirect('auth/login');
        }

        $u = $this->db->prepare('
            SELECT id, full_name, email, role, company_id, balance, created_at
            FROM "User"
            WHERE id = ?
            LIMIT 1
        ');
        $u->execute([$cu['id']]);
        $me = $u->fetch(PDO::FETCH_ASSOC);

        if (!$me) {
            flash('err', 'Hesap bulunamadı (ID uyumsuzluğu olabilir). Lütfen tekrar giriş yapın.');
            session_destroy();
            redirect('auth/login');
        }

        $tickets = [];
        if (has_role(ROLE_USER)) {
            $q = $this->db->prepare('
                SELECT
                    tk.id AS ticket_id,
                    tk.status,
                    tk.total_price,
                    tk.created_at,
                    t.departure_city,
                    t.destination_city,
                    t.departure_time,
                    t.arrival_time,
                    b.name AS company_name
                FROM Tickets tk
                JOIN Trips t       ON t.id = tk.trip_id
                JOIN Bus_Company b ON b.id = t.company_id
                WHERE tk.user_id = ?
                ORDER BY tk.created_at DESC
            ');
            $q->execute([$cu['id']]); 
            $tickets = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $company = null;
        if (has_role(ROLE_COMPANY) && !empty($me['company_id'])) {
            $c = $this->db->prepare('SELECT id, name FROM Bus_Company WHERE id = ? LIMIT 1');
            $c->execute([$me['company_id']]); 
            $company = $c->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        render('account/index', [
            'me'       => $me,
            'tickets'  => $tickets,
            'company'  => $company,
        ]);
    }

    public function update(): void {
        require_login();
        csrf_verify_or_die();

        $cu = current_user();
        if (!$cu || ($cu['id'] ?? '') === '') {
            http_response_code(401);
            redirect('auth/login');
        }

        $nameRaw  = trim((string)($_POST['full_name'] ?? ''));
        $emailRaw = trim((string)($_POST['email'] ?? ''));
        $passRaw  = (string)($_POST['new_password'] ?? '');

        $name = mb_substr($nameRaw, 0, 120);
        if (mb_strlen($name) < 2) {
            flash('err', 'Ad en az 2 karakter olmalı.');
            redirect('account/index');
        }

        $emailNorm = mb_strtolower($emailRaw);
        if (!filter_var($emailNorm, FILTER_VALIDATE_EMAIL) || mb_strlen($emailNorm) > 190) {
            flash('err', 'Geçerli bir e-posta giriniz.');
            redirect('account/index');
        }

        $chk = $this->db->prepare('SELECT 1 FROM "User" WHERE LOWER(email) = ? AND id <> ? LIMIT 1');
        $chk->execute([$emailNorm, $cu['id']]);
        if ($chk->fetch()) {
            flash('err', 'Bu e-posta başka bir hesapta kullanılıyor.');
            redirect('account/index');
        }

        $doPassword = ($passRaw !== '');
        if ($doPassword) {
            if (mb_strlen($passRaw) < 8) {
                flash('err', 'Yeni parola en az 8 karakter olmalı.');
                redirect('account/index');
            }
            $hash = hash_password($passRaw);
        }

        if ($doPassword) {
            $q = $this->db->prepare('UPDATE "User" SET full_name = ?, email = ?, password = ? WHERE id = ?');
            $q->execute([$name, $emailNorm, $hash, $cu['id']]);
        } else {
            $q = $this->db->prepare('UPDATE "User" SET full_name = ?, email = ? WHERE id = ?');
            $q->execute([$name, $emailNorm, $cu['id']]);
        }

        $_SESSION['user']['full_name'] = $name;
        $_SESSION['user']['name']      = $name;
        $_SESSION['user']['email']     = $emailNorm;

        flash('ok', 'Profil güncellendi.');
        redirect('account/index');
    }

    public function topup(): void {
        require_login();
        require_role(ROLE_USER);
        csrf_verify_or_die();

        $cu = current_user();
        if (!$cu || ($cu['id'] ?? '') === '') {
            http_response_code(401);
            redirect('auth/login');
        }

        $amount = (int)($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            flash('err', 'Geçerli bir tutar girin.');
            redirect('account/index');
        }
        $MAX = 10000;
        if ($amount > $MAX) {
            flash('err', "Maksimum yükleme tutarı {$MAX}₺.");
            redirect('account/index');
        }

        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare('UPDATE "User" SET balance = balance + ? WHERE id = ?');
            $q->execute([$amount, $cu['id']]); 
            if ($q->rowCount() !== 1) {
                throw new RuntimeException('Bakiye güncellenemedi.');
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            flash('err', 'İşlem sırasında bir hata oluştu.');
            redirect('account/index');
        }

        flash('ok', "Bakiye +{$amount}₺ eklendi.");
        redirect('account/index');
    }
}
