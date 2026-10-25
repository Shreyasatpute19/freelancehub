<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/booking_helpers.php';
$me = require_login();
$id = (int)($_GET['id'] ?? 0);
$b = get_booking_for_user($id, $me);
if (!$b) { flash('danger', 'Booking not found.'); redirect('dashboard.php'); }

$isClient = (int)$b['client_id'] === (int)$me['id'];
$isFreelancer = (int)$b['freelancer_id'] === (int)$me['id'];
$isAdmin = $me['role'] === 'admin';
$self = 'booking.php?id=' . $id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $status = $b['status'];
    $set = function (string $newStatus) use ($pdo, $id) {
        $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
    };

    if ($action === 'message' && ($isClient || $isFreelancer)) {
        $msg = trim($_POST['message'] ?? '');
        if ($msg !== '') {
            $pdo->prepare('INSERT INTO messages (booking_id, sender_id, message) VALUES (?,?,?)')->execute([$id, $me['id'], mb_substr($msg, 0, 2000)]);
        }
        redirect($self . '#chat');
    }

    if ($isFreelancer && $status === 'pending' && $action === 'accept') { $set('accepted'); flash('success', 'Booking accepted. The client can now pay.'); }
    elseif ($isFreelancer && $status === 'pending' && $action === 'reject') { $set('rejected'); flash('info', 'Booking rejected.'); }
    elseif ($isFreelancer && $status === 'accepted' && $b['payment_status'] === 'paid' && $action === 'start') { $set('in_progress'); flash('success', 'Marked as in progress.'); }
    elseif ($isFreelancer && $status === 'in_progress' && $action === 'complete') { $set('completed'); flash('success', 'Project marked as completed.'); }
    elseif ($isClient && in_array($status, ['pending', 'accepted'], true) && $b['payment_status'] === 'unpaid' && $action === 'cancel') { $set('cancelled'); flash('info', 'Booking cancelled.'); }
    elseif ($isClient && $status === 'accepted' && $b['payment_status'] === 'unpaid' && $action === 'pay') {
        $method = in_array($_POST['method'] ?? '', ['UPI', 'Card', 'Net Banking', 'Wallet'], true) ? $_POST['method'] : 'UPI';
        // Demo payment: records a successful transaction. Replace with a real gateway (Razorpay/Stripe) for production.
        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO payments (booking_id, amount, method, transaction_ref, status) VALUES (?,?,?,?, "success")')
                ->execute([$id, $b['amount'], $method, 'TXN' . strtoupper(bin2hex(random_bytes(5)))]);
            $pdo->prepare('UPDATE bookings SET payment_status = "paid" WHERE id = ?')->execute([$id]);
            $pdo->commit();
            flash('success', 'Payment successful.');
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', 'Payment failed. Please try again.');
        }
    }
    elseif ($isClient && $status === 'completed' && $action === 'review') {
        $exists = $pdo->prepare('SELECT 1 FROM reviews WHERE booking_id = ?');
        $exists->execute([$id]);
        $rating = (int)($_POST['rating'] ?? 0);
        if ($exists->fetch()) flash('warning', 'You already reviewed this booking.');
        elseif ($rating < 1 || $rating > 5) flash('danger', 'Please choose a star rating.');
        else {
            $pdo->prepare('INSERT INTO reviews (booking_id, service_id, client_id, freelancer_id, rating, comment) VALUES (?,?,?,?,?,?)')
                ->execute([$id, $b['service_id'], $me['id'], $b['freelancer_id'], $rating, mb_substr(trim($_POST['comment'] ?? ''), 0, 500)]);
            flash('success', 'Thanks for your review!');
        }
    }
    redirect($self);
}

$pay = $pdo->prepare('SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC');
$pay->execute([$id]);
$payments = $pay->fetchAll();

$rv = $pdo->prepare('SELECT * FROM reviews WHERE booking_id = ?');
$rv->execute([$id]);
$review = $rv->fetch();

