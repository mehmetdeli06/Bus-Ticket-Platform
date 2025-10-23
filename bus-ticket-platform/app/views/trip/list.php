<!-- (GEREKİRSE) Bootstrap 5 + Icons; layout'ta zaten varsa bu iki satırı kaldırın -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<?php
// Güvenli kaçış helper
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// Güvenli/yardımcı formatlar
$fmtDT = function($s){
  if (!$s) return '—';
  $ts = strtotime((string)$s);
  return $ts ? date('d.m.Y H:i', $ts) : e((string)$s);
};
$badgeStatus = function($depTime){
  $ts = strtotime((string)$depTime);
  if ($ts && $ts < time()) return '<span class="badge text-bg-secondary">Geçmiş</span>';
  return '<span class="badge text-bg-success">Uygun</span>';
};

$departure   = $departure   ?? '';
$destination = $destination ?? '';
$date        = $date        ?? '';
$trips       = is_array($trips ?? null) ? $trips : [];
?>

<div class="container my-4">
  <div class="card shadow-sm mb-4 border-0">
    <div class="card-body">
      <h2 class="h4 mb-2">🚌 Sefer Sonuçları</h2>
      <p class="text-muted mb-0">
        <strong>Arama:</strong> <?= e($departure ?: '-') ?> → <?= e($destination ?: '-') ?>
        <?php if (!empty($date)): ?>
          <span class="mx-2">|</span> <strong>Tarih:</strong> <?= e($date) ?>
        <?php endif; ?>
      </p>
    </div>
  </div>

  <?php if (empty($trips)): ?>
    <div class="alert alert-warning text-center">
      <i class="bi bi-exclamation-triangle"></i> Sonuç bulunamadı.
    </div>
  <?php else: ?>
    <div class="card shadow-sm border-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Firma</th>
              <th>Güzergâh</th>
              <th>Kalkış</th>
              <th>Varış</th>
              <th class="text-nowrap">Fiyat (₺)</th>
              <th class="text-nowrap">Kapasite</th>
              <th class="text-center">Durum</th>
              <th class="text-center">İşlem</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($trips as $t): ?>
              <?php
                $id        = (string)($t['id'] ?? '');
                $company   = (string)($t['company_name'] ?? '');
                $depCity   = (string)($t['departure_city'] ?? '');
                $dstCity   = (string)($t['destination_city'] ?? '');
                $depTime   = (string)($t['departure_time'] ?? '');
                $arrTime   = (string)($t['arrival_time'] ?? '');
                $price     = (int)($t['price'] ?? 0);
                $capacity  = (int)($t['capacity'] ?? 0);
                $ts        = strtotime($depTime);
                $isPast    = $ts && $ts < time();
                $rowClass  = $isPast ? 'table-secondary' : '';
                $href      = '/?r=ticket/purchase&id=' . rawurlencode($id);
              ?>
              <tr class="<?= $rowClass ?>">
                <td class="fw-semibold"><?= e($company) ?></td>
                <td><?= e($depCity) ?> → <?= e($dstCity) ?></td>
                <td class="text-nowrap">
                  <?php if ($isPast): ?><span class="badge text-bg-secondary me-1">Geçmiş</span><?php endif; ?>
                  <?= $fmtDT($depTime) ?>
                </td>
                <td class="text-nowrap"><?= $fmtDT($arrTime) ?></td>
                <td class="fw-semibold"><?= $price ?></td>
                <td><?= $capacity ?></td>
                <td class="text-center"><?= $badgeStatus($depTime) ?></td>
                <td class="text-center" style="min-width:220px;">
                  <a href="<?= e($href) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-info-circle"></i> Detay
                  </a>
                  <?php if (function_exists('has_role') && has_role(ROLE_USER) && !$isPast): ?>
                    <a href="<?= e($href) ?>" class="btn btn-success btn-sm">
                      <i class="bi bi-ticket-perforated"></i> Satın Al
                    </a>
                  <?php elseif (!function_exists('is_logged_in') || !is_logged_in()): ?>
                    <a href="/?r=auth/login" class="btn btn-outline-secondary btn-sm">
                      <i class="bi bi-box-arrow-in-right"></i> Giriş Yap
                    </a>
                  <?php else: ?>
                    <?php if ($isPast): ?>
                      <span class="text-muted small">Sefer geçmiş.</span>
                    <?php else: ?>
                      <span class="text-muted small">Satın alma yalnızca yolcular içindir.</span>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>
