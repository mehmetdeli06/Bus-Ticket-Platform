<style>
  :root{
    --bs-primary:#19a974;
    --bs-success:#138a63;
    --bs-link-color:#138a63;
    --bs-btn-border-radius:.6rem;
  }
  .card{border:0;box-shadow:0 6px 16px rgba(0,0,0,.06);}
  .table>thead th{font-weight:600;}
</style>

<?php if (!function_exists('e')){ function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } } ?>

<div class="container my-4">
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0"><i class="bi bi-person-badge me-2"></i>Firma Adminleri</h2>
        <a href="/?r=admin/admin-new" class="btn btn-primary">
          <i class="bi bi-plus-lg"></i> Yeni Firma Admin
        </a>
      </div>

      <?php if (empty($admins)): ?>
        <div class="alert alert-info mb-0">
          <i class="bi bi-info-circle me-1"></i> Henüz kayıtlı firma admini bulunmuyor. Yeni bir admin ekleyin.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="adminsTable">
            <thead class="table-light">
              <tr>
                <th scope="col">Ad Soyad</th>
                <th scope="col">E-posta</th>
                <th scope="col">Firma</th>
                <th scope="col" class="text-nowrap">Oluşturulma</th>
                <th scope="col" class="text-end text-nowrap">İşlem</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($admins as $a): ?>
              <tr>
                <td><?= e($a['full_name'] ?? '-') ?></td>
                <td><a href="mailto:<?= e($a['email'] ?? '-') ?>"><?= e($a['email'] ?? '-') ?></a></td>
                <td><?= e($a['company_name'] ?? '-') ?></td>
                <td class="text-nowrap">
                  <?= !empty($a['created_at']) ? e(date('d.m.Y H:i', strtotime($a['created_at']))) : '—' ?>
                </td>
                <td class="text-end" style="min-width:260px;">
                  <a href="/?r=admin/admin-edit&id=<?= rawurlencode((string)($a['id'] ?? '')) ?>"
                     class="btn btn-sm btn-outline-primary me-1">
                    <i class="bi bi-pencil"></i> Düzenle
                  </a>
                  <form method="post" action="/?r=admin/admin-delete-post"
                        class="d-inline-block js-confirm"
                        onsubmit="return confirm('Bu admini silmek istediğinizden emin misiniz?')">
                    <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                    <input type="hidden" name="id" value="<?= e($a['id'] ?? '') ?>">
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
        if (!confirm('Bu admini silmek istediğinizden emin misiniz?')) e.preventDefault();
      });
    });
  })();