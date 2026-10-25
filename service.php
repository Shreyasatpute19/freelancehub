<?php
require_once __DIR__ . '/config.php';
$id = (int)($_GET['id'] ?? 0);
$me = current_user();

$st = $pdo->prepare(
    'SELECT s.*, u.name AS fname, u.city, u.is_verified, u.avatar, c.name AS cat, fp.title AS ftitle,
            ' . SQL_AVG_RATING . ' AS avg_rating, ' . SQL_REVIEW_COUNT . ' AS rev_count
     FROM services s
     JOIN users u ON u.id = s.freelancer_id
     LEFT JOIN freelancer_profiles fp ON fp.user_id = u.id
     JOIN categories c ON c.id = s.category_id
     WHERE s.id = ?'
);
$st->execute([$id]);
$s = $st->fetch();
$isOwner = $me && $s && (int)$me['id'] === (int)$s['freelancer_id'];
$isAdmin = $me && $me['role'] === 'admin';

if (!$s || ($s['status'] !== 'approved' && !$isOwner && !$isAdmin)) {
    http_response_code(404);
    flash('danger', 'Service not found.');
    redirect('services.php');
}

$rv = $pdo->prepare('SELECT r.*, u.name FROM reviews r JOIN users u ON u.id = r.client_id WHERE r.service_id = ? ORDER BY r.created_at DESC');
$rv->execute([$id]);
$reviews = $rv->fetchAll();

$pageTitle = $s['title'];
include __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
  <div class="col-lg-8">
    <?php if ($s['status'] !== 'approved'): ?><div class="alert alert-warning">This service is <?= e($s['status']) ?> and not visible to clients.</div><?php endif; ?>
    <span class="badge bg-light text-dark border"><?= e($s['cat']) ?></span>
    <h2 class="mt-2"><?= e($s['title']) ?></h2>
    <div class="mb-3"><?= stars($s['avg_rating'], $s['rev_count']) ?></div>
    <div class="card card-body mb-4"><?= nl2br(e($s['description'])) ?></div>

    <h5>Reviews</h5>
    <?php if (!$reviews): ?><p class="text-muted">No reviews yet.</p><?php endif; ?>
    <?php foreach ($reviews as $r): ?>
      <div class="card card-body mb-2">
        <div><strong><?= e($r['name']) ?></strong> <?= stars($r['rating']) ?> <span class="small text-muted"><?= e(date('d M Y', strtotime($r['created_at']))) ?></span></div>
        <div><?= e($r['comment']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm mb-3"><div class="card-body">
      <h3><?= money($s['price']) ?></h3>
      <p class="text-muted mb-3">Delivery in <?= (int)$s['delivery_days'] ?> day(s)</p>
      <?php if (!$me): ?>
        <a class="btn btn-primary w-100" href="<?= url('login.php') ?>">Log in to book</a>
      <?php elseif ($me['role'] === 'client' && $s['status'] === 'approved'): ?>
        <form method="post" action="<?= url('book.php') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
          <div class="mb-2"><label class="form-label small">Describe what you need</label><textarea name="details" rows="4" class="form-control" required></textarea></div>
          <div class="mb-3"><label class="form-label small">Needed by (optional)</label><input type="date" name="needed_by" min="<?= date('Y-m-d') ?>" class="form-control"></div>
          <button class="btn btn-primary w-100">Book this service</button>
        </form>
      <?php elseif ($isOwner): ?>
        <a class="btn btn-outline-primary w-100" href="<?= url('service_form.php?id=' . $s['id']) ?>">Edit service</a>
      <?php else: ?>
        <p class="small text-muted mb-0">Only client accounts can book services.</p>
      <?php endif; ?>
    </div></div>

    <div class="card"><div class="card-body d-flex align-items-center">
      <img class="avatar avatar-lg me-3" src="<?= e(avatar_url($s['avatar'])) ?>" alt="">
      <div>
        <a class="fw-bold text-decoration-none" href="<?= url('freelancer.php?id=' . $s['freelancer_id']) ?>"><?= e($s['fname']) ?></a>
        <?= $s['is_verified'] ? '<span class="verified" title="Verified">✔</span>' : '' ?>
        <div class="small text-muted"><?= e($s['ftitle'] ?: 'Freelancer') ?></div>
        <div class="small text-muted"><?= e($s['city'] ?: 'Remote') ?></div>
      </div>
    </div></div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
