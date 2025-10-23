<?php

declare(strict_types=1);

if (!function_exists('require_login')) {
    function require_login(): void {
        if (empty($_SESSION['user'])) {
            redirect('auth/login?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
            exit;
        }
    }
}

class TicketController {
    public function __construct(private PDO $db) {
        $this->db->exec("PRAGMA foreign_keys = ON;");
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function buy(): void {
        require_login(); 
      
    }

    private function nowStrTR(): string {
        return (new DateTimeImmutable('now', new DateTimeZone('Europe/Istanbul')))->format('Y-m-d H:i:s');
    }

    private function pdfTxt(string $s): string {
        $out = @iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $s);
        return $out !== false ? $out : $s;
    }

    private function expirePastTicketsLocal(): void {
        $now = $this->nowStrTR();
        $stmt = $this->db->prepare("
            UPDATE Tickets
               SET status = 'expired'
             WHERE status = 'active'
               AND trip_id IN (
                   SELECT id FROM Trips
                    WHERE DATETIME(departure_time) <= DATETIME(:now)
               )
        ");
        $stmt->execute([':now' => $now]);
    }

    private function normalizeCoupon(string $code): string {
        $code = strtoupper(trim($code));
        if ($code === '') return '';
        if (!preg_match('/^[A-Z0-9\-]{3,32}$/', $code)) return '';
        return $code;
    }

    /*  SATIN ALMA FORMU  */
    public function purchaseForm(): void {
        require_user();
        if (function_exists('require_method')) { require_method(['GET']); }

        $tripId = (string)($_GET['id'] ?? '');
        if ($tripId === '') { http_response_code(400); echo "Sefer ID gerekli."; return; }

        $t = $this->db->prepare("
            SELECT t.*, b.name AS company_name
              FROM Trips t
              JOIN Bus_Company b ON b.id = t.company_id
             WHERE t.id = ?
             LIMIT 1
        ");
        $t->execute([$tripId]);
        $trip = $t->fetch(PDO::FETCH_ASSOC);
        if (!$trip) { http_response_code(404); echo "Sefer bulunamadı."; return; }

        if (function_exists('assertTripNotDeparted')) {
            assertTripNotDeparted($trip);
        } else {
            try {
                if ((new DateTimeImmutable((string)$trip['departure_time'])) <= new DateTimeImmutable('now')) {
                    http_response_code(400); echo "Bu seferin kalkış saati geçmiş."; return;
                }
            } catch (Throwable) {
                http_response_code(400); echo "Kalkış zamanı geçersiz."; return;
            }
        }

        // Dolu koltuklar
        $q = $this->db->prepare("
            SELECT bs.seat_number
              FROM Booked_Seats bs
              JOIN Tickets tk ON tk.id = bs.ticket_id
             WHERE tk.trip_id = ? AND tk.status = 'active'
        ");
        $q->execute([$tripId]);
        $occupied = array_map(fn($r) => (int)$r['seat_number'], $q->fetchAll(PDO::FETCH_ASSOC));

        // Kullanıcı bakiyesi
        $u = $this->db->prepare('SELECT balance FROM "User" WHERE id = ? LIMIT 1');
        $u->execute([current_user()['id']]);
        $balance = (int)($u->fetch(PDO::FETCH_ASSOC)['balance'] ?? 0);

        render('ticket/purchase', [
            'trip'     => $trip,
            'occupied' => $occupied,
            'balance'  => $balance,
        ]);
    }

    /*  ORTAK HESAPLAYICI*/
    private function computePrice(string $tripId, array $seats, string $couponCode, string $userId): array {
        $t = $this->db->prepare("SELECT price, capacity, company_id FROM Trips WHERE id = :tid LIMIT 1");
        $t->execute([':tid' => $tripId]);
        $trip = $t->fetch(PDO::FETCH_ASSOC);
        if (!$trip) return ['ok' => false, 'msg' => 'Sefer bulunamadı.'];

        $seats = array_values(array_unique(array_map('intval', (array)$seats)));
        if (empty($seats)) return ['ok' => false, 'msg' => 'En az bir koltuk seçmelisin.'];

        $capacity = (int)$trip['capacity'];
        foreach ($seats as $s) {
            if ($s < 1 || $s > $capacity) return ['ok' => false, 'msg' => "Geçersiz koltuk: $s"];
        }

        $unit  = (int)$trip['price'];
        $total = $unit * count($seats);
        $disc  = 0;
        $appliedCode = '';
        $couponRow   = null;

        $code = $this->normalizeCoupon($couponCode);
        if ($code !== '') {
            $now = $this->nowStrTR();
            $c = $this->db->prepare("
                SELECT *
                  FROM Coupons
                 WHERE code = :code
                   AND (company_id = :cid OR company_id IS NULL)
                   AND DATETIME(expire_date) >= DATETIME(:now)
                 ORDER BY company_id IS NOT NULL DESC
                 LIMIT 1
            ");
            $c->execute([':code' => $code, ':cid' => $trip['company_id'], ':now' => $now]);
            $cp = $c->fetch(PDO::FETCH_ASSOC);

            if ($cp) {
                $used = $this->db->prepare("SELECT COUNT(*) AS c FROM User_Coupons WHERE coupon_id = :cpid");
                $used->execute([':cpid' => $cp['id']]);
                $totalUsed = (int)($used->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

                $already = $this->db->prepare("SELECT 1 FROM User_Coupons WHERE coupon_id = :cpid AND user_id = :uid LIMIT 1");
                $already->execute([':cpid' => $cp['id'], ':uid' => $userId]);
                $userHasUsed = (bool)$already->fetchColumn();

                $limit = isset($cp['usage_limit']) ? (int)$cp['usage_limit'] : 0;
                $limitOk = ($limit <= 0) || ($totalUsed < $limit);

                if ($limitOk && !$userHasUsed) {
                    $rate = (float)$cp['discount']; 
                    if ($rate > 0 && $rate < 1) {
                        $disc  = (int)round($total * $rate);
                        $total = max(0, $total - $disc);
                        $appliedCode = $code;
                        $couponRow   = $cp;
                    }
                }
            }
        }

        return [
            'ok'       => true,
            'unit'     => $unit,
            'seats'    => $seats,
            'discount' => $disc,
            'total'    => $total,
            'code'     => $appliedCode,
            'coupon'   => $couponRow,
        ];
    }

    /* FİYAT HESAPLA */
    public function calcPrice(): void {
        require_user();
        csrf_verify_or_die();
        if (function_exists('require_method')) { require_method(['POST']); }

        $tripId = (string)($_POST['trip_id'] ?? '');
        $seats  = (array)($_POST['seats'] ?? []);
        $code   = (string)($_POST['coupon_code'] ?? '');

        header('Content-Type: application/json; charset=utf-8');

        if ($tripId === '' || empty($seats)) {
            echo json_encode(['ok' => false, 'msg' => 'Sefer veya koltuk seçimi eksik.']);
            return;
        }

        $res = $this->computePrice($tripId, $seats, $code, (string)current_user()['id']);
        echo json_encode($res);
    }

    /*SATIN ALMA */
    public function purchasePost(): void {
        require_user();
        csrf_verify_or_die();
        if (function_exists('require_method')) { require_method(['POST']); }

        $userId     = (string)current_user()['id'];
        $tripId     = (string)($_POST['trip_id'] ?? '');
        $seatsInput = (array)($_POST['seats'] ?? []);
        $couponCode = (string)($_POST['coupon_code'] ?? '');

        if ($tripId === '' || empty($seatsInput)) {
            render('ticket/result', ['ok' => false, 'msg' => 'Sefer veya koltuk seçimi eksik.']);
            return;
        }

        $t = $this->db->prepare("
            SELECT price, capacity, departure_time, company_id
              FROM Trips
             WHERE id = :tid LIMIT 1
        ");
        $t->execute([':tid' => $tripId]);
        $trip = $t->fetch(PDO::FETCH_ASSOC);
        if (!$trip) { render('ticket/result', ['ok'=>false,'msg'=>'Sefer bulunamadı.']); return; }

        if (function_exists('assertTripNotDeparted')) {
            assertTripNotDeparted($trip);
        } else {
            try {
                if ((new DateTimeImmutable((string)$trip['departure_time'])) <= new DateTimeImmutable('now')) {
                    render('ticket/result', ['ok'=>false, 'msg'=>'Kalkış geçmiş. Satın alma yapılamaz.']);
                    return;
                }
            } catch (Throwable) {
                render('ticket/result', ['ok'=>false, 'msg'=>'Kalkış zamanı geçersiz.']);
                return;
            }
        }

        // Fiyat + kupon ön hesap
        $priced = $this->computePrice($tripId, $seatsInput, $couponCode, $userId);
        if (!$priced['ok']) {
            render('ticket/result', ['ok' => false, 'msg' => $priced['msg'] ?? 'Geçersiz istek.']);
            return;
        }

        $seats          = $priced['seats'];
        $totalPrice     = (int)$priced['total'];
        $discountAmount = (int)$priced['discount'];
        $appliedCode    = (string)$priced['code'];
        $cp             = $priced['coupon'] ?? null;

        // Bakiye kontrolü 
        $u = $this->db->prepare('SELECT balance FROM "User" WHERE id = :uid LIMIT 1');
        $u->execute([':uid' => $userId]);
        $balance = (int)($u->fetch(PDO::FETCH_ASSOC)['balance'] ?? 0);
        if ($balance < $totalPrice) {
            render('ticket/result', ['ok' => false, 'msg' => 'Yetersiz bakiye.']);
            return;
        }

        $now = $this->nowStrTR();

        try {
            $this->db->exec("BEGIN IMMEDIATE;");
            $this->db->exec("PRAGMA foreign_keys = ON;");

            // Koltuk çakışma kontrolü
            $in  = implode(',', array_fill(0, count($seats), '?'));
            $sql = "
                SELECT bs.seat_number
                  FROM Booked_Seats bs
                  JOIN Tickets tk ON tk.id = bs.ticket_id
                 WHERE tk.trip_id = ?
                   AND tk.status   = 'active'
                   AND bs.seat_number IN ($in)
                 LIMIT 1
            ";
            $params = array_merge([$tripId], $seats);
            $chk = $this->db->prepare($sql);
            $chk->execute($params);
            if ($chk->fetch(PDO::FETCH_ASSOC)) {
                $this->db->exec("ROLLBACK;");
                render('ticket/result', ['ok'=>false, 'msg'=>'Seçtiğin koltuklardan bazıları az önce doldu.']);
                return;
            }

            //  Kupon tekrar doğrula
            if ($appliedCode !== '' && !empty($cp['id'])) {
                $c2 = $this->db->prepare("
                    SELECT id, discount, usage_limit, expire_date
                      FROM Coupons
                     WHERE id = :cid
                       AND DATETIME(expire_date) >= DATETIME(:now)
                     LIMIT 1
                ");
                $c2->execute([':cid' => $cp['id'], ':now' => $now]);
                $curr = $c2->fetch(PDO::FETCH_ASSOC);
                if (!$curr) {
                    $appliedCode = '';
                    $discountAmount = 0;
                    $totalPrice = (int)((int)$trip['price'] * count($seats));
                } else {
                    $used2 = $this->db->prepare("SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = :cid");
                    $used2->execute([':cid' => $cp['id']]);
                    $usedCount = (int)$used2->fetchColumn();
                    $limit2 = isset($curr['usage_limit']) ? (int)$curr['usage_limit'] : 0;
                    if ($limit2 > 0 && $usedCount >= $limit2) {
                        $appliedCode = '';
                        $discountAmount = 0;
                        $totalPrice = (int)((int)$trip['price'] * count($seats));
                    }
                    $al2 = $this->db->prepare("SELECT 1 FROM User_Coupons WHERE coupon_id=:cid AND user_id=:uid LIMIT 1");
                    $al2->execute([':cid' => $cp['id'], ':uid' => $userId]);
                    if ($al2->fetchColumn()) {
                        $appliedCode = '';
                        $discountAmount = 0;
                        $totalPrice = (int)((int)$trip['price'] * count($seats));
                    }
                }
            }

            // Ticket oluştur
            $ticketId = bin2hex(random_bytes(16));
            $insT = $this->db->prepare("
                INSERT INTO Tickets (id, trip_id, user_id, status, total_price, created_at)
                VALUES (:id, :trip, :user, 'active', :total, :now)
            ");
            $insT->execute([
                ':id'    => $ticketId,
                ':trip'  => $tripId,
                ':user'  => $userId,
                ':total' => $totalPrice,
                ':now'   => $now
            ]);

            //  Koltuklar
            $insS = $this->db->prepare("
                INSERT INTO Booked_Seats (id, ticket_id, seat_number)
                VALUES (:id, :tid, :seat)
            ");
            foreach ($seats as $s) {
                $sid = bin2hex(random_bytes(16));
                $insS->execute([':id' => $sid, ':tid' => $ticketId, ':seat' => (int)$s]);
            }

            //  Kupon kullanım kaydı
            if ($appliedCode !== '' && !empty($cp['id'])) {
                $linkId = bin2hex(random_bytes(16));
                $insUC = $this->db->prepare("
                    INSERT INTO User_Coupons (id, coupon_id, user_id)
                    VALUES (:id, :cid, :uid)
                ");
                $insUC->execute([':id' => $linkId, ':cid' => $cp['id'], ':uid' => $userId]);
            }

            //  Bakiye düş
            $updU = $this->db->prepare('UPDATE "User" SET balance = balance - :amt WHERE id = :uid');
            $updU->execute([':amt' => $totalPrice, ':uid' => $userId]);
            if ($updU->rowCount() !== 1) throw new RuntimeException('Bakiye güncellenemedi.');

            if (function_exists('audit')) {
                audit('ticket.purchase', ['ticket_id'=>$ticketId,'trip_id'=>$tripId,'total'=>$totalPrice,'discount'=>$discountAmount,'code'=>$appliedCode]);
            }

            $this->db->exec("COMMIT;");

            render('ticket/result', [
                'ok'              => true,
                'ticket_id'       => $ticketId,
                'total'           => $totalPrice,
                'discount_amount' => $discountAmount,
            ]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->exec("ROLLBACK;"); }
            render('ticket/result', ['ok' => false, 'msg' => 'İşlem başarısız.']);
        }
    }

    /*  BİLETLERİM  */
    public function myTickets(): void {
        require_user();
        if (function_exists('require_method')) { require_method(['GET']); }

        if (function_exists('expire_past_tickets')) {
            expire_past_tickets($this->db);
        } else {
            $this->expirePastTicketsLocal();
        }

        $now = $this->nowStrTR();
        $stmt = $this->db->prepare("
          SELECT
            tk.id              AS ticket_id,
            tk.total_price     AS total_price,
            tk.created_at      AS created_at,
            CASE
              WHEN tk.status='active'
               AND DATETIME(t.departure_time) <= DATETIME(:now) THEN 'expired'
              ELSE tk.status
            END                 AS status_view,
            t.departure_city, t.destination_city, t.departure_time, t.arrival_time, t.price,
            b.name              AS company_name
          FROM Tickets tk
          JOIN Trips t       ON t.id = tk.trip_id
          JOIN Bus_Company b ON b.id = t.company_id
          WHERE tk.user_id = :uid
          ORDER BY DATETIME(t.departure_time) DESC, tk.created_at DESC
        ");
        $stmt->execute([':uid' => current_user()['id'], ':now' => $now]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        render('ticket/my', ['tickets' => $tickets]);
    }

    /* BİLET İPTAL  */
    public function cancel(): void {
        require_user();
        csrf_verify_or_die();
        if (function_exists('require_method')) { require_method(['POST']); }

        $ticketId = (string)($_POST['id'] ?? '');
        if ($ticketId === '') { http_response_code(400); echo "Bilet ID gerekli."; return; }

        if (function_exists('expire_past_tickets')) {
            expire_past_tickets($this->db);
        } else {
            $this->expirePastTicketsLocal();
        }

        $q = $this->db->prepare("
            SELECT tk.id, tk.user_id, tk.status, tk.total_price, t.departure_time
              FROM Tickets tk
              JOIN Trips t ON t.id = tk.trip_id
             WHERE tk.id = :tid AND tk.user_id = :uid
             LIMIT 1
        ");
        $q->execute([':tid' => $ticketId, ':uid' => current_user()['id']]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) { http_response_code(404); echo "Bilet bulunamadı."; return; }

        try {
            $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Istanbul'));
            $dep = new DateTimeImmutable((string)$row['departure_time'], new DateTimeZone('Europe/Istanbul'));
        } catch (Throwable) {
            http_response_code(400); echo "Tarih doğrulama hatası."; return;
        }

        $status = strtolower((string)$row['status']);
        if ($status !== 'active') { echo "Bu bilet iptal edilemez."; return; }
        if ($dep <= $now)         { echo "Kalkış geçmiş; iptal edilemez."; return; }
        if (($dep->getTimestamp() - $now->getTimestamp()) < 3600) {
            echo "Kalkışa 1 saatten az kaldığı için iptal edilemez."; return;
        }

        try {
            $this->db->exec("BEGIN IMMEDIATE;");
            $this->db->exec("PRAGMA foreign_keys = ON;");

            $a = $this->db->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = :tid AND status = 'active'");
            $a->execute([':tid' => $ticketId]);
            if ($a->rowCount() !== 1) throw new RuntimeException('Bilet durumu güncellenemedi.');

            $this->db->prepare("DELETE FROM Booked_Seats WHERE ticket_id = :tid")->execute([':tid' => $ticketId]);

            $c = $this->db->prepare('UPDATE "User" SET balance = balance + :amt WHERE id = :uid');
            $c->execute([':amt' => (int)$row['total_price'], ':uid' => current_user()['id']]);
            if ($c->rowCount() !== 1) throw new RuntimeException('İade yapılamadı.');

            if (function_exists('audit')) { audit('ticket.cancel', ['ticket_id'=>$ticketId]); }

            $this->db->exec("COMMIT;");
            redirect('/?r=ticket/my');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->exec("ROLLBACK;"); }
            echo "İptal hatası.";
        }
    }

    /* PDF */
    public function pdf(): void {
        if (function_exists('require_login')) { require_login(); } else { require_user(); }
        if (function_exists('require_method')) { require_method(['GET']); }

        $ticketId = (string)($_GET['id'] ?? '');
        if (!preg_match('/^[A-Fa-f0-9]{16,128}$/', $ticketId)) {
            http_response_code(400);
            echo 'Geçersiz istek';
            return;
        }

        $q = $this->db->prepare("
            SELECT 
                tk.id            AS ticket_id,
                tk.user_id       AS owner_user_id,
                tk.status        AS ticket_status,
                tk.total_price   AS total_price,
                tk.created_at    AS purchased_at,
                t.id             AS trip_id,
                t.company_id     AS trip_company_id,
                t.departure_time, t.arrival_time,
                t.departure_city, t.destination_city,
                t.price          AS unit_price,
                b.name           AS company_name
            FROM Tickets tk
            JOIN Trips   t     ON t.id = tk.trip_id
            JOIN Bus_Company b ON b.id = t.company_id
            WHERE tk.id = :tid
            LIMIT 1
        ");
        $q->execute([':tid' => $ticketId]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) { http_response_code(404); echo "Bilet bulunamadı."; return; }

        $me = current_user() ?: [];
        $roleStr = strtolower((string)($me['role'] ?? ''));

        $isOwner = ((string)$row['owner_user_id'] === (string)($me['id'] ?? ''));

        $isAdmin = (function_exists('has_role') && defined('ROLE_ADMIN') && has_role(ROLE_ADMIN)) || ($roleStr === 'admin');

        $sessionCompanyId = (string)($me['company_id'] ?? '');
        if ($sessionCompanyId === '') {
            try {
                $uq = $this->db->prepare('SELECT company_id FROM "User" WHERE id = ? LIMIT 1');
                $uq->execute([(string)($me['id'] ?? '')]);
                $urow = $uq->fetch(PDO::FETCH_ASSOC);
                if ($urow && ($urow['company_id'] ?? '') !== '') {
                    $sessionCompanyId = (string)$urow['company_id'];
                }
            } catch (Throwable) {  }
        }
       
        $tripCompanyStr = (string)$row['trip_company_id'];
        $tripCompanyInt = (int)$row['trip_company_id'];
        $sessCompanyStr = (string)$sessionCompanyId;
        $sessCompanyInt = (int)$sessionCompanyId;

        $isCompanyMatch = ($sessCompanyStr !== '') && (
            $tripCompanyStr === $sessCompanyStr || $tripCompanyInt === $sessCompanyInt
        );

        if (!($isOwner || $isCompanyMatch || $isAdmin)) {
            http_response_code(403);
            echo 'Bu bilete erişim yetkiniz yok';
            return;
        }

        $s = $this->db->prepare("
            SELECT seat_number
            FROM Booked_Seats
            WHERE ticket_id = :tid
            ORDER BY seat_number ASC
        ");
        $s->execute([':tid' => $ticketId]);
        $seats = array_map(fn($r) => (int)$r['seat_number'], $s->fetchAll(PDO::FETCH_ASSOC));

        if (!class_exists('FPDF')) {
            if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__, 1)); }
            require_once BASE_PATH . '/app/lib/fpdf.php';
        }
        $prevDisplay = ini_get('display_errors');
        @ini_set('display_errors', '0');
        while (ob_get_level() > 0) { @ob_end_clean(); }

        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0, 10, $this->pdfTxt('Otobüs Bileti'), 0, 1, 'C');

        $pdf->SetFont('Arial','',12);
        $pdf->Ln(2);
        $pdf->Cell(40, 8, $this->pdfTxt('Firma:'));          $pdf->Cell(0, 8, $this->pdfTxt((string)$row['company_name']), 0, 1);
        $route = $row['departure_city'].' -> '.$row['destination_city'];
        $pdf->Cell(40, 8, $this->pdfTxt('Güzergâh:'));       $pdf->Cell(0, 8, $this->pdfTxt($route), 0, 1);
        $pdf->Cell(40, 8, $this->pdfTxt('Kalkış:'));         $pdf->Cell(0, 8, $this->pdfTxt((string)$row['departure_time']), 0, 1);
        $pdf->Cell(40, 8, $this->pdfTxt('Varış:'));          $pdf->Cell(0, 8, $this->pdfTxt((string)($row['arrival_time'] ?? '-')), 0, 1);
        $pdf->Cell(40, 8, $this->pdfTxt('Koltuk(lar):'));    $pdf->Cell(0, 8, $this->pdfTxt(implode(', ', $seats)), 0, 1);
        $pdf->Cell(40, 8, $this->pdfTxt('Birim Fiyat:'));    $pdf->Cell(0, 8, $this->pdfTxt((string)((int)$row['unit_price']).' TL'), 0, 1);
        $pdf->Cell(40, 8, $this->pdfTxt('Toplam:'));         $pdf->Cell(0, 8, $this->pdfTxt((string)((int)$row['total_price']).' TL'), 0, 1);
        $pdf->Cell(40, 8, $this->pdfTxt('Satın Alma:'));     $pdf->Cell(0, 8, $this->pdfTxt((string)($row['purchased_at'] ?? '-')), 0, 1);

        $pdf->Ln(4);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0, 8, $this->pdfTxt('Bilet No: '.$row['ticket_id']), 0, 1);

        $st = strtolower((string)$row['ticket_status']);
        if ($st === 'canceled' || $st === 'expired') {
            $label = strtoupper($st); 
            $pdf->Ln(10);
            $pdf->SetTextColor(180, 0, 0);
            $pdf->SetFont('Arial','B',36);
           
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="bilet_'.$row['ticket_id'].'.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        $pdf->Output('I', 'bilet_'.$row['ticket_id'].'.pdf');
        @ini_set('display_errors', (string)$prevDisplay);
        exit;
    }
}
