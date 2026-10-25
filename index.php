<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Home';

$top = $pdo->query(
    'SELECT s.*, u.name AS fname, u.city, u.is_verified, u.avatar, c.name AS cat,
            ' . SQL_AVG_RATING . ' AS avg_rating, ' . SQL_REVIEW_COUNT . ' AS rev_count
     FROM services s
     JOIN users u ON u.id = s.freelancer_id AND u.status = "active"
     JOIN categories c ON c.id = s.category_id
     WHERE s.status = "approved"
     ORDER BY avg_rating DESC, s.created_at DESC LIMIT 6'
)->fetchAll();
$cats = categories();

include __DIR__ . '/includes/header.php';
?>
<section class="hero mb-5">
  <div class="hero-grid">
    <div>
      <h1 class="display-title">Find a freelancer you can trust with the job.</h1>
      <p class="lead-soft mt-3">Compare profiles, portfolios and reviews. Book, pay and chat in one place.</p>
      <form class="search-pill" action="<?= url('services.php') ?>" method="get">
        <input class="form-control" name="q" placeholder="Logo design, PHP developer, tutor…" aria-label="Search services">
        <select name="category" class="form-select" aria-label="Category">
          <option value="">All categories</option>
          <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-primary">Search</button>
      </form>
    </div>
    <div class="hero-stack" aria-hidden="true">
      <div class="stack-inner">
        <?php if ($top): foreach (array_slice($top, 0, 3) as $s): ?>
          <a class="float-card" tabindex="-1" href="<?= url('service.php?id=' . $s['id']) ?>">
            <div class="d-flex justify-content-between"><strong><?= e(mb_strimwidth($s['title'], 0, 28, '…')) ?></strong><span><?= money($s['price']) ?></span></div>
            <div class="meta"><?= e($s['fname']) ?> &middot; <?= e($s['cat']) ?></div>
          </a>
        <?php endforeach; else: foreach (array_slice($cats, 0, 3) as $c): ?>
          <a class="float-card" tabindex="-1" href="<?= url('services.php?category=' . $c['id']) ?>">
            <strong><?= e($c['name']) ?></strong><div class="meta">Browse freelancers</div>
          </a>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="mb-5">
  <?= page_header('Browse by category') ?>
  <div class="d-flex flex-wrap gap-2">
    <?php foreach ($cats as $c): ?>
      <a class="btn btn-outline-primary" href="<?= url('services.php?category=' . $c['id']) ?>"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div>
</section>

<section class="mb-5">
  <?= page_header('Top-rated services', '', '<a class="btn btn-outline-primary btn-sm" href="' . e(url('services.php')) . '">See all</a>') ?>
  <?php if (!$top): ?>
    <?= empty_state('No services have been approved yet. Freelancers can add the first one.', 'Join as a freelancer', url('register.php')) ?>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($top as $s): ?><div class="col-md-6 col-lg-4"><?= service_card($s) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section>
  <?= page_header('How hiring works') ?>
  <div class="flow">
    <div class="card flow-step"><h3>Search</h3><p>Filter by category, city, price and rating.</p></div>
    <div class="card flow-step"><h3>Book</h3><p>Describe the job and send a request to the freelancer.</p></div>
    <div class="card flow-step"><h3>Pay and chat</h3><p>Pay once they accept, and talk through the details in the booking chat.</p></div>
    <div class="card flow-step"><h3>Review</h3><p>When the work is done, rate it so others can hire with confidence.</p></div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
