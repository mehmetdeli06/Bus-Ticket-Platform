<?php
/** @var array $trip */
/** @var int   $sold */

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$cap   = (int)($trip['capacity'] ?? 0);
$sold  = (int)($sold ?? 0);
$left  = max(0, $cap - $sold);

$depTs = !empty($trip['departure_time']) ? strtotime((string)$trip['departure_time']) : false;
$arrTs = !empty($trip['arrival_time'])   ? strtotime((string)$trip['arrival_time'])   : false;
?>
<div class="container my-4">
  <a href="/?r=trip/publicIndex" class="btn btn-link">&larr; Seferlere dön</a>

  <div class="card shadow-sm">
    <div class="card-body">
      <h1 class="h5 mb-3">
        <?= e($trip['route_from'] ?? $trip['departure_city'] ?? '') ?>
        &rarr;
        <?= e($trip['route_to']   ?? $trip['destination_city'] ?? '') ?>
      </h1>

      <div class="row g-3">
        <div class="col-md-6">
          <ul class="list-unstyled mb-0">
            <li><strong>Kalkış:</strong>
              <?= $depTs ? e(date('d.m.Y H:i', $depTs)) : '—' ?>
            </li>
            <li><strong>Varış:</strong>
              <?= $arrTs ? e(date('d.m.Y H:i', $arrTs)) : '—' ?>
            </li>
            <li><strong>Firma:</strong> <?= e($trip['company_name'] ?? '—') ?></li>
          </ul>
        </div>
        <div class="col-md-6">
          <ul class="list-unstyled mb-0">
            <li><strong>Fiyat:</strong>
              <?php
                $price = isset($trip['price']) ? (float)$trip['price'] : null;
                echo $price !== null ? e(number_format($price, 2)) . ' ₺' : '—';
              ?>
            </li>
            <li><strong>Kapasite:</strong> <?= (int)$cap ?> (Kalan: <?= (int)$left ?>)</li>
          </ul>
        </div>
      </div>

      <hr class="my-4">

      <!-- Misafir için satın alma kilitli -->
      <div class="alert alert-warning d-flex align-items-center gap-2 mb-0">
        <div>Satın almak için giriş yapmalısınız.</div>
        <a class="btn btn-sm btn-primary ms-auto"
           href="/?r=auth/login&next=<?= urlencode('/?r=ticket/buy&trip_id='.(int)($trip['id'] ?? 0)) ?>">
           Giriş yap
        </a>
      </div>
    </div>
  </div>
</div>
