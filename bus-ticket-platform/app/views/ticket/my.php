<?php
/** @var array $tickets */

if (!function_exists('e')) {
  function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
$NONCE = (string)($GLOBALS['CSP_NONCE'] ?? '');

$fmtDT = function($s){
  try {
    if ($s === null || $s === '') return '—';
    $dt = new DateTimeImmutable((string)$s, new DateTimeZone('Europe/Istanbul'));
    return $dt->format('d.m.Y H:i');
  } catch (Throwable) {
    return e((string)$s);
  }
};
$fmtTL = fn($p) => number_format((float)$p, 0, ',', '.') . ' ₺';

$badgeClass = function($status){
  $s = strtolower((string)$status);
  return match ($s) {
    'active'                     => 'text-bg-success',
    'pending'                    => 'text-bg-warning',
    'cancelled', 'canceled'      => 'text-bg-secondary',
    'refunded'                   => 'text-bg-info',
    'expired'                    => 'text-bg-dark',
    default                      => 'text-bg-light'
  };
};

$canDownloadPdf = function() : bool {
  try {
    if (function_exists('has_role')) {
      $roleUser = defined('ROLE_USER') ? ROLE_USER : 'user';
      return has_role($roleUser);
    }
  } catch (Throwable) {}
  $u = $_SESSION['user'] ?? null;
  return is_array($u) && (strtolower((string)($u['role'] ?? '')) === 'user');
};

$canCancelFn = function(array $row): bool {
  if (strtolower((string)($row['status_view'] ?? '')) !== 'active') return false;
  try {
    $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Istanbul'));
    $dep = new DateTimeImmutable((string)$row['departure_time'], new DateTimeZone('Europe/Istanbul'));
    return ($dep->getTimestamp() - $now->getTimestamp()) >= 3600;
  } catch (Throwable) {
    return false;
  }
};
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="container my-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
    <h2 class="h4 mb-0">🎟️ Biletlerim</h2>
    <?php if (!empty($tickets)): ?>
      <div class="ms-md-auto" style="min-width:260px;">
        <input id="ticketSearch" type="search" class="form-control" placeholder="Ara (güzergâh, firma, tarih...)">
      </div>
    <?php endif; ?>
  </div>

  <?php if (empty($tickets)): ?>
    <div class="alert alert-info d-flex align-items-center">
      <i class="bi bi-info-circle me-2"></i>
      <div>Henüz biletiniz yok. Arama sayfasından sefer bulun ve bilet alın.</div>
    </div>
  <?php else: ?>
    <div class="card shadow-sm border-0">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="ticketsTable">
            <thead class="table-light">
              <tr>
                <th scope="col">Firma</th>
                <th scope="col">Güzergâh</th>
                <th scope="col" class="text-nowrap">Kalkış</th>
                <th scope="col">Durum</th>
                <th scope="col" class="text-end">Toplam</th>
                <th scope="col" class="text-end text-nowrap">İşlemler</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tickets as $t): ?>
                <?php
                  $ticketId  = $t['ticket_id'] ?? ($t['id'] ?? '');
                  $statusStr = (string)($t['status_view'] ?? '');
                  $company   = (string)($t['company_name'] ?? '');
                  $depCity   = (string)($t['departure_city'] ?? '');
                  $dstCity   = (string)($t['destination_city'] ?? '');
                  $depTime   = (string)($t['departure_time'] ?? '');
                  $total     = (float)($t['total_price'] ?? 0);

                  $isPast = false;
                  try {
                    $depTs = (new DateTimeImmutable($depTime, new DateTimeZone('Europe/Istanbul')))->getTimestamp();
                    $nowTs = (new DateTimeImmutable('now', new DateTimeZone('Europe/Istanbul')))->getTimestamp();
                    $isPast = $depTs < $nowTs;
                  } catch (Throwable) {}

                  $canCancel = $canCancelFn($t);
                  $allowPdf  = $canDownloadPdf() && (strtolower($statusStr) === 'active') && !empty($ticketId);
                ?>
                <tr>
                  <td class="fw-semibold"><?= e($company) ?></td>
                  <td><?= e($depCity) ?> → <?= e($dstCity) ?></td>
                  <td>
                    <?php if ($isPast): ?>
                      <span class="badge text-bg-secondary me-1">Geçmiş</span>
                    <?php endif; ?>
                    <?= $fmtDT($depTime) ?>
                  </td>
                  <td>
                    <span class="badge <?= $badgeClass($statusStr) ?>">
                      <?= e($statusStr) ?>
                    </span>
                  </td>
                  <td class="text-end"><?= $fmtTL($total) ?></td>
                  <td class="text-end text-nowrap" style="min-width:190px;">
                    <?php if ($allowPdf): ?>
                      <a target="_blank" rel="noopener" href="/?r=ticket/pdf&id=<?= e((string)$ticketId) ?>"
                         class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                      </a>
                    <?php endif; ?>

                    <?php if ($canCancel && !empty($ticketId)): ?>
                      <form method="post" action="/?r=ticket/cancel" class="d-inline-block js-confirm-cancel">
                        <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                        <input type="hidden" name="id" value="<?= e((string)$ticketId) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                          <i class="bi bi-x-circle"></i> İptal Et
                        </button>
                      </form>
                    <?php else: ?>
                      <span class="text-muted small ms-2">İptal edilemez</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script nonce="<?= e($NONCE) ?>">
(() => {
  const $  = (s, r=document) => r.querySelector(s);
  const $$ = (s, r=document) => Array.from(r.querySelectorAll(s));

  const q = $('#ticketSearch');
  const table = $('#ticketsTable');
  if (q && table) {
    q.addEventListener('input', () => {
      const s = q.value.toLocaleLowerCase('tr-TR').trim();
      $$('#ticketsTable tbody tr').forEach(tr => {
        const text = tr.innerText.toLocaleLowerCase('tr-TR');
        tr.style.display = text.includes(s) ? '' : 'none';
      });
    });
  }

  $$('.js-confirm-cancel').forEach(f => {
    f.addEventListener('submit', (e) => {
      if (!confirm('Bu bileti iptal etmek istediğinize emin misiniz?')) {
        e.preventDefault();
      }
    });
  });
})();
</script>
