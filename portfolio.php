<?php
require_once __DIR__ . '/config.php';
$me = require_login('freelancer');
$pageTitle = 'Portfolio';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        $st = $pdo->prepare('SELECT image FROM portfolio WHERE id=? AND freelancer_id=?');
        $st->execute([(int)($_POST['id'] ?? 0), $me['id']]);
        if ($row = $st->fetch()) {
            delete_upload($row['image']);
            $pdo->prepare('DELETE FROM portfolio WHERE id=? AND freelancer_id=?')->execute([(int)($_POST['id'] ?? 0), $me['id']]);
            flash('success', 'Item removed.');
        }
    } else {
        $title = trim($_POST['title'] ?? '');
        $img = upload_image($_FILES['image'] ?? [], 'portfolio');
        if ($title === '') flash('danger', 'Please enter a title.');
        elseif (!$img) flash('danger', 'Upload a JPG, PNG or WebP image under 2 MB.');
        else {
            $pdo->prepare('INSERT INTO portfolio (freelancer_id, title, description, image) VALUES (?,?,?,?)')
                ->execute([$me['id'], $title, mb_substr(trim($_POST['description'] ?? ''), 0, 255), $img]);
            flash('success', 'Portfolio item added.');
        }
    }
    redirect('portfolio.php');
}

$st = $pdo->prepare('SELECT * FROM portfolio WHERE freelancer_id=? ORDER BY created_at DESC');
$st->execute([$me['id']]);
$items = $st->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
  <div class="col-lg-4"><div class="card"><div class="card-body">
    <h5>Add work sample</h5>
    <form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
      <div class="mb-2"><input name="title" class="form-control" placeholder="Title" required></div>
      <div class="mb-2"><input name="description" class="form-control" placeholder="Short description" maxlength="255"></div>
      <div class="mb-3"><input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="form-control" required></div>
      <button class="btn btn-primary">Upload</button>
    </form>
  </div></div></div>
  <div class="col-lg-8">
    <h5>Your portfolio</h5>
    <div class="row g-3">
      <?php if (!$items): ?><p class="text-muted">Nothing uploaded yet.</p><?php endif; ?>
      <?php foreach ($items as $p): ?>
        <div class="col-6 col-md-4"><div class="card h-100">
          <img class="portfolio-img card-img-top" src="<?= url('uploads/' . e($p['image'])) ?>" alt="">
          <div class="card-body p-2"><div class="small fw-semibold"><?= e($p['title']) ?></div>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button class="btn btn-sm btn-link text-danger p-0" data-confirm="Remove this item?">Remove</button></form>
          </div>
        </div></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
