<style>
  :root{ --bs-primary:#19a974; --bs-success:#138a63; --bs-link-color:#138a63; --bs-btn-border-radius:.6rem; }
  .card{border:0;box-shadow:0 8px 24px rgba(0,0,0,.06);}
  .table>thead th{font-weight:600;}
  .code-copy{cursor:pointer;}
</style>

<?php
  if (!function_exists('e')) {
    function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
  }

  $fmtDT = function($s){
    if (!$s) return '—';
    $ts = strtotime((string)$s);
    return $ts !== false ? date('d.m.Y H:i', $ts) : e((string)$s);
  };

  $coupons = is_array($coupons ?? null) ? $coupons : [];
?>

<div class="container my-5">
  <div class="card shadow-sm border-0">
    <div class="card-body p-4">
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
        <h2 class="h4 mb-0"><i class="bi bi-ticket-perforated me-2"></i>Kuponlarım</h2>
        <div class="d-flex flex-wrap gap-2">
          <a href="/?r=company/coupon-new" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Yeni Kupon
          </a>
          <a href="/?r=company/panel" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Firma Paneli
          </a>
        </div>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-12 col-md-6">
          <input id="filterInput" type="search" class="form-control" placeholder="Koda veya tarihe göre ara...">
        </div>
        <div class="col-12 col-md-6 text-md-end text-muted small">
          Toplam kupon: <strong><?= count($coupons) ?></strong>
        </div>
      </div>

      <?php if (empty($coupons)): ?>
        <div class="alert alert-warning mb-0">
          <i class="bi bi-exclamation-triangle"></i> Henüz kupon bulunamadı. “Yeni Kupon” ile oluşturabilirsiniz.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="couponTable">
            <thead class="table-light">
              <tr>
                <th>Kod</th>
                <th class="text-nowrap">% İnd.</th>
                <th class="text-nowrap">Kullanım</th>
                <th>Kalan</th>
                <th class="text-nowrap">Son Kullanma</th>
                <th>Durum</th>
                <th class="text-center">İşlem</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($coupons as $c):
                $code       = (string)($c['code'] ?? '');
                $discount   = (float)($c['discount'] ?? 0);
                $discountPct= max(0, min(100, (int)round($discount * 100)));

                $used       = (int)($c['used_count']  ?? 0);
                $limit      = max(0, (int)($c['usage_limit'] ?? 0));
                $remaining  = (int)($c['remaining']   ?? max(0, $limit - $used));

                $expireRaw  = $c['expire_date'] ?? null;
                $expTs      = $expireRaw ? strtotime((string)$expireRaw) : false;
                $nowTs      = time();

                $isExpired   = ($expTs !== false) && ($expTs < $nowTs);
                $isExhausted = ($remaining <= 0);
                $daysLeft    = ($expTs !== false) ? (int)floor(($expTs - $nowTs) / 86400) : null;
                $nearExpire  = (!$isExpired && $expTs !== false && $daysLeft !== null && $daysLeft <= 7);

                if ($isExpired)      $statusBadge = '<span class="badge text-bg-danger">Süresi Doldu</span>';
                elseif ($isExhausted)$statusBadge = '<span class="badge text-bg-secondary">Limit Doldu</span>';
                elseif ($nearExpire) $statusBadge = '<span class="badge text-bg-warning">Sona Yakın</span>';
                else                 $statusBadge = '<span class="badge text-bg-success">Aktif</span>';

                $trClass = '';
                if ($isExpired)      $trClass = 'table-danger';
                elseif ($isExhausted)$trClass = 'table-secondary';
                elseif ($nearExpire) $trClass = 'table-warning';
              ?>
              <tr class="<?= $trClass ?>">
                <td class="fw-semibold">
                  <span class="code-copy" data-code="<?= e($code) ?>" role="button" title="Kodu kopyala" tabindex="0">
                    <i class="bi bi-clipboard"></i> <?= e($code) ?>
                  </span>
                </td>
                <td><span class="badge text-bg-info"><?= $discountPct ?>%</span></td>

                <td style="min-width:200px"><?php $used=max(0,(int)($c['used_count']??0));$limitForPct=max(1,(int)($c['usage_limit']??1));$pctBar=min(100,max(0,(int)round(($used/$limitForPct)*100)));$barClass=($pctBar>=90)?'bg-danger':(($pctBar>=60)?'bg-warning':'bg-success');?><div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:8px;" aria-label="Kullanım oranı"><div class="progress-bar <?= $barClass ?>" role="progressbar" style="width: <?= $pctBar ?>%;" aria-valuenow="<?= $pctBar ?>" aria-valuemin="0" aria-valuemax="100"></div></div><div class="text-muted small"><?= $used ?> / <?= $limit ?></div></div></td>

                <td><?= $remaining ?></td>
                <td>
                  <?php if ($expireRaw): ?>
                    <span class="text-nowrap"><?= e($fmtDT($expireRaw)) ?></span>
                    <?php if (!$isExpired && $daysLeft !== null && $daysLeft >= 0): ?>
                      <div class="text-muted small">(<?= $daysLeft ?> gün kaldı)</div>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td><?= $statusBadge ?></td>
                <td class="text-center" style="min-width:200px;">
                  <a href="/?r=company/coupon-edit&id=<?= rawurlencode((string)($c['id'] ?? '')) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil"></i> Düzenle
                  </a>
                  <form method="post" action="/?r=company/coupon-delete-post" class="d-inline-block js-confirm">
                    <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                    <input type="hidden" name="id" value="<?= e((string)($c['id'] ?? '')) ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                      <i class="bi bi-trash"></i> Sil
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const input=document.getElementById('filterInput');
  const table=document.getElementById('couponTable');
  if(input&&table){
    input.addEventListener('input',()=>{
      const q=input.value.toLowerCase().trim();
      table.querySelectorAll('tbody tr').forEach(tr=>{
        tr.style.display=tr.innerText.toLowerCase().includes(q)?'':'none';
      });
    });
  }
  document.querySelectorAll('form.js-confirm').forEach(f=>{
    f.addEventListener('submit',e=>{
      if(!confirm('Bu kuponu silmek istediğinizden emin misiniz?')) e.preventDefault();
    });
  });
  const copyEls=document.querySelectorAll('.code-copy');
  copyEls.forEach(el=>{
    const copy=async()=>{
      const code=el.getAttribute('data-code')||'';
      try{
        await navigator.clipboard.writeText(code);
        const orig=el.innerHTML;
        el.innerHTML='<i class="bi bi-check2"></i> '+code;
        setTimeout(()=>{el.innerHTML=orig;},1200);
      }catch{alert('Kopyalanamadı, lütfen elle kopyalayın.');}
    };
    el.addEventListener('click',copy);
    el.addEventListener('keypress',ev=>{
      if(ev.key==='Enter'||ev.key===' '){ev.preventDefault();copy();}
    });
  });
})();
</script>
