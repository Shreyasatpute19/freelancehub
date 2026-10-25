<?php
require_once __DIR__ . '/../config.php';
$me = require_login('admin');
$pageTitle = 'Admin Dashboard';

$one = fn(string $sql) => $pdo->query($sql)->fetchColumn();
$stats = [
    'Clients' => $one('SELECT COUNT(*) FROM users WHERE role="client"'),
    'Freelancers' => $one('SELECT COUNT(*) FROM users WHERE role="freelancer"'),
    'Unverified freelancers' => $one('SELECT COUNT(*) FROM users WHERE role="freelancer" AND is_verified=0'),
    'Services pending' => $one('SELECT COUNT(*) FROM services WHERE status="pending"'),
    'Total bookings' => $one('SELECT COUNT(*) FROM bookings'),
    'Completed' => $one('SELECT COUNT(*) FROM bookings WHERE status="completed"'),
    'Revenue (paid)' => money($one('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status="success"')),
    'Reviews' => $one('SELECT COUNT(*) FROM reviews'),
];

$byCat = $pdo->query('SELECT c.name, COUNT(s.id) AS n FROM categories c LEFT JOIN services s ON s.category_id=c.id AND s.status="approved" GROUP BY c.id ORDER BY n DESC')->fetchAll();
$byStatus = $pdo->query('SELECT status, COUNT(*) n FROM bookings GROUP BY status')->fetchAll();
$monthly = $pdo->query('SELECT DATE_FORMAT(created_at,"%b %Y") m, COUNT(*) n, SUM(amount) amt FROM bookings GROUP BY DATE_FORMAT(created_at,"%Y-%m"), m ORDER BY DATE_FORMAT(created_at,"%Y-%m") DESC LIMIT 6')->fetchAll();
$top = $pdo->query('SELECT u.id, u.name, COUNT(b.id) jobs, COALESCE(SUM(b.amount),0) earned, (SELECT AVG(rating) FROM reviews WHERE freelancer_id=u.id) rating
                    FROM users u JOIN bookings b ON b.freelancer_id=u.id AND b.status="completed" GROUP BY u.id ORDER BY jobs DESC LIMIT 5')->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/nav.php';
?>
<div class="row g-3 mb-4">
  <?php foreach ($stats as $label => $val): ?>
    <div class="col-6 col-md-3"><div class="card stat-card h-100"><div class="card-body"><div class="small text-muted"><?= e($label) ?></div><div class="h4 mb-0"><?= e($val) ?></div></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-4">
  <div class="col-lg-4"><div class="card h-100"><div class="card-body"><h6>Approved services by category</h6>
    <?php foreach ($byCat as $r): ?><div class="d-flex justify-content-between small border-bottom py-1"><span><?= e($r['name']) ?></span><strong><?= (int)$r['n'] ?></strong></div><?php endforeach; ?>
  </div></div></div>
  <div class="col-lg-4"><div class="card h-100"><div class="card-body"><h6>Bookings by status</h6>
    <?php if (!$byStatus): ?><p class="text-muted small">No bookings yet.</p><?php endif; ?>
    <?php foreach ($byStatus as $r): ?><div class="d-flex justify-content-between small border-bottom py-1"><?= badge($r['status']) ?><strong><?= (int)$r['n'] ?></strong></div><?php endforeach; ?>
    <h6 class="mt-3">Last 6 months</h6>
    <?php foreach ($monthly as $r): ?><div class="d-flex justify-content-between small border-bottom py-1"><span><?= e($r['m']) ?></span><span><?= (int)$r['n'] ?> bookings &middot; <?= money($r['amt']) ?></span></div><?php endforeach; ?>
  </div></div></div>
  <div class="col-lg-4"><div class="card h-100"><div class="card-body"><h6>Top freelancers</h6>
    <?php if (!$top): ?><p class="text-muted small">No completed projects yet.</p><?php endif; ?>
    <?php foreach ($top as $r): ?><div class="d-flex justify-content-between small border-bottom py-1"><a href="<?= url('freelancer.php?id=' . $r['id']) ?>"><?= e($r['name']) ?></a><span><?= (int)$r['jobs'] ?> jobs &middot; <?= money($r['earned']) ?> &middot; <?= $r['rating'] ? number_format($r['rating'], 1) . '★' : '—' ?></span></div><?php endforeach; ?>
  </div></div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
