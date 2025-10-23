<?php

/** @var array $trip */
/** @var array $occupied  seatNo => ['ticket_id','full_name'] */
/** @var int   $capacity,$occupiedCt,$freeCt,$rate */

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$NONCE = (string)($GLOBALS['CSP_NONCE'] ?? ''); 

$trip        = is_array($trip ?? null) ? $trip : [];
$occupied    = is_array($occupied ?? null) ? $occupied : [];
$capacity    = max(0, (int)($capacity ?? ($trip['capacity'] ?? 0)));
$occupiedCt  = isset($occupiedCt) ? max(0,(int)$occupiedCt) : count($occupied);
$freeCt      = max(0, $capacity - $occupiedCt);
$rate        = isset($rate) ? (float)$rate : ($capacity ? ($occupiedCt / $capacity) * 100 : 0);
$rateInt     = (int)round(min(100, max(0, $rate)));

$company     = $trip['company_name'] ?? $trip['company_id'] ?? '-';
$depCity     = $trip['departure_city']   ?? '-';
$destCity    = $trip['destination_city'] ?? '-';
$depTimeRaw  = $trip['departure_time']   ?? '';
$arrTimeRaw  = $trip['arrival_time']     ?? '';

$fmtDT = function($s){
  if (!$s) return '—';
  $ts = strtotime((string)$s);
  return $ts!==false ? date('d.m.Y H:i', $ts) : e((string)$s);
};
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<div class="container my-5">
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div>
          <h2 class="h4 mb-1">🚌 <?= e($depCity) ?> → <?= e($destCity) ?></h2>
          <div class="text-muted small">
            Firma: <strong><?= e($company) ?></strong>
            <span class="mx-2">|</span>
            Kalkış: <strong><?= e($fmtDT($depTimeRaw)) ?></strong>
            <span class="mx-2">|</span>
            Varış: <strong><?= e($fmtDT($arrTimeRaw)) ?></strong>
            <span class="mx-2">|</span>
            Kapasite: <strong><?= (int)$capacity ?></strong>
          </div>
        </div>
        <div class="mt-3 mt-md-0">
          <a href="/?r=company/panel" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Sefer listesine dön
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
      <div class="row g-3 align-items-center">
        <div class="col-12 col-lg-6">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="badge rounded-pill text-bg-danger px-3 py-2">Dolu: <strong class="ms-1"><?= (int)$occupiedCt ?></strong></span>
            <span class="badge rounded-pill text-bg-success px-3 py-2">Müsait: <strong class="ms-1"><?= (int)$freeCt ?></strong></span>
            <span class="badge rounded-pill text-bg-primary px-3 py-2">Doluluk: <strong class="ms-1">%<?= $rateInt ?></strong></span>
          </div>
        </div>
        <div class="col-12 col-lg-6">
          <?php $barClass = $rateInt >= 90 ? 'bg-danger' : ($rateInt >= 60 ? 'bg-warning' : 'bg-success'); ?>
          <div class="progress" style="height:10px;" aria-label="Doluluk oranı">
            <div class="progress-bar <?= $barClass ?>" role="progressbar"
                 style="width: <?= $rateInt ?>%;" aria-valuenow="<?= $rateInt ?>" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
        </div>
      </div>

      <div class="d-flex align-items-center gap-3 small mt-3" aria-label="Koltuk lejantı">
        <span class="legend legend-free"></span> Müsait
        <span class="legend legend-occupied"></span> Dolu
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <div class="bus-wrap mx-auto">
        <div class="driver">Sürücü</div>

        <?php
          $rows = (int)ceil($capacity / 4);
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
                $occ = $occupied[$seatNo] ?? null;
                $isOcc = is_array($occ);
                $tId = $isOcc ? (string)($occ['ticket_id'] ?? '') : '';
                $fnm = $isOcc ? (string)($occ['full_name'] ?? '') : '';
                $title = $isOcc ? ("Koltuk $seatNo — DOLU\nBilet: ".$tId."\nYolcu: ".$fnm) : "Koltuk $seatNo — Müsait";
              ?>
                <div class="seat <?= $isOcc ? 'seat-occupied' : 'seat-free' ?>"
                     data-bs-toggle="tooltip" data-bs-title="<?= e($title) ?>"
                     aria-label="Koltuk <?= (int)$seatNo ?>">
                  <div class="seat-box">
                    <div class="seat-no"><?= (int)$seatNo ?></div>
                    <div class="seat-state"><?= $isOcc ? 'DOLU' : 'Müsait' ?></div>
                  </div>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>

            <div class="aisle" aria-hidden="true"></div>

            <?php foreach ([$right1,$right2] as $seatNo): ?>
              <?php if ($seatNo === null): ?>
                <div class="seat placeholder" aria-hidden="true"></div>
              <?php else:
                $occ = $occupied[$seatNo] ?? null;
                $isOcc = is_array($occ);
                $tId = $isOcc ? (string)($occ['ticket_id'] ?? '') : '';
                $fnm = $isOcc ? (string)($occ['full_name'] ?? '') : '';
                $title = $isOcc ? ("Koltuk $seatNo — DOLU\nBilet: ".$tId."\nYolcu: ".$fnm) : "Koltuk $seatNo — Müsait";
              ?>
                <div class="seat <?= $isOcc ? 'seat-occupied' : 'seat-free' ?>"
                     data-bs-toggle="tooltip" data-bs-title="<?= e($title) ?>"
                     aria-label="Koltuk <?= (int)$seatNo ?>">
                  <div class="seat-box">
                    <div class="seat-no"><?= (int)$seatNo ?></div>
                    <div class="seat-state"><?= $isOcc ? 'DOLU' : 'Müsait' ?></div>
                  </div>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>

  <div class="text-center mt-3">
    <a href="/?r=company/panel" class="btn btn-light">
      <i class="bi bi-arrow-left"></i> Sefer listesine dön
    </a>
  </div>
</div>

<style nonce="<?= e($NONCE) ?>">
  :root{--bs-primary:#19a974;--bs-success:#138a63;--bs-link-color:#138a63;--bs-btn-border-radius:.6rem}
  .card{border:0;box-shadow:0 8px 24px rgba(0,0,0,.06)}
  .legend{display:inline-block;width:14px;height:14px;border-radius:4px;border:1px solid rgba(0,0,0,.08)}
  .legend-free{background:#e8ffe8}
  .legend-occupied{background:#ffd6d6}
  .bus-wrap{max-width:720px}
  .driver{width:140px;text-align:center;font-weight:600;font-size:12px;padding:6px 8px;background:#f1f5f9;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:10px}
  .row-seats{display:grid;grid-template-columns:1fr 1fr 34px 1fr 1fr;gap:10px;margin-bottom:10px;align-items:center}
  .aisle{height:1px}
  .seat{user-select:none;display:flex;align-items:center;justify-content:center}
  .seat-box{display:flex;flex-direction:column;align-items:center;justify-content:center;width:56px;height:56px;border-radius:10px;font-weight:700;border:1px solid #e5e7eb;background:#f8fafc;color:#111;transition:transform .12s,box-shadow .12s,background .12s,border-color .12s}
  .seat-free .seat-box{background:#e8ffe8;border-color:#c4f3c4}
  .seat-occupied .seat-box{background:#ffd6d6;border-color:#f1b7b7}
  .seat .seat-box:hover{transform:translateY(-1px);box-shadow:0 6px 14px rgba(0,0,0,.08)}
  .seat-no{line-height:1}
  .seat-state{font-size:12px;color:#6b7280}
  .seat.placeholder{visibility:hidden}
  @media(max-width:576px){
    .row-seats{gap:8px;grid-template-columns:1fr 1fr 22px 1fr 1fr}
    .seat-box{width:52px;height:52px}
    .driver{width:120px}
  }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script nonce="<?= e($NONCE) ?>">
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){ new bootstrap.Tooltip(el); });
</script>
