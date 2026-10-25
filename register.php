<?php
require_once __DIR__ . '/config.php';
if (current_user()) redirect('dashboard.php');
$pageTitle = 'Sign up';
$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'city' => '', 'role' => 'client'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');
    $password = $_POST['password'] ?? '';
    $role = in_array($old['role'], ['client', 'freelancer'], true) ? $old['role'] : 'client';

    if (strlen($old['name']) < 2) $errors[] = 'Please enter your name.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== ($_POST['confirm'] ?? '')) $errors[] = 'Passwords do not match.';
    if ($old['phone'] !== '' && !preg_match('/^[0-9+\-\s]{7,15}$/', $old['phone'])) $errors[] = 'Enter a valid phone number.';

    if (!$errors) {
        $st = $pdo->prepare('SELECT id FROM users WHERE email=?');
        $st->execute([$old['email']]);
        if ($st->fetch()) $errors[] = 'That email is already registered.';
    }

    if (!$errors) {
        $pdo->prepare('INSERT INTO users (name,email,password,role,phone,city) VALUES (?,?,?,?,?,?)')
            ->execute([$old['name'], $old['email'], password_hash($password, PASSWORD_DEFAULT), $role, $old['phone'] ?: null, $old['city'] ?: null]);
        $id = (int)$pdo->lastInsertId();
        if ($role === 'freelancer') $pdo->prepare('INSERT INTO freelancer_profiles (user_id) VALUES (?)')->execute([$id]);
        session_regenerate_id(true);
        $_SESSION['uid'] = $id;
        flash('success', 'Welcome to ' . SITE_NAME . '! ' . ($role === 'freelancer' ? 'Complete your profile and add a service.' : 'Start by searching for a service.'));
        redirect($role === 'freelancer' ? 'profile.php' : 'services.php');
    }
}
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-6 col-lg-5">
  <div class="card shadow-sm"><div class="card-body p-4">
    <h4 class="mb-3">Create your account</h4>
    <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" data-validate-password>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">I want to</label>
        <div class="btn-group w-100" role="group">
          <input type="radio" class="btn-check" name="role" id="r1" value="client" <?= $old['role'] !== 'freelancer' ? 'checked' : '' ?>>
          <label class="btn btn-outline-primary" for="r1">Hire (Client)</label>
          <input type="radio" class="btn-check" name="role" id="r2" value="freelancer" <?= $old['role'] === 'freelancer' ? 'checked' : '' ?>>
          <label class="btn btn-outline-primary" for="r2">Work (Freelancer)</label>
        </div>
      </div>
      <div class="mb-3"><label class="form-label">Full name</label><input name="name" class="form-control" value="<?= e($old['name']) ?>" required></div>
      <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($old['email']) ?>" required></div>
      <div class="row">
        <div class="col-6 mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($old['phone']) ?>"></div>
        <div class="col-6 mb-3"><label class="form-label">City</label><input name="city" class="form-control" value="<?= e($old['city']) ?>"></div>
      </div>
      <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" minlength="6" required></div>
      <div class="mb-3"><label class="form-label">Confirm password</label><input type="password" name="confirm" class="form-control" required></div>
      <button class="btn btn-primary w-100">Sign up</button>
    </form>
    <p class="text-center small mt-3 mb-0">Already registered? <a href="<?= url('login.php') ?>">Log in</a></p>
  </div></div>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
