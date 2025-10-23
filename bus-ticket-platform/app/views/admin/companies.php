<style>
  :root{
    --bs-primary:#19a974;
    --bs-success:#138a63;
    --bs-link-color:#138a63;
    --bs-btn-border-radius:.6rem;
  }
  .card{border:0;box-shadow:0 6px 16px rgba(0,0,0,.06);}
</style>

<?php if (!function_exists('e')){ function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } } ?>

<div class="container my-4">
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0"><i class="bi bi-buildings me-2"></i>Firmalar</h2>
        <a href="/?r=admin/company-new" class="btn btn-primary">
          <i class="bi bi-plus-lg"></i> Yeni Firma
        </a>
      </div>

      <?php if (empty($companies)): ?>
        <div class="alert alert-info mb-0">
          <i class="bi bi-info-circle me-1"></i> Henüz kayıtlı firma bulunmuyor. Yeni bir firma ekleyin.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col">Ad</th>
                <th scope="col" class="text-nowrap">Oluşturulma</th>
                <th scope="col" class="text-end text-nowrap">İşlemler</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($companies as $c): ?>
              <tr>
                <td><?= e($c['name'] ?? '-') ?></td>
                <td class="text-nowrap">
                  <?= !empty($c['created_at']) ? e(date('d.m.Y H:i', strtotime($c['created_at']))) : '—' ?>
                </td>
                <td class="text-end" style="min-width:220px;">
                  <a href="/?r=admin/company-edit&id=<?= rawurlencode((string)($c['id'] ?? '')) ?>"
                     class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil"></i> Düzenle
                  </a>
                  <form method="post" action="/?r=admin/company-delete-post"
                        class="d-inline-block js-confirm"
                        onsubmit="return confirm('Bu firmayı silmek istediğinizden emin misiniz?')">
                    <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                    <input type="hidden" name="id" value="<?= e($c['id'] ?? '') ?>">
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
    document.querySelectorAll('form.js-confirm').forEach(f => {
      f.addEventListener('submit', e => {
        if (!confirm('Bu firmayı silmek istediğinizden emin misiniz?')) e.preventDefault();
      });
    });
  })();