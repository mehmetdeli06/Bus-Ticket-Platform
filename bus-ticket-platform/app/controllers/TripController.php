<?php
declare(strict_types=1);

class TripController {
    public function __construct(private PDO $db) {
        $this->db->exec("PRAGMA foreign_keys = ON;");
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function tr_fold(string $s): string {
        $map = [
            'İ'=>'I','I'=>'I','ı'=>'i',
            'Ş'=>'S','ş'=>'s',
            'Ğ'=>'G','ğ'=>'g',
            'Ü'=>'U','ü'=>'u',
            'Ö'=>'O','ö'=>'o',
            'Ç'=>'C','ç'=>'c',
        ];
        return strtolower(strtr($s, $map));
    }

    private function sql_tr_fold(string $col): string {
        $expr = $col;
        $pairs = [
            ["'İ'","'I'"], ["'I'","'I'"], ["'ı'","'i'"],
            ["'Ş'","'S'"], ["'ş'","'s'"],
            ["'Ğ'","'G'"], ["'ğ'","'g'"],
            ["'Ü'","'U'"], ["'ü'","'u'"],
            ["'Ö'","'O'"], ["'ö'","'o'"],
            ["'Ç'","'C'"], ["'ç'","'c'"],
        ];
        foreach ($pairs as [$from, $to]) {
            $expr = "REPLACE($expr, $from, $to)";
        }
        return "LOWER($expr)";
    }

    private function escape_like(string $s): string {
        $s = str_replace('\\', '\\\\', $s);
        $s = str_replace('%',  '\%',  $s);
        $s = str_replace('_',  '\_',  $s);
        return $s;
    }

    private function nowStrTR(): string {
        return (new DateTimeImmutable('now', new DateTimeZone('Europe/Istanbul')))->format('Y-m-d H:i:s');
    }

    private function paginate(): array {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per  = max(1, min(100, (int)($_GET['per'] ?? 20)));
        $offset = ($page - 1) * $per;
        return [$page, $per, $offset];
    }

    /* Arama/List  */

    public function search(): void {
        if (function_exists('require_method')) { require_method('GET'); }

        if (is_logged_in()) {
            if (has_role(ROLE_COMPANY)) { redirect('company/panel'); return; }
            if (has_role(ROLE_ADMIN))   { redirect('admin/panel');   return; }
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $departure   = trim((string)($_GET['departure']   ?? ''));
        $destination = trim((string)($_GET['destination'] ?? ''));
        $dateRaw     = trim((string)($_GET['date']        ?? ''));
        $showPast    = (isset($_GET['show_past']) && $_GET['show_past'] === '1');

        if (mb_strlen($departure)   > 64) $departure   = mb_substr($departure,   0, 64);
        if (mb_strlen($destination) > 64) $destination = mb_substr($destination, 0, 64);

        $depKey  = $departure   !== '' ? '%'.$this->tr_fold($this->escape_like($departure)).'%'   : null;
        $destKey = $destination !== '' ? '%'.$this->tr_fold($this->escape_like($destination)).'%' : null;

        $date = '';
        if ($dateRaw !== '') {
            try {
                $d = DateTimeImmutable::createFromFormat('Y-m-d', $dateRaw, new DateTimeZone('Europe/Istanbul'));
                if ($d === false) { throw new RuntimeException('bad date'); }
                $date = $d->format('Y-m-d');
            } catch (Throwable) {
                $date = '';
            }
        }

        [$page, $per, $offset] = $this->paginate();
        $now = $this->nowStrTR();

        $where  = ["1=1"];
        $params = [];

        if ($departure !== '') {
            $where[] = $this->sql_tr_fold("t.departure_city") . " LIKE :dep ESCAPE '\\'";
            $params[':dep'] = $depKey;
        }
        if ($destination !== '') {
            $where[] = $this->sql_tr_fold("t.destination_city") . " LIKE :dest ESCAPE '\\'";
            $params[':dest'] = $destKey;
        }
        if ($date !== '') {
            $where[] = "DATE(t.departure_time) = DATE(:date)";
            $params[':date'] = $date;
        }
        if (!$showPast) {
            $where[] = "DATETIME(t.departure_time) > DATETIME(:now)";
            $params[':now'] = $now;
        }

        $whereSql = implode("\n  AND ", $where);

        $sqlCount = "
            SELECT COUNT(*)
              FROM Trips t
              JOIN Bus_Company b ON t.company_id = b.id
             WHERE $whereSql
        ";
        $c = $this->db->prepare($sqlCount);
        foreach ($params as $k => $v) {
            $c->bindValue($k, $v);
        }
        $c->execute();
        $total = (int)$c->fetchColumn();

        $sqlData = "
            SELECT t.*, b.name AS company_name
              FROM Trips t
              JOIN Bus_Company b ON t.company_id = b.id
             WHERE $whereSql
             ORDER BY t.departure_time ASC
             LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->db->prepare($sqlData);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  (int)$per,    PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        render('trip/list', [
            'trips'       => $trips,
            'departure'   => $departure,
            'destination' => $destination,
            'date'        => $dateRaw,
            'page'        => $page,
            'per'         => $per,
            'total'       => $total,
            'showPast'    => $showPast ? '1' : '0',
        ]);
    }

    public function show(): void {
        if (function_exists('require_method')) { require_method('GET'); }

        // yetki
        if (is_logged_in()) {
            if (has_role(ROLE_COMPANY)) { redirect('company/panel'); return; }
            if (has_role(ROLE_ADMIN))   { redirect('admin/panel');   return; }
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $id = (string)($_GET['id'] ?? '');
        if ($id === '') { http_response_code(400); echo "Eksik sefer ID"; return; }


        $stmt = $this->db->prepare("
            SELECT t.*, b.name AS company_name
              FROM Trips t
              JOIN Bus_Company b ON t.company_id = b.id
             WHERE t.id = :id
             LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trip) { http_response_code(404); echo "Sefer bulunamadı"; return; }

        render('trip/show', ['trip' => $trip]);
    }


    public function publicIndex(): void {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $now = $this->nowStrTR();

        $sql = "
            SELECT
                t.id,
                t.departure_city  AS route_from,
                t.destination_city AS route_to,
                t.departure_time,
                t.arrival_time,
                t.price,
                t.capacity,
                b.name AS company_name
            FROM Trips t
            LEFT JOIN Bus_Company b ON b.id = t.company_id
            WHERE DATETIME(t.departure_time) >= DATETIME(:now)
            ORDER BY t.departure_time ASC
            LIMIT 200
        ";
        $st = $this->db->prepare($sql);
        $st->execute([':now' => $now]);
        $trips = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        render('trip/public_index', [
            'trip' => $trips
        ]);

    }

    public function publicDetail(): void {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo 'Geçersiz sefer.';
            return;
        }

        $sql = "
            SELECT
                t.id,
                t.departure_city   AS route_from,
                t.destination_city AS route_to,
                t.departure_time,
                t.arrival_time,
                t.price,
                t.capacity,
                t.company_id,
                b.name AS company_name
            FROM Trips t
            LEFT JOIN Bus_Company b ON b.id = t.company_id
            WHERE t.id = :id
            LIMIT 1
        ";
        $st = $this->db->prepare($sql);
        $st->execute([':id' => $id]);
        $trip = $st->fetch(PDO::FETCH_ASSOC);

        if (!$trip) {
            http_response_code(404);
            echo 'Sefer bulunamadı.';
            return;
        }

        $st2 = $this->db->prepare("
            SELECT COUNT(1) AS sold
            FROM Tickets
            WHERE trip_id = :id AND status IN ('paid','reserved')
        ");
        $st2->execute([':id' => $id]);
        $sold = (int)($st2->fetchColumn() ?: 0);

        render('trip/public_detail', [
            'trip' => $trip,
            'sold' => $sold
        ]);
    }
}