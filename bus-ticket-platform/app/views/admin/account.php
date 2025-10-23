<style>
  :root{
    --bs-primary:#19a974;      
    --bs-success:#138a63;
    --bs-link-color:#138a63;
    --bs-btn-border-radius:.6rem;
  }
  .card{border:0;box-shadow:0 6px 16px rgba(0,0,0,.06);}
  .brand-pill{background:#e6f7f1;color:#0f6f54;border:1px solid #bfe9db;}
  .table>thead th{font-weight:600;}
</style>

<?php
/** @var array $me           — oturumdaki admin */
/** @var array $stats        — ['users'=>int,'companies'=>int,'active_trips'=>int,'tickets_today'=>int] (ops.) */
/** @var array $users        — kullanıcı listesi (ops.) */
/** @var array $companies    — firma listesi (ops.) */

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$flashOk  = function_exists('flash_get') ? flash_get('ok')  : null;
$flashErr = function_exists('flash_get') ? flash_get('err') : null;

function role_badge($r){
  $k = strtolower((string)$r);
  return match ($k) {
    'admin'   => '<span class="badge text-bg-danger">Admin</span>',
    'company' => '<span class="badge text-bg-info">Firma</span>',
    'user'    => '<span class="badge text-bg-success">Yolcu</span>',
    default   => '<span class="badge text-bg-secondary">'.e($r).'</span>',
  };
}

if (!function_exists('has_role') || !has_role(ROLE_ADMIN)) {
  echo '<div class="container my-5"><div class="alert alert-danger"><i class="bi bi-shield-lock"></i> Bu sayfa yalnızca yöneticiler içindir.</div></div>';
  return;
}

$stats     = $stats     ?? [];
$users     = $users     ?? [];
$companies = $companies ?? [];
?>

<div class="container my-5">

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <h1 class="h4 mb-2 mb-md-0"><i class="bi bi-shield-check me-2"></i>Admin — Hesap & Yönetim</h1>
    <span class="badge brand-pill px-3 py-2">
      <i class="bi bi-envelope"></i>
      <strong class="ms-2"><?= e($me['email'] ?? '-') ?></strong>
    </span>
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
    <div class="col-12 col-lg-5">
      <div class="card h-100">
        <div class="card-body p-4">
          <h2 class="h5 mb-3">Profil Bilgilerim</h2>
          <form method="post" action="/?r=admin/account-update" class="needs-validation" novalidate autocomplete="on">
            <?= function_exists('csrf_field') ? csrf_field() : '' ?>

            <div class="mb-3">
              <label for="full_name" class="form-label fw-semibold">Ad Soyad</label>
              <input id="full_name" type="text" name="full_name" class="form-control" required
                     value="<?= e($me['full_name'] ?? '') ?>" maxlength="120" autocomplete="name">
              <div class="invalid-feedback">Lütfen ad soyad girin.</div>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label fw-semibold">E-posta</label>
              <input id="email" type="email" name="email" class="form-control" required
                     value="<?= e($me['email'] ?? '') ?>" maxlength="190" inputmode="email" autocomplete="email">
              <div class="invalid-feedback">Geçerli bir e-posta girin.</div>
            </div>

            <div class="mb-3">
              <label for="new_password" class="form-label fw-semibold">Yeni Şifre (opsiyonel)</label>
              <input id="new_password" type="password" name="new_password" class="form-control"
                     placeholder="••••••" minlength="8" maxlength="72" autocomplete="new-password">
              <div class="form-text">Boş bırakırsanız şifre değişmez (min. 8 karakter).</div>
            </div>

            <div class="d-flex justify-content-end">
              <button class="btn btn-success"><i class="bi bi-save2 me-1"></i>Güncelle</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-7">
      <div class="card mb-4">
        <div class="card-body p-4">
          <h2 class="h5 mb-3">Sistem Özeti</h2>
          <div class="row g-3">
            <div class="col-6 col-md-3">
              <div class="p-3 rounded border bg-light h-100">
                <div class="text-muted small">Kullanıcı</div>
                <div class="fs-5 fw-semibold"><?= isset($stats['users']) ? (int)$stats['users'] : '—' ?></div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-3 rounded border bg-light h-100">
                <div class="text-muted small">Firma</div>
                <div class="fs-5 fw-semibold"><?= isset($stats['companies']) ? (int)$stats['companies'] : '—' ?></div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-3 rounded border bg-light h-100">
                <div class="text-muted small">Aktif Sefer</div>
                <div class="fs-5 fw-semibold"><?= isset($stats['active_trips']) ? (int)$stats['active_trips'] : '—' ?></div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-3 rounded border bg-light h-100">
                <div class="text-muted small">Bugün Bilet</div>
                <div class="fs-5 fw-semibold"><?= isset($stats['tickets_today']) ? (int)$stats['tickets_today'] : '—' ?></div>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="/?r=admin/company-new" class="btn btn-primary">
              <i class="bi bi-building-add me-1"></i>Yeni Firma
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-body p-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <h2 class="h5 mb-2 mb-md-0">Kullanıcılar</h2>
        <input id="userSearch" type="search" class="form-control w-auto" placeholder="Ara (ad, e-posta, rol)" aria-label="Kullanıcı arama">
      </div>

      <?php if (empty($users)): ?>
        <div class="alert alert-info mb-0"><i class="bi bi-info-circle"></i> Kullanıcı bulunamadı.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="usersTable">
            <thead class="table-light">
              <tr>
                <th>Ad Soyad</th>
                <th>E-posta</th>
                <th>Rol</th>
                <th class="text-nowrap">Oluşturma</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
              <tr>
                <td><?= e($u['full_name'] ?? '-') ?></td>
                <td><a href="mailto:<?= e($u['email'] ?? '-') ?>"><?= e($u['email'] ?? '-') ?></a></td>
                <td><?= role_badge($u['role'] ?? '-') ?></td>
                <td class="text-nowrap"><?= e($u['created_at'] ?? '-') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-body p-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <h2 class="h5 mb-2 mb-md-0">Firmalar</h2>
        <input id="companySearch" type="search" class="form-control w-auto" placeholder="Ara (ad, e-posta, yetkili)" aria-label="Firma arama">
      </div>

      <?php if (empty($companies)): ?>
        <div class="alert alert-info mb-0"><i class="bi bi-info-circle"></i> Firma bulunamadı.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="companiesTable">
            <thead class="table-light">
              <tr>
                <th>Ad</th>
                <th>E-posta</th>
                <th>Yetkili</th>
                <th class="text-nowrap">Oluşturma</th>
                <th class="text-end text-nowrap">İşlem</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($companies as $c): ?>
              <tr>
                <td><?= e($c['name'] ?? '-') ?></td>
                <td><a href="mailto:<?= e($c['email'] ?? '-') ?>"><?= e($c['email'] ?? '-') ?></a></td>
                <td><?= e($c['contact_name'] ?? '-') ?></td>
                <td class="text-nowrap"><?= e($c['created_at'] ?? '-') ?></td>
                <td class="text-end" style="min-width:220px;">
                  <a href="/?r=admin/company-edit&id=<?= rawurlencode((string)($c['id'] ?? '')) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil"></i> Düzenle
                  </a>
                  <form method="post" action="/?r=admin/company-delete-post" class="d-inline-block js-confirm" >
                    <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                    <input type="hidden" name="id" value="<?= e($c['id'] ?? '') ?>">
                    <button class="btn btn-outline-danger btn-sm">
                      <i class="bi bi-trash"></i> Sil
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

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
    const makeFilter = (inputId, tableId) => {
      const q = document.getElementById(inputId);
      const t = document.getElementById(tableId);
      if (!q || !t) return;
      q.addEventListener('input', () => {
        const s = q.value.toLowerCase().trim();
        t.querySelectorAll('tbody tr').forEach(tr => {
          tr.style.display = tr.innerText.toLowerCase().includes(s) ? '' : 'none';
        });
      });
    };
    makeFilter('userSearch','usersTable');
    makeFilter('companySearch','companiesTable');
  })();

  (() => {
    document.querySelectorAll('form.js-confirm').forEach(f => {
      f.addEventListener('submit', (e) => {
        if (!confirm('Firma silinsin mi? Bu işlem geri alınamaz!')) e.preventDefault();
      });
    });
  })();