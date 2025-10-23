<style>
  :root{ --bs-primary:#19a974; --bs-link-color:#138a63; --bs-btn-border-radius:.6rem; }
  .nav-pills .nav-link{ font-weight:600; }
  .nav-pills .nav-link i{ margin-right:.4rem; }
</style>

<?php
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$current = (string)($_GET['r'] ?? '');
$current = preg_replace('~[^a-zA-Z0-9/_-]~', '', $current); 

function active_link(string $target, string $current): string {
  if ($target === $current) return 'active';
  if ($current !== '' && str_starts_with($current, $target.'/')) return 'active';
  return '';
}
?>

<nav class="nav nav-pills flex-wrap mb-4 gap-2" aria-label="Firma paneli alt menü">
  <?php
    $items = [
      ['href' => '/?r=company/panel',   'r' => 'company/panel',   'icon' => 'bi-bus-front',          'label' => 'Seferler'],
      ['href' => '/?r=company/coupons', 'r' => 'company/coupons', 'icon' => 'bi-ticket-perforated',  'label' => 'Kuponlarım'],
      ['href' => '/?r=company/tickets', 'r' => 'company/tickets', 'icon' => 'bi-receipt',            'label' => 'Satılan Biletler'],
    ];
    foreach ($items as $it):
      $isActive = active_link($it['r'], $current) === 'active';
  ?>
    <a href="<?= e($it['href']) ?>"
       class="nav-link <?= $isActive ? 'active' : '' ?>"
       <?= $isActive ? 'aria-current="page"' : '' ?>>
      <i class="bi <?= e($it['icon']) ?>"></i> <?= e($it['label']) ?>
    </a>
  <?php endforeach; ?>
</nav>