$pageTitle = 'Booking #' . $id;
include __DIR__ . '/includes/header.php';
?>
<?= page_header('Booking #' . $id, $b['service_title']) ?>
<div class="card mb-4"><div class="card-body pb-2"><?= booking_stepper($b['status'], $b['payment_status']) ?></div></div>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="card"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Details</h5><?= badge($b['status']) ?></div>
      <p class="mb-1"><strong><a href="<?= url('service.php?id=' . $b['service_id']) ?>"><?= e($b['service_title']) ?></a></strong></p>
      <p class="small text-muted mb-1">Client: <?= e($b['client_name']) ?> &middot; Freelancer: <a href="<?= url('freelancer.php?id=' . $b['freelancer_id']) ?>"><?= e($b['freelancer_name']) ?></a></p>
      <p class="small text-muted">Requested <?= e(date('d M Y', strtotime($b['created_at']))) ?><?= $b['needed_by'] ? ' &middot; Needed by ' . e(date('d M Y', strtotime($b['needed_by']))) : '' ?></p>
      <div class="border rounded p-2 mb-3 bg-light"><?= nl2br(e($b['details'])) ?></div>
      <div class="d-flex justify-content-between mb-3"><span>Amount</span><strong><?= money($b['amount']) ?></strong></div>
      <div class="d-flex justify-content-between mb-3"><span>Payment</span><?= badge($b['payment_status']) ?></div>

      <!-- Freelancer actions -->
      <?php if ($isFreelancer): ?>
        <form method="post" class="d-flex gap-2"><?= csrf_field() ?>
          <?php if ($b['status'] === 'pending'): ?>
            <button name="action" value="accept" class="btn btn-success">Accept</button>
            <button name="action" value="reject" class="btn btn-outline-danger" data-confirm="Reject this booking?">Reject</button>
          <?php elseif ($b['status'] === 'accepted' && $b['payment_status'] === 'paid'): ?>
            <button name="action" value="start" class="btn btn-primary">Start work</button>
          <?php elseif ($b['status'] === 'accepted'): ?>
            <span class="text-muted small">Waiting for the client to pay.</span>
          <?php elseif ($b['status'] === 'in_progress'): ?>
            <button name="action" value="complete" class="btn btn-success" data-confirm="Mark this project as completed?">Mark completed</button>
          <?php endif; ?>
        </form>
      <?php endif; ?>

      <!-- Client actions -->
      <?php if ($isClient): ?>
        <?php if ($b['status'] === 'accepted' && $b['payment_status'] === 'unpaid'): ?>
          <form method="post" class="mb-2"><?= csrf_field() ?>
            <input type="hidden" name="action" value="pay">
            <label class="form-label small">Payment method (demo gateway)</label>
            <div class="input-group">
              <select name="method" class="form-select"><option>UPI</option><option>Card</option><option>Net Banking</option><option>Wallet</option></select>
              <button class="btn btn-success">Pay <?= money($b['amount']) ?></button>
            </div>
          </form>
        <?php endif; ?>
        <?php if (in_array($b['status'], ['pending', 'accepted'], true) && $b['payment_status'] === 'unpaid'): ?>
          <form method="post"><?= csrf_field() ?><button name="action" value="cancel" class="btn btn-outline-secondary btn-sm" data-confirm="Cancel this booking?">Cancel booking</button></form>
        <?php endif; ?>
      <?php endif; ?>
    </div></div>

    <?php if ($payments): ?>
      <div class="card mt-3"><div class="card-body">
        <h6>Payment history</h6>
        <?php foreach ($payments as $p): ?>
          <div class="small d-flex justify-content-between"><span><?= e($p['transaction_ref']) ?> &middot; <?= e($p['method']) ?></span><span><?= money($p['amount']) ?> <?= badge($p['status'] === 'success' ? 'paid' : $p['status']) ?></span></div>
        <?php endforeach; ?>
      </div></div>
    <?php endif; ?>

    <?php if ($b['status'] === 'completed'): ?>
      <div class="card mt-3"><div class="card-body">
        <h6>Rating &amp; review</h6>
        <?php if ($review): ?>
          <div><?= stars($review['rating']) ?></div><div><?= e($review['comment']) ?></div>
        <?php elseif ($isClient): ?>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="review">
            <div class="rating-input mb-2">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="st<?= $i ?>" value="<?= $i ?>"><label for="st<?= $i ?>">★</label>
              <?php endfor; ?>
            </div>
            <textarea name="comment" rows="3" maxlength="500" class="form-control mb-2" placeholder="Share your experience"></textarea>
            <button class="btn btn-primary btn-sm">Submit review</button>
          </form>
        <?php else: ?><p class="text-muted small mb-0">The client has not left a review yet.</p><?php endif; ?>
      </div></div>
    <?php endif; ?>
  </div>

  <div class="col-lg-7" id="chat">
    <h5>Chat</h5>
    <div id="chat-box" class="chat-box mb-2" data-url="<?= url('chat_fetch.php?id=' . $id) ?>"><?= render_messages($id, (int)$me['id']) ?></div>
    <?php if ($isClient || $isFreelancer): ?>
      <form method="post" class="input-group"><?= csrf_field() ?><input type="hidden" name="action" value="message">
        <input name="message" class="form-control" placeholder="Type a message…" maxlength="2000" required autocomplete="off">
        <button class="btn btn-primary">Send</button>
      </form>
    <?php else: ?><p class="small text-muted">Admins can view but not post in booking chats.</p><?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
