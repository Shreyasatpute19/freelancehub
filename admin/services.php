<?php
require_once __DIR__ . '/../config.php';
$me = require_login('admin');
$pageTitle = 'Service Verification';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sid = (int)($_POST['id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if ($act === 'approve') $pdo->prepare('UPDATE services SET status="approved" WHERE id=?')->execute([$sid]);
    elseif ($act === 'reject') $pdo->prepare('UPDATE services SET status="rejected" WHERE id=?')->execute([$sid]);
    elseif ($act === 'delete') {
        $busy = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE service_id=? AND status IN ("pending","accepted","in_progress")');
        $busy->execute([$sid]);
        if ($busy->fetchColumn() > 0) { flash('danger', 'Service has active bookings; reject it instead.'); redirect('admin/services.php'); }
        $pdo->prepare('DELETE FROM services WHERE id=?')->execute([$sid]);
    }
    flash('success', 'Service updated.');
    redirect('admin/services.php?status=' . urlencode($_GET['status'] ?? 'pending'));
}

$filter = $_GET['status'] ?? 'pending';
if (!in_array($filter, ['pending', 'approved', 'rejected', 'all'], true)) $filter = 'pending';
$sql = 'SELECT s.*, u.name AS fname, u.is_verified, c.name AS cat FROM services s JOIN users u ON u.id=s.freelancer_id JOIN categories c ON c.id=s.category_id';
$p = [];
if ($filter !== 'all') { $sql .= ' WHERE s.status=?'; $p[] = $filter; }
$st = $pdo->prepare($sql . ' ORDER BY s.created_at DESC');
$st->execute($p);
$rows = $st->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/nav.php';
?>
<div class="btn-group mb-3">
  <?php foreach (['pending', 'approved', 'rejected', 'all'] as $f): ?>
    <a class="btn btn-outline-primary <?= $filter === $f ? 'active' : '' ?>" href="?status=<?= $f ?>"><?= ucfirst($f) ?></a>
  <?php endforeach; ?>
</div>
<div class="table-responsive card"><table class="table align-middle mb-0">
  <thead><tr><th>Service</th><th>Freelancer</th><th>Category</th><th>Price</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">Nothing here.</td></tr><?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="<?= url('service.php?id=' . $r['id']) ?>"><?= e($r['title']) ?></a></td>
      <td><?= e($r['fname']) ?> <?= $r['is_verified'] ? '✔' : '<span class="small text-muted">(unverified)</span>' ?></td>
      <td><?= e($r['cat']) ?></td><td><?= money($r['price']) ?></td><td><?= badge($r['status']) ?></td>
      <td class="text-end text-nowrap"><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
        <?php if ($r['status'] !== 'approved'): ?><button name="action" value="approve" class="btn btn-sm btn-success">Approve</button><?php endif; ?>
        <?php if ($r['status'] !== 'rejected'): ?><button name="action" value="reject" class="btn btn-sm btn-outline-warning">Reject</button><?php endif; ?>
        <button name="action" value="delete" class="btn btn-sm btn-outline-danger" data-confirm="Delete this service?">Delete</button>
      </form></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
