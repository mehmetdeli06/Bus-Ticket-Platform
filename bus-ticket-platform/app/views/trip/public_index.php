<?php
/** @var array $trip  (publicIndex() 'trip' => $trips gönderiyor) */
/** @var array $trips (bazı sürümlerde 'trips' de gelebilir) */

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

/* Hem 'trip' hem 'trips' key'lerini destekle */
$trips = isset($trips) && is_array($trips) ? $trips : (is_array($trip ?? null) ? $trip : []);
?>
<div class="container my-4">
  <h1 class="h4 mb-3">Seferler (Misafir Görünümü)</h1>

  <?php if (empty($trips)): ?>
    <div class="alert alert-info">Yaklaşan sefer bulunamadı.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>Kalkış</th>
            <th>Varış</th>
            <th>Kalkış Tarih/Saat</th>
            <th>Firma</th>
            <th>Fiyat</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($trips as $t): ?>
          <tr>
            <td><?= e($t['route_from'] ?? $t['departure_city'] ?? '') ?></td>
            <td><?= e($t['route_to']   ?? $t['destination_city'] ?? '') ?></td>
            <td>
              <?php
                $dt = (string)($t['departure_time'] ?? '');
                $ts = $dt ? strtotime($dt) : false;
                echo $ts ? e(date('d.m.Y H:i', $ts)) : '—';
              ?>
            </td>
            <td><?= e($t['company_name'] ?? '—') ?></td>
            <td>
              <?php
                $price = isset($t['price']) ? (float)$t['price'] : null;
                echo $price !== null ? e(number_format($price, 2)) . ' ₺' : '—';
              ?>
            </td>
            <td class="text-end">
              <a class="btn btn-outline-primary btn-sm"
                 href="/?r=trip/publicDetail&id=<?= (int)($t['id'] ?? 0) ?>">
                 Detay
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
