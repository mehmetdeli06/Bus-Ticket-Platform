<?php
if (!function_exists('e')) {
  function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
$mode = (($mode ?? 'create') === 'edit') ? 'edit' : 'create';

$expireVal = '';
$srcExpire = $_POST['expire_date'] ?? ($coupon['expire_date'] ?? '');
if (!empty($srcExpire)) {
  $ts = strtotime((string)$srcExpire);
  if ($ts !== false) { $expireVal = date('Y-m-d\TH:i', $ts); }
}
?>

<style>
  :root{
    --bs-primary:#19a974; --bs-success:#138a63; --bs-link-color:#138a63; --bs-btn-border-radius:.6rem;
  }
  .card{border:0;box-shadow:0 8px 24px rgba(0,0,0,.06);}
</style>

<div class="container my-5">
  <div class="card shadow-sm border-0">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">
          <?= $mode === 'create' ? '🆕 Yeni Kupon Oluştur' : '✏️ Kupon Düzenle' ?>
        </h2>
        <a href="/?r=company/coupons" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left"></i> Kuponlara Dön
        </a>
      </div>

      <form method="post"
            action="/?r=<?= $mode === 'create' ? 'company/coupon-new-post' : 'company/coupon-edit-post' ?>"
            class="needs-validation" novalidate autocomplete="off">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>

        <?php if ($mode === 'edit'): ?>
          <input type="hidden" name="id" value="<?= e((string)($coupon['id'] ?? '')) ?>">
        <?php endif; ?>

        <div class="mb-3">
          <label for="code" class="form-label fw-semibold">Kupon Kodu</label>
          <input
            type="text"
            id="code"
            name="code"
            class="form-control"
            required
            minlength="3"
            maxlength="64"
            placeholder="Örn: INDIRIM20 veya YAZ_2025"
            pattern="^[A-Za-z0-9_-]+$"
            value="<?= e((string)($_POST['code'] ?? ($coupon['code'] ?? ''))) ?>">
          <div class="form-text">Sadece harf, rakam, tire (-) ve alt çizgi (_). Boşluk yok.</div>
          <div class="invalid-feedback">Geçerli bir kupon kodu girin (min. 3, boşluk içermez).</div>
        </div>

        <div class="row g-3">
          <div class="col-md-4">
            <label for="discount" class="form-label fw-semibold">İndirim Oranı</label>
            <div class="input-group">
              <input
                type="number"
                id="discount"
                name="discount"
                class="form-control"
                required
                step="0.01" min="0" max="1"
                inputmode="decimal"
                value="<?= e((string)($_POST['discount'] ?? ($coupon['discount'] ?? '0.20'))) ?>">
              <span class="input-group-text">0–1</span>
            </div>
            <div class="form-text">Örn: %20 için <strong>0.20</strong> yazın.</div>
            <div class="invalid-feedback">0 ile 1 arasında bir oran girin (örn. 0.15).</div>
          </div>

          <div class="col-md-4">
            <label for="usage_limit" class="form-label fw-semibold">Kullanım Limiti</label>
            <input
              type="number"
              id="usage_limit"
              name="usage_limit"
              class="form-control"
              required
              min="1" max="1000000" step="1"
              inputmode="numeric"
              value="<?= e((string)($_POST['usage_limit'] ?? ($coupon['usage_limit'] ?? '10'))) ?>">
            <div class="form-text">Kupon toplamda kaç kez kullanılabilir.</div>
            <div class="invalid-feedback">Kullanım limiti en az 1 olmalıdır.</div>
          </div>

          <div class="col-md-4">
            <label for="expire_date" class="form-label fw-semibold">Son Kullanma Tarihi</label>
            <input
              type="datetime-local"
              id="expire_date"
              name="expire_date"
              class="form-control"
              required
              value="<?= e($expireVal) ?>">
            <div class="form-text">Bu tarih/saatten sonra kupon kullanılamaz.</div>
            <div class="invalid-feedback">Geçerli bir tarih/saat girin (gelecek bir zaman olmalı).</div>
          </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
          <button type="submit" class="btn btn-success px-4">
            <i class="bi bi-check-circle"></i> Kaydet
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(() => {
  'use strict';
  const forms   = document.querySelectorAll('.needs-validation');
  const codeInp = document.getElementById('code');
  const discInp = document.getElementById('discount');
  const expInp  = document.getElementById('expire_date');

  if (codeInp) {
    const fmt = v => (v || '').trim().toUpperCase().replace(/\s+/g,'');
    codeInp.addEventListener('blur', () => { codeInp.value = fmt(codeInp.value); });
    codeInp.addEventListener('input', () => { codeInp.value = codeInp.value.replace(/\s+/g,''); });
  }

  forms.forEach(form => {
    form.addEventListener('submit', e => {
      if (codeInp) codeInp.value = (codeInp.value || '').trim().toUpperCase().replace(/\s+/g,'');

      if (discInp && discInp.value !== '') {
        const v = Number(discInp.value);
        if (isNaN(v) || v < 0 || v > 1) {
          discInp.setCustomValidity('0 ile 1 arasında olmalı');
        } else {
          discInp.setCustomValidity('');
        }
      }

      if (expInp && expInp.value) {
        const t = new Date(expInp.value);
        if (!isNaN(t) && t < new Date()) {
          expInp.setCustomValidity('Geçmiş bir tarih olamaz');
        } else {
          expInp.setCustomValidity('');
        }
      }

      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    }, false);
  });
})();
</script>
