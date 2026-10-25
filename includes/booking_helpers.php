<?php
// Returns the booking if $me is its client, its freelancer, or an admin; otherwise null.
function get_booking_for_user(int $id, array $me): ?array {
    global $pdo;
    $st = $pdo->prepare(
        'SELECT b.*, s.title AS service_title, c.name AS client_name, f.name AS freelancer_name
         FROM bookings b
         JOIN services s ON s.id = b.service_id
         JOIN users c ON c.id = b.client_id
         JOIN users f ON f.id = b.freelancer_id
         WHERE b.id = ?'
    );
    $st->execute([$id]);
    $b = $st->fetch();
    if (!$b) return null;
    $ok = $me['role'] === 'admin' || (int)$b['client_id'] === (int)$me['id'] || (int)$b['freelancer_id'] === (int)$me['id'];
    return $ok ? $b : null;
}

function render_messages(int $bookingId, int $meId): string {
    global $pdo;
    $st = $pdo->prepare('SELECT m.*, u.name FROM messages m JOIN users u ON u.id = m.sender_id WHERE m.booking_id = ? ORDER BY m.id');
    $st->execute([$bookingId]);
    $rows = $st->fetchAll();
    if (!$rows) return '<p class="text-muted small text-center mt-5">No messages yet. Say hello!</p>';
    $html = '';
    foreach ($rows as $m) {
        $mine = (int)$m['sender_id'] === $meId ? ' mine' : '';
        $html .= '<div class="msg' . $mine . '"><small>' . e($m['name']) . ' · ' . e(date('d M, H:i', strtotime($m['created_at']))) . '</small>' . nl2br(e($m['message'])) . '</div>';
    }
    return $html;
}
