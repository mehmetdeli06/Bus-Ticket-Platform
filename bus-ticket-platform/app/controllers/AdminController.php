<?php
declare(strict_types=1);

class AdminController {
    public function __construct(private PDO $db) {
        $this->db->exec("PRAGMA foreign_keys = ON;");
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function requireAdmin(): void { require_role(ROLE_ADMIN); }

    private function in(array $src, string $key, ?callable $filter = null, mixed $default = null): mixed {
        $v = $src[$key] ?? $default;
        if ($filter && $v !== null) { $v = $filter($v); }
        return $v;
    }

  
    private function assertDateTime(string $s, string $name = 'datetime'): void {
        $sNorm = str_replace('T', ' ', trim($s));
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $sNorm, new DateTimeZone('Europe/Istanbul'));
        if (!$dt) {
            http_response_code(400);
            exit("$name formatı geçersiz. Beklenen: YYYY-MM-DD HH:MM");
        }
    }

    private function newId(): string { return bin2hex(random_bytes(16)); }

    private function nowStr(): string { return now_str_tr(); } 

    private function emailNorm(string $e): string { return mb_strtolower(trim($e)); }

    private function isHexId(string $s): bool {
        return (bool)preg_match('/^[a-f0-9]{32}$/i', $s);
    }

    /*  Admin Panel  */
    public function panel(): void {
        $this->requireAdmin();

        $stats = [
            'companies' => (int)$this->db->query("SELECT COUNT(*) c FROM Bus_Company")->fetch()['c'],
            'admins'    => (int)$this->db->query("SELECT COUNT(*) c FROM \"User\" WHERE role='company'")->fetch()['c'],
            'coupons'   => (int)$this->db->query("SELECT COUNT(*) c FROM Coupons")->fetch()['c'],
            'trips'     => (int)$this->db->query("SELECT COUNT(*) c FROM Trips")->fetch()['c'],
        ];
        render('admin/panel', ['stats'=>$stats]);
    }

    /* Hesabım / Genel Bakış  */
    public function account(): void {
        $this->requireAdmin();

        $me = current_user() ?: [];

        $stats = [];
        $stats['users']     = (int)$this->db->query('SELECT COUNT(*) FROM "User"')->fetchColumn();
        $stats['companies'] = (int)$this->db->query('SELECT COUNT(*) FROM Bus_Company')->fetchColumn();

        $now = $this->nowStr();
        $q = $this->db->prepare('SELECT COUNT(*) FROM Trips WHERE departure_time > ?');
        $q->execute([$now]);
        $stats['active_trips'] = (int)$q->fetchColumn();

        $today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Istanbul')))->format('Y-m-d');
        $q = $this->db->prepare('SELECT COUNT(*) FROM Tickets WHERE substr(created_at,1,10) = ?');
        $q->execute([$today]);
        $stats['tickets_today'] = (int)$q->fetchColumn();

        $users = $this->db->query(
            'SELECT id, full_name, email, role, company_id, created_at
             FROM "User"
             ORDER BY created_at DESC
             LIMIT 200'
        )->fetchAll(PDO::FETCH_ASSOC);

        $companies = $this->db->query(
            'SELECT id, name, created_at
             FROM Bus_Company
             ORDER BY created_at DESC
             LIMIT 200'
        )->fetchAll(PDO::FETCH_ASSOC);

        render('admin/account', [
            'me'        => $me,
            'stats'     => $stats,
            'users'     => $users,
            'companies' => $companies,
        ]);
    }

    /* Firmalar */
    public function companies(): void {
        $this->requireAdmin();
        $rows = $this->db->query("SELECT id, name, created_at FROM Bus_Company ORDER BY created_at DESC")->fetchAll();
        render('admin/companies', ['companies'=>$rows]);
    }

    public function companyCreateForm(): void {
        $this->requireAdmin();
        render('admin/company_form', ['mode'=>'create']);
    }

    public function companyCreatePost(): void {
        $this->requireAdmin(); csrf_verify_or_die();
        $name = trim((string)$this->in($_POST, 'name', 'trim', ''));
        if ($name === '' || mb_strlen($name) > 120) {
            flash('err','Firma adı zorunlu ve 120 karakteri geçmemeli');
            redirect('admin/company-new');
        }
        $id = $this->newId();
        $st = $this->db->prepare("INSERT INTO Bus_Company (id, name) VALUES (?, ?)");
        $st->execute([$id, $name]);
        flash('ok','Firma eklendi'); redirect('admin/companies');
    }

    public function companyEditForm(): void {
        $this->requireAdmin();
        $id = (string)$this->in($_GET, 'id');
        if (!$this->isHexId($id)) { http_response_code(400); exit('Geçersiz ID'); }
        $q = $this->db->prepare("SELECT * FROM Bus_Company WHERE id=? LIMIT 1");
        $q->execute([$id]);
        $c = $q->fetch();
        if(!$c){ http_response_code(404); exit('Firma bulunamadı'); }
        render('admin/company_form', ['mode'=>'edit', 'company'=>$c]);
    }

    public function companyEditPost(): void {
        $this->requireAdmin(); csrf_verify_or_die();
        $id   = (string)$this->in($_POST, 'id');
        $name = trim((string)$this->in($_POST, 'name', 'trim', ''));
        if (!$this->isHexId($id) || $name==='' || mb_strlen($name) > 120){
            flash('err','Eksik/uzun alan'); redirect('admin/company-edit&id='.$id);
        }
        $st = $this->db->prepare("UPDATE Bus_Company SET name=? WHERE id=?");
        $st->execute([$name, $id]);
        flash('ok','Firma güncellendi'); redirect('admin/companies');
    }

    public function companyDeletePost(): void {
        $this->requireAdmin(); csrf_verify_or_die();
        $id = (string)$this->in($_POST, 'id');
        if (!$this->isHexId($id)){ http_response_code(400); exit('Geçersiz ID'); }
        $this->db->prepare("DELETE FROM Bus_Company WHERE id=?")->execute([$id]);
        flash('ok','Firma silindi'); redirect('admin/companies');
    }

