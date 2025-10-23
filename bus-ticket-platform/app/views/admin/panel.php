<?php
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$NONCE = (string)($GLOBALS['CSP_NONCE'] ?? ''); 
$stats = $stats ?? [];
$companies = (int)($stats['companies'] ?? 0);
$admins    = (int)($stats['admins'] ?? 0);
$coupons   = (int)($stats['coupons'] ?? 0);
$trips     = (int)($stats['trips'] ?? 0);
?>

<div class="container py-4">
  <div class="admin-panel" role="region" aria-label="Yönetim Kontrol Merkezi">
    <h2>🧭 Yönetim Kontrol Merkezi</h2>
    <div class="admin-subtitle">Hızlı erişim ve anlık özet metrikler</div>

    <nav class="admin-links" aria-label="Yönetim bağlantıları">
      <a href="/?r=admin/companies"><i class="bi bi-buildings"></i> Firmalar</a>
      <a href="/?r=admin/company-admins"><i class="bi bi-person-badge"></i> Firma Adminleri</a>
      <a href="/?r=admin/coupons"><i class="bi bi-ticket-perforated"></i> Kuponlar</a>
      <a href="/?r=admin/trips"><i class="bi bi-bus-front"></i> Seferler</a>
    </nav>

    <ul class="stats" aria-label="Özet sayılar">
      <li><span class="label">Firma Sayısı</span><span class="value"><?= $companies ?></span></li>
      <li><span class="label">Firma Admin Sayısı</span><span class="value"><?= $admins ?></span></li>
      <li><span class="label">Kupon Sayısı</span><span class="value"><?= $coupons ?></span></li>
      <li><span class="label">Sefer Sayısı</span><span class="value"><?= $trips ?></span></li>
    </ul>
  </div>
</div>

<style nonce="<?= e($NONCE) ?>">
  :root{
    --brand-primary:#19a974;
    --brand-primary-dark:#138a63;
    --brand-surface:#f6fbf9;
  }
  .admin-panel {
    max-width: 820px;
    margin: 48px auto;
    background: var(--brand-surface);
    padding: 28px 36px;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(16, 94, 70, 0.08);
    font-family: system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans","Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol";
  }
  .admin-panel h2 {
    text-align: center;
    color: #0f5132;
    margin-bottom: 22px;
    font-weight: 700;
  }
  .admin-subtitle{
    text-align:center;
    color:#46665a;
    margin-top:-8px;
    margin-bottom:22px;
    font-size:.95rem;
  }

  .admin-links {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px 14px;
    margin-bottom: 26px;
  }
  .admin-links a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: #fff;
    background: var(--brand-primary);
    padding: 10px 14px;
    border-radius: 10px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(25,169,116,.18);
    transition: transform .12s ease, box-shadow .12s ease, background .12s ease;
  }
  .admin-links a:hover,
  .admin-links a:focus-visible {
    background: var(--brand-primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(25,169,116,.22);
    outline: none;
  }

  .stats {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 14px;
  }
  @media (max-width: 768px){
    .stats{ grid-template-columns: repeat(2, minmax(0,1fr)); }
  }
  .stats li {
    background: #fff;
    padding: 14px 12px;
    border-radius: 12px;
    text-align: center;
    color: #2f3f38;
    border: 1px solid #e8f2ee;
    box-shadow: 0 2px 10px rgba(0,0,0,.04);
  }
  .stats .label {
    display:block;
    font-size:.86rem;
    color:#627d72;
    margin-bottom:6px;
    font-weight:600;
  }
  .stats .value {
    display:block;
    font-size:1.4rem;
    font-weight:800;
    color: var(--brand-primary-dark);
  }
</style>
