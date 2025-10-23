<?php
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$mode = (($mode ?? 'create') === 'edit') ? 'edit' : 'create';

$expireVal = '';
$srcExpire = $_POST['expire_date'] ?? ($coupon['expire_date'] ?? '');
if (!empty($srcExpire)) {
    $ts = strtotime((string)$srcExpire);
    if ($ts !== false) { $expireVal = date('Y-m-d\TH:i', $ts); }
}

$flash_success = $flash_success ?? (function_exists('flash_get') ? flash_get('ok')  : null);
$flash_error   = $flash_error   ?? (function_exists('flash_get') ? flash_get('err') : null);

$companies = is_array($companies ?? null) ? $companies : [];
$errors    = is_array($errors ?? null)    ? $errors    : [];
?>

<style>
  :root{ --bs-primary:#19a974; --bs-success:#138a63; --bs-link-color:#138a63; --bs-btn-border-radius:.6rem; }
  .card{border:0;box-shadow:0 6px 16px rgba(0,0,0,.06);}
</style>

<div class="container my-4">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0"><?= $mode === 'create' ? '🆕 Yeni Kupon' : '✏️ Kupon Düzenle' ?></h2>
        <a href="/?r=admin/coupons" class="btn btn-outline-secondary btn-sm">Listeye Dön</a>
      </div>

      <?php if (!empty($flash_success)): ?>
        <div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle me-2"></i><div><?= e($flash_success) ?></div></div>
      <?php endif; ?>
      <?php if (!empty($flash_error)): ?>
        <div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle me-2"></i><div><?= e($flash_error) ?></div></div>
      <?php endif; ?>

      <form class="needs-validation" novalidate method="post"
            action="/?r=<?= $mode === 'create' ? 'admin/coupon-new-post' : 'admin/coupon-edit-post' ?>"
            autocomplete="off">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>
        <?php if ($mode === 'edit'): ?>
          <input type="hidden" name="id" value="<?= e($coupon['id'] ?? '') ?>">
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-md-6">
            <label for="code" class="form-label fw-semibold">Kupon Kodu</label>
            <input
              type="text"
              id="code"
              name="code"
              class="form-control <?= !empty($errors['code']) ? 'is-invalid' : '' ?>"
              required
              minlength="3"
              maxlength="64"
              placeholder="Örn: SAVE10, YAZ_2025"
              pattern="^[A-Za-z0-9_-]+$"
              inputmode="latin"
              value="<?= e($_POST['code'] ?? ($coupon['code'] ?? '')) ?>">
            <div class="form-text">Sadece harf, rakam, tire (-) ve alt çizgi (_). Boşluk yok.</div>
            <div class="invalid-feedback"><?= e($errors['code'] ?? 'Geçerli bir kupon kodu girin (min. 3).') ?></div>
          </div>

          <div class="col-md-3">
            <label for="discount" class="form-label fw-semibold">İndirim Oranı (0–1)</label>
            <input
              type="number"
              id="discount"
              name="discount"
              class="form-control <?= !empty($errors['discount']) ? 'is-invalid' : '' ?>"
              required
              step="0.01"
              min="0"
              max="1"
              placeholder="Örn: 0.15"
              inputmode="decimal"
              value="<?= e($_POST['discount'] ?? ($coupon['discount'] ?? '')) ?>">
            <div class="form-text">%15 için <strong>0.15</strong> yazın (0 ile 1 arasında).</div>
            <div class="invalid-feedback"><?= e($errors['discount'] ?? '0 ile 1 arasında bir oran girin (ör.: 0.20).') ?></div>
          </div>

          <div class="col-md-3">
            <label for="usage_limit" class="form-label fw-semibold">Kullanım Limiti</label>
            <input
              type="number"
              id="usage_limit"
              name="usage_limit"
              class="form-control <?= !empty($errors['usage_limit']) ? 'is-invalid' : '' ?>"
              required
              min="1"
              max="1000000"
              step="1"
              placeholder="Örn: 100"
              inputmode="numeric"
              value="<?= e($_POST['usage_limit'] ?? ($coupon['usage_limit'] ?? 1)) ?>">
            <div class="invalid-feedback"><?= e($errors['usage_limit'] ?? 'Kullanım limiti en az 1 olmalıdır.') ?></div>
          </div>

          <div class="col-md-6">
            <label for="expire_date" class="form-label fw-semibold">Son Kullanma</label>
            <input
              type="datetime-local"
              id="expire_date"
              name="expire_date"
              class="form-control <?= !empty($errors['expire_date']) ? 'is-invalid' : '' ?>"
              required
              value="<?= e($expireVal) ?>">
            <div class="form-text">Bu tarih/saatten sonra kupon kullanılamaz.</div>
            <div class="invalid-feedback"><?= e($errors['expire_date'] ?? 'Geçerli bir tarih/saat girin.') ?></div>
          </div>

          <div class="col-md-6">
            <label for="company_id" class="form-label fw-semibold">Firma (boşsa genel)</label>
            <?php $selectedCompany = (string)($_POST['company_id'] ?? ($coupon['company_id'] ?? '')); ?>
            <select
              id="company_id"
              name="company_id"
              class="form-select <?= !empty($errors['company_id']) ? 'is-invalid' : '' ?>">
              <option value="" <?= $selectedCompany === '' ? 'selected' : '' ?>>— Genel (Tüm firmalar) —</option>
              <?php foreach ($companies as $c): ?>
                <?php $cid=(string)($c['id'] ?? ''); ?>
                <option value="<?= e($cid) ?>" <?= $selectedCompany === $cid ? 'selected' : '' ?>>
                  <?= e($c['name'] ?? '-') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback"><?= e($errors['company_id'] ?? 'Geçerli bir firma seçin veya genel bırakın.') ?></div>
          </div>
        </div>

        <div class="d-flex gap-2 mt-4">
          <button type="submit" class="btn btn-primary">Kaydet</button>
          <a href="/?r=admin/coupons" class="btn btn-outline-secondary">İptal</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
  const forms = document.querySelectorAll('.needs-validation');
  forms.forEach(form => {
    form.addEventListener('submit', (e) => {
      const code = form.querySelector('#code');
      if (code) {
        code.value = (code.value || '').trim();
        code.value = code.value.toUpperCase();
      }

      const exp = form.querySelector('#expire_date');
      if (exp && exp.value) {
        const t = new Date(exp.value);
        if (!isNaN(t) && t < new Date()) {
          exp.setCustomValidity('Geçmiş bir tarih olamaz');
        } else { exp.setCustomValidity(''); }
      }

      const dc = form.querySelector('#discount');
      if (dc && dc.value) {
        const v = Number(dc.value);
        if (isNaN(v) || v < 0 || v > 1) {
          dc.setCustomValidity('0 ile 1 arasında olmalı');
        } else { dc.setCustomValidity(''); }
      }

      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    });
  });
})
();