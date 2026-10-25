<?php
require_once __DIR__ . '/config.php';
if (current_user()) redirect('dashboard.php');
$pageTitle = 'Login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $st = $pdo->prepare('SELECT * FROM users WHERE email=?');
    $st->execute([$email]);
    $u = $st->fetch();
    if (!$u || !password_verify($_POST['password'] ?? '', $u['password'])) {
        $error = 'Incorrect email or password.';
    } elseif ($u['status'] !== 'active') {
        $error = 'Your account has been blocked. Contact the administrator.';
    } else {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        flash('success', 'Welcome back, ' . $u['name'] . '!');
        redirect($u['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
    }
}
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-5 col-lg-4">
  <div class="card shadow-sm"><div class="card-body p-4">
    <h4 class="mb-3">Log in</h4>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-primary w-100">Log in</button>
    </form>
    <p class="text-center small mt-3 mb-0">New here? <a href="<?= url('register.php') ?>">Create an account</a></p>
  </div></div>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
