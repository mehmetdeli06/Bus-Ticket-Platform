<?php
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$fmtDT = function ($s) {
  if (!$s) return '—';
  $ts = strtotime((string)$s);
  return $ts !== false ? date('d.m.Y H:i', $ts) : e((string)$s);
};
$fmtPrice = function ($p) { return number_format((float)$p, 0, ',', '.') . ' ₺'; };

$trip      = is_array($trip ?? null) ? $trip : [];
$occupied  = array_map('intval', (array)($occupied ?? []));
$occSet    = array_fill_keys($occupied, true);

$capacity  = max(0, (int)($trip['capacity'] ?? 0));
$rows      = (int)ceil($capacity / 4);
$used      = count($occupied);
$free      = max(0, $capacity - $used);
$rate      = ($capacity > 0) ? (int)round(($used / $capacity) * 100) : 0;

$companyName = $trip['company_name']     ?? '-';
$depCity     = $trip['departure_city']   ?? '-';
$destCity    = $trip['destination_city'] ?? '-';

$NONCE = (string)($GLOBALS['CSP_NONCE'] ?? ''); 
?>

<div class="container my-4">
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">
          🚌 <?= e($companyName) ?> — <?= e($depCity) ?> → <?= e($destCity) ?>
        </h2>
        <a href="/?r=admin/trips" class="btn btn-outline-secondary btn-sm">Listeye Dön</a>
      </div>

      <p class="text-muted mb-2">
        <strong>Kalkış:</strong> <?= e($fmtDT($trip['departure_time'] ?? '')) ?> &nbsp;|
        <strong>Varış:</strong> <?= e($fmtDT($trip['arrival_time'] ?? '')) ?> &nbsp;|
        <strong>Kapasite:</strong> <?= $capacity ?> &nbsp;|
        <strong>Fiyat:</strong> <?= $fmtPrice($trip['price'] ?? 0) ?>
      </p>

      <div class="row g-2 align-items-center mb-3">
        <div class="col-auto"><span class="badge text-bg-danger">Dolu: <?= $used ?></span></div>
        <div class="col-auto"><span class="badge text-bg-success">Boş: <?= $free ?></span></div>
        <div class="col">
          <?php $barClass = $rate>=80?'bg-danger':($rate>=40?'bg-warning':'bg-success'); ?>
          <div class="progress" style="height:8px;" aria-label="Doluluk oranı">
            <div class="progress-bar <?= $barClass ?>" role="progressbar"
                 style="width: <?= $rate ?>%;"
                 aria-valuenow="<?= $rate ?>" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          <div class="small text-muted mt-1">Doluluk: %<?= $rate ?></div>
        </div>
      </div>

      <div class="d-flex align-items-center gap-3 small mb-3" aria-label="Lejant">
        <span class="legend legend-available" aria-hidden="true"></span> Boş
        <span class="legend legend-occupied" aria-hidden="true"></span> Dolu
      </div>

      <div class="bus-wrap" role="group" aria-label="Koltuk planı">
        <div class="driver" aria-hidden="true">Sürücü</div>

        <?php
        $n = 1;
        for ($row = 1; $row <= $rows; $row++):
          $left1  = ($n <= $capacity) ? $n++ : null;
          $left2  = ($n <= $capacity) ? $n++ : null;
          $right1 = ($n <= $capacity) ? $n++ : null;
          $right2 = ($n <= $capacity) ? $n++ : null;
        ?>
          <div class="row-seats">
            <?php foreach ([$left1,$left2] as $seatNo): ?>
              <?php if ($seatNo === null): ?>
                <div class="seat placeholder" aria-hidden="true"></div>
              <?php else:
                $isOcc = isset($occSet[(int)$seatNo]);
              ?>
                <div class="seat <?= $isOcc ? 'occupied' : 'available' ?>"
                     aria-label="Koltuk <?= (int)$seatNo ?> <?= $isOcc ? 'dolu' : 'boş' ?>"
                     tabindex="0">
                  <?= (int)$seatNo ?>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>

            <div class="aisle" aria-hidden="true"></div>

            <?php foreach ([$right1,$right2] as $seatNo): ?>
              <?php if ($seatNo === null): ?>
                <div class="seat placeholder" aria-hidden="true"></div>
              <?php else:
                $isOcc = isset($occSet[(int)$seatNo]);
              ?>
                <div class="seat <?= $isOcc ? 'occupied' : 'available' ?>"
                     aria-label="Koltuk <?= (int)$seatNo ?> <?= $isOcc ? 'dolu' : 'boş' ?>"
                     tabindex="0">
                  <?= (int)$seatNo ?>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</div>

<style nonce="<?= e($NONCE) ?>">
  :root{
    --seat-border:#e5e7eb;
    --seat-free:#eaf7f2;
    --seat-free-text:#0f5132;
    --seat-occ:#dc3545;
  }
  .card{ border:0; box-shadow:0 6px 16px rgba(0,0,0,.06); }

  .legend { display:inline-block; width:16px; height:16px; border-radius:4px; border:1px solid rgba(0,0,0,.1); }
  .legend-available { background: var(--seat-free); border-color: var(--seat-border); }
  .legend-occupied  { background: var(--seat-occ);  border-color: var(--seat-occ); }

  .bus-wrap { max-width: 560px; }
  .driver {
    width: 140px; text-align:center; font-weight:600; font-size:12px;
    padding:6px 8px; background:#f1f5f9; border:1px solid #e5e7eb; border-radius:8px; margin-bottom:10px;
  }
  .row-seats {
    display:grid;
    grid-template-columns: 1fr 1fr 30px 1fr 1fr; 
    gap: 10px; margin-bottom: 10px; align-items: center;
  }
  .aisle { height: 1px; }

  .seat {
    display:flex; align-items:center; justify-content:center;
    height:48px; border-radius:10px; border:1px solid var(--seat-border);
    font-weight:700; user-select:none; outline: none;
    transition: transform .08s ease, box-shadow .08s ease;
  }
  .seat.available { background: var(--seat-free); color: var(--seat-free-text); }
  .seat.occupied  { background: var(--seat-occ); color:#fff; border-color: var(--seat-occ); }
  .seat.placeholder { visibility:hidden; }
  .seat:focus-visible { box-shadow:0 0 0 3px rgba(25,169,116,.35); transform: translateY(-1px); }

  @media (max-width:480px){
    .row-seats{ gap:8px; grid-template-columns: 1fr 1fr 20px 1fr 1fr; }
    .seat{ height:44px; font-size:14px; }
    .driver{ width:120px; }
  }
</style>
