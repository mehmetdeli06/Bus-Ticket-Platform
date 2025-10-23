<?php
/** @var array $trips */
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$NONCE = (string)($GLOBALS['CSP_NONCE'] ?? '');

$cu        = function_exists('current_user') ? (current_user() ?: []) : [];
$companyId = (string)($cu['company_id'] ?? '—');

$flashOk  = function_exists('flash_get') ? flash_get('ok')  : null;
$flashErr = function_exists('flash_get') ? flash_get('err') : null;

$fmtDT = function($s){
  if (!$s) return '—';
  $ts = strtotime((string)$s);
  return $ts ? date('d.m.Y H:i', $ts) : e((string)$s);
};
$fmtPrice = function($p){
  $v = (float)$p;
  return number_format($v, 0, ',', '.') . ' ₺';
};

function trip_status_badge(bool $isPast): string {
  return $isPast
    ? '<span class="badge text-bg-secondary">Geçmiş</span>'
    : '<span class="badge text-bg-success">Aktif</span>';
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="container my-5">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
    <h3 class="mb-2 mb-md-0">🚌 Seferlerim</h3>
    <div class="d-flex align-items-center gap-2">
      <span class="text-muted me-2">Oturum firması: <strong><?= e($companyId) ?></strong></span>
      <a href="/?r=company/trip-new" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Yeni Sefer Ekle
      </a>
    </div>
  </div>

  <?php if (!empty($flashOk)): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
      <i class="bi bi-check-circle me-2"></i><div><?= e($flashOk) ?></div>
    </div>
  <?php endif; ?>
  <?php if (!empty($flashErr)): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i><div><?= e($flashErr) ?></div>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-6">
          <label for="tripFilter" class="form-label fw-semibold">Ara</label>
          <input id="tripFilter" type="search" class="form-control" placeholder="Şehir, tarih, durum, fiyat...">
        </div>
        <div class="col-6 col-md-3">
          <label for="statusFilter" class="form-label fw-semibold">Durum</label>
          <select id="statusFilter" class="form-select">
            <option value="">Tümü</option>
            <option value="aktif">Aktif</option>
            <option value="geçmiş">Geçmiş</option>
          </select>
        </div>
        <div class="col-6 col-md-3 d-grid">
          <button id="btnClear" class="btn btn-light">Filtreleri Temizle</button>
        </div>
      </div>
    </div>
  </div>

  <?php if (empty($trips)): ?>
    <div class="alert alert-info">
      <i class="bi bi-info-circle"></i> Kayıt bulunamadı. “Yeni Sefer Ekle” ile başlayabilirsiniz.
    </div>
  <?php else: ?>
    <div class="card shadow-sm border-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tripsTable">
          <thead class="table-light">
            <tr>
              <th>Firma</th>
              <th>Güzergâh</th>
              <th class="text-nowrap">Kalkış</th>
              <th>Durum</th>
              <th class="text-nowrap">Varış</th>
              <th class="text-nowrap">Fiyat</th>
              <th>Kapasite</th>
              <th class="text-end">İşlem</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($trips as $t):
              $company  = (string)($t['company_name'] ?? '');
              $depCity  = (string)($t['departure_city'] ?? '');
              $dstCity  = (string)($t['destination_city'] ?? '');
              $depTime  = (string)($t['departure_time'] ?? '');
              $arrTime  = (string)($t['arrival_time'] ?? '');
              $price    = (float)($t['price'] ?? 0);
              $cap      = (int)($t['capacity'] ?? 0);
              $idRaw    = (string)($t['id'] ?? '');
              $idHref   = rawurlencode($idRaw);

              $depTs   = strtotime($depTime);
              $isPastC = $depTs ? ($depTs < time()) : false;
              $isPast  = isset($t['is_past']) ? (bool)$t['is_past'] : $isPastC;

              $rowClass = $isPast ? 'table-secondary' : '';
            ?>
              <tr class="<?= $rowClass ?>" data-status="<?= $isPast ? 'geçmiş' : 'aktif' ?>">
                <td><strong><?= e($company) ?></strong></td>
                <td><?= e($depCity) ?> → <?= e($dstCity) ?></td>
                <td class="text-nowrap"><?= e($fmtDT($depTime)) ?></td>
                <td><?= trip_status_badge($isPast) ?></td>
                <td class="text-nowrap"><?= e($fmtDT($arrTime)) ?></td>
                <td class="fw-semibold"><?= e($fmtPrice($price)) ?></td>
                <td><?= (int)$cap ?></td>
                <td class="text-end" style="min-width: 260px;">
                  <?php if ($isPast): ?>
                    <span class="text-muted small me-2">Düzenle/Sil pasif</span>
                    <a href="/?r=company/trip-seats&id=<?= $idHref ?>" class="btn btn-outline-secondary btn-sm">
                      <i class="bi bi-grid-3x3-gap"></i> Koltuklar
                    </a>
                  <?php else: ?>
                    <a href="/?r=company/trip-edit&id=<?= $idHref ?>" class="btn btn-outline-primary btn-sm">
                      <i class="bi bi-pencil"></i> Düzenle
                    </a>
                    <a href="/?r=company/trip-seats&id=<?= $idHref ?>" class="btn btn-outline-secondary btn-sm">
                      <i class="bi bi-grid-3x3-gap"></i> Koltuklar
                    </a>
                    <form action="/?r=company/trip-delete-post" method="post" class="d-inline-block js-confirm">
                      <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                      <input type="hidden" name="id" value="<?= e($idRaw) ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash"></i> Sil
                      </button>
                    </form>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script nonce="<?= e($NONCE) ?>">
(() => {
  const $  = (s, r=document) => r.querySelector(s);
  const $$ = (s, r=document) => Array.from(r.querySelectorAll(s));

  const table = $('#tripsTable');
  const txt   = $('#tripFilter');
  const sel   = $('#statusFilter');
  const clr   = $('#btnClear');

  function applyFilters(){
    const q  = (txt?.value || '').toLocaleLowerCase('tr-TR').trim();
    const st = (sel?.value || '').toLocaleLowerCase('tr-TR');

    $$('#tripsTable tbody tr').forEach(tr => {
      let ok = true;
      if (q) {
        const text = tr.innerText.toLocaleLowerCase('tr-TR');
        ok = ok && text.includes(q);
      }
      if (st) {
        const rowSt = (tr.getAttribute('data-status') || '').toLocaleLowerCase('tr-TR');
        ok = ok && (rowSt === st);
      }
      tr.style.display = ok ? '' : 'none';
    });
  }

  if (table){
    txt?.addEventListener('input', applyFilters);
    sel?.addEventListener('change', applyFilters);
    clr?.addEventListener('click', () => { if (txt) txt.value=''; if (sel) sel.value=''; applyFilters(); });
    applyFilters();
  }

  $$('.js-confirm').forEach(f => {
    f.addEventListener('submit', (e) => { if (!confirm('Sefer silinsin mi?')) e.preventDefault(); });
  });
})();
</script>
