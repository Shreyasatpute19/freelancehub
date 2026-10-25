<?php
require_once __DIR__ . '/config.php';
$me = require_login('client');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('services.php');
csrf_check();

$sid = (int)($_POST['service_id'] ?? 0);
$details = trim($_POST['details'] ?? '');
$needed = $_POST['needed_by'] ?? '';

$st = $pdo->prepare('SELECT s.* FROM services s JOIN users u ON u.id = s.freelancer_id WHERE s.id = ? AND s.status = "approved" AND u.status = "active"');
$st->execute([$sid]);
$s = $st->fetch();
if (!$s) { flash('danger', 'That service is not available.'); redirect('services.php'); }
if ($details === '') { flash('danger', 'Please describe what you need.'); redirect('service.php?id=' . $sid); }
if ($needed !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $needed) || $needed < date('Y-m-d'))) $needed = '';

$pdo->prepare('INSERT INTO bookings (service_id, client_id, freelancer_id, details, needed_by, amount) VALUES (?,?,?,?,?,?)')
    ->execute([$sid, $me['id'], $s['freelancer_id'], $details, $needed ?: null, $s['price']]);
$bid = (int)$pdo->lastInsertId();
flash('success', 'Booking request sent! The freelancer will review it shortly.');
redirect('booking.php?id=' . $bid);
