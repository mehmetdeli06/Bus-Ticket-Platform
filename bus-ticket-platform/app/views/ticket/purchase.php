<?php
/** @var array $trip  */
/** @var array $occupied */
/** @var int   $balance */

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$NONCE = (string)($GLOBALS['CSP_NONCE'] ?? '');

$depEpoch = 0;
try {
  if (!empty($trip['departure_time'])) {
    $depEpoch = (new DateTimeImmutable((string)$trip['departure_time'], new DateTimeZone('Europe/Istanbul')))->getTimestamp();
  }
} catch (Throwable) { $depEpoch = 0; }

$cap   = (int)($trip['capacity'] ?? 0);
$occ   = array_fill_keys(array_map('intval', $occupied ?? []), true);
$rows  = (int)ceil(max(0, $cap) / 4);
$price = (int)($trip['price'] ?? 0);
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container my-4">
  <div class="card shadow-sm mb-3 border-0">
    <div class="card-body d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
      <div>
        <h1 class="h4 mb-1">
          🚌 <?= e($trip['company_name'] ?? '-') ?> — <?= e($trip['departure_city'] ?? '-') ?> → <?= e($trip['destination_city'] ?? '-') ?>
        </h1>
        <div class="text-muted">
          Kalkış: <strong><?= e($trip['departure_time'] ?? '-') ?></strong>
          <span class="mx-2">|</span>
          Fiyat: <strong id="unitPrice" data-unit="<?= $price ?>"><?= $price ?></strong> ₺
          <span class="mx-2">|</span>
          Bakiye: <strong id="balance" data-balance="<?= (int)$balance ?>"><?= (int)$balance ?></strong> ₺
        </div>
      </div>
      <div class="d-flex align-items-center gap-3 small">
        <span class="legend legend-available"></span> Boş
        <span class="legend legend-selected"></span> Seçili
        <span class="legend legend-occupied"></span> Dolu
      </div>
    </div>
  </div>

  <form id="buy-form" method="post" action="/?r=ticket/purchase-post" novalidate>
    <?= function_exists('csrf_field') ? csrf_field() : '' ?>
    <input type="hidden" id="trip_id" name="trip_id" value="<?= e((string)($trip['id'] ?? '')) ?>">
    <input type="hidden" id="dep_ts" value="<?= (int)$depEpoch ?>">

    <div class="card shadow-sm mb-3 border-0">
      <div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-6">
            <label for="coupon" class="form-label fw-semibold">Kupon Kodu (opsiyonel)</label>
            <input type="text" id="coupon" name="coupon_code" class="form-control" placeholder="ORNEK20" autocomplete="off" inputmode="latin">
          </div>
          <div class="col-12 col-md-auto d-grid">
            <button type="button" id="btn-calc" class="btn btn-outline-primary">Fiyatı Hesapla</button>
          </div>
          <div class="col-12 col-md">
            <span id="calc-status" class="text-muted small"></span>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-3 border-0">
      <div class="card-body">
        <div class="bus-wrap mx-auto">
          <div class="driver">Sürücü</div>

          <?php
            $n = 1;
            for ($row = 1; $row <= $rows; $row++):
              $left1  = ($n <= $cap) ? $n++ : null;
              $left2  = ($n <= $cap) ? $n++ : null;
              $right1 = ($n <= $cap) ? $n++ : null;
              $right2 = ($n <= $cap) ? $n++ : null;
          ?>
            <div class="row-seats">
              <?php foreach ([$left1,$left2] as $seatNo): ?>
                <?php if ($seatNo === null): ?>
                  <div class="seat placeholder" aria-hidden="true"></div>
                <?php else:
                  $disabled = isset($occ[$seatNo]);
                ?>
                  <label class="seat <?= $disabled ? 'seat-occupied' : 'seat-available' ?>" aria-label="Koltuk <?= (int)$seatNo ?>">
                    <input type="checkbox" name="seats[]" value="<?= (int)$seatNo ?>" <?= $disabled ? 'disabled' : '' ?>>
                    <span><?= (int)$seatNo ?></span>
                  </label>
                <?php endif; ?>
              <?php endforeach; ?>

              <div class="aisle" aria-hidden="true"></div>

              <?php foreach ([$right1,$right2] as $seatNo): ?>
                <?php if ($seatNo === null): ?>
                  <div class="seat placeholder" aria-hidden="true"></div>
                <?php else:
                  $disabled = isset($occ[$seatNo]);
                ?>
                  <label class="seat <?= $disabled ? 'seat-occupied' : 'seat-available' ?>" aria-label="Koltuk <?= (int)$seatNo ?>">
                    <input type="checkbox" name="seats[]" value="<?= (int)$seatNo ?>" <?= $disabled ? 'disabled' : '' ?>>
                    <span><?= (int)$seatNo ?></span>
                  </label>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-body">
        <div class="row gy-2 align-items-center">
          <div class="col-12 col-md">
            <div class="d-flex flex-wrap gap-3">
              <div>🧾 Birim fiyat: <strong id="unit" data-unit="<?= $price ?>"><?= $price ?></strong> ₺</div>
              <div>💸 İndirim: <strong id="discount">0</strong> ₺</div>
              <div>🧮 Toplam: <strong id="total">0</strong> ₺</div>
            </div>
            <div class="mt-2">
              <span id="balance-warn" class="text-danger d-none">Bakiye yetersiz görünüyor.</span><br>
              <span id="dep-warn" class="text-danger d-none">Bu seferin kalkış saati geçmiş.</span>
            </div>
          </div>
          <div class="col-12 col-md-auto d-grid">
            <button id="btn-buy" type="submit" class="btn btn-success" disabled>Satın Al</button>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<style nonce="<?= e($NONCE) ?>">
:root{
  --seat-green: #198754;          
  --seat-green-soft:#e8f5ef;      
  --seat-available:#e6efe9;       
  --seat-occupied:#dc3545;      
  --border:#e5e7eb;
}

.legend { display:inline-block; width:14px; height:14px; border-radius:4px; border:1px solid rgba(0,0,0,.08); }
.legend-available { background:var(--seat-available); }
.legend-selected  { background:var(--seat-green); }
.legend-occupied  { background:var(--seat-occupied); }

.bus-wrap { max-width: 560px; }
.driver {
  width: 140px; text-align:center; font-weight:600; font-size:12px;
  padding:6px 8px; background:#f1f5f9; border:1px solid var(--border); border-radius:8px; margin-bottom:10px;
}
.row-seats {
  display:grid;
  grid-template-columns: 1fr 1fr 30px 1fr 1fr; 
  gap: 10px;
  margin-bottom: 10px;
  align-items: center;
}
.aisle { height: 1px; }

.seat { display:flex; align-items:center; justify-content:center; }
.seat input[type="checkbox"]{ display:none; } 
.seat > span{
  display:flex; align-items:center; justify-content:center;
  width:48px; height:48px; border-radius:10px; font-weight:700;
  border:1px solid var(--border); background:var(--seat-available); color:#0f2b1a;
  transition: transform .15s, box-shadow .15s, background .15s, color .15s, border-color .15s;
}
.seat-available > span:hover{
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,.08);
  background: var(--seat-green-soft);
  border-color: var(--seat-green);
}

.seat input[type="checkbox"]:checked + span{
  background:var(--seat-green); color:#fff; border-color:var(--seat-green);
}

.seat-occupied > span{
  background:var(--seat-occupied); color:#fff; border-color:var(--seat-occupied);
  cursor:not-allowed; opacity:.9;
}
.seat-occupied { pointer-events:none; } 

.seat.placeholder { visibility:hidden; }

@media (max-width:480px){
  .row-seats{ gap:8px; grid-template-columns: 1fr 1fr 20px 1fr 1fr; }
  .seat > span{ width:44px; height:44px; font-size:14px; }
  .driver { width: 120px; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" nonce="<?= e($NONCE) ?>"></script>

<script nonce="<?= e($NONCE) ?>">
(() => {
  const $  = (s) => document.querySelector(s);
  const $$ = (s) => Array.from(document.querySelectorAll(s));

  const form       = $('#buy-form');
  const unitEl     = $('#unit');       
  const unitHdr    = $('#unitPrice');  
  const totalEl    = $('#total');
  const discEl     = $('#discount');
  const statusEl   = $('#calc-status');
  const warnEl     = $('#balance-warn');
  const depWarnEl  = $('#dep-warn');
  const buyBtn     = $('#btn-buy');
  const depTs      = parseInt($('#dep_ts')?.value || '0', 10);
  const balance    = parseInt($('#balance')?.dataset.balance || '0', 10);
  const unitData   = parseInt(unitEl?.dataset.unit || unitEl?.textContent || '0', 10);

  const safeInt = (v, d=0) => { const n = parseInt(v, 10); return Number.isFinite(n) ? n : d; };
  const getCsrf = () => { const el = form.querySelector('input[name="_csrf"]'); return el ? el.value : ''; };
  const selectedSeats = () =>
    $$('#buy-form input[name="seats[]"]:checked').filter(i => !i.disabled).map(i => safeInt(i.value));

  const setBuyEnabled = (on) => { buyBtn.disabled = !on; buyBtn.classList.toggle('disabled', !on); };

  const checkDeparturePastNow = () => {
    if (!depTs) return false;
    const past = depTs <= Math.floor(Date.now()/1000);
    depWarnEl.classList.toggle('d-none', !past);
    if (past) setBuyEnabled(false);
    return past;
  };

  async function previewPrice() {
    if (checkDeparturePastNow()) {
      statusEl.textContent = 'Kalkış geçmiş.';
      discEl.textContent = '0'; totalEl.textContent = '0';
      return;
    }

    const seats = selectedSeats();
    if (seats.length === 0) {
      statusEl.textContent = 'Lütfen en az bir koltuk seçin.';
      discEl.textContent = '0'; totalEl.textContent = '0';
      warnEl.classList.add('d-none'); setBuyEnabled(false);
      return;
    }

    const fd = new FormData();
    fd.append('_csrf', getCsrf());
    fd.append('trip_id', $('#trip_id').value);
    seats.forEach(s => fd.append('seats[]', String(s)));
    fd.append('coupon_code', ($('#coupon').value || '').trim());

    try {
      statusEl.textContent = 'Hesaplanıyor...';
      const res = await fetch('/?r=ticket/calc-price', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      if (!res.ok) {
        statusEl.textContent = 'Hesaplama hatası (HTTP ' + res.status + ').';
        setBuyEnabled(false);
        return;
      }

      const j = await res.json().catch(() => null);
      if (!j || typeof j !== 'object') {
        statusEl.textContent = 'Beklenmeyen yanıt.';
        setBuyEnabled(false);
        return;
      }

      if (!j.ok) {
        statusEl.textContent = j.msg || 'Kupon/hesaplama reddedildi.';
        if (($('#coupon').value || '').trim().length > 0) {
          $('#coupon').value = '';
          setTimeout(previewPrice, 50);
        } else {
          setBuyEnabled(false);
        }
        return;
      }

      const unit = safeInt(j.unit, unitData);
      unitEl.textContent = String(unit);
      if (unitHdr) unitHdr.textContent = String(unit);

      const discount = Math.max(0, safeInt(j.discount, 0));
      const total    = Math.max(0, safeInt(j.total, unit * seats.length));

      discEl.textContent  = String(discount);
      totalEl.textContent = String(total);

      const needWarn = total > balance;
      warnEl.classList.toggle('d-none', !needWarn);
      setBuyEnabled(!needWarn);

      statusEl.textContent = j.code ? ('Kupon uygulandı: ' + j.code) : 'Hesap güncellendi';
    } catch (err) {
      statusEl.textContent = 'Ağ hatası: tekrar deneyin.';
      setBuyEnabled(false);
    }
  }

  const debounce = (fn, ms) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; };
  const previewDebounced = debounce(previewPrice, 250);

  $('#btn-calc').addEventListener('click', previewPrice);
  $('#coupon').addEventListener('input', previewDebounced);
  $$('#buy-form input[name="seats[]"]').forEach(cb => cb.addEventListener('change', previewDebounced));
  form.addEventListener('submit', (e) => { if (buyBtn.disabled) e.preventDefault(); });

  previewPrice();
})();
</script>
