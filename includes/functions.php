<?php
// Small polyfills so the project also runs on PHP 7.4 (these exist natively in PHP 8).
if (!function_exists('str_contains')) { function str_contains(string $h, string $n): bool { return $n === '' || strpos($h, $n) !== false; } }
if (!function_exists('str_starts_with')) { function str_starts_with(string $h, string $n): bool { return strncmp($h, $n, strlen($n)) === 0; } }
if (!function_exists('str_ends_with')) { function str_ends_with(string $h, string $n): bool { return $n === '' || substr($h, -strlen($n)) === $n; } }
if (!function_exists('mb_substr')) { function mb_substr($s, $start, $len = null, $enc = null) { return $len === null ? substr($s, $start) : substr($s, $start, $len); } }
if (!function_exists('mb_strimwidth')) { function mb_strimwidth($s, $start, $width, $trim = '', $enc = null) { $s = substr($s, $start); return strlen($s) <= $width ? $s : substr($s, 0, max(0, $width - strlen($trim))) . $trim; } }

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }

function redirect(string $path) {
    header('Location: ' . url($path));
    exit;
}

function money($n): string { return CURRENCY . number_format((float)$n, 2); }

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }

function show_flash(): void {
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
        echo '<div class="alert alert-' . e($type) . ' alert-dismissible fade show">' . e($msg)
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    unset($_SESSION['flash']);
}

// ---- CSRF ----
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
            http_response_code(419);
            die('Invalid form token. Go back and try again.');
        }
    }
}

// ---- Auth ----
function current_user(): ?array {
    global $pdo;
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $st = $pdo->prepare('SELECT * FROM users WHERE id=? AND status="active"');
            $st->execute([$_SESSION['uid']]);
            $user = $st->fetch() ?: null;
            if (!$user) unset($_SESSION['uid']);
        }
    }
    return $user;
}

function require_login(?string $role = null): array {
    $u = current_user();
    if (!$u) {
        flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }
    if ($role && $u['role'] !== $role) {
        flash('danger', 'You do not have access to that page.');
        redirect('dashboard.php');
    }
    return $u;
}

// ---- Uploads ----
function upload_image(array $file, string $folder): ?string {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > 2 * 1024 * 1024) return null;
    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($allowed[$info[2]])) return null;
    $dir = UPLOAD_DIR . $folder . '/';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$info[2]];
    return move_uploaded_file($file['tmp_name'], $dir . $name) ? $folder . '/' . $name : null;
}

function delete_upload(?string $rel): void {
    if ($rel && is_file(UPLOAD_DIR . $rel)) @unlink(UPLOAD_DIR . $rel);
}

function avatar_url(?string $path): string {
    return $path ? url('uploads/' . $path) : url('assets/img/avatar.svg');
}

// ---- UI helpers ----
function stars($rating, $count = null): string {
    if ($rating === null) return '<span class="text-muted small">No reviews yet</span>';
    $r = round((float)$rating);
    $s = str_repeat('★', $r) . str_repeat('☆', 5 - $r);
    $out = '<span class="text-warning">' . $s . '</span> <span class="small text-muted">' . number_format((float)$rating, 1);
    if ($count !== null) $out .= ' (' . (int)$count . ')';
    return $out . '</span>';
}

function badge(string $status): string {
    $map = [
        'pending' => 'warning text-dark', 'accepted' => 'info text-dark', 'in_progress' => 'primary',
        'completed' => 'success', 'cancelled' => 'secondary', 'rejected' => 'danger',
        'approved' => 'success', 'paid' => 'success', 'unpaid' => 'secondary',
        'active' => 'success', 'blocked' => 'danger',
    ];
    return '<span class="badge bg-' . ($map[$status] ?? 'secondary') . '">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
}

function categories(): array {
    global $pdo;
    return $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
}

require_once __DIR__ . '/components.php';

// Rating subqueries reused across pages
const SQL_AVG_RATING = '(SELECT AVG(r.rating) FROM reviews r WHERE r.service_id = s.id)';
const SQL_REVIEW_COUNT = '(SELECT COUNT(*) FROM reviews r WHERE r.service_id = s.id)';
