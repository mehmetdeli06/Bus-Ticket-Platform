<?php
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$u        = $_SESSION['user'] ?? null;
$role     = $u['role'] ?? null;
$name     = trim($u['name'] ?? ($u['full_name'] ?? ''));
$current  = $_GET['r'] ?? 'home/index';
$starts   = fn(string $p) => strncmp($current, $p, strlen($p)) === 0;
$active   = fn(string $p) => $starts($p) ? 'active' : '';

$has      = function_exists('has_role');
$roleUser    = $u && ( ($role === 'user')    || ($has && has_role(ROLE_USER)) );
$roleCompany = $u && ( ($role === 'company') || ($has && has_role(ROLE_COMPANY)) );
$roleAdmin   = $u && ( ($role === 'admin')   || ($has && has_role(ROLE_ADMIN)) );

$section = match (true) {
  $starts('admin/') => 'admin',
  $roleCompany && ($starts('company/') || $starts('account')) => 'company',
  default => 'public',
};
$brandHref = match ($section) {
  'admin'   => '/?r=admin/panel',
  'company' => '/?r=company/trips',
  default   => '/?r=home/index',
};

$NONCE = (string)($GLOBALS['CSP_NONCE'] ?? '');
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= e($brandHref) ?>">
      🚌 <span>Biletci</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="Menüyü Aç/Kapat">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if ($section === 'public'): ?>
          <li class="nav-item">
            <a class="nav-link <?= $active('home/index') ?>" href="/?r=home/index">🏠 Ana Sayfa</a>
          </li>
          <?php if ($roleUser): ?>
            <li class="nav-item">
              <a class="nav-link <?= $active('ticket/my') ?>" href="/?r=ticket/my">🎟️ Biletlerim</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= $active('account') ?>" href="/?r=account">👤 Hesabım</a>
            </li>
          <?php endif; ?>
          <?php if ($roleCompany): ?>
            <li class="nav-item">
              <a class="nav-link <?= $active('company/trips') ?>" href="/?r=company/trips">🚌 Seferlerim</a>
            </li>
          <?php endif; ?>
          <?php if ($roleAdmin): ?>
            <li class="nav-item">
              <a class="nav-link <?= $active('admin/panel') ?>" href="/?r=admin/panel">⚙️ Admin Paneli</a>
            </li>
          <?php endif; ?>

        <?php elseif ($section === 'company'): ?>
          <li class="nav-item">
            <a class="nav-link <?= $active('company/trips') ?>" href="/?r=company/trips">🚌 Seferlerim</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $active('company/coupons') ?>" href="/?r=company/coupons">🏷️ Kuponlar</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $active('company/tickets') ?>" href="/?r=company/tickets">💳 Satışlar</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $active('account') ?>" href="/?r=account">👤 Hesabım</a>
          </li>

        <?php else:  ?>
          <li class="nav-item">
            <a class="nav-link <?= $active('admin/panel') ?>" href="/?r=admin/panel">📊 Panel</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $active('admin/companies') ?>" href="/?r=admin/companies">🏢 Firmalar</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $active('admin/coupons') ?>" href="/?r=admin/coupons">🏷️ Kuponlar</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $active('admin/trips') ?>" href="/?r=admin/trips">🚌 Seferler</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $active('admin/account') ?>" href="/?r=admin/account">🧑‍💼 Admin Hesabı</a>
          </li>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav ms-auto">
        <?php if (!$u): ?>
          <li class="nav-item me-2">
            <a class="btn btn-sm btn-outline-primary" href="/?r=auth/login">🔑 Giriş</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-sm btn-primary" href="/?r=auth/register">📝 Kayıt Ol</a>
          </li>
        <?php else: ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle fw-semibold d-flex align-items-center gap-2"
               href="#" id="userMenu" role="button"
               data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
              <span class="rounded-circle bg-light border d-inline-flex justify-content-center align-items-center"
                    style="width:28px;height:28px;">
                <?= strtoupper(mb_substr($name,0,1,'UTF-8')) ?>
              </span>
              <span><?= e($name) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
              <?php if ($roleUser || $roleCompany): ?>
                <li><a class="dropdown-item" href="/?r=account">👤 Hesabım</a></li>
              <?php endif; ?>
              <?php if ($roleCompany): ?>
                <li><a class="dropdown-item" href="/?r=company/trips">🚌 Seferlerim</a></li>
                <li><a class="dropdown-item" href="/?r=company/coupons">🏷️ Kuponlar</a></li>
                <li><a class="dropdown-item" href="/?r=company/tickets">💳 Satışlar</a></li>
              <?php endif; ?>
              <?php if ($roleAdmin): ?>
                <li><a class="dropdown-item" href="/?r=admin/panel">⚙️ Admin Paneli</a></li>
                <li><a class="dropdown-item" href="/?r=admin/account">🧑‍💼 Admin Hesabı</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>

              <li><a class="dropdown-item text-danger" href="/?r=auth/logout">🚪 Çıkış</a></li>
            </ul>
          </li>
          <noscript><li class="nav-item ms-2"><a class="btn btn-sm btn-outline-danger" href="/?r=auth/logout">🚪 Çıkış</a></li></noscript>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<style nonce="<?= e($NONCE) ?>">
  .navbar-brand span{ font-size:1.1rem; }
  .navbar .nav-link.active{ font-weight:600; color:#0d6efd !important; }
  .navbar .dropdown-menu{ min-width:220px; }
  .navbar .rounded-circle{ font-weight:700; }
  .btn-sm{ font-size:.875rem; }
</style>

<script nonce="<?= e($NONCE) ?>">
  (function(){
    if (window.bootstrap && document.querySelector('[data-bs-toggle="dropdown"]')) {
      document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(el){
        try { new bootstrap.Dropdown(el); } catch(e){}
      });
    }
    if (window.bootstrap && document.querySelector('[data-bs-toggle="collapse"]')) {
    
    }
  })();
</script>
