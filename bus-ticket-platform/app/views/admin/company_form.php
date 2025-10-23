<style>
  :root{
    --bs-primary:#19a974; --bs-success:#138a63; --bs-link-color:#138a63; --bs-btn-border-radius:.6rem;
  }
  .card{border:0;box-shadow:0 6px 16px rgba(0,0,0,.06);}
</style>

<?php
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$mode = ($mode ?? 'create') === 'edit' ? 'edit' : 'create';
$flashOk  = function_exists('flash_get') ? flash_get('ok')  : null;
$flashErr = function_exists('flash_get') ? flash_get('err') : null;
?>

<div class="container my-4">
  <div class="card">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">
          <?= $mode === 'create' ? '🆕 Yeni Firma' : '🏢 Firma Düzenle' ?>
        </h2>
        <a href="/?r=admin/companies" class="btn btn-outline-secondary btn-sm">Listeye Dön</a>
      </div>

      <?php if (!empty($flashOk)): ?>
        <div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle me-2"></i><div><?= e($flashOk) ?></div></div>
      <?php endif; ?>
      <?php if (!empty($flashErr)): ?>
        <div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle me-2"></i><div><?= e($flashErr) ?></div></div>
      <?php endif; ?>

      <form class="needs-validation" novalidate method="post"
            action="/?r=<?= $mode === 'create' ? 'admin/company-new-post' : 'admin/company-edit-post' ?>"
            autocomplete="on">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>

        <?php if ($mode === 'edit'): ?>
          <input type="hidden" name="id" value="<?= e($company['id'] ?? '') ?>">
        <?php endif; ?>

        <div class="mb-3">
          <label for="name" class="form-label fw-semibold">Firma Adı</label>
          <input
            type="text"
            id="name"
            name="name"
            class="form-control"
            placeholder="Örn: Ankara Turizm"
            required
            minlength="2"
            maxlength="120"
            pattern="^[A-Za-zÇĞİÖŞÜçğıöşü0-9\s'.&()-]{2,}$"
            value="<?= e($company['name'] ?? ($_POST['name'] ?? '')) ?>"
            autocomplete="organization">
          <div class="form-text">Türkçe karakter, rakam, boşluk ve ( . ' & ( ) - ) işaretlerine izin verilir.</div>
          <div class="invalid-feedback">Lütfen geçerli bir firma adı girin (en az 2 karakter).</div>
        </div>

        <div class="d-flex gap-2 mt-4">
          <button type="submit" class="btn btn-primary">Kaydet</button>
          <a href="/?r=admin/companies" class="btn btn-outline-secondary">İptal</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  (() => {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
      form.addEventListener('submit', e => {
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        form.classList.add('was-validated');
      });
    });
  })();