<?php
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$mode   = (($mode ?? 'create') === 'edit') ? 'edit' : 'create';
$isEdit = ($mode === 'edit');

$trip = is_array($trip ?? null) ? $trip : [];

$title  = $isEdit ? 'Seferi Düzenle' : 'Yeni Sefer';
$action = $isEdit ? '/?r=company/trip-edit-post' : '/?r=company/trip-new-post';

$fmtDTLocal = function($s){
  if (empty($s)) return '';
  $ts = strtotime((string)$s);
  return $ts !== false ? date('Y-m-d\TH:i', $ts) : '';
};

$departure_city   = (string)($trip['departure_city']   ?? ($_POST['departure_city']   ?? ''));
$destination_city = (string)($trip['destination_city'] ?? ($_POST['destination_city'] ?? ''));
$departure_time   = (string)($trip['departure_time']   ?? ($_POST['departure_time']   ?? ''));
$arrival_time     = (string)($trip['arrival_time']     ?? ($_POST['arrival_time']     ?? ''));
$price            = (string)($trip['price']            ?? ($_POST['price']            ?? ''));
$capacity         = (string)($trip['capacity']         ?? ($_POST['capacity']         ?? ''));
$id               = (string)($trip['id']               ?? ($_POST['id']               ?? ''));

$departure_time_val = $fmtDTLocal($departure_time);
$arrival_time_val   = $fmtDTLocal($arrival_time);

$flashErr = function_exists('flash_get') ? flash_get('err') : null;
?>

<style>
  :root{ --bs-primary:#19a974; --bs-success:#138a63; --bs-link-color:#138a63; --bs-btn-border-radius:.6rem; }
  .card{border:0;box-shadow:0 8px 24px rgba(0,0,0,.06);}
</style>

<div class="container my-5">
  <div class="card shadow-sm border-0">
    <div class="card-body p-4">

      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0"><?= e($title) ?></h2>
        <a href="/?r=company/panel" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left"></i> Seferlere Dön
        </a>
      </div>

      <?php if (!empty($flashErr)): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <div><?= e($flashErr) ?></div>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= e($action) ?>" class="needs-validation" novalidate autocomplete="off" id="tripForm">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>
        <?php if ($isEdit): ?>
          <input type="hidden" name="id" value="<?= e($id) ?>">
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label for="departure_city" class="form-label fw-semibold">Çıkış Şehri</label>
            <input
              type="text" id="departure_city" name="departure_city"
              class="form-control"
              required minlength="2" maxlength="80"
              pattern="^[A-Za-zÇĞİÖŞÜçğıöşü\s'.-]{2,}$"
              placeholder="Örn: İstanbul"
              value="<?= e($departure_city) ?>">
           
            <div class="invalid-feedback">Lütfen geçerli bir şehir girin (min. 2 karakter).</div>
          </div>

          <div class="col-12 col-md-6">
            <label for="destination_city" class="form-label fw-semibold">Varış Şehri</label>
            <input
              type="text" id="destination_city" name="destination_city"
              class="form-control"
              required minlength="2" maxlength="80"
              pattern="^[A-Za-zÇĞİÖŞÜçğıöşü\s'.-]{2,}$"
              placeholder="Örn: Ankara"
              value="<?= e($destination_city) ?>">
            <div class="invalid-feedback">Lütfen geçerli bir şehir girin (min. 2 karakter).</div>
          </div>

          <div class="col-12 col-md-6">
            <label for="departure_time" class="form-label fw-semibold">Kalkış Zamanı</label>
            <input
              type="datetime-local" id="departure_time" name="departure_time"
              class="form-control" required
              value="<?= e($departure_time_val) ?>">
            <div class="form-text">Takvimden tarih ve saat seçin.</div>
            <div class="invalid-feedback">Kalkış zamanı zorunludur.</div>
          </div>

          <div class="col-12 col-md-6">
            <label for="arrival_time" class="form-label fw-semibold">Varış Zamanı</label>
            <input
              type="datetime-local" id="arrival_time" name="arrival_time"
              class="form-control" required
              value="<?= e($arrival_time_val) ?>">
            <div class="form-text">Kalkıştan sonra bir zaman olmalıdır.</div>
            <div class="invalid-feedback">Varış zamanı zorunludur.</div>
          </div>

          <div class="col-12 col-md-6">
            <label for="price" class="form-label fw-semibold">Fiyat</label>
            <div class="input-group">
              <input
                type="number" id="price" name="price"
                class="form-control" required
                min="1" max="1000000" step="1" inputmode="numeric"
                placeholder="Örn: 450"
                value="<?= e((string)$price) ?>">
              <span class="input-group-text">₺</span>
            </div>
            <div class="invalid-feedback">Lütfen geçerli bir fiyat girin (en az 1 ₺).</div>
          </div>

          <div class="col-12 col-md-6">
            <label for="capacity" class="form-label fw-semibold">Kapasite</label>
            <input
              type="number" id="capacity" name="capacity"
              class="form-control" required
              min="1" max="100" step="1" inputmode="numeric"
              placeholder="Örn: 46"
              value="<?= e((string)$capacity) ?>">
            <div class="invalid-feedback">Kapasite 1–100 aralığında olmalıdır.</div>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
          <a href="/?r=company/panel" class="btn btn-light">
            <i class="bi bi-x-circle"></i> İptal
          </a>
          <button type="submit" class="btn btn-success">
            <i class="bi bi-check-circle"></i> <?= $isEdit ? 'Kaydet' : 'Ekle' ?>
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
  const form = document.getElementById('tripForm');
  const depC = document.getElementById('departure_city');
  const desC = document.getElementById('destination_city');
  const depT = document.getElementById('departure_time');
  const arrT = document.getElementById('arrival_time');
  const price= document.getElementById('price');
  const capa = document.getElementById('capacity');

  [depC, desC].forEach(el => {
    if (!el) return;
    el.addEventListener('blur', () => { el.value = (el.value || '').trim(); });
  });

  function notPast(dt) {
    const t = new Date(dt);
    if (isNaN(t)) return true; 
    const now = new Date();
    return t >= now;
  }

  function arrivalAfterDeparture(depVal, arrVal) {
    const d = new Date(depVal), a = new Date(arrVal);
    if (isNaN(d) || isNaN(a)) return true;
    return a >= d;
  }

  if (form) {
    form.addEventListener('submit', (e) => {
      if (depT && depT.value && !notPast(depT.value)) {
        depT.setCustomValidity('Kalkış geçmiş tarih olamaz');
      } else if (depT) {
        depT.setCustomValidity('');
      }

      if (arrT && arrT.value && !notPast(arrT.value)) {
        arrT.setCustomValidity('Varış geçmiş tarih olamaz');
      } else if (arrT) {
        arrT.setCustomValidity('');
      }

      if (depT && arrT && depT.value && arrT.value && !arrivalAfterDeparture(depT.value, arrT.value)) {
        arrT.setCustomValidity('Varış, kalkıştan önce olamaz');
      } else if (arrT) {
        arrT.setCustomValidity('');
      }

      if (price && (Number(price.value) < 1 || isNaN(Number(price.value)))) {
        price.setCustomValidity('Fiyat en az 1 olmalıdır');
      } else if (price) {
        price.setCustomValidity('');
      }
      if (capa && (Number(capa.value) < 1 || Number(capa.value) > 100 || isNaN(Number(capa.value)))) {
        capa.setCustomValidity('Kapasite 1–100 aralığında olmalıdır');
      } else if (capa) {
        capa.setCustomValidity('');
      }

      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    });
  }
})();
</script>