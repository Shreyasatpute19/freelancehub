<?php
require_once __DIR__ . '/config.php';
$me = require_login();
$pageTitle = 'My Profile';
$errors = [];

$fp = ['title' => '', 'bio' => '', 'skills' => '', 'hourly_rate' => ''];
if ($me['role'] === 'freelancer') {
    $st = $pdo->prepare('SELECT * FROM freelancer_profiles WHERE user_id=?');
    $st->execute([$me['id']]);
    $fp = $st->fetch() ?: $fp;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'password') {
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($_POST['current_password'] ?? '', $me['password'])) $errors[] = 'Current password is incorrect.';
        elseif (strlen($new) < 6) $errors[] = 'New password must be at least 6 characters.';
        else {
            $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
            flash('success', 'Password changed.');
            redirect('profile.php');
        }
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        if (strlen($name) < 2) $errors[] = 'Name is required.';
        if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,15}$/', $phone)) $errors[] = 'Enter a valid phone number.';

        $avatar = $me['avatar'];
        if (!empty($_FILES['avatar']['name'])) {
            $new = upload_image($_FILES['avatar'], 'avatars');
            if ($new) { delete_upload($avatar); $avatar = $new; }
            else $errors[] = 'Avatar must be a JPG, PNG or WebP image under 2 MB.';
        }
        if (!$errors) {
            $pdo->prepare('UPDATE users SET name=?, phone=?, city=?, avatar=? WHERE id=?')->execute([$name, $phone ?: null, $city ?: null, $avatar, $me['id']]);
            if ($me['role'] === 'freelancer') {
                $rate = is_numeric($_POST['hourly_rate'] ?? '') ? $_POST['hourly_rate'] : null;
                $pdo->prepare('INSERT INTO freelancer_profiles (user_id,title,bio,skills,hourly_rate) VALUES (?,?,?,?,?)
                               ON DUPLICATE KEY UPDATE title=VALUES(title), bio=VALUES(bio), skills=VALUES(skills), hourly_rate=VALUES(hourly_rate)')
                    ->execute([$me['id'], trim($_POST['title'] ?? ''), trim($_POST['bio'] ?? ''), trim($_POST['skills'] ?? ''), $rate]);
            }
            flash('success', 'Profile updated.');
            redirect('profile.php');
        }
    }
}
include __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card"><div class="card-body">
      <h5>Edit profile</h5>
      <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
      <form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
        <div class="d-flex align-items-center mb-3">
          <img class="avatar avatar-lg me-3" src="<?= e(avatar_url($me['avatar'])) ?>" alt="">
          <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" class="form-control">
        </div>
        <div class="row">
          <div class="col-md-6 mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="<?= e($me['name']) ?>" required></div>
          <div class="col-md-6 mb-3"><label class="form-label">Email</label><input class="form-control" value="<?= e($me['email']) ?>" disabled></div>
          <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($me['phone']) ?>"></div>
          <div class="col-md-6 mb-3"><label class="form-label">City</label><input name="city" class="form-control" value="<?= e($me['city']) ?>"></div>
        </div>
        <?php if ($me['role'] === 'freelancer'): ?>
          <div class="mb-3"><label class="form-label">Professional title</label><input name="title" class="form-control" value="<?= e($fp['title']) ?>" placeholder="e.g. Full-stack web developer"></div>
          <div class="mb-3"><label class="form-label">Bio</label><textarea name="bio" rows="4" class="form-control"><?= e($fp['bio']) ?></textarea></div>
          <div class="row">
            <div class="col-md-8 mb-3"><label class="form-label">Skills (comma separated)</label><input name="skills" class="form-control" value="<?= e($fp['skills']) ?>" placeholder="PHP, MySQL, Bootstrap"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Hourly rate (<?= CURRENCY ?>)</label><input type="number" min="0" step="0.01" name="hourly_rate" class="form-control" value="<?= e($fp['hourly_rate']) ?>"></div>
          </div>
        <?php endif; ?>
        <button class="btn btn-primary">Save changes</button>
      </form>
    </div></div>
  </div>
  <div class="col-lg-4">
    <div class="card"><div class="card-body">
      <h5>Change password</h5>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password">
        <div class="mb-2"><input type="password" name="current_password" class="form-control" placeholder="Current password" required></div>
        <div class="mb-3"><input type="password" name="new_password" class="form-control" placeholder="New password" minlength="6" required></div>
        <button class="btn btn-outline-primary">Update password</button>
      </form>
    </div></div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
