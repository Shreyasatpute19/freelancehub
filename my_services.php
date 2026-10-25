<?php
require_once __DIR__ . '/config.php';
$me = require_login('freelancer');
$pageTitle = 'My Services';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $sid = (int)($_POST['id'] ?? 0);
    $busy = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE service_id=? AND status IN ("pending","accepted","in_progress")');
    $busy->execute([$sid]);
    if ($busy->fetchColumn() > 0) flash('danger', 'This service has active bookings and cannot be deleted.');
    else {
        $pdo->prepare('DELETE FROM services WHERE id=? AND freelancer_id=?')->execute([$sid, $me['id']]);
        flash('success', 'Service deleted.');
    }
    redirect('my_services.php');
}

$st = $pdo->prepare('SELECT s.*, c.name AS cat FROM services s JOIN categories c ON c.id=s.category_id WHERE s.freelancer_id=? ORDER BY s.created_at DESC');
$st->execute([$me['id']]);
$rows = $st->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<?= page_header('My services', 'New and edited services are reviewed by an admin before they appear in search.', '<a class="btn btn-primary" href="' . e(url('service_form.php')) . '">Add service</a>') ?>
<div class="table-responsive card"><table class="table align-middle mb-0">
  <thead><tr><th>Title</th><th>Category</th><th>Price</th><th>Days</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">You have not added any services yet.</td></tr><?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="<?= url('service.php?id=' . $r['id']) ?>"><?= e($r['title']) ?></a></td>
      <td><?= e($r['cat']) ?></td><td><?= money($r['price']) ?></td><td><?= (int)$r['delivery_days'] ?></td><td><?= badge($r['status']) ?></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-outline-primary" href="<?= url('service_form.php?id=' . $r['id']) ?>">Edit</a>
        <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>">
          <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this service?">Delete</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
