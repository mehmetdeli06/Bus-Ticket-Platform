<?php
if (!function_exists('e')) {
  function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
$NONCE   = (string)($GLOBALS['CSP_NONCE'] ?? '');
$error   = $error   ?? null;
$success = $success ?? null;
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
          <h1 class="h4 text-center mb-3">🧾 Kayıt Ol</h1>
          <p class="text-muted text-center mb-4">Hızlıca hesap oluşturun ve devam edin.</p>

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= e((string)$error) ?></div>
          <?php endif; ?>
          <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="status"><?= e((string)$success) ?></div>
          <?php endif; ?>

          <form method="post" action="/?r=auth/register"
                class="needs-validation" novalidate
                autocapitalize="off" spellcheck="false" autocomplete="off">
            <?= function_exists('csrf_field') ? csrf_field() : '' ?>

            <div class="visually-hidden" aria-hidden="true">
              <label>Websitesi <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <div class="mb-3">
              <label for="name" class="form-label fw-semibold">Ad Soyad</label>
              <input type="text" class="form-control" id="name" name="name"
                     required minlength="3" maxlength="120"
                     pattern="^[A-Za-zÇĞİÖŞÜçğıöşü\s'.-]{3,}$"
                     autocomplete="name"
                     value="<?= e((string)($_POST['name'] ?? '')) ?>">
              <div class="form-text">Türkçe karakterlere izin verilir ( . ' - boşluk ).</div>
              <div class="invalid-feedback">Lütfen ad soyad girin (en az 3 karakter).</div>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label fw-semibold">E-posta</label>
              <input type="email" class="form-control" id="email" name="email"
                     required maxlength="190" inputmode="email" autocomplete="email"
                     value="<?= e((string)($_POST['email'] ?? '')) ?>">
              <div class="invalid-feedback">Geçerli bir e-posta adresi girin.</div>
            </div>

            <div class="mb-3">
              <label for="password" class="form-label fw-semibold">Şifre</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password" name="password"
                       required minlength="8" maxlength="72"
                       autocomplete="new-password" aria-describedby="pwHelp">
                <button class="btn btn-outline-secondary" type="button" id="togglePw">Göster</button>
              </div>
              <div id="pwHelp" class="form-text">Öneri: büyük/küçük harf, rakam ve sembol.</div>

              <div class="mt-2">
                <div class="progress">
                  <div id="pwbar" class="progress-bar w-25 bg-danger"
                       role="progressbar" aria-valuemin="0" aria-valuemax="5" aria-valuenow="0"></div>
                </div>
                <div id="pwlabel" class="small text-muted mt-1">Şifre gücü: Zayıf</div>
              </div>
            </div>

            <div class="mb-4">
              <label for="password2" class="form-label fw-semibold">Şifre (Tekrar)</label>
              <input type="password" class="form-control" id="password2" name="password2"
                     required minlength="8" maxlength="72" autocomplete="new-password">
              <div class="invalid-feedback">Şifreler eşleşmiyor.</div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>

            <div class="text-center mt-3">
              <a href="/?r=auth/login" class="text-decoration-none">Zaten hesabınız var mı? <strong>Giriş Yapın</strong></a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        nonce="<?= e($NONCE) ?>"></script>

<script nonce="<?= e($NONCE) ?>">
(() => {
  'use strict';
  const form  = document.querySelector('form.needs-validation');
  const nameI = document.getElementById('name');
  const email = document.getElementById('email');
  const pw    = document.getElementById('password');
  const pw2   = document.getElementById('password2');
  const bar   = document.getElementById('pwbar');
  const label = document.getElementById('pwlabel');
  const toggle= document.getElementById('togglePw');

  if (toggle && pw && pw2) {
    toggle.addEventListener('click', () => {
      const toType = pw.type === 'password' ? 'text' : 'password';
      pw.type = toType; pw2.type = toType;
      toggle.textContent = (toType === 'password') ? 'Göster' : 'Gizle';
    });
  }

  if (nameI)  nameI.addEventListener('blur', () => { nameI.value  = (nameI.value  || '').trim(); });
  if (email)  email.addEventListener('blur', () => { email.value  = (email.value  || '').trim().toLowerCase(); });

  const score = v => {
    let s = 0;
    if (v.length >= 8) s++;
    if (/[A-ZÇĞİÖŞÜ]/.test(v)) s++;
    if (/[a-zçğıöşü]/.test(v)) s++;
    if (/\d/.test(v)) s++;
    if (/[^A-Za-z0-9ÇĞİÖŞÜçğıöşü]/.test(v)) s++;
    return Math.min(s, 5);
  };

  const updateStrength = sc => {
    if (!bar || !label) return;
    bar.className = 'progress-bar';
    const widths = ['w-25','w-25','w-50','w-75','w-100','w-100'];
    bar.classList.add(widths[sc]);
    let text = 'Zayıf';
    if (sc <= 2) { bar.classList.add('bg-danger');  text = 'Zayıf'; }
    else if (sc === 3) { bar.classList.add('bg-warning'); text = 'Orta'; }
    else { bar.classList.add('bg-success'); text = 'Güçlü'; }
    bar.setAttribute('aria-valuenow', String(sc));
    label.textContent = 'Şifre gücü: ' + text;
  };

  if (pw) pw.addEventListener('input', () => updateStrength(score(pw.value)));

  if (form) {
    form.addEventListener('submit', e => {
      const hp = form.querySelector('input[name="website"]');
      if (hp && hp.value) { e.preventDefault(); return; }

      if (pw && pw2 && pw.value !== pw2.value) pw2.setCustomValidity('Passwords do not match');
      else if (pw2) pw2.setCustomValidity('');

      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    });
  }
})();
</script>
