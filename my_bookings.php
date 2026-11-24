<?php
/**
 * Serene Shores Coastal Villa - My Bookings (Guest Portal)
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 * Security: requireLogin(), Ownership Validation on Cancellations, CSRF Protection
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Guest authentication enforced
requireLogin('my_bookings.php');

$user = currentUser();
$pageTitle = 'My Villa Reservations | Serene Shores Coastal Villa';

// Handle Guest Cancellation of Pending Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Security validation failed. Please try again.');
        header('Location: my_bookings.php');
        exit;
    }

    $bookingId = (int)($_POST['booking_id'] ?? 0);

    // Strict Ownership & Status Verification: Can ONLY cancel own PENDING bookings
    $cancelStmt = $pdo->prepare(
        "UPDATE bookings 
         SET booking_status = 'Cancelled' 
         WHERE booking_id = :booking_id 
           AND user_id = :user_id 
           AND booking_status = 'Pending'"
    );

    $cancelStmt->execute([
        ':booking_id' => $bookingId,
        ':user_id'    => $user['user_id']
    ]);

    if ($cancelStmt->rowCount() > 0) {
        setFlash('success', 'Booking reservation #' . $bookingId . ' has been cancelled successfully.');
    } else {
        setFlash('danger', 'Unable to cancel this reservation. You can only cancel your own pending reservations. Confirmed bookings require contacting villa reception directly.');
    }

    header('Location: my_bookings.php');
    exit;
}

// Fetch all bookings for the logged-in user joined with room data
$stmt = $pdo->prepare(
    "SELECT b.booking_id, b.room_id, b.guest_name, b.guest_email, b.guest_contact, 
            b.guests_count, b.special_requests, b.check_in, b.check_out, b.total_amount, 
            b.booking_status, b.booking_date,
            r.room_name, r.room_type, r.image_url, r.price_per_night,
            DATEDIFF(b.check_out, b.check_in) AS nights
     FROM bookings b
     JOIN rooms r ON b.room_id = r.room_id
     WHERE b.user_id = :user_id
     ORDER BY b.booking_date DESC"
);
$stmt->execute([':user_id' => $user['user_id']]);
$userBookings = $stmt->fetchAll();

// Count stats
$pendingCount   = 0;
$confirmedCount = 0;
foreach ($userBookings as $b) {
    if ($b['booking_status'] === 'Pending') $pendingCount++;
    if ($b['booking_status'] === 'Confirmed') $confirmedCount++;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 30px;">
        <div>
            <span class="section-badge">Guest Portal</span>
            <h1 style="font-size: 2rem; color: var(--primary-navy); margin-top: 6px;">
                My Villa Reservations
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Welcome back, <strong><?= e($user['name']); ?></strong>. View and manage all your Mirissa getaway bookings.
            </p>
        </div>
        <div>
            <a href="rooms.php" class="btn btn-primary">&#128719; Book Another Room</a>
        </div>
    </div>

    <!-- Quick Overview Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 35px;">
        <div style="background: #fff; padding: 20px; border-radius: var(--radius-md); border-left: 4px solid var(--accent-teal); box-shadow: var(--shadow-sm);">
            <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">Total Reservations</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary-navy);"><?= count($userBookings); ?></div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: var(--radius-md); border-left: 4px solid #10b981; box-shadow: var(--shadow-sm);">
            <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">Confirmed Stays</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #065f46;"><?= $confirmedCount; ?></div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: var(--radius-md); border-left: 4px solid #f59e0b; box-shadow: var(--shadow-sm);">
            <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">Awaiting Confirmation</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #92400e;"><?= $pendingCount; ?></div>
        </div>
    </div>

    <!-- Bookings Table -->
    <?php if (!empty($userBookings)): ?>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Ref #</th>
                        <th>Room & Suite</th>
                        <th>Check-In & Check-Out</th>
                        <th>Nights / Guests</th>
                        <th>Total Cost</th>
                        <th>Status</th>
                        <th>Booked On</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userBookings as $bk): ?>
                        <?php 
                        $badgeClass = match($bk['booking_status']) {
                            'Confirmed' => 'badge-confirmed',
                            'Cancelled' => 'badge-cancelled',
                            default     => 'badge-pending'
                        };
                        ?>
                        <tr>
                            <td>
                                <strong>#<?= (int)$bk['booking_id']; ?></strong>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="<?= e($bk['image_url']); ?>" alt="<?= e($bk['room_name']); ?>" style="width: 50px; height: 40px; object-fit: cover; border-radius: 4px;">
                                    <div>
                                        <div style="font-weight: 700; color: var(--primary-navy);"><?= e($bk['room_name']); ?></div>
                                        <small style="color: var(--text-muted);"><?= e($bk['room_type']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><strong><?= date('M d, Y', strtotime($bk['check_in'])); ?></strong></div>
                                <small style="color: var(--text-muted);">to <?= date('M d, Y', strtotime($bk['check_out'])); ?></small>
                            </td>
                            <td>
                                <?= (int)$bk['nights']; ?> Night(s)<br>
                                <small style="color: var(--text-muted);">&#128101; <?= (int)$bk['guests_count']; ?> Guest(s)</small>
                            </td>
                            <td>
                                <strong style="color: var(--accent-teal);"><?= formatLKR((float)$bk['total_amount']); ?></strong>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass; ?>">
                                    <?= e($bk['booking_status']); ?>
                                </span>
                            </td>
                            <td>
                                <small><?= date('M d, Y H:i', strtotime($bk['booking_date'])); ?></small>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($bk['booking_status'] === 'Pending'): ?>
                                    <form action="my_bookings.php" method="POST" class="cancel-booking-form" style="display: inline-block;">
                                        <?= csrfField(); ?>
                                        <input type="hidden" name="action" value="cancel_booking">
                                        <input type="hidden" name="booking_id" value="<?= (int)$bk['booking_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Cancel this pending reservation">
                                            Cancel
                                        </button>
                                    </form>
                                <?php elseif ($bk['booking_status'] === 'Confirmed'): ?>
                                    <span style="font-size: 0.8rem; color: #065f46; font-weight: 600;">
                                        &#10003; Confirmed
                                    </span>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);">
                                        Cancelled
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($bk['special_requests'])): ?>
                            <tr style="background: #fbfdff;">
                                <td colspan="8" style="padding: 8px 18px; font-size: 0.82rem; color: var(--text-muted); border-bottom: 2px solid var(--border-color);">
                                    <strong>Special Requests:</strong> <?= e($bk['special_requests']); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
            <div style="font-size: 3rem; margin-bottom: 12px; color: var(--accent-sand);">&#128392;</div>
            <h3 style="color: var(--primary-navy); margin-bottom: 10px;">No Reservations Found</h3>
            <p style="color: var(--text-muted); max-width: 480px; margin: 0 auto 24px;">
                You haven't made any reservations at Serene Shores Villa yet. Explore our luxury beachfront suites and plan your Mirissa escape today.
            </p>
            <a href="rooms.php" class="btn btn-primary">&#127960; Browse Available Rooms</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
