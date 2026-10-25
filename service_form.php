<?php
require_once __DIR__ . '/config.php';
$me = require_login('freelancer');
$id = (int)($_GET['id'] ?? 0);
$svc = ['title' => '', 'description' => '', 'price' => '', 'delivery_days' => 3, 'category_id' => 0];

if ($id) {
    $st = $pdo->prepare('SELECT * FROM services WHERE id=? AND freelancer_id=?');
    $st->execute([$id, $me['id']]);
    $svc = $st->fetch();
    if (!$svc) { flash('danger', 'Service not found.'); redirect('my_services.php'); }
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $svc['title'] = trim($_POST['title'] ?? '');
    $svc['description'] = trim($_POST['description'] ?? '');
    $svc['price'] = $_POST['price'] ?? '';
    $svc['delivery_days'] = (int)($_POST['delivery_days'] ?? 0);
    $svc['category_id'] = (int)($_POST['category_id'] ?? 0);

    if (strlen($svc['title']) < 5) $errors[] = 'Title must be at least 5 characters.';
    if (strlen($svc['description']) < 20) $errors[] = 'Please write a description of at least 20 characters.';
    if (!is_numeric($svc['price']) || $svc['price'] <= 0) $errors[] = 'Enter a valid price.';
    if ($svc['delivery_days'] < 1 || $svc['delivery_days'] > 365) $errors[] = 'Delivery days must be between 1 and 365.';
    $ok = $pdo->prepare('SELECT 1 FROM categories WHERE id=?');
    $ok->execute([$svc['category_id']]);
    if (!$ok->fetch()) $errors[] = 'Choose a category.';

    if (!$errors) {
        if ($id) {
            // edits go back to pending so admin re-verifies the content
            $pdo->prepare('UPDATE services SET category_id=?, title=?, description=?, price=?, delivery_days=?, status="pending" WHERE id=? AND freelancer_id=?')
                ->execute([$svc['category_id'], $svc['title'], $svc['description'], $svc['price'], $svc['delivery_days'], $id, $me['id']]);
        } else {
            $pdo->prepare('INSERT INTO services (freelancer_id, category_id, title, description, price, delivery_days) VALUES (?,?,?,?,?,?)')
                ->execute([$me['id'], $svc['category_id'], $svc['title'], $svc['description'], $svc['price'], $svc['delivery_days']]);
        }
        flash('success', 'Service saved. It will be visible after admin approval.');
        redirect('my_services.php');
    }
}
$pageTitle = $id ? 'Edit Service' : 'Add Service';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-lg-8"><div class="card"><div class="card-body">
  <h5><?= $id ? 'Edit service' : 'Add a new service' ?></h5>
  <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" value="<?= e($svc['title']) ?>" required></div>
    <div class="mb-3"><label class="form-label">Category</label>
      <select name="category_id" class="form-select" required><option value="">Select…</option>
        <?php foreach (categories() as $c): ?><option value="<?= $c['id'] ?>" <?= (int)$svc['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" rows="6" class="form-control" required><?= e($svc['description']) ?></textarea></div>
    <div class="row">
      <div class="col-6 mb-3"><label class="form-label">Price (<?= CURRENCY ?>)</label><input type="number" step="0.01" min="1" name="price" class="form-control" value="<?= e($svc['price']) ?>" required></div>
      <div class="col-6 mb-3"><label class="form-label">Delivery (days)</label><input type="number" min="1" name="delivery_days" class="form-control" value="<?= e($svc['delivery_days']) ?>" required></div>
    </div>
    <button class="btn btn-primary">Save service</button> <a class="btn btn-link" href="<?= url('my_services.php') ?>">Cancel</a>
  </form>
</div></div></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
