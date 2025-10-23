<?php

?>
<style>
  :root{ --bs-primary:#19a974; --bs-success:#138a63; --bs-link-color:#138a63; --bs-btn-border-radius:.6rem; }
  .card{border:0;box-shadow:0 8px 24px rgba(0,0,0,.06);}
  .table>thead th{font-weight:600;}
</style>

<?php
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$fmtDT = function($s){
  if (!$s) return '—';
  $ts = strtotime((string)$s);
  return $ts !== false ? date('d.m.Y H:i', $ts) : e((string)$s);
};
$fmtPrice = function($p){ return number_format((float)$p, 0, ',', '.') . ' ₺'; };

function badge_status($s){
  $k = strtolower((string)$s);
  return match ($k) {
    'active'   => '<span class="badge text-bg-success">Aktif</span>',
    'canceled' => '<span class="badge text-bg-secondary">İptal</span>',
    'expired'  => '<span class="badge text-bg-warning">Sefer Geçti</span>',
    default    => '<span class="badge text-bg-light">'.e($s).'</span>',
  };
}

$rows = is_array($rows ?? null) ? $rows : [];
?>

<div class="container my-5">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-receipt me-2"></i>Satılan Biletler</h2>
    <div class="d-flex flex-wrap gap-2">
      <a href="/?r=company/panel" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Firma Paneli
      </a>
    </div>
  </div>

  <?php if (empty($rows)): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Henüz satılan bilet yok.</div>
  <?php else: ?>
    <div class="row g-2 mb-3">
      <div class="col-12 col-md-6">
        <input id="ticketFilter" type="search" class="form-control" placeholder="Ara (yolcu, e-posta, güzergâh, bilet #...)">
      </div>
      <div class="col-12 col-md-6 text-md-end text-muted small">
        Toplam bilet: <strong><?= count($rows) ?></strong>
      </div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="ticketsTable">
          <thead class="table-light">
            <tr>
              <th>Bilet #</th>
              <th>Yolcu</th>
              <th>E-posta</th>
              <th>Güzergâh</th>
              <th>Kalkış</th>
              <th>Koltuk(lar)</th>
              <th>Tutar</th>
              <th>Durum</th>
              <th class="text-center">PDF</th>
              <th class="text-center">İptal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r):
              $ticketId = (string)($r['ticket_id'] ?? '');
              $status   = $r['status_view'] ?? $r['status'] ?? '';
              $isActive = (strtolower((string)$status) === 'active');

              $fullName = $r['full_name'] ?? '-';
              $email    = $r['email']     ?? '-';
              $dep      = $r['departure_city']    ?? '-';
              $dest     = $r['destination_city']  ?? '-';
              $depTime  = $r['departure_time']    ?? '';
              $seats    = $r['seats']             ?? '-';
              $price    = $r['total_price']       ?? 0;

              $rowClass = $isActive ? '' : 'table-secondary';
            ?>
            <tr class="<?= $rowClass ?>">
              <td><code><?= e($ticketId) ?></code></td>
              <td><?= e($fullName) ?></td>
              <td><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></td>
              <td><?= e($dep) ?> → <?= e($dest) ?></td>
              <td class="text-nowrap"><?= e($fmtDT($depTime)) ?></td>
              <td><?= e($seats) ?></td>
              <td class="fw-semibold"><?= $fmtPrice($price) ?></td>
              <td><?= badge_status($status) ?></td>

              <td class="text-center">
                <?php if ($isActive): ?>
                  <a target="_blank" rel="noopener"
                     class="btn btn-outline-danger btn-sm"
                     href="/?r=ticket/pdf&id=<?= rawurlencode($ticketId) ?>">
                    <i class="bi bi-file-earmark-pdf"></i>
                  </a>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>

              <td class="text-center">
                <?php if ($isActive): ?>
                  <form method="post" action="/?r=company/ticket-cancel-post"
                        class="d-inline-block js-confirm">
                    <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                    <input type="hidden" name="id" value="<?= e($ticketId) ?>">
                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Bileti iptal et">
                      <i class="bi bi-x-circle"></i>
                    </button>
                  </form>
                <?php else: ?>
                  <span class="text-muted">—</span>
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<script>
(() => {
  'use strict';
  const q = document.getElementById('ticketFilter');
  const t = document.getElementById('ticketsTable');
  if (q && t) {
    q.addEventListener('input', () => {
      const s = q.value.toLowerCase().trim();
      t.querySelectorAll('tbody tr').forEach(tr => {
        tr.style.display = tr.innerText.toLowerCase().includes(s) ? '' : 'none';
      });
    });
  }
  document.querySelectorAll('form.js-confirm').forEach(f => {
    f.addEventListener('submit', e => {
      if (!confirm('Bu bileti iptal etmek istediğinize emin misiniz?')) e.preventDefault();
    });
  });
})();
</script>