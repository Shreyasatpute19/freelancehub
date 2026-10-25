<?php
require_once __DIR__ . '/config.php';
$me = require_login();
if ($me['role'] === 'admin') redirect('admin/index.php');
$pageTitle = 'Dashboard';
$uid = (int)$me['id'];

function scalar(string $sql, array $p): float {
    global $pdo;
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return (float)$st->fetchColumn();
}

if ($me['role'] === 'freelancer') {
    $stats = [
        'Active services' => (int)scalar('SELECT COUNT(*) FROM services WHERE freelancer_id=? AND status="approved"', [$uid]),
        'Pending requests' => (int)scalar('SELECT COUNT(*) FROM bookings WHERE freelancer_id=? AND status="pending"', [$uid]),
        'In progress' => (int)scalar('SELECT COUNT(*) FROM bookings WHERE freelancer_id=? AND status IN ("accepted","in_progress")', [$uid]),
        'Completed' => (int)scalar('SELECT COUNT(*) FROM bookings WHERE freelancer_id=? AND status="completed"', [$uid]),
        'Earnings' => money(scalar('SELECT COALESCE(SUM(amount),0) FROM bookings WHERE freelancer_id=? AND payment_status="paid" AND status<>"cancelled"', [$uid])),
        'Avg rating' => ($r = scalar('SELECT AVG(rating) FROM reviews WHERE freelancer_id=?', [$uid])) ? number_format($r, 1) . ' ★' : '—',
    ];
    $prof = $pdo->prepare('SELECT title, bio FROM freelancer_profiles WHERE user_id=?');
    $prof->execute([$uid]);
    $p = $prof->fetch();
    $incomplete = !$p || !$p['title'] || !$p['bio'];
    $pending = $pdo->prepare('SELECT b.id, s.title, c.name FROM bookings b JOIN services s ON s.id=b.service_id JOIN users c ON c.id=b.client_id WHERE b.freelancer_id=? AND b.status="pending" ORDER BY b.id DESC LIMIT 5');
    $pending->execute([$uid]);
    $pendingRows = $pending->fetchAll();
} else {
    $stats = [
        'Total bookings' => (int)scalar('SELECT COUNT(*) FROM bookings WHERE client_id=?', [$uid]),
        'Awaiting payment' => (int)scalar('SELECT COUNT(*) FROM bookings WHERE client_id=? AND status="accepted" AND payment_status="unpaid"', [$uid]),
        'In progress' => (int)scalar('SELECT COUNT(*) FROM bookings WHERE client_id=? AND status IN ("accepted","in_progress")', [$uid]),
        'Completed' => (int)scalar('SELECT COUNT(*) FROM bookings WHERE client_id=? AND status="completed"', [$uid]),
        'Total spent' => money(scalar('SELECT COALESCE(SUM(amount),0) FROM bookings WHERE client_id=? AND payment_status="paid"', [$uid])),
    ];
    $incomplete = false;
    $pendingRows = [];
}

include __DIR__ . '/includes/header.php';
?>
<?= page_header('Hello, ' . $me['name'], $me['role'] === 'freelancer' ? 'Your work at a glance.' : 'Your bookings at a glance.') ?>
<?php if ($me['role'] === 'freelancer' && !$me['is_verified']): ?>
  <div class="alert alert-info">Your account is awaiting admin verification. You can add services, and they will appear once approved.</div>
<?php endif; ?>
<?php if ($incomplete): ?>
  <div class="alert alert-warning">Your profile is incomplete. <a href="<?= url('profile.php') ?>">Add a title and bio</a> so clients can trust you.</div>
<?php endif; ?>
<div class="row g-3 mb-4">
  <?php foreach ($stats as $label => $val): ?>
    <div class="col-6 col-md-4 col-lg-2"><div class="card stat-card h-100"><div class="card-body">
      <div class="small text-muted"><?= e($label) ?></div><div class="h4 mb-0"><?= is_string($val) ? e($val) : $val ?></div>
    </div></div></div>
  <?php endforeach; ?>
</div>

<?php if ($me['role'] === 'freelancer'): ?>
  <div class="d-flex gap-2 mb-4 flex-wrap">
    <a class="btn btn-primary" href="<?= url('service_form.php') ?>">+ Add service</a>
    <a class="btn btn-outline-primary" href="<?= url('portfolio.php') ?>">Manage portfolio</a>
    <a class="btn btn-outline-primary" href="<?= url('freelancer.php?id=' . $uid) ?>">View public profile</a>
  </div>
  <h5 class="mb-3">Pending requests</h5>
  <?php if (!$pendingRows): ?><?= empty_state('No pending requests right now.') ?><?php endif; ?>
  <?php foreach ($pendingRows as $r): ?>
    <a class="card card-body mb-2 text-decoration-none text-dark card-hover" href="<?= url('booking.php?id=' . $r['id']) ?>">
      <strong><?= e($r['title']) ?></strong><span class="small text-muted">Requested by <?= e($r['name']) ?></span>
    </a>
  <?php endforeach; ?>
<?php else: ?>
  <a class="btn btn-primary" href="<?= url('services.php') ?>">Find a service</a>
  <a class="btn btn-outline-primary" href="<?= url('my_bookings.php') ?>">View bookings</a>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
