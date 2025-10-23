<style>
  :root{
    --bs-primary:#19a974;   
    --bs-success:#138a63;
    --bs-link-color:#138a63;
    --bs-btn-border-radius:.6rem;
  }
  .card{border:0;box-shadow:0 6px 16px rgba(0,0,0,.06);}
  .pwbar { height:6px; background:#e9ecef; border-radius:6px; overflow:hidden; }
  .pwbar>div { height:100%; width:0%; transition:width .25s; }
  .pw-weak{background:#dc3545;} .pw-mid{background:#fd7e14;} .pw-strong{background:#198754;}
</style>

<?php if (!function_exists('e')){ function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } } ?>

<div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body p-4">
          <h2 class="h4 mb-2">Yeni Firma Admin</h2>
          <p class="text-muted mb-4">Şifre en az 8 karakter olmalı. Kaydetmeden önce tarayıcı ve sunucu tarafı doğrulaması yapılır.</p>

          <?php if (!empty($flash_success)): ?>
            <div class="alert alert-success"><?= e($flash_success) ?></div>
          <?php endif; ?>
          <?php if (!empty($flash_error)): ?>
            <div class="alert alert-danger"><?= e($flash_error) ?></div>
          <?php endif; ?>

          <form class="needs-validation" novalidate method="post" action="/?r=admin/admin-new-post" autocomplete="off">
            <?= function_exists('csrf_field') ? csrf_field() : '' ?>

            <div class="row g-3">
              <div class="col-12">
                <label for="full_name" class="form-label fw-semibold">Ad Soyad</label>
                <input
                  type="text"
                  class="form-control <?= !empty($errors['full_name']) ? 'is-invalid' : '' ?>"
                  id="full_name" name="full_name" required minlength="3" maxlength="120"
                  placeholder="Örn: Mehmet Deli" pattern="^[A-Za-zÇĞİÖŞÜçğıöşü\s'.-]{3,}$"
                  autocomplete="name" value="<?= e($_POST['full_name'] ?? '') ?>">
                <div class="form-text">Türkçe karakterlere izin verilir. En az 3 karakter.</div>
                <div class="invalid-feedback">
                  <?= !empty($errors['full_name']) ? e($errors['full_name']) : 'Lütfen geçerli bir ad soyad girin.' ?>
                </div>
              </div>

              <div class="col-md-6">
                <label for="email" class="form-label fw-semibold">E-posta</label>
                <input
                  type="email"
                  class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                  id="email" name="email" required maxlength="190"
                  placeholder="ornek@firma.com" autocomplete="email" inputmode="email"
                  value="<?= e($_POST['email'] ?? '') ?>">
                <div class="invalid-feedback">
                  <?= !empty($errors['email']) ? e($errors['email']) : 'Geçerli bir e-posta girin.' ?>
                </div>
              </div>

              <div class="col-md-6">
                <label for="password" class="form-label fw-semibold">Şifre</label>
                <div class="input-group">
                  <input
                    type="password"
                    class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                    id="password" name="password" required minlength="8" maxlength="72"
                    placeholder="En az 8 karakter" autocomplete="new-password" aria-describedby="pwHelp">
                  <button class="btn btn-outline-secondary" type="button" id="togglePw">Göster</button>
                  <div class="invalid-feedback">
                    <?= !empty($errors['password']) ? e($errors['password']) : 'Şifre en az 8 karakter olmalı.' ?>
                  </div>
                </div>
                <div id="pwHelp" class="form-text">Öneri: 1 büyük, 1 küçük, 1 rakam, 1 özel karakter.</div>
                <div class="pwbar mt-2" aria-hidden="true"><div id="pwbarFill"></div></div>
              </div>

              <div class="col-md-6">
                <label for="password_confirm" class="form-label fw-semibold">Şifre (Tekrar)</label>
                <input
                  type="password"
                  class="form-control <?= !empty($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                  id="password_confirm" name="password_confirm" required minlength="8" maxlength="72"
                  placeholder="Şifreyi tekrar girin" autocomplete="new-password">
                <div class="invalid-feedback">
                  <?= !empty($errors['password_confirm']) ? e($errors['password_confirm']) : 'Şifreler eşleşmiyor.' ?>
                </div>
              </div>

              <div class="col-12">
                <label for="company_id" class="form-label fw-semibold">Firma</label>
                <select
                  class="form-select <?= !empty($errors['company_id']) ? 'is-invalid' : '' ?>"
                  id="company_id" name="company_id" required>
                  <option value="" disabled <?= empty($_POST['company_id'] ?? '') ? 'selected' : '' ?>>Seçin</option>
                  <?php foreach (($companies ?? []) as $c): ?>
                    <option value="<?= e($c['id']) ?>"
                      <?= (($_POST['company_id'] ?? '') == ($c['id'] ?? '')) ? 'selected' : '' ?>>
                      <?= e($c['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">
                  <?= !empty($errors['company_id']) ? e($errors['company_id']) : 'Lütfen bir firma seçin.' ?>
                </div>
              </div>
            </div>

            <div class="d-flex gap-2 mt-4">
              <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Kaydet</button>
              <a href="/?r=admin/company-admins" class="btn btn-outline-secondary">İptal</a>
            </div>
          </form>
        </div>
      </div>

      <div class="text-muted small mt-3">
        • Sunucu tarafında: e-posta benzersizliği, firma varlığı/aktifliği, şifre hash’i, rol ataması (yalnızca <code>company</code>) ve audit log önerilir.
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  (() => {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
      form.addEventListener('submit', e => {
        const pw  = form.querySelector('#password');
        const pw2 = form.querySelector('#password_confirm');
        if (pw && pw2 && pw.value !== pw2.value) pw2.setCustomValidity('Passwords do not match');
        else if (pw2) pw2.setCustomValidity('');
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        form.classList.add('was-validated');
      });
    });
  })();

  (() => {
    const pw = document.getElementById('password');
    const btn = document.getElementById('togglePw');
    const bar = document.getElementById('pwbarFill');
    if (!pw || !btn || !bar) return;

    btn.addEventListener('click', () => {
      pw.type = (pw.type === 'password') ? 'text' : 'password';
      btn.textContent = (pw.type === 'password') ? 'Göster' : 'Gizle';
    });

    const score = (v) => {
      let s = 0;
      if (v.length >= 8) s++;
      if (/[A-ZÇĞİÖŞÜ]/.test(v)) s++;
      if (/[a-zçğıöşü]/.test(v)) s++;
      if (/\d/.test(v)) s++;
      if (/[^A-Za-z0-9ÇĞİÖŞÜçğıöşü]/.test(v)) s++;
      return Math.min(s,5);
    };

    pw.addEventListener('input', () => {
      const sc = score(pw.value);
      const pct = [0,20,40,70,85,100][sc];
      bar.style.width = pct + '%';
      bar.className = '';
      if (sc <= 2) bar.classList.add('pw-weak');
      else if (sc <= 3) bar.classList.add('pw-mid');
      else bar.classList.add('pw-strong');
    });
  })();