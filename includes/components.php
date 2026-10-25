<?php
// Reusable UI components. Each returns an HTML string so pages stay short and consistent.

function page_header(string $title, string $subtitle = '', string $actionsHtml = ''): string {
    $h = '<div class="page-head"><div><h1>' . e($title) . '</h1>';
    if ($subtitle !== '') $h .= '<p>' . e($subtitle) . '</p>';
    $h .= '</div>';
    if ($actionsHtml !== '') $h .= '<div class="d-flex gap-2 flex-wrap">' . $actionsHtml . '</div>';
    return $h . '</div>';
}

function empty_state(string $message, string $ctaLabel = '', string $ctaUrl = ''): string {
    $h = '<div class="card empty-state"><div class="orb"></div><p class="text-muted mb-3">' . e($message) . '</p>';
    if ($ctaLabel !== '') $h .= '<div><a class="btn btn-primary" href="' . e($ctaUrl) . '">' . e($ctaLabel) . '</a></div>';
    return $h . '</div>';
}

// $s needs: id, title, description, price, delivery_days, cat, fname, city, is_verified, avatar (optional), avg_rating, rev_count
function service_card(array $s): string {
    $verified = !empty($s['is_verified']) ? ' <span class="verified" title="Verified by admin">✔</span>' : '';
    return '<article class="card card-hover svc h-100"><div class="card-body d-flex flex-column">'
        . '<div class="d-flex justify-content-between align-items-start mb-3"><span class="chip">' . e($s['cat']) . '</span><span class="price">' . money($s['price']) . '</span></div>'
        . '<h3 class="svc-title"><a class="stretched-link" href="' . e(url('service.php?id=' . $s['id'])) . '">' . e($s['title']) . '</a></h3>'
        . '<p class="small text-muted flex-grow-1">' . e(mb_strimwidth($s['description'] ?? '', 0, 110, '…')) . '</p>'
        . '<div class="d-flex align-items-center gap-2 mt-2">'
        . '<img class="avatar avatar-sm" src="' . e(avatar_url($s['avatar'] ?? null)) . '" alt="">'
        . '<div class="small lh-sm"><div class="fw-semibold">' . e($s['fname']) . $verified . '</div>'
        . '<div class="text-muted">' . e($s['city'] ?: 'Remote') . ' · ' . (int)$s['delivery_days'] . ' day delivery</div></div>'
        . '<div class="ms-auto text-end small">' . stars($s['avg_rating'], $s['rev_count']) . '</div>'
        . '</div></div></article>';
}

// Progress tracker for a booking. Cancelled/rejected bookings show a notice instead.
function booking_stepper(string $status, string $payment): string {
    if (in_array($status, ['cancelled', 'rejected'], true)) {
        return '<div class="alert alert-warning py-2">This booking was ' . e($status) . '.</div>';
    }
    $labels = ['Requested', 'Accepted', 'Paid', 'In progress', 'Completed'];
    if ($status === 'completed') $done = 5;
    elseif ($status === 'in_progress') $done = 4;
    elseif ($status === 'accepted' && $payment === 'paid') $done = 3;
    elseif ($status === 'accepted') $done = 2;
    else $done = 1;
    $h = '<ol class="stepper">';
    foreach ($labels as $i => $label) {
        $cls = $i < $done ? 'done' : ($i === $done ? 'current' : '');
        $h .= '<li class="' . $cls . '">' . e($label) . '</li>';
    }
    return $h . '</ol>';
}