    /* Firma Admin Kullanıcıları  */
    public function companyAdmins(): void {
        $this->requireAdmin();
        $rows = $this->db->query("
            SELECT u.id, u.full_name, u.email, u.company_id, b.name AS company_name, u.created_at
            FROM \"User\" u LEFT JOIN Bus_Company b ON b.id = u.company_id
            WHERE u.role = 'company'
            ORDER BY u.created_at DESC
        ")->fetchAll();
        render('admin/company_admins', ['admins'=>$rows]);
    }

    public function adminCreateForm(): void {
        $this->requireAdmin();
        $companies = $this->db->query("SELECT id,name FROM Bus_Company ORDER BY name")->fetchAll();
        render('admin/admin_form', ['companies'=>$companies]);
    }

    public function adminCreatePost(): void {
        $this->requireAdmin(); csrf_verify_or_die();
        $name = trim((string)$this->in($_POST,'full_name','trim',''));
        $email= $this->emailNorm((string)$this->in($_POST,'email','trim',''));
        $pass = (string)$this->in($_POST,'password', null, '');
        $cid  = (string)$this->in($_POST,'company_id');

        if ($name==='' || mb_strlen($name) > 120 || $email==='' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $pass==='' || mb_strlen($pass) < 8 || !$this->isHexId($cid)) {
            flash('err','Alanlar hatalı (ad/e-posta/parola/şirket).');
            redirect('admin/admin-new');
        }

        $exists = $this->db->prepare('SELECT 1 FROM "User" WHERE LOWER(email)=? LIMIT 1');
        $exists->execute([$email]);
        if ($exists->fetch()){
            flash('err','E-posta zaten kayıtlı');
            redirect('admin/admin-new');
        }

        $has = $this->db->prepare('SELECT 1 FROM Bus_Company WHERE id=?');
        $has->execute([$cid]);
        if (!$has->fetch()){
            flash('err','Geçersiz şirket');
            redirect('admin/admin-new');
        }

        $id   = $this->newId();
        $hash = hash_password($pass);

        $st = $this->db->prepare('
          INSERT INTO "User" (id, full_name, email, role, password, company_id, balance, created_at)
          VALUES (?, ?, ?, \'company\', ?, ?, 0, ?)
        ');
        $st->execute([$id, $name, $email, $hash, $cid, $this->nowStr()]);

        flash('ok','Firma admini oluşturuldu');
        redirect('admin/company-admins');
    }

    /* Firma Admini  */
    public function adminEdit(): void {
        $this->requireAdmin();

        $id = (string)($this->in($_GET, 'id', 'strval', '') ?? '');
        if (!$this->isHexId($id)) { http_response_code(400); echo "Geçersiz ID"; return; }

        $q = $this->db->prepare("
            SELECT u.id, u.full_name, u.email, u.role, u.company_id, u.created_at
            FROM \"User\" u
            WHERE u.id = ? AND u.role = 'company'
            LIMIT 1
        ");
        $q->execute([$id]);
        $admin = $q->fetch(PDO::FETCH_ASSOC);
        if (!$admin) { http_response_code(404); echo "Firma admini bulunamadı."; return; }

        $companies = $this->db->query("SELECT id, name FROM Bus_Company ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        $me = current_user();
        $flash_success = flash_get('ok');
        $flash_error   = flash_get('err');
        $errors = [];
        render('admin/admin_edit', compact('me','admin','companies','errors','flash_success','flash_error'));
    }

    /*  Firma Admini   */
    public function adminEditPost(): void {
        $this->requireAdmin();
        csrf_verify_or_die();

        $id          = (string)$this->in($_POST, 'id', 'strval', '');
        $full_name   = trim((string)$this->in($_POST, 'full_name', 'strval', ''));
        $email       = $this->emailNorm((string)$this->in($_POST, 'email', 'strval', ''));
        $company_id  = (string)$this->in($_POST, 'company_id', 'strval', '');
        $new_password= (string)$this->in($_POST, 'new_password', 'strval', '');

        $errors = [];

        if (!$this->isHexId($id)) { $errors['id'] = 'Geçersiz ID'; }

        $q = $this->db->prepare('SELECT id, email, role FROM "User" WHERE id=? LIMIT 1');
        $q->execute([$id]);
        $exists = $q->fetch(PDO::FETCH_ASSOC);
        if (!$exists || ($exists['role'] ?? '') !== 'company') {
            $errors['id'] = 'Kayıt bulunamadı veya rol uyumsuz';
        }

        if ($full_name === '' || mb_strlen($full_name) < 3) {
            $errors['full_name'] = 'Ad soyad en az 3 karakter olmalı';
        } elseif (mb_strlen($full_name) > 120) {
            $errors['full_name'] = 'Ad soyad çok uzun';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Geçerli bir e-posta girin';
        } else {
            $uq = $this->db->prepare('SELECT id FROM "User" WHERE LOWER(email)=LOWER(?) AND id<>? LIMIT 1');
            $uq->execute([$email, $id]);
            if ($uq->fetch()) { $errors['email'] = 'Bu e-posta başka bir kullanıcıda kayıtlı'; }
        }

        if (!$this->isHexId($company_id)) {
            $errors['company_id'] = 'Geçerli bir firma seçin';
        } else {
            $cq = $this->db->prepare("SELECT id FROM Bus_Company WHERE id=? LIMIT 1");
            $cq->execute([$company_id]);
            if (!$cq->fetch()) { $errors['company_id'] = 'Firma bulunamadı'; }
        }

        if ($new_password !== '' && mb_strlen($new_password) < 8) {
            $errors['new_password'] = 'Şifre en az 8 karakter olmalı';
        }
        if (mb_strlen($new_password) > 72) {
            $errors['new_password'] = 'Şifre çok uzun (maks. 72)';
        }

        if (!empty($errors)) {
            $admin = [
                'id'         => $id,
                'full_name'  => $full_name,
                'email'      => $email,
                'company_id' => $company_id,
                'role'       => 'company',
            ];
            $companies = $this->db->query("SELECT id, name FROM Bus_Company ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
            $me = current_user();
            $flash_success = null; $flash_error = 'Lütfen hataları düzeltin.';
            render('admin/admin_edit', compact('me','admin','companies','errors','flash_success','flash_error'));
            return;
        }

        try {
            $this->db->beginTransaction();

            if ($new_password !== '') {
                $hash = hash_password($new_password);
                $u = $this->db->prepare('
                    UPDATE "User"
                       SET full_name=?, email=?, company_id=?, password=?, role=\'company\'
                     WHERE id=?
                ');
                $u->execute([$full_name, $email, $company_id, $hash, $id]);
            } else {
                $u = $this->db->prepare('
                    UPDATE "User"
                       SET full_name=?, email=?, company_id=?, role=\'company\'
                     WHERE id=?
                ');
                $u->execute([$full_name, $email, $company_id, $id]);
            }

            $this->db->commit();
            flash('ok', 'Firma admini güncellendi.');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            if (APP_DEBUG) {
                flash('err', 'Güncelleme hatası: '.$e->getMessage());
            } else {
                flash('err', 'Güncelleme sırasında bir hata oluştu.');
            }
        }

        redirect('admin/company-admins');
    }

    /* Kuponlar  */
    public function coupons(): void {
        $this->requireAdmin();

        $sql = "
          SELECT
            c.*,
            COALESCE(u.used, 0) AS used_count,
            MAX(c.usage_limit - COALESCE(u.used, 0), 0) AS remaining,
            b.name AS company_name
          FROM Coupons c
          LEFT JOIN (
              SELECT coupon_id, COUNT(*) AS used
              FROM User_Coupons
              GROUP BY coupon_id
          ) u ON u.coupon_id = c.id
          LEFT JOIN Bus_Company b ON b.id = c.company_id
          ORDER BY c.expire_date ASC, c.created_at DESC
        ";

        $rows = $this->db->query($sql)->fetchAll();
        render('admin/coupons', ['coupons' => $rows]);
    }

    public function couponCreateForm(): void {
        $this->requireAdmin();
        $companies = $this->db->query("SELECT id,name FROM Bus_Company ORDER BY name")->fetchAll();
        render('admin/coupon_form', ['companies'=>$companies, 'mode'=>'create']);
    }

    public function couponCreatePost(): void {
        $this->requireAdmin(); csrf_verify_or_die();

        $code = strtoupper(trim((string)$this->in($_POST,'code','trim','')));
        $rate = (float)$this->in($_POST,'discount', fn($v)=>floatval($v), 0);
        $limit= (int)$this->in($_POST,'usage_limit', fn($v)=>intval($v), 0);
        $exp  = trim((string)$this->in($_POST,'expire_date','trim',''));
        $cid  = $this->in($_POST,'company_id');
        if ($cid !== null && $cid !== '' && !$this->isHexId((string)$cid)) { $cid = null; } // global kupon 

        if (!preg_match('/^[A-Z0-9\-]{3,32}$/', $code)) {
            flash('err','Kupon kodu yalnızca A-Z, 0-9, - ve 3-32 uzunlukta olmalı.');
            redirect('admin/coupon-new');
        }
        if (!($rate > 0.0 && $rate < 1.0)) {
            flash('err','İndirim 0 ile 1 arasında olmalı (örn. 0.15)');
            redirect('admin/coupon-new');
        }
        if ($limit <= 0 || $limit > 100000) {
            flash('err','Kullanım sınırı 1–100000 arası olmalı.');
            redirect('admin/coupon-new');
        }
        $this->assertDateTime($exp, 'expire_date');

        $id = $this->newId();
        $st = $this->db->prepare("
          INSERT INTO Coupons (id, code, discount, company_id, usage_limit, expire_date, created_at)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $st->execute([$id, $code, $rate, $cid ?: null, $limit, str_replace('T',' ',$exp), $this->nowStr()]);
        flash('ok','Kupon eklendi'); redirect('admin/coupons');
    }

    public function couponEditForm(): void {
        $this->requireAdmin();
        $id = (string)$this->in($_GET,'id');
        if (!$this->isHexId($id)) { http_response_code(400); exit('Geçersiz ID'); }
        $q=$this->db->prepare("SELECT * FROM Coupons WHERE id=?");
        $q->execute([$id]);
        $cp = $q->fetch(); if(!$cp){ http_response_code(404); exit('Kupon bulunamadı'); }
        $companies = $this->db->query("SELECT id,name FROM Bus_Company ORDER BY name")->fetchAll();
        render('admin/coupon_form', ['mode'=>'edit', 'coupon'=>$cp, 'companies'=>$companies]);
    }

    public function couponEditPost(): void {
        $this->requireAdmin(); csrf_verify_or_die();
        $id   = (string)$this->in($_POST,'id');
        $code = strtoupper(trim((string)$this->in($_POST,'code','trim','')));
        $rate = (float)$this->in($_POST,'discount', fn($v)=>floatval($v), 0);
        $limit= (int)$this->in($_POST,'usage_limit', fn($v)=>intval($v), 0);
        $exp  = trim((string)$this->in($_POST,'expire_date','trim',''));
        $cid  = $this->in($_POST,'company_id');
        if ($cid !== null && $cid !== '' && !$this->isHexId((string)$cid)) { $cid = null; }

        if (!$this->isHexId($id) || !preg_match('/^[A-Z0-9\-]{3,32}$/', $code) || !($rate>0.0 && $rate<1.0) || $limit<=0 || $limit>100000 || $exp==='') {
            flash('err','Alanlar hatalı');
            redirect('admin/coupon-edit&id='.$id);
        }
        $this->assertDateTime($exp, 'expire_date');

        $st=$this->db->prepare("
          UPDATE Coupons SET code=?, discount=?, company_id=?, usage_limit=?, expire_date=?
          WHERE id=?
        ");
        $st->execute([$code,$rate,$cid ?: null,$limit,str_replace('T',' ',$exp),$id]);
        flash('ok','Kupon güncellendi'); redirect('admin/coupons');
    }

    public function couponDeletePost(): void {
        $this->requireAdmin(); csrf_verify_or_die();
        $id = (string)$this->in($_POST,'id');
        if (!$this->isHexId($id)){ http_response_code(400); exit('Geçersiz ID'); }
        $this->db->prepare("DELETE FROM Coupons WHERE id=?")->execute([$id]);
        flash('ok','Kupon silindi'); redirect('admin/coupons');
    }

    public function trips(): void {
        $this->requireAdmin();
        $cid = trim((string)$this->in($_GET,'company_id','trim',''));
        if ($cid !== '') {
            if (!$this->isHexId($cid)) { flash('err','Geçersiz firma ID'); redirect('admin/trips'); }
            $st = $this->db->prepare("
              SELECT t.*, b.name AS company_name
              FROM Trips t JOIN Bus_Company b ON b.id=t.company_id
              WHERE t.company_id=?
              ORDER BY t.departure_time DESC
            ");
            $st->execute([$cid]);
            $trips = $st->fetchAll();
        } else {
            $q = $this->db->query("
              SELECT t.*, b.name AS company_name
              FROM Trips t JOIN Bus_Company b ON b.id=t.company_id
              ORDER BY t.departure_time DESC
            ");
            $trips = $q ? $q->fetchAll() : [];
        }
        $companies = $this->db->query("SELECT id,name FROM Bus_Company ORDER BY name")->fetchAll();
        render('admin/trips', ['trips'=>$trips, 'companies'=>$companies, 'filter_company'=>$cid]);
    }

    public function tripShow(): void {
        $this->requireAdmin();
        $id = (string)$this->in($_GET,'id');
        if (!$this->isHexId($id)) { http_response_code(400); exit('Geçersiz ID'); }

        $q = $this->db->prepare("
          SELECT t.*, b.name AS company_name
          FROM Trips t JOIN Bus_Company b ON b.id=t.company_id
          WHERE t.id=? LIMIT 1
        ");
        $q->execute([$id]);
        $trip = $q->fetch(); if(!$trip){ http_response_code(404); exit('Sefer yok'); }

        $s = $this->db->prepare("
          SELECT bs.seat_number
          FROM Booked_Seats bs JOIN Tickets tk ON tk.id=bs.ticket_id
          WHERE tk.trip_id=? AND tk.status='active'
          ORDER BY 1
        ");
        $s->execute([$id]);
        $occupied = array_map(fn($r)=>(int)$r['seat_number'], $s->fetchAll());
        render('admin/trip_show', ['trip'=>$trip, 'occupied'=>$occupied]);
    }

    public function addCompany(): void {
        $this->requireAdmin();
        if (!APP_DEBUG) { http_response_code(403); exit('Kapalı'); }
        $id   = trim((string)$this->in($_GET,'id','trim',''));
        $name = trim((string)$this->in($_GET,'name','trim',''));
        if (!$this->isHexId($id) || $name === '') { exit("id (32hex) ve name zorunlu.\n"); }

        $stmt = $this->db->prepare("INSERT OR IGNORE INTO Bus_Company (id, name, logo_path, created_at) VALUES (?, ?, NULL, ?)");
        $stmt->execute([$id, $name, $this->nowStr()]);
        echo "OK - Firma eklendi veya zaten vardı.";
    }

    public function addTrip(): void {
        $this->requireAdmin();
        if (!APP_DEBUG) { http_response_code(403); exit('Kapalı'); }

        $id         = (string)$this->in($_GET,'id');
        $company_id = (string)$this->in($_GET,'company_id');
        $dep_city   = (string)$this->in($_GET,'dep');
        $dest_city  = (string)$this->in($_GET,'dest');
        $dep_time   = (string)$this->in($_GET,'dt');
        $arr_time   = (string)$this->in($_GET,'at');
        $price      = (int)$this->in($_GET,'price', fn($v)=>intval($v), 0);
        $cap        = (int)$this->in($_GET,'cap', fn($v)=>intval($v), 0);

        if (!$this->isHexId($id) || !$this->isHexId($company_id) || !$dep_city || !$dest_city || !$dep_time || !$arr_time || $price<=0 || $cap<=0) {
            exit("Eksik/hatalı parametre.\n");
        }
        $this->assertDateTime($dep_time, 'departure_time');
        $this->assertDateTime($arr_time, 'arrival_time');

        $has = $this->db->prepare("SELECT 1 FROM Bus_Company WHERE id=?");
        $has->execute([$company_id]);
        if (!$has->fetch()) exit("Önce firma ekle: $company_id yok.\n");

        $stmt = $this->db->prepare("
          INSERT OR REPLACE INTO Trips
            (id, company_id, destination_city, arrival_time, departure_time, departure_city, price, capacity, created_at)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id, $company_id,
            $dest_city,
            str_replace('T',' ',$arr_time),
            str_replace('T',' ',$dep_time),
            $dep_city,
            $price, $cap,
            $this->nowStr()
        ]);

        echo "OK - Trip eklendi.";
    }

    public function accountUpdatePost(): void {
        $this->requireAdmin();
        csrf_verify_or_die();

        $cu   = current_user() ?: [];
        $uid  = (string)($cu['id'] ?? '');
        $name = trim((string)$this->in($_POST, 'full_name', 'trim', ''));
        $mail = $this->emailNorm((string)$this->in($_POST, 'email', 'trim', ''));
        $pass = (string)$this->in($_POST, 'new_password', null, '');

        if (!$this->isHexId($uid) || $name === '' || mb_strlen($name) > 120 || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            flash('err','Lütfen geçerli ad-soyad ve e-posta girin.');
            redirect('admin/account');
        }

        try {
            $this->db->beginTransaction();

            $chk = $this->db->prepare('SELECT 1 FROM "User" WHERE LOWER(email)=? AND id<>? LIMIT 1');
            $chk->execute([$mail, $uid]);
            if ($chk->fetch()) {
                $this->db->rollBack();
                flash('err','E-posta başka bir hesapta kullanılıyor.');
                redirect('admin/account');
            }

            $q = $this->db->prepare('UPDATE "User" SET full_name=?, email=? WHERE id=?');
            $q->execute([$name, $mail, $uid]);

            if ($pass !== '') {
                if (mb_strlen($pass) < 8) {
                    $this->db->rollBack();
                    flash('err','Yeni parola en az 8 karakter olmalı.');
                    redirect('admin/account');
                }
                $hp = hash_password($pass);
                $q2 = $this->db->prepare('UPDATE "User" SET password=? WHERE id=?');
                $q2->execute([$hp, $uid]);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            if (APP_DEBUG) {
                flash('err','Güncelleme hatası: '.$e->getMessage());
            } else {
                flash('err','Güncelleme sırasında bir hata oluştu.');
            }
            redirect('admin/account');
        }

        $_SESSION['user']['full_name'] = $name;
        $_SESSION['user']['name']      = $name;
        $_SESSION['user']['email']     = $mail;

        flash('ok','Profil güncellendi.');
        redirect('admin/account');
    }
}
