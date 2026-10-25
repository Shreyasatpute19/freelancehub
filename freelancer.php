<?php
require_once __DIR__ . '/config.php';
$id = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare('SELECT u.*, fp.title, fp.bio, fp.skills, fp.hourly_rate FROM users u JOIN freelancer_profiles fp ON fp.user_id = u.id WHERE u.id = ? AND u.role = "freelancer" AND u.status = "active"');
$st->execute([$id]);
$f = $st->fetch();
if (!$f) { flash('danger', 'Freelancer not found.'); redirect('services.php'); }

$svc = $pdo->prepare(
    'SELECT s.*, c.name AS cat, ' . SQL_AVG_RATING . ' AS avg_rating, ' . SQL_REVIEW_COUNT . ' AS rev_count
     FROM services s JOIN categories c ON c.id = s.category_id
     WHERE s.freelancer_id = ? AND s.status = "approved" ORDER BY s.created_at DESC'
);
$svc->execute([$id]);
$services = $svc->fetchAll();

$pf = $pdo->prepare('SELECT * FROM portfolio WHERE freelancer_id = ? ORDER BY created_at DESC');
$pf->execute([$id]);
$items = $pf->fetchAll();

$ov = $pdo->prepare('SELECT AVG(rating) a, COUNT(*) n FROM reviews WHERE freelancer_id = ?');
$ov->execute([$id]);
$overall = $ov->fetch();

$rv = $pdo->prepare('SELECT r.*, u.name FROM reviews r JOIN users u ON u.id = r.client_id WHERE r.freelancer_id = ? ORDER BY r.created_at DESC LIMIT 10');
$rv->execute([$id]);
$reviews = $rv->fetchAll();

$done = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE freelancer_id = ? AND status = "completed"');
$done->execute([$id]);
$completed = (int)$done->fetchColumn();

$pageTitle = $f['name'];
include __DIR__ . '/includes/header.php';
?>
<div class="card mb-4"><div class="card-body d-flex flex-wrap align-items-center">
  <img class="avatar avatar-lg me-3" src="<?= e(avatar_url($f['avatar'])) ?>" alt="">
  <div class="flex-grow-1">
    <h3 class="mb-0"><?= e($f['name']) ?> <?= $f['is_verified'] ? '<span class="badge bg-success">✔ Verified</span>' : '' ?></h3>
    <div class="text-muted"><?= e($f['title'] ?: 'Freelancer') ?> &middot; <?= e($f['city'] ?: 'Remote') ?></div>
    <div><?= stars($overall['n'] ? $overall['a'] : null, $overall['n']) ?> &middot; <span class="small text-muted"><?= $completed ?> project(s) completed</span></div>
  </div>
  <?php if ($f['hourly_rate']): ?><div class="text-end"><div class="small text-muted">Hourly rate</div><strong><?= money($f['hourly_rate']) ?></strong></div><?php endif; ?>
</div></div>

<div class="row g-4">
  <div class="col-lg-8">
    <h5>About</h5>
    <p><?= $f['bio'] ? nl2br(e($f['bio'])) : '<span class="text-muted">No bio added yet.</span>' ?></p>
    <?php if ($f['skills']): foreach (explode(',', $f['skills']) as $sk): ?>
      <span class="badge bg-secondary me-1"><?= e(trim($sk)) ?></span>
    <?php endforeach; endif; ?>

    <h5 class="mt-4">Portfolio</h5>
    <div class="row g-3">
      <?php if (!$items): ?><p class="text-muted">No portfolio items yet.</p><?php endif; ?>
      <?php foreach ($items as $p): ?>
        <div class="col-6 col-md-4"><div class="card h-100">
          <img class="portfolio-img card-img-top" src="<?= url('uploads/' . e($p['image'])) ?>" alt="">
          <div class="card-body p-2"><div class="small fw-semibold"><?= e($p['title']) ?></div><div class="small text-muted"><?= e($p['description']) ?></div></div>
        </div></div>
      <?php endforeach; ?>
    </div>

    <h5 class="mt-4">Reviews</h5>
    <?php if (!$reviews): ?><p class="text-muted">No reviews yet.</p><?php endif; ?>
    <?php foreach ($reviews as $r): ?>
      <div class="card card-body mb-2"><div><strong><?= e($r['name']) ?></strong> <?= stars($r['rating']) ?></div><div><?= e($r['comment']) ?></div></div>
    <?php endforeach; ?>
  </div>
  <div class="col-lg-4">
    <h5>Services</h5>
    <?php if (!$services): ?><p class="text-muted">No active services.</p><?php endif; ?>
    <?php foreach ($services as $s): ?>
      <a class="card card-body mb-2 text-decoration-none text-dark card-hover" href="<?= url('service.php?id=' . $s['id']) ?>">
        <div class="fw-semibold"><?= e($s['title']) ?></div>
        <div class="small text-muted"><?= e($s['cat']) ?></div>
        <div class="d-flex justify-content-between"><span><?= stars($s['avg_rating'], $s['rev_count']) ?></span><strong><?= money($s['price']) ?></strong></div>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
