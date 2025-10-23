<?php
declare(strict_types=1);

class CompanyController {
    public function __construct(private PDO $db) {
        $this->db->exec("PRAGMA foreign_keys = ON;");
    }

    private function requireCompanyRole(): void {
        require_role(ROLE_COMPANY);
        $cu = current_user();
        if (empty($cu['company_id'])) {
            http_response_code(403);
            exit('Bu kullanıcıya firma atanmadı.');
        }
    }

    private function getTripOr404(string $id, string $companyId): array {
        $q = $this->db->prepare("SELECT * FROM Trips WHERE id=? AND company_id=? LIMIT 1");
        $q->execute([$id, $companyId]);
        $trip = $q->fetch(PDO::FETCH_ASSOC);
        if (!$trip) {
            http_response_code(404);
            exit('Sefer bulunamadı.');
        }
        return $trip;
    }

    private function nowStrTR(): string {
        return (new DateTimeImmutable('now', new DateTimeZone('Europe/Istanbul')))->format('Y-m-d H:i:s');
    }

    private function normalizeDatetime(string $s, string $fieldName = 'datetime'): string {
        $s = trim(str_replace('T', ' ', $s));
        foreach (['Y-m-d H:i:s','Y-m-d H:i'] as $fmt) {
            $dt = DateTimeImmutable::createFromFormat($fmt, $s, new DateTimeZone('Europe/Istanbul'));
            if ($dt !== false) {
                return $dt->format('Y-m-d H:i:s');
            }
        }
        http_response_code(400);
        exit("$fieldName formatı geçersiz. Beklenen: YYYY-MM-DD HH:MM");
    }

    private function paginate(): array {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per  = max(1, min(100, (int)($_GET['per'] ?? 20)));
        $offset = ($page - 1) * $per;
        return [$page, $per, $offset];
    }

    /* KUPON YÖNETİMİ */

