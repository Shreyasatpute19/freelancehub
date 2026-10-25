<?php
$me = current_user();
$inAdmin = str_contains($_SERVER['SCRIPT_NAME'], '/admin/');
// Active-state helper: $file is relative to project root, e.g. "services.php" or "admin/index.php"
$navLink = function (string $file, string $label) use ($inAdmin): string {
    $isAdminLink = str_starts_with($file, 'admin/');
    $active = $isAdminLink ? ($inAdmin) : (!$inAdmin && str_ends_with($_SERVER['SCRIPT_NAME'], '/' . $file));
    return '<li class="nav-item"><a class="nav-link' . ($active ? ' active' : '') . '" href="' . e(url($file)) . '">' . e($label) . '</a></li>';
};
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#090d1f">
  <title><?= e(($pageTitle ?? 'Home') . ' | ' . SITE_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<header class="dock-wrap">
  <nav class="dock navbar navbar-expand-lg">
    <a class="navbar-brand" href="<?= url('index.php') ?>"><span class="brand-orb"></span><?= e(SITE_NAME) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto ms-lg-3">
        <?= $navLink('services.php', 'Find services') ?>
        <?php if ($me): ?>
          <?= $navLink('dashboard.php', 'Dashboard') ?>
          <?php if ($me['role'] === 'admin'): ?>
            <?= $navLink('admin/index.php', 'Admin') ?>
          <?php else: ?>
            <?= $navLink('my_bookings.php', 'Bookings') ?>
          <?php endif; ?>
          <?php if ($me['role'] === 'freelancer'): ?>
            <?= $navLink('my_services.php', 'My services') ?>
            <?= $navLink('portfolio.php', 'Portfolio') ?>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav align-items-lg-center">
        <?php if ($me): ?>
          <?= $navLink('profile.php', $me['name']) ?>
          <li class="nav-item"><a class="nav-link" href="<?= url('logout.php') ?>">Log out</a></li>
        <?php else: ?>
          <?= $navLink('login.php', 'Log in') ?>
          <li class="nav-item ms-lg-1"><a class="btn btn-primary btn-sm" href="<?= url('register.php') ?>">Sign up</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>
</header>
<main class="container shell">
<?php show_flash(); ?>
