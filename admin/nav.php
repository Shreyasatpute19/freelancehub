<?php
// Shared admin header: page title + section switcher. Expects $pageTitle to be set by the page.
$cur = basename($_SERVER['SCRIPT_NAME']);
echo page_header($pageTitle ?? 'Admin', 'Administration');
?>
<ul class="nav nav-pills mb-4">
  <?php foreach (['index.php' => 'Overview', 'users.php' => 'Users', 'services.php' => 'Services', 'bookings.php' => 'Bookings', 'categories.php' => 'Categories'] as $f => $label): ?>
    <li class="nav-item"><a class="nav-link <?= $cur === $f ? 'active' : '' ?>" href="<?= url('admin/' . $f) ?>"><?= $label ?></a></li>
  <?php endforeach; ?>
</ul>