    public function coupons(): void {
        $this->requireCompanyRole();
        $cid = current_user()['company_id'];

        $sql = "
          SELECT c.*,
                 COALESCE(u.used,0) AS used_count,
                 MAX(c.usage_limit - COALESCE(u.used,0),0) AS remaining
          FROM Coupons c
          LEFT JOIN (
            SELECT coupon_id, COUNT(*) AS used
            FROM User_Coupons
            GROUP BY coupon_id
          ) u ON u.coupon_id = c.id
          WHERE c.company_id = ?
          ORDER BY c.expire_date ASC, c.created_at DESC
        ";
        $st = $this->db->prepare($sql);
        $st->execute([$cid]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        render('company/coupons', ['coupons' => $rows]);
    }

    public function couponCreateForm(): void {
        $this->requireCompanyRole();
        render('company/coupon_form', ['mode' => 'create', 'coupon' => null]);
    }

    public function couponCreatePost(): void {
        $this->requireCompanyRole();
        csrf_verify_or_die();

        $cid = current_user()['company_id'];

        $code        = strtoupper(trim((string)($_POST['code'] ?? '')));
        $discount    = (float)($_POST['discount'] ?? 0);
        $usage_limit = (int)($_POST['usage_limit'] ?? 0);
        $expireRaw   = (string)($_POST['expire_date'] ?? '');

        if (!preg_match('/^[A-Z0-9\-]{3,32}$/', $code)) {
            flash('err','Kupon kodu yalnızca A-Z, 0-9 ve - içerebilir (3–32).');
            redirect('company/coupon-new');
        }
        if (!($discount > 0.0 && $discount < 1.0)) {
            flash('err','İndirim 0 ile 1 arasında olmalı (örn. 0.15).');
            redirect('company/coupon-new');
        }
        if ($usage_limit <= 0 || $usage_limit > 100000) {
            flash('err','Kullanım sınırı 1–100000 arası olmalı.');
            redirect('company/coupon-new');
        }
        $expire = $this->normalizeDatetime($expireRaw, 'expire_date');

        $id  = bin2hex(random_bytes(16));
        $st  = $this->db->prepare("
          INSERT INTO Coupons (id, code, discount, company_id, usage_limit, expire_date, created_at)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $st->execute([$id, $code, $discount, $cid, $usage_limit, $expire, $this->nowStrTR()]);

        flash('ok','Kupon oluşturuldu.');
        redirect('company/coupons');
    }

    public function couponEditForm(): void {
        $this->requireCompanyRole();
        $cid = current_user()['company_id'];
        $id  = (string)($_GET['id'] ?? '');

        $q = $this->db->prepare("SELECT * FROM Coupons WHERE id=? AND company_id=? LIMIT 1");
        $q->execute([$id, $cid]);
        $coupon = $q->fetch(PDO::FETCH_ASSOC);
        if (!$coupon) { http_response_code(404); exit('Kupon bulunamadı.'); }

        render('company/coupon_form', ['mode'=>'edit', 'coupon'=>$coupon]);
    }

    public function couponEditPost(): void {
        $this->requireCompanyRole();
        csrf_verify_or_die();

        $cid = current_user()['company_id'];

        $id          = (string)($_POST['id'] ?? '');
        $code        = strtoupper(trim((string)($_POST['code'] ?? '')));
        $discount    = (float)($_POST['discount'] ?? 0);
        $usage_limit = (int)($_POST['usage_limit'] ?? 0);
        $expireRaw   = (string)($_POST['expire_date'] ?? '');

        if ($id === '') {
            flash('err','ID gerekli');
            redirect('company/coupon-edit&id='.$id);
        }
        if (!preg_match('/^[A-Z0-9\-]{3,32}$/', $code) || !($discount>0.0 && $discount<1.0) || $usage_limit<=0 || $usage_limit>100000) {
            flash('err','Alanlar hatalı.');
            redirect('company/coupon-edit&id='.$id);
        }
        $expire = $this->normalizeDatetime($expireRaw, 'expire_date');

        $st = $this->db->prepare("
          UPDATE Coupons
             SET code=?, discount=?, usage_limit=?, expire_date=?
           WHERE id=? AND company_id=?
        ");
        $st->execute([$code, $discount, $usage_limit, $expire, $id, $cid]);

        flash('ok','Kupon güncellendi.');
        redirect('company/coupons');
    }

    public function couponDeletePost(): void {
        $this->requireCompanyRole();
        csrf_verify_or_die();

        $cid = current_user()['company_id'];
        $id  = (string)($_POST['id'] ?? '');
        if ($id==='') { http_response_code(400); exit('ID gerekli.'); }

        $this->db->prepare("DELETE FROM Coupons WHERE id=? AND company_id=?")->execute([$id, $cid]);
        flash('ok','Kupon silindi.');
        redirect('company/coupons');
    }

    /*  SATIŞLAR  */

    public function tickets(): void {
        $this->requireCompanyRole();
        $cid = current_user()['company_id'];
        [$page, $per, $offset] = $this->paginate();

        $w = ["t.company_id = :cid"];
        $p = [':cid' => $cid];

        $status = (string)($_GET['status'] ?? '');
        $now    = $this->nowStrTR(); 
        if ($status !== '') {
            if ($status === 'expired') {
                $w[] = "(tk.status = 'active' AND DATETIME(t.departure_time) <= DATETIME(:now))";
                $p[':now'] = $now;
            } elseif ($status === 'active') {
                $w[] = "(tk.status = 'active' AND DATETIME(t.departure_time) > DATETIME(:now))";
                $p[':now'] = $now;
            } elseif ($status === 'canceled') {
                $w[] = "tk.status = 'canceled'";
            }
        }

        if (!empty($_GET['trip_id'])) {
            $w[] = "tk.trip_id = :trip_id";
            $p[':trip_id'] = (string)$_GET['trip_id'];
        }

        $from = trim((string)($_GET['from'] ?? ''));
        if ($from !== '') {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $from .= ' 00:00:00'; }
            $from = $this->normalizeDatetime($from, 'from'); 
            $w[] = "DATETIME(tk.created_at) >= DATETIME(:from)";
            $p[':from'] = $from;
        }

        $to = trim((string)($_GET['to'] ?? ''));
        if ($to !== '') {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $to .= ' 23:59:59'; }
            $to = $this->normalizeDatetime($to, 'to');
            $w[] = "DATETIME(tk.created_at) <= DATETIME(:to)";
            $p[':to'] = $to;
        }

        $where = $w ? ('WHERE '.implode(' AND ', $w)) : '';

        $countSql = "SELECT COUNT(DISTINCT tk.id)
                     FROM Tickets tk
                     JOIN Trips t ON t.id = tk.trip_id
                     $where";
        $cq = $this->db->prepare($countSql);
        foreach ($p as $k=>$v) { $cq->bindValue($k, $v); }
        $cq->execute();
        $total = (int)$cq->fetchColumn();

        $sql = "SELECT
                  tk.id            AS ticket_id,
                  tk.status,
                  CASE
                    WHEN tk.status='active' AND DATETIME(t.departure_time) <= DATETIME(:now2) THEN 'expired'
                    ELSE tk.status
                  END              AS status_view,
                  tk.total_price,
                  tk.created_at,
                  t.id             AS trip_id,
                  t.departure_city,
                  t.destination_city,
                  t.departure_time,
                  t.arrival_time,
                  u.full_name,
                  u.email,
                  GROUP_CONCAT(bs.seat_number, ', ') AS seats
                FROM Tickets tk
                JOIN Trips t ON t.id = tk.trip_id
                JOIN \"User\"  u ON u.id = tk.user_id
                LEFT JOIN Booked_Seats bs ON bs.ticket_id = tk.id
                $where
                GROUP BY tk.id
                ORDER BY tk.created_at DESC
                LIMIT :limit OFFSET :offset";

        $q = $this->db->prepare($sql);
        foreach ($p as $k=>$v) { $q->bindValue($k, $v); }
        $q->bindValue(':now2', $now);
        $q->bindValue(':limit',  (int)$per,    PDO::PARAM_INT);
        $q->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $q->execute();
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);

        render('company/tickets', [
            'rows'    => $rows,
            'page'    => $page,
            'per'     => $per,
            'total'   => $total,
            'filters' => [
                'status'  => $status,
                'trip_id' => $_GET['trip_id'] ?? '',
                'from'    => $_GET['from'] ?? '',
                'to'      => $_GET['to']   ?? '',
            ],
        ]);
    }

