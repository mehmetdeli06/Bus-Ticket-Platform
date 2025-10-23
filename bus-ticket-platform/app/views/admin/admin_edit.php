<style>
  :root{
    --bs-primary:#19a974; --bs-success:#138a63; --bs-link-color:#138a63; --bs-btn-border-radius:.6rem;
  }
  .card{border:0;box-shadow:0 6px 16px rgba(0,0,0,.06);}
</style>

<?php if (!function_exists('e')){ function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } } ?>

<div class="container my-4">
  <div class="card">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0"><i class="bi bi-pencil-square me-2"></i>Firma Adminini Düzenle</h2>
        <a href="/?r=admin/company-admins" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left"></i> Listeye Dön
        </a>
      </div>

      <?php if (!empty($flash_success)): ?>
        <div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle me-2"></i><div><?= e($flash_success) ?></div></div>
      <?php endif; ?>
      <?php if (!empty($flash_error)): ?>
        <div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle me-2"></i><div><?= e($flash_error) ?></div></div>
      <?php endif; ?>

      <form method="post" action="/?r=admin/admin-edit-post" class="needs-validation" novalidate autocomplete="on">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>
        <input type="hidden" name="id" value="<?= e($admin['id'] ?? '') ?>">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="full_name">Ad Soyad</label>
            <input id="full_name" name="full_name" class="form-control <?= !empty($errors['full_name']) ? 'is-invalid' : '' ?>"
                   required minlength="3" maxlength="120" value="<?= e($admin['full_name'] ?? '') ?>">
            <div class="invalid-feedback"><?= e($errors['full_name'] ?? 'Lütfen ad soyad girin (min. 3)') ?></div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold" for="email">E-posta</label>
            <input id="email" type="email" name="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                   required maxlength="190" value="<?= e($admin['email'] ?? '') ?>">
            <div class="invalid-feedback"><?= e($errors['email'] ?? 'Geçerli bir e-posta girin') ?></div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold" for="company_id">Firma</label>
            <select id="company_id" name="company_id" class="form-select <?= !empty($errors['company_id']) ? 'is-invalid' : '' ?>" required>
              <option value="" disabled>Seçin</option>
              <?php foreach (($companies ?? []) as $c): ?>
                <option value="<?= e($c['id']) ?>"
                  <?= (string)($admin['company_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>>
                  <?= e($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback"><?= e($errors['company_id'] ?? 'Lütfen bir firma seçin') ?></div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold" for="new_password">Yeni Şifre (opsiyonel)</label>
            <input id="new_password" type="password" name="new_password"
                   class="form-control <?= !empty($errors['new_password']) ? 'is-invalid' : '' ?>"
                   minlength="8" maxlength="72" autocomplete="new-password" placeholder="Boş bırakırsanız değişmez">
            <div class="invalid-feedback"><?= e($errors['new_password'] ?? 'Şifre min. 8 karakter') ?></div>
            <div class="form-text">Boş bırakılırsa şifre güncellenmez.</div>
          </div>
        </div>

        <div class="d-flex gap-2 mt-4">
          <button class="btn btn-success"><i class="bi bi-save2 me-1"></i>Kaydet</button>
          <a href="/?r=admin/company-admins" class="btn btn-outline-secondary">İptal</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  (() => {
    document.querySelectorAll('.needs-validation').forEach(form => {
      form.addEventListener('submit', e => {
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        form.classList.add('was-validated');
      });
    });
  })();