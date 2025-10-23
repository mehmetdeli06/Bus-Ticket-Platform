<!-- (GEREKİRSE) Bootstrap 5 + Icons; layout'ta varsa bu iki satırı kaldırın -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<?php
// Güvenli kaçış helper
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// Güvenli veri / format yardımcıları
$trip = is_array($trip ?? null) ? $trip : [];

$fmtDT = function($s){
  if (!$s) return '—';
  $ts = strtotime((string)$s);
  return $ts ? date('d.m.Y H:i', $ts) : e((string)$s);
};

$depTime = (string)($trip['departure_time'] ?? '');
$arrTime = (string)($trip['arrival_time'] ?? '');
$depTs   = $depTime ? strtotime($depTime) : null;
$isPast  = ($depTs && $depTs < time());

$price   = (int)($trip['price'] ?? 0);
$cap     = (int)($trip['capacity'] ?? 0);

// Durum rozeti
$statusBadge = $isPast
  ? '<span class="badge text-bg-secondary">Geçmiş</span>'
  : '<span class="badge text-bg-success">Uygun</span>';
?>

<div class="container my-5">
  <?php if (empty($trip)): ?>
    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle"></i> Sefer bilgisi bulunamadı.
    </div>
  <?php else: ?>
    <div class="card shadow-sm border-0">
      <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
          <div>
            <h1 class="h4 mb-1">
              🚌 <?= e($trip['company_name'] ?? '-') ?> —
              <?= e($trip['departure_city'] ?? '-') ?> → <?= e($trip['destination_city'] ?? '-') ?>
            </h1>
            <div class="text-muted small">
              <i class="bi bi-calendar-event"></i>
              Kalkış: <strong><?= $fmtDT($depTime) ?></strong>
              <span class="mx-2">|</span>
              Varış: <strong><?= $fmtDT($arrTime) ?></strong>
              <span class="mx-2">|</span>
              Durum: <?= $statusBadge ?>
            </div>
          </div>
          <div class="text-end mt-3 mt-md-0">
            <div class="fs-5 fw-semibold text-success"><?= $price ?> ₺</div>
            <div class="text-muted small">Kapasite: <?= $cap ?> koltuk</div>
          </div>
        </div>

        <hr>

        <div class="d-flex flex-wrap gap-2">
          <?php
            $purchaseHref = '/?r=ticket/purchase&id=' . rawurlencode((string)($trip['id'] ?? ''));
            $canBuy = function_exists('has_role') && has_role(ROLE_USER) && !$isPast;
          ?>

          <?php if ($canBuy): ?>
            <a href="<?= e($purchaseHref) ?>" class="btn btn-success">
              <i class="bi bi-ticket-perforated"></i> Koltuk Seç & Satın Al
            </a>
          <?php elseif (function_exists('is_logged_in') && !is_logged_in()): ?>
            <a href="/?r=auth/login" class="btn btn-outline-primary">
              <i class="bi bi-box-arrow-in-right"></i> Satın almak için giriş yap
            </a>
          <?php else: ?>
            <div class="alert alert-secondary mb-0 py-2 px-3">
              <i class="bi bi-info-circle"></i>
              <?php if ($isPast): ?>
                Bu seferin kalkış saati geçmiş.
              <?php else: ?>
                Satın alma yalnızca yolcu hesapları için kullanılabilir.
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <a href="/?r=home/index" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Ana sayfaya dön
          </a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
