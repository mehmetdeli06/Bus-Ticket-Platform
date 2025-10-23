<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<?php
  if (!function_exists('e')) {
    function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
  }
  $NONCE = (string)($GLOBALS['CSP_NONCE'] ?? '');
  $okSafe      = !empty($ok);
  $totalSafe   = isset($total) ? (int)$total : 0;
  $ticketIdStr = isset($ticket_id) ? (string)$ticket_id : '';
  $pdfUrlSafe  = isset($pdf_url) ? (string)$pdf_url : '';
  $msgSafe     = isset($msg) ? (string)$msg : 'Bilinmeyen hata';
?>
<div class="container py-5">
  <?php if ($okSafe): ?>
    <div class="row justify-content-center">
      <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width:48px;height:48px;" aria-hidden="true">
                <i class="bi bi-check-lg fs-4"></i>
              </div>
              <div>
                <h2 class="h4 mb-1">Satın alma başarılı <span aria-hidden="true">🎉</span></h2>
                <div class="text-muted">İşleminiz tamamlandı.</div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <div class="p-3 rounded border bg-light">
                  <div class="text-muted small">Toplam Tutar</div>
                  <div class="fs-5 fw-semibold"><?= $totalSafe ?> ₺</div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="p-3 rounded border bg-light d-flex align-items-center justify-content-between">
                  <div class="me-3">
                    <div class="text-muted small">Bilet No</div>
                    <code id="ticketCode" class="fw-semibold"><?= e($ticketIdStr) ?></code>
                  </div>
                  <button id="copyBtn" type="button" class="btn btn-outline-secondary btn-sm" aria-label="Bilet numarasını kopyala">
                    <i class="bi bi-clipboard"></i> Kopyala
                  </button>
                </div>
              </div>
            </div>

            <?php if ($pdfUrlSafe !== ''): ?>
              <div class="alert alert-info d-flex align-items-center gap-2 mt-3 mb-0" role="status">
                <i class="bi bi-file-earmark-pdf"></i>
                <div>Bilet PDF hazır. Aşağıdan indirebilirsiniz.</div>
              </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2 mt-4">
              <?php if ($pdfUrlSafe !== ''): ?>
                <a class="btn btn-outline-danger" href="<?= e($pdfUrlSafe) ?>" target="_blank" rel="noopener noreferrer" aria-label="Bilet PDF indir (yeni sekme)">
                  <i class="bi bi-file-earmark-pdf"></i> PDF İndir
                </a>
              <?php endif; ?>
              <a class="btn btn-primary" href="/?r=ticket/my">
                <i class="bi bi-ticket-perforated"></i> Biletlerim
              </a>
              <a class="btn btn-success" href="/?r=home/index">
                <i class="bi bi-house-door"></i> Ana sayfa
              </a>
              <a class="btn btn-outline-secondary" href="javascript:history.back()" aria-label="Önceki sayfaya dön">
                <i class="bi bi-arrow-left"></i> Geri dön
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

  <?php else: ?>
    <div class="row justify-content-center">
      <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width:48px;height:48px;" aria-hidden="true">
                <i class="bi bi-x-lg fs-5"></i>
              </div>
              <div>
                <h2 class="h4 mb-1">Satın alma başarısız</h2>
                <div class="text-muted">İşlem tamamlanamadı, lütfen tekrar deneyin.</div>
              </div>
            </div>

            <div class="alert alert-danger" role="alert">
              <?= e($msgSafe) ?>
            </div>

            <div class="d-flex flex-wrap gap-2">
              <a class="btn btn-outline-secondary" href="javascript:history.back()" aria-label="Önceki sayfaya dön">
                <i class="bi bi-arrow-counterclockwise"></i> Tekrar dene
              </a>
              <a class="btn btn-primary" href="/?r=ticket/my">
                <i class="bi bi-ticket-perforated"></i> Biletlerim
              </a>
              <a class="btn btn-light" href="/?r=home/index">
                <i class="bi bi-house-door"></i> Ana sayfa
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script nonce="<?= e($NONCE) ?>">
(() => {
  const copyBtn = document.getElementById('copyBtn');
  const codeEl  = document.getElementById('ticketCode');
  if (!copyBtn || !codeEl) return;

  copyBtn.addEventListener('click', async () => {
    try {
      const text = (codeEl.textContent || '').trim();
      if (!text) return;
      await navigator.clipboard.writeText(text);
      const prev = copyBtn.innerHTML;
      copyBtn.innerHTML = '<i class="bi bi-check2"></i> Kopyalandı';
      copyBtn.classList.remove('btn-outline-secondary');
      copyBtn.classList.add('btn-success');
      setTimeout(() => {
        copyBtn.innerHTML = prev;
        copyBtn.classList.remove('btn-success');
        copyBtn.classList.add('btn-outline-secondary');
      }, 1500);
    } catch {
      alert('Kopyalama başarısız. Lütfen elle kopyalayın.');
    }
  });
})();
</script>
