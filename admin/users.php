<?php
require_once __DIR__ . '/../config.php';
$me = require_login('admin');
$pageTitle = 'Manage Users';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $uid = (int)($_POST['id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if ($uid === (int)$me['id']) { flash('danger', 'You cannot modify your own account here.'); redirect('admin/users.php'); }
    if ($act === 'verify') $pdo->prepare('UPDATE users SET is_verified=1 WHERE id=?')->execute([$uid]);
    elseif ($act === 'unverify') $pdo->prepare('UPDATE users SET is_verified=0 WHERE id=?')->execute([$uid]);
    elseif ($act === 'block') $pdo->prepare('UPDATE users SET status="blocked" WHERE id=? AND role<>"admin"')->execute([$uid]);
    elseif ($act === 'unblock') $pdo->prepare('UPDATE users SET status="active" WHERE id=?')->execute([$uid]);
    elseif ($act === 'delete') {
        $st = $pdo->prepare('SELECT avatar FROM users WHERE id=? AND role<>"admin"');
        $st->execute([$uid]);
        if ($row = $st->fetch()) { delete_upload($row['avatar']); $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]); }
    }
    flash('success', 'User updated.');
    redirect('admin/users.php?' . http_build_query(array_filter(['role' => $_GET['role'] ?? '', 'q' => $_GET['q'] ?? ''])));
}

$role = $_GET['role'] ?? '';
$q = trim($_GET['q'] ?? '');
$where = ['role <> "admin"']; $p = [];
if (in_array($role, ['client', 'freelancer'], true)) { $where[] = 'role = ?'; $p[] = $role; }
if ($q !== '') { $where[] = '(name LIKE ? OR email LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
$st = $pdo->prepare('SELECT * FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC');
$st->execute($p);
$users = $st->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/nav.php';
?>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-4"><input name="q" class="form-control" placeholder="Search name or email" value="<?= e($q) ?>"></div>
  <div class="col-md-3"><select name="role" class="form-select"><option value="">All roles</option><option value="client" <?= $role === 'client' ? 'selected' : '' ?>>Clients</option><option value="freelancer" <?= $role === 'freelancer' ? 'selected' : '' ?>>Freelancers</option></select></div>
  <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
</form>
<div class="table-responsive card"><table class="table align-middle mb-0">
  <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>City</th><th>Verified</th><th>Status</th><th>Joined</th><th></th></tr></thead>
  <tbody>
  <?php if (!$users): ?><tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr><?php endif; ?>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= $u['role'] === 'freelancer' ? '<a href="' . url('freelancer.php?id=' . $u['id']) . '">' . e($u['name']) . '</a>' : e($u['name']) ?></td>
      <td><?= e($u['email']) ?></td><td><?= e($u['role']) ?></td><td><?= e($u['city']) ?></td>
      <td><?= $u['is_verified'] ? '✔' : '—' ?></td><td><?= badge($u['status']) ?></td>
      <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
      <td class="text-end text-nowrap">
        <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>">
          <?php if ($u['role'] === 'freelancer'): ?>
            <?php if ($u['is_verified']): ?><button name="action" value="unverify" class="btn btn-sm btn-outline-secondary">Unverify</button>
            <?php else: ?><button name="action" value="verify" class="btn btn-sm btn-success">Verify</button><?php endif; ?>
          <?php endif; ?>
          <?php if ($u['status'] === 'active'): ?><button name="action" value="block" class="btn btn-sm btn-outline-warning" data-confirm="Block this user?">Block</button>
          <?php else: ?><button name="action" value="unblock" class="btn btn-sm btn-outline-success">Unblock</button><?php endif; ?>
          <button name="action" value="delete" class="btn btn-sm btn-outline-danger" data-confirm="Permanently delete this user and all their data?">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
