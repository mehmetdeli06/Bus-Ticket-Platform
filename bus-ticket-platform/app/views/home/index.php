<?php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$qDeparture   = e($_GET['departure']  ?? '');
$qDestination = e($_GET['destination']?? '');
$qDate        = e($_GET['date']       ?? '');

$NONCE = (string)($GLOBALS['CSP_NONCE'] ?? '');
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<section class="py-5 bg-light border-bottom mb-4">
  <div class="container text-center">
    <h1 class="display-6 mb-3">🚌 Otobüs Seferi Ara</h1>
    <p class="text-muted mb-0">Kalkış ve varış şehirlerini seçin, tarih belirleyin ve uygun seferleri görüntüleyin.</p>
  </div>
</section>

<div class="container my-4">
  <div class="card shadow-sm">
    <div class="card-body">
      <form method="get" action="/" class="row g-3 align-items-end needs-validation" novalidate autocapitalize="off" autocomplete="off" spellcheck="false">
        <input type="hidden" name="r" value="trip/search">

        <div class="col-12 col-md-4">
          <label for="departure" class="form-label fw-semibold">🟢 Kalkış</label>
          <input
            type="text"
            class="form-control"
            id="departure"
            name="departure"
            placeholder="Örn: Ankara"
            value="<?= $qDeparture ?>"
            required
            minlength="2"
            maxlength="64"
            pattern="^[A-Za-zÇĞİÖŞÜçğıöşü\s'.-]{2,64}$"
            inputmode="text"
            aria-describedby="depHelp"
          >
          
          <div class="invalid-feedback">Lütfen geçerli bir kalkış şehri girin.</div>
        </div>

        <div class="col-12 col-md-4">
          <label for="destination" class="form-label fw-semibold">🔵 Varış</label>
          <input
            type="text"
            class="form-control"
            id="destination"
            name="destination"
            placeholder="Örn: İstanbul"
            value="<?= $qDestination ?>"
            required
            minlength="2"
            maxlength="64"
            pattern="^[A-Za-zÇĞİÖŞÜçğıöşü\s'.-]{2,64}$"
            inputmode="text"
            aria-describedby="destHelp"
          >
         
          <div class="invalid-feedback">Lütfen geçerli bir varış şehri girin.</div>
        </div>

        <div class="col-12 col-md-3">
          <label for="date" class="form-label fw-semibold">📅 Tarih</label>
          <input
            type="date"
            class="form-control"
            id="date"
            name="date"
            value="<?= $qDate ?>"
            aria-describedby="dateHelp"
          >
          
          <div class="invalid-feedback">Lütfen geçerli bir tarih seçin.</div>
        </div>

        <div class="col-12 col-md-1 d-grid">
          <button type="submit" class="btn btn-primary mt-3 mt-md-0">Ara</button>
          <noscript><button class="btn btn-outline-secondary mt-2" type="submit">Gönder</button></noscript>
        </div>
      </form>
    </div>
  </div>

  <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
    <div class="alert alert-success mt-4 mb-0 text-center">
      👋 Hoş geldin, <strong><?= e((string)(current_user()['name'] ?? '')) ?></strong>!
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script nonce="<?= e($NONCE) ?>">
(() => {
  'use strict';
  const form = document.querySelector('form.needs-validation');
  const dateInput = document.getElementById('date');

  if (dateInput) {
    const today = new Date();
    const y = today.getFullYear();
    const m = String(today.getMonth() + 1).padStart(2, '0');
    const d = String(today.getDate()).padStart(2, '0');
    const minVal = `${y}-${m}-${d}`;
    dateInput.setAttribute('min', minVal);

    if (dateInput.value && dateInput.value < minVal) {
      dateInput.value = '';
    }
  }

  if (form) {
    form.addEventListener('submit', (e) => {
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    });
  }
})();
</script>
