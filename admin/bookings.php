<?php
require_once __DIR__ . '/../config.php';
$me = require_login('admin');
$pageTitle = 'All Bookings';

$filter = $_GET['status'] ?? '';
$valid = ['pending', 'accepted', 'in_progress', 'completed', 'cancelled', 'rejected'];
$sql = 'SELECT b.*, s.title, c.name AS client_name, f.name AS freelancer_name
        FROM bookings b JOIN services s ON s.id=b.service_id JOIN users c ON c.id=b.client_id JOIN users f ON f.id=b.freelancer_id';
$p = [];
if (in_array($filter, $valid, true)) { $sql .= ' WHERE b.status=?'; $p[] = $filter; }
$st = $pdo->prepare($sql . ' ORDER BY b.created_at DESC LIMIT 200');
$st->execute($p);
$rows = $st->fetchAll();
$total = array_sum(array_map(fn($r) => $r['payment_status'] === 'paid' ? $r['amount'] : 0, $rows));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/nav.php';
?>
<form method="get" class="d-flex gap-2 mb-3">
  <select name="status" class="form-select w-auto" onchange="this.form.submit()">
    <option value="">All statuses</option>
    <?php foreach ($valid as $v): ?><option value="<?= $v ?>" <?= $filter === $v ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $v))) ?></option><?php endforeach; ?>
  </select>
  <span class="align-self-center text-muted small"><?= count($rows) ?> booking(s) &middot; paid total <?= money($total) ?></span>
</form>
<div class="table-responsive card"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>#</th><th>Service</th><th>Client</th><th>Freelancer</th><th>Date</th><th>Amount</th><th>Status</th><th>Payment</th><th></th></tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">No bookings.</td></tr><?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= $r['id'] ?></td><td><?= e($r['title']) ?></td><td><?= e($r['client_name']) ?></td><td><?= e($r['freelancer_name']) ?></td>
      <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td><td><?= money($r['amount']) ?></td><td><?= badge($r['status']) ?></td><td><?= badge($r['payment_status']) ?></td>
      <td><a class="btn btn-sm btn-outline-primary" href="<?= url('booking.php?id=' . $r['id']) ?>">View</a></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
