<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Find Services';

$q = trim($_GET['q'] ?? '');
$cat = (int)($_GET['category'] ?? 0);
$city = trim($_GET['city'] ?? '');
$min = $_GET['min'] ?? '';
$max = $_GET['max'] ?? '';
$rating = (int)($_GET['rating'] ?? 0);
$sort = $_GET['sort'] ?? 'new';

$where = ['s.status = "approved"', 'u.status = "active"'];
$params = [];
if ($q !== '') {
    $where[] = '(s.title LIKE ? OR s.description LIKE ? OR u.name LIKE ? OR fp.skills LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}
if ($cat) { $where[] = 's.category_id = ?'; $params[] = $cat; }
if ($city !== '') { $where[] = 'u.city LIKE ?'; $params[] = "%$city%"; }
if (is_numeric($min)) { $where[] = 's.price >= ?'; $params[] = $min; }
if (is_numeric($max)) { $where[] = 's.price <= ?'; $params[] = $max; }

if ($rating) $where[] = SQL_AVG_RATING . ' >= ' . (int)$rating;
$order = ['new' => 's.created_at DESC', 'price_asc' => 's.price ASC', 'price_desc' => 's.price DESC', 'rating' => 'avg_rating DESC'][$sort] ?? 's.created_at DESC';

$sql = 'SELECT s.*, u.name AS fname, u.city, u.is_verified, u.avatar, c.name AS cat,
               ' . SQL_AVG_RATING . ' AS avg_rating, ' . SQL_REVIEW_COUNT . ' AS rev_count
        FROM services s
        JOIN users u ON u.id = s.freelancer_id
        LEFT JOIN freelancer_profiles fp ON fp.user_id = u.id
        JOIN categories c ON c.id = s.category_id
        WHERE ' . implode(' AND ', $where) . " ORDER BY $order";
$st = $pdo->prepare($sql);
$st->execute($params);
$services = $st->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
  <aside class="col-lg-3">
    <form class="card card-body" method="get">
      <h6>Filters</h6>
      <input class="form-control mb-2" name="q" placeholder="Keyword" value="<?= e($q) ?>">
      <select name="category" class="form-select mb-2">
        <option value="">All categories</option>
        <?php foreach (categories() as $c): ?><option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
      <input class="form-control mb-2" name="city" placeholder="City / location" value="<?= e($city) ?>">
      <div class="input-group mb-2">
        <input class="form-control" type="number" min="0" name="min" placeholder="Min <?= CURRENCY ?>" value="<?= e($min) ?>">
        <input class="form-control" type="number" min="0" name="max" placeholder="Max <?= CURRENCY ?>" value="<?= e($max) ?>">
      </div>
      <select name="rating" class="form-select mb-2">
        <option value="0">Any rating</option>
        <?php foreach ([4, 3, 2] as $r): ?><option value="<?= $r ?>" <?= $rating == $r ? 'selected' : '' ?>><?= $r ?>★ &amp; up</option><?php endforeach; ?>
      </select>
      <select name="sort" class="form-select mb-3">
        <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option>
        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Top rated</option>
        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
      </select>
      <button class="btn btn-primary">Apply</button>
      <a class="btn btn-link btn-sm" href="<?= url('services.php') ?>">Reset</a>
    </form>
  </aside>
  <section class="col-lg-9">
    <?= page_header('Find services', count($services) . ' result' . (count($services) === 1 ? '' : 's')) ?>
    <?php if (!$services): ?>
      <?= empty_state('No services match those filters. Try a broader keyword or reset the filters.', 'Reset filters', url('services.php')) ?>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($services as $s): ?><div class="col-md-6"><?= service_card($s) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
