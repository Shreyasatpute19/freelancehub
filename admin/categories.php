<?php
require_once __DIR__ . '/../config.php';
$me = require_login('admin');
$pageTitle = 'Categories';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') flash('danger', 'Enter a category name.');
        else {
            try { $pdo->prepare('INSERT INTO categories (name) VALUES (?)')->execute([$name]); flash('success', 'Category added.'); }
            catch (PDOException $ex) { flash('danger', 'That category already exists.'); }
        }
    } elseif ($act === 'delete') {
        $used = $pdo->prepare('SELECT COUNT(*) FROM services WHERE category_id=?');
        $used->execute([(int)($_POST['id'] ?? 0)]);
        if ($used->fetchColumn() > 0) flash('danger', 'Category is in use by services and cannot be deleted.');
        else { $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]); flash('success', 'Category deleted.'); }
    }
    redirect('admin/categories.php');
}
$rows = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM services s WHERE s.category_id=c.id) n FROM categories c ORDER BY name')->fetchAll();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/nav.php';
?>
<div class="row g-4">
  <div class="col-md-4"><div class="card card-body"><h6>Add category</h6>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add">
      <input name="name" class="form-control mb-2" placeholder="Category name" required><button class="btn btn-primary">Add</button></form></div></div>
  <div class="col-md-8"><div class="card"><table class="table align-middle mb-0">
    <thead><tr><th>Name</th><th>Services</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= e($r['name']) ?></td><td><?= (int)$r['n'] ?></td>
        <td class="text-end"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>">
          <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this category?">Delete</button></form></td></tr>
    <?php endforeach; ?>
    </tbody></table></div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
