<style>
  :root{
    --bs-primary:#19a974; --bs-success:#138a63; --bs-link-color:#138a63; --bs-btn-border-radius:.6rem;
  }
  .card{border:0;box-shadow:0 6px 16px rgba(0,0,0,.06);}
  .table>thead th{font-weight:600;}
</style>

<?php
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$fmtPrice = function($p){
  $v = (float)$p; return number_format($v, 0, ',', '.') . ' ₺';
};
$fmtDT = function($s){
  if (!$s) return '—';
  $ts = strtotime((string)$s);
  return $ts ? date('d.m.Y H:i', $ts) : e((string)$s);
};

$companies = is_array($companies ?? null) ? $companies : [];
$trips     = is_array($trips ?? null)     ? $trips     : [];
$filter_company = (string)($filter_company ?? '');
?>

<div class="container my-4">
  <div class="card">
    <div class="card-body">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <h2 class="h4 mb-0"><i class="bi bi-bus-front me-2"></i>Seferler</h2>

        <form method="get" action="/" class="d-flex align-items-center gap-2" role="search" aria-label="Firma filtresi">
          <input type="hidden" name="r" value="admin/trips">
          <label for="company_id" class="form-label mb-0">Firma:</label>
          <select id="company_id" name="company_id" class="form-select" style="min-width:220px"
                  onchange="this.form.submit()">
            <option value="">Hepsi</option>
            <?php foreach ($companies as $c): ?>
              <?php $cid = (string)($c['id'] ?? ''); ?>
              <option value="<?= e($cid) ?>" <?= $filter_company === $cid ? 'selected' : '' ?>>
                <?= e($c['name'] ?? '-') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <noscript><button class="btn btn-primary">Uygula</button></noscript>
        </form>
      </div>

      <?php if (empty($trips)): ?>
        <div class="alert alert-info mb-0">
          <i class="bi bi-info-circle me-1"></i> Seçili filtreye uygun sefer bulunamadı.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col">Firma</th>
                <th scope="col">Kalkış</th>
                <th scope="col">Varış</th>
                <th scope="col">Fiyat</th>
                <th scope="col">Kalkış Zamanı</th>
                <th scope="col" class="text-end">İşlemler</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($trips as $t): ?>
                <?php
                  $company  = $t['company_name']    ?? '-';
                  $depCity  = $t['departure_city']  ?? '-';
                  $destCity = $t['destination_city']?? '-';
                  $price    = $t['price']           ?? 0;
                  $depTime  = $t['departure_time']  ?? '';
                  $id       = $t['id']              ?? '';

                  $ts = $depTime ? strtotime((string)$depTime) : false;
                  $isPast = $ts !== false && $ts < time();
                ?>
                <tr>
                  <td><?= e($company) ?></td>
                  <td><?= e($depCity) ?></td>
                  <td><?= e($destCity) ?></td>
                  <td><?= $fmtPrice($price) ?></td>
                  <td>
                    <?php if ($isPast): ?>
                      <span class="badge text-bg-secondary me-1">Geçmiş</span>
                    <?php endif; ?>
                    <?= $fmtDT($depTime) ?>
                  </td>
                  <td class="text-end" style="min-width:160px;">
                    <a href="/?r=admin/trip-show&id=<?= rawurlencode((string)$id) ?>"
                       class="btn btn-sm btn-outline-primary">
                      <i class="bi bi-eye"></i> Detay
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if (!empty($pagination)): ?>
          <nav class="mt-3" aria-label="Sayfalama">
            <ul class="pagination justify-content-end mb-0">
              <?php if (!empty($pagination['prev'])): ?>
                <li class="page-item">
                  <a class="page-link" href="<?= e($pagination['prev']) ?>">Önceki</a>
                </li>
              <?php endif; ?>
              <?php if (!empty($pagination['next'])): ?>
                <li class="page-item">
                  <a class="page-link" href="<?= e($pagination['next']) ?>">Sonraki</a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>