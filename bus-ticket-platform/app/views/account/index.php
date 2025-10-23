<style>
  :root{
    --bs-primary: #19a974;     
    --bs-success: #138a63;
    --bs-link-color: #138a63;
    --bs-btn-border-radius: .6rem;
  }
  .card{ border:0; box-shadow:0 6px 16px rgba(0,0,0,.06); }
  .table>thead th{ font-weight:600; }
  .brand-pill{ background:#e6f7f1; color:#0f6f54; border:1px solid #bfe9db; }
</style>

<?php
/** @var array $me */
/** @var array $tickets */
/** @var array|null $company */

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$flashOk  = function_exists('flash_get') ? flash_get('ok')  : null;
$flashErr = function_exists('flash_get') ? flash_get('err') : null;

function ticket_status_badge($s){
  $k = strtolower((string)$s);
  return match ($k) {
    'active'   => '<span class="badge text-bg-success">Aktif</span>',
    'canceled' => '<span class="badge text-bg-secondary">İptal</span>',
    'expired'  => '<span class="badge text-bg-warning">Süresi Doldu</span>',
    default    => '<span class="badge text-bg-light">'.e($s).'</span>',
  };
}
?>

<div class="container my-5">

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <h1 class="h4 mb-2 mb-md-0">
      <i class="bi bi-person-circle me-2"></i>Hesabım
    </h1>
    <?php if (!empty($company)): ?>
      <span class="badge brand-pill px-3 py-2">
        <i class="bi bi-building"></i>
        <span class="ms-2">Firma:</span>
        <strong class="ms-1"><?= e($company['name']) ?></strong>
      </span>
    <?php endif; ?>
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

  <div class="row g-4">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body p-4">
          <h2 class="h5 mb-3">Profil</h2>
          <form method="post" action="/?r=account/update" class="needs-validation" novalidate autocomplete="on">
            <?= function_exists('csrf_field') ? csrf_field() : '' ?>

            <div class="mb-3">
              <label class="form-label fw-semibold" for="full_name">Ad Soyad</label>
              <input id="full_name" name="full_name" class="form-control" required
                     value="<?= e($me['full_name'] ?? '') ?>" maxlength="120" autocomplete="name">
              <div class="invalid-feedback">Lütfen ad soyad girin.</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" for="email">E-posta</label>
              <input id="email" name="email" type="email" class="form-control" required
                     value="<?= e($me['email'] ?? '') ?>" maxlength="190" inputmode="email" autocomplete="email">
              <div class="invalid-feedback">Geçerli bir e-posta girin.</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" for="new_password">Yeni Şifre (opsiyonel)</label>
              <input id="new_password" type="password" name="new_password" class="form-control"
                     placeholder="••••••" minlength="8" maxlength="72" autocomplete="new-password">
              <div class="form-text">Boş bırakırsanız şifreniz değişmez (min. 8 karakter).</div>
            </div>

            <div class="d-flex justify-content-end">
              <button class="btn btn-success">
                <i class="bi bi-save2 me-1"></i>Güncelle
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card mb-4">
        <div class="card-body p-4">
          <h2 class="h5 mb-3">Hesap Özeti</h2>
          <div class="row g-3">
            <div class="col-12 col-md-4">
              <div class="p-3 rounded border bg-light">
                <div class="text-muted small">Rol</div>
                <div class="fw-semibold text-uppercase"><?= e($me['role'] ?? '-') ?></div>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="p-3 rounded border bg-light">
                <div class="text-muted small">Oluşturma</div>
                <div class="fw-semibold text-nowrap"><?= e($me['created_at'] ?? '-') ?></div>
              </div>
            </div>
            <?php if (!empty($company)): ?>
            <div class="col-12 col-md-4">
              <div class="p-3 rounded border bg-light">
                <div class="text-muted small">Firma</div>
                <div class="fw-semibold text-truncate" title="<?= e($company['name']) ?>">
                  <?= e($company['name']) ?>
                </div>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if (function_exists('has_role') && has_role(ROLE_USER)): ?>
        <div class="card">
          <div class="card-body p-4">
            <h2 class="h5 mb-3">Bakiye</h2>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <div class="text-muted small">Mevcut Bakiye</div>
                <div class="fs-5 fw-semibold"><?= (int)($me['balance'] ?? 0) ?> ₺</div>
              </div>
              <form method="post" action="/?r=account/topup" class="d-flex align-items-end gap-2 needs-validation" novalidate autocomplete="off">
                <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                <div>
                  <label class="form-label fw-semibold mb-1" for="amount">Tutar</label>
                  <div class="input-group">
                    <input id="amount" type="number" name="amount" class="form-control"
                           min="1" max="100000" step="1" value="50" required inputmode="numeric" pattern="[0-9]*">
                    <span class="input-group-text">₺</span>
                  </div>
                  <div class="invalid-feedback">Lütfen geçerli bir tutar girin.</div>
                </div>
                <button class="btn btn-primary">
                  <i class="bi bi-plus-circle me-1"></i>Ekle
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (function_exists('has_role') && has_role(ROLE_USER)): ?>
  <div class="card mt-4">
    <div class="card-body p-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <h2 class="h5 mb-2 mb-md-0">Geçmiş Biletlerim</h2>
        <?php if (!empty($tickets)): ?>
          <input id="ticketSearch" type="search" class="form-control w-auto" placeholder="Ara (güzergâh, tarih, firma...)" aria-label="Bilet arama">
        <?php endif; ?>
      </div>

      <?php if (empty($tickets)): ?>
        <div class="alert alert-info mb-0">
          <i class="bi bi-info-circle"></i> Henüz biletiniz yok.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="ticketsTable">
            <thead class="table-light">
              <tr>
                <th>Firma</th>
                <th>Güzergâh</th>
                <th class="text-nowrap">Kalkış</th>
                <th>Durum</th>
                <th class="text-nowrap">Tutar</th>
                <th class="text-center text-nowrap">PDF</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tickets as $t):
                $status  = strtolower((string)($t['status'] ?? ''));
                $pdfHref = '/?r=ticket/pdf&id=' . rawurlencode((string)($t['ticket_id'] ?? ''));
                $amount  = (int)($t['total_price'] ?? 0);
              ?>
                <tr>
                  <td><?= e($t['company_name'] ?? '-') ?></td>
                  <td><?= e($t['departure_city'] ?? '-') ?> → <?= e($t['destination_city'] ?? '-') ?></td>
                  <td class="text-nowrap"><?= e($t['departure_time'] ?? '-') ?></td>
                  <td><?= ticket_status_badge($status) ?></td>
                  <td class="fw-semibold"><?= $amount ?> ₺</td>
                  <td class="text-center">
                    <?php if ($status === 'active'): ?>
                      <a target="_blank" rel="noopener" class="btn btn-outline-danger btn-sm" href="<?= e($pdfHref) ?>">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                      </a>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  (() => {
    document.querySelectorAll('.needs-validation').forEach(form => {
      form.addEventListener('submit', (e) => {
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        form.classList.add('was-validated');
      });
    });
  })();

  (() => {
    const q = document.getElementById('ticketSearch');
    const table = document.getElementById('ticketsTable');
    if (!q || !table) return;
    q.addEventListener('input', () => {
      const s = q.value.toLowerCase().trim();
      table.querySelectorAll('tbody tr').forEach(tr => {
        const text = tr.innerText.toLowerCase();
        tr.style.display = text.includes(s) ? '' : 'none';
      });
    });
  })();