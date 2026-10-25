<?php
require_once __DIR__ . '/config.php';
$me = require_login();
if ($me['role'] === 'admin') redirect('admin/bookings.php');
$pageTitle = 'My Bookings';

$col = $me['role'] === 'client' ? 'b.client_id' : 'b.freelancer_id';
$other = $me['role'] === 'client' ? 'f.name' : 'c.name';
$filter = $_GET['status'] ?? '';
$valid = ['pending', 'accepted', 'in_progress', 'completed', 'cancelled', 'rejected'];

$sql = "SELECT b.*, s.title, $other AS other_name
        FROM bookings b JOIN services s ON s.id = b.service_id
        JOIN users c ON c.id = b.client_id JOIN users f ON f.id = b.freelancer_id
        WHERE $col = ?";
$params = [$me['id']];
if (in_array($filter, $valid, true)) { $sql .= ' AND b.status = ?'; $params[] = $filter; }
$sql .= ' ORDER BY b.created_at DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<?php
ob_start(); ?>
<form method="get"><select name="status" class="form-select" onchange="this.form.submit()" aria-label="Filter by status">
  <option value="">All statuses</option>
  <?php foreach ($valid as $v): ?><option value="<?= $v ?>" <?= $filter === $v ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $v))) ?></option><?php endforeach; ?>
</select></form>
<?= page_header('Booking history', count($rows) . ' booking' . (count($rows) === 1 ? '' : 's'), ob_get_clean()) ?>
<div class="table-responsive card"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>#</th><th>Service</th><th><?= $me['role'] === 'client' ? 'Freelancer' : 'Client' ?></th><th>Date</th><th>Amount</th><th>Status</th><th>Payment</th><th></th></tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">No bookings yet.</td></tr><?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td><td><?= e($r['title']) ?></td><td><?= e($r['other_name']) ?></td>
      <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td><td><?= money($r['amount']) ?></td>
      <td><?= badge($r['status']) ?></td><td><?= badge($r['payment_status']) ?></td>
      <td><a class="btn btn-sm btn-outline-primary" href="<?= url('booking.php?id=' . $r['id']) ?>">Open</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
