<?php
if (!function_exists('e')) {
  function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
$error = $error ?? null;
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  :root{
    --bs-primary:#19a974; --bs-success:#138a63; --bs-link-color:#138a63; --bs-btn-border-radius:.6rem;
  }
  .card{ border:0; box-shadow:0 8px 24px rgba(0,0,0,.06); }
  .auth-wrap{ min-height:calc(100dvh - 120px); display:grid; place-items:center; padding:24px 12px; }
</style>

<div class="container auth-wrap">
  <div class="row justify-content-center w-100">
    <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">
      <div class="card">
        <div class="card-body p-4 p-md-5">
          <h1 class="h4 text-center mb-3">🔐 Giriş</h1>
          <p class="text-muted text-center mb-4">Hesabınıza erişmek için e-posta ve şifrenizi girin.</p>

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= e((string)$error) ?></div>
          <?php endif; ?>

          <form method="post" action="/?r=auth/login" class="needs-validation" novalidate autocomplete="off" spellcheck="false">
            <?= function_exists('csrf_field') ? csrf_field() : '' ?>

            <div class="visually-hidden" aria-hidden="true">
              <label>Telefon<input type="text" name="phone" tabindex="-1" autocomplete="off"></label>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label fw-semibold">E-posta</label>
              <input
                type="email"
                class="form-control"
                id="email"
                name="email"
                required
                inputmode="email"
                maxlength="190"
                autocomplete="username"
                value="<?= e((string)($_POST['email'] ?? '')) ?>"
              >
              <div class="invalid-feedback">Geçerli bir e-posta adresi girin.</div>
            </div>

            <div class="mb-3">
              <label for="password" class="form-label fw-semibold">Şifre</label>
              <div class="input-group">
                <input
                  type="password"
                  class="form-control"
                  id="password"
                  name="password"
                  required
                  minlength="0"
                  maxlength="72"
                  autocomplete="current-password"
                >
                <button type="button" class="btn btn-outline-secondary" id="togglePw" aria-label="Şifreyi göster/gizle">Göster</button>
              </div>
              <div class="invalid-feedback">Lütfen geçerli bir şifre girin.</div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                <label class="form-check-label" for="remember">Beni hatırla</label>
              </div>
              <a href="/?r=auth/forgot" class="link-underline link-underline-opacity-0">Şifremi unuttum</a>
            </div>

            <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>

            <div class="text-center mt-3">
              <a href="/?r=auth/register" class="text-decoration-none">Hesabınız yok mu? <strong>Kayıt olun</strong></a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        nonce="<?= e((string)($GLOBALS['CSP_NONCE'] ?? '')) ?>"></script>

<script nonce="<?= e((string)($GLOBALS['CSP_NONCE'] ?? '')) ?>">
(() => {
  'use strict';
  const form   = document.querySelector('form.needs-validation');
  const email  = document.getElementById('email');
  const pw     = document.getElementById('password');
  const toggle = document.getElementById('togglePw');

  if (toggle && pw) {
    toggle.addEventListener('click', () => {
      const isPw = pw.type === 'password';
      pw.type = isPw ? 'text' : 'password';
      toggle.textContent = isPw ? 'Gizle' : 'Göster';
    });
  }

  if (email) {
    email.addEventListener('blur', () => { email.value = (email.value || '').trim(); });
  }

  if (form) {
    form.addEventListener('submit', (e) => {
      const hp = form.querySelector('input[name="phone"]');
      if (hp && hp.value) { e.preventDefault(); e.stopPropagation(); return; } 
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    });
  }
})();
</script>
