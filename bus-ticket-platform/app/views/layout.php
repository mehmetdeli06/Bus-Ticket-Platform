<?php
declare(strict_types=1);

$u = function_exists('current_user') ? (current_user() ?: []) : [];

if (!function_exists('e')) {
  function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

$CSP_NONCE = (string)($GLOBALS['CSP_NONCE'] ?? '');

?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Bilet Platformu</title>

  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">

  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-light">

  <?php
    $navPath = defined('BASE_PATH') ? (BASE_PATH . '/app/views/partials/nav.php') : null;
    if ($navPath && is_file($navPath)) {
      include $navPath;
    } else {
    }
  ?>

  <main class="container py-4">
    <?= $content ?? ''  ?>
  </main>

  <footer class="text-center py-3 text-muted small border-top">
    <div>© <?= e((string)date('Y')) ?> Bilet Platformu — Tüm hakları saklıdır.</div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
          nonce="<?= e($CSP_NONCE) ?>" crossorigin="anonymous"></script>
</body>
</html>
