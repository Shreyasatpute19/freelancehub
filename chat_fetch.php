<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/booking_helpers.php';
$me = current_user();
if (!$me) { http_response_code(401); exit; }
$b = get_booking_for_user((int)($_GET['id'] ?? 0), $me);
if (!$b) { http_response_code(404); exit; }
echo render_messages((int)$b['id'], (int)$me['id']);
