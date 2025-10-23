<style>
  :root{
    --bs-primary:#19a974; --bs-success:#138a63; --bs-link-color:#138a63; --bs-btn-border-radius:.6rem;
  }
  .card{border:0;box-shadow:0 6px 16px rgba(0,0,0,.06);}
  .table>thead th{font-weight:600;}
</style>

<?php

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$fmtDiscount = function ($d) {
  $v = (float)$d;
  if ($v >= 0 && $v <= 1) return (int)round($v * 100) . '%';
  return e($d);
};
?>

<div class="container my-4">
  <div class="card">
    <div class="card-body">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <h2 class="h4 mb-0"><i class="bi bi-ticket-perforated me-2"></i>Kuponlar</h2>
        <div class="d-flex gap-2">
          <?php if (!empty($coupons)): ?>
            <input id="couponSearch" type="search" class="form-control w-auto" placeholder="Ara (kod, firma...)" aria-label="Kupon arama">
          <?php endif; ?>
          <a href="/?r=admin/coupon-new" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Yeni Kupon
          </a>
        </div>
      </div>

      <?php if (empty($coupons)): ?>
        <div class="alert alert-info mb-0">
          <i class="bi bi-info-circle me-1"></i> Henüz kupon bulunmuyor. “Yeni Kupon” butonu ile ekleyin.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="couponsTable">
            <thead class="table-light">
              <tr>
                <th scope="col">Kod</th>
                <th scope="col">İndirim</th>
                <th scope="col">Firma</th>
                <th scope="col">Kullanım</th>
                <th scope="col">Kalan</th>
                <th scope="col">Son Kullanma</th>
                <th scope="col" class="text-end">İşlemler</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($coupons as $c): ?>
                <?php
                  $code   = $c['code'] ?? '';
                  $used   = (int)($c['used_count']   ?? 0);
                  $limit  = (int)($c['usage_limit']  ?? 0);
                  $remain = (int)($c['remaining']    ?? max(0, $limit - $used));

                  $expRaw = $c['expire_date'] ?? '';
                  $expTs  = $expRaw ? strtotime((string)$expRaw) : false;
                  $isExpired   = $expTs !== false && $expTs < time();
                  $isExhausted = ($remain <= 0);

                  $companyName = $c['company_name'] ?? '';
                  $id          = $c['id'] ?? '';
                ?>
                <tr>
                  <td><span class="fw-semibold"><?= e($code) ?></span></td>
                  <td><?= $fmtDiscount($c['discount'] ?? '') ?></td>
                  <td>
                    <?php if ($companyName !== ''): ?>
                      <span class="badge text-bg-secondary"><?= e($companyName) ?></span>
                    <?php else: ?>
                      <span class="badge text-bg-info">Genel</span>
                    <?php endif; ?>
                  </td>
                  <td><?= $used ?> / <?= $limit ?></td>
                  <td>
                    <?php if ($isExhausted): ?>
                      <span class="badge text-bg-danger">Tükendi</span>
                    <?php else: ?>
                      <span class="badge text-bg-success"><?= $remain ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($isExpired): ?>
                      <span class="badge text-bg-danger me-1">Süresi Doldu</span>
                    <?php endif; ?>
                    <?= e($expTs ? date('d.m.Y H:i', $expTs) : ($expRaw ?? '')) ?>
                  </td>
                  <td class="text-end" style="min-width:240px;">
                    <a href="/?r=admin/coupon-edit&id=<?= rawurlencode((string)$id) ?>"
                       class="btn btn-sm btn-outline-primary me-1">
                      <i class="bi bi-pencil"></i> Düzenle
                    </a>
                    <form method="post" action="/?r=admin/coupon-delete-post"
                          class="d-inline-block js-confirm"
                          onsubmit="return confirm('Bu kuponu silmek istediğinize emin misiniz?')">
                      <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                      <input type="hidden" name="id" value="<?= e($id) ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">
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
  (() => {
    const q = document.getElementById('couponSearch');
    const t = document.getElementById('couponsTable');
    if (!q || !t) return;
    q.addEventListener('input', () => {
      const s = q.value.toLowerCase().trim();
      t.querySelectorAll('tbody tr').forEach(tr => {
        tr.style.display = tr.innerText.toLowerCase().includes(s) ? '' : 'none';
      });
    });
  })();

  (() => {
    document.querySelectorAll('form.js-confirm').forEach(f => {
      f.addEventListener('submit', e => {
        if (!confirm('Bu kuponu silmek istediğinize emin misiniz?')) e.preventDefault();
      });
    });
  })();