    /* Bilet İptal*/
    public function cancelTicketPost(): void {
        $this->requireCompanyRole();
        csrf_verify_or_die();

        $cid      = current_user()['company_id'];
        $ticketId = (string)($_POST['id'] ?? '');
        if ($ticketId==='') { http_response_code(400); exit('Bilet ID gerekli.'); }

        $q = $this->db->prepare("
          SELECT tk.id, tk.user_id, tk.status, tk.total_price, t.departure_time
          FROM Tickets tk
          JOIN Trips t ON t.id = tk.trip_id
          WHERE tk.id = ? AND t.company_id = ?
          LIMIT 1
        ");
        $q->execute([$ticketId, $cid]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) { http_response_code(404); exit('Bilet bulunamadı.'); }

        if ($row['status'] !== 'active') {
            flash('err','Bu bilet iptal edilemez.');
            redirect('company/tickets');
        }

        $now  = new DateTimeImmutable('now', new DateTimeZone('Europe/Istanbul'));
        $dep  = new DateTimeImmutable((string)$row['departure_time'], new DateTimeZone('Europe/Istanbul'));
        if ($dep <= $now) {
            flash('err','Kalkış geçmiş; iptal edilemez.');
            redirect('company/tickets');
        }
        if (($dep->getTimestamp() - $now->getTimestamp()) < 3600) {
            flash('err','Kalkışa 1 saatten az kaldı.');
            redirect('company/tickets');
        }

        try {
            $this->db->beginTransaction();

            $a = $this->db->prepare("UPDATE Tickets SET status='canceled' WHERE id=? AND status='active'");
            $a->execute([$ticketId]);
            if ($a->rowCount() !== 1) { throw new RuntimeException('Bilet durum güncellenemedi'); }

            $b = $this->db->prepare("DELETE FROM Booked_Seats WHERE ticket_id=?");
            $b->execute([$ticketId]);

            $c = $this->db->prepare("UPDATE \"User\" SET balance = balance + ? WHERE id=?");
            $c->execute([(int)$row['total_price'], $row['user_id']]);
            if ($c->rowCount() !== 1) { throw new RuntimeException('İade yapılamadı'); }

            $this->db->commit();
            flash('ok','Bilet iptal edildi ve ücret iade edildi.');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            flash('err','İptal hatası.');
        }

        redirect('company/tickets');
    }

    /*  SEFERLER */

    public function trips(): void {
        $this->requireCompanyRole();
        $companyId  = current_user()['company_id'];
        $showPast = isset($_GET['show_past']) && $_GET['show_past'] === '1';

        $now = $this->nowStrTR();

        $sqlBase = "
            SELECT
              t.*,
              b.name AS company_name,
              CASE WHEN DATETIME(t.departure_time) <= DATETIME(:now) THEN 1 ELSE 0 END AS is_past
            FROM Trips t
            JOIN Bus_Company b ON b.id = t.company_id
            WHERE t.company_id = :cid
        ";

        if ($showPast) {
            $sql = $sqlBase . " AND DATETIME(t.departure_time) <= DATETIME(:now)
                                ORDER BY t.departure_time DESC";
        } else {
            $sql = $sqlBase . " AND DATETIME(t.departure_time) >  DATETIME(:now)
                                ORDER BY t.departure_time ASC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cid' => $companyId, ':now' => $now]);
        $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        render('company/trips', ['trips' => $trips, 'showPast' => $showPast]);
    }

    public function tripCreateForm(): void {
        $this->requireCompanyRole();
        render('company/trip_form', ['mode' => 'create', 'trip' => null]);
    }

    public function tripCreatePost(): void {
    $this->requireCompanyRole();
    csrf_verify_or_die();

    $cid  = current_user()['company_id'] ?? null;

    $depC = trim((string)($_POST['departure_city']   ?? ''));
    $dest = trim((string)($_POST['destination_city'] ?? ''));
    $depT = $this->normalizeDatetime((string)($_POST['departure_time'] ?? ''), 'departure_time'); 
    $arrT = $this->normalizeDatetime((string)($_POST['arrival_time']   ?? ''), 'arrival_time');  
    $price= (int)($_POST['price'] ?? 0);
    $cap  = (int)($_POST['capacity'] ?? 0);

    if (!$cid || $depC === '' || $dest === '' || $price <= 0 || $cap <= 0) {
        flash('err', 'Zorunlu alanlar eksik veya hatalı.');
        redirect('company/trip-new');
    }
    if (mb_strtolower($depC, 'UTF-8') === mb_strtolower($dest, 'UTF-8')) {
        flash('err', 'Çıkış ve varış şehirleri farklı olmalıdır.');
        redirect('company/trip-new');
    }

    try {
        $tz  = new DateTimeZone('Europe/Istanbul');
        $d1  = new DateTimeImmutable($depT, $tz);
        $d2  = new DateTimeImmutable($arrT, $tz);
        if ($d2 <= $d1) {
            flash('err','Varış saati kalkıştan önce/aynı olamaz.');
            redirect('company/trip-new');
        }
    } catch (\Throwable $e) {
        flash('err', 'Tarih formatı geçersiz.');
        redirect('company/trip-new');
    }

    // Kayıt
    $id = bin2hex(random_bytes(16)); 

    $sql = "
        INSERT INTO Trips (
            id, company_id,
            destination_city, arrival_time,
            departure_time,  departure_city,
            price, capacity
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";

    try {
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $id, $cid,
            $dest, $arrT,
            $depT, $depC,
            $price, $cap,
        ]);
    } catch (\PDOException $e) {
        error_log('tripCreatePost insert error: '.$e->getMessage());
        flash('err', 'Sefer kaydedilirken bir hata oluştu.');
        redirect('company/trip-new');
    }

    flash('ok','Sefer eklendi.');
    redirect('company/panel'); 
}


    public function tripEditForm(): void {
        $this->requireCompanyRole();
        $cid = current_user()['company_id'];
        $id  = (string)($_GET['id'] ?? '');

        $trip = $this->getTripOr404($id, $cid);

        if (new DateTimeImmutable((string)$trip['departure_time']) <= new DateTimeImmutable('now')) {
            flash('err', 'Geçmiş sefere işlem yapılamaz.');
            redirect('company/panel');
            return;
        }

        render('company/trip_form', ['trip' => $trip, 'mode' => 'edit']);
    }

    public function tripEditPost(): void {
        $this->requireCompanyRole();
        csrf_verify_or_die();

        $cid = current_user()['company_id'];
        $id  = (string)($_POST['id'] ?? '');

        $trip = $this->getTripOr404($id, $cid);

        if (new DateTimeImmutable((string)$trip['departure_time']) <= new DateTimeImmutable('now')) {
            flash('err', 'Geçmiş sefere işlem yapılamaz.');
            redirect('company/panel');
            return;
        }

        $dep_city = trim((string)($_POST['departure_city'] ?? ''));
        $dst_city = trim((string)($_POST['destination_city'] ?? ''));
        $dep_time = $this->normalizeDatetime((string)($_POST['departure_time'] ?? ''), 'departure_time');
        $price    = (int)($_POST['price'] ?? 0);

        if ($dep_city==='' || $dst_city==='' || $price<=0) {
            flash('err','Zorunlu alanlar eksik veya hatalı.');
            redirect('company/trip-edit&id='.$id);
        }

        $u = $this->db->prepare("
          UPDATE Trips
             SET departure_city=?, destination_city=?, departure_time=?, price=?
           WHERE id=? AND company_id=?
        ");
        $u->execute([$dep_city, $dst_city, $dep_time, $price, $id, $cid]);

        flash('ok', 'Sefer güncellendi.');
        redirect('company/trips');
    }

    public function tripDeletePost(): void {
        $this->requireCompanyRole();
        csrf_verify_or_die();

        $cid = current_user()['company_id'];
        $id  = (string)($_POST['id'] ?? '');

        $q = $this->db->prepare("SELECT departure_time FROM Trips WHERE id=? AND company_id=? LIMIT 1");
        $q->execute([$id, $cid]);
        $trip = $q->fetch(PDO::FETCH_ASSOC);

        if (!$trip) {
            http_response_code(404);
            exit('Sefer yok.');
        }

        if (new DateTimeImmutable((string)$trip['departure_time']) <= new DateTimeImmutable('now')) {
            flash('err', 'Geçmiş sefere silme yapılamaz.');
            redirect('company/panel');
            return;
        }

        $d = $this->db->prepare("DELETE FROM Trips WHERE id=? AND company_id=?");
        $d->execute([$id, $cid]);

        flash('ok', 'Sefer silindi.');
        redirect('company/trips');
    }

    /* Koltuk görünümü */
    public function tripSeats(): void {
        $this->requireCompanyRole();
        $cid = current_user()['company_id'];
        $id  = (string)($_GET['id'] ?? '');

        $trip = $this->getTripOr404($id, $cid);

        $s = $this->db->prepare("
          SELECT bs.seat_number, tk.id AS ticket_id, u.full_name
          FROM Booked_Seats bs
          JOIN Tickets tk ON tk.id = bs.ticket_id AND tk.status = 'active'
          JOIN \"User\" u ON u.id = tk.user_id
          WHERE tk.trip_id = ?
          ORDER BY bs.seat_number ASC
        ");
        $s->execute([$id]);
        $rows = $s->fetchAll(PDO::FETCH_ASSOC);

        $occupied = []; 
        foreach ($rows as $r) {
            $occupied[(int)$r['seat_number']] = [
                'ticket_id' => $r['ticket_id'],
                'full_name' => $r['full_name'] ?? '—',
            ];
        }

        $capacity   = (int)$trip['capacity'];
        $occupiedCt = count($occupied);
        $freeCt     = max(0, $capacity - $occupiedCt);
        $rate       = $capacity > 0 ? round(($occupiedCt / $capacity) * 100) : 0;

        render('company/trip_seats', [
            'trip'       => $trip,
            'occupied'   => $occupied,
            'capacity'   => $capacity,
            'occupiedCt' => $occupiedCt,
            'freeCt'     => $freeCt,
            'rate'       => $rate,
        ]);
    }
}
