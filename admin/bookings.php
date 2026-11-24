<?php
/**
 * Serene Shores Coastal Villa - Admin Booking Management
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 * Security: requireAdmin(), Double-Booking Overlap Prevention, CSRF Protection
 */

declare(strict_types=1);

$pageTitle = 'Manage Guest Reservations | Admin Portal';
require_once __DIR__ . '/includes/admin_header.php';

// ----------------------------------------------------------------------------
// Handle Status Updates (Confirm / Cancel)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Security validation failed. Please refresh and retry.');
        header('Location: bookings.php');
        exit;
    }

    $action    = (string)($_POST['action'] ?? '');
    $bookingId = (int)($_POST['booking_id'] ?? 0);

    // Retrieve the target booking details
    $targetStmt = $pdo->prepare('SELECT * FROM bookings WHERE booking_id = :id LIMIT 1');
    $targetStmt->execute([':id' => $bookingId]);
    $booking = $targetStmt->fetch();

    if (!$booking) {
        setFlash('danger', 'Booking record #' . $bookingId . ' was not found.');
        header('Location: bookings.php');
        exit;
    }

    // 1. CONFIRM BOOKING ACTION (WITH MANDATORY OVERLAP CHECK)
    if ($action === 'confirm') {
        if ($booking['booking_status'] === 'Confirmed') {
            setFlash('info', 'Booking #' . $bookingId . ' is already confirmed.');
        } else {
            // Strict Overlap Check: Block confirmation if another Confirmed booking overlaps
            $overlapCheck = $pdo->prepare(
                "SELECT COUNT(*) FROM bookings 
                 WHERE room_id = :room_id 
                   AND booking_id != :current_booking_id 
                   AND booking_status = 'Confirmed' 
                   AND check_in < :check_out 
                   AND check_out > :check_in"
            );
            $overlapCheck->execute([
                ':room_id'            => $booking['room_id'],
                ':current_booking_id' => $bookingId,
                ':check_out'          => $booking['check_out'],
                ':check_in'           => $booking['check_in']
            ]);

            $conflicts = (int)$overlapCheck->fetchColumn();

            if ($conflicts > 0) {
                // BLOCK CONFIRMATION: Double-booking prevention
                setFlash(
                    'danger',
                    'Confirmation Blocked: Another guest already holds a CONFIRMED reservation for this suite ' .
                    'during the requested dates (' . $booking['check_in'] . ' to ' . $booking['check_out'] . '). ' .
                    'To prevent double-booking, this request cannot be confirmed.'
                );
            } else {
                // Safe to confirm
                $confirmStmt = $pdo->prepare("UPDATE bookings SET booking_status = 'Confirmed' WHERE booking_id = :id");
                $confirmStmt->execute([':id' => $bookingId]);

                setFlash('success', 'Reservation #' . $bookingId . ' for ' . $booking['guest_name'] . ' has been CONFIRMED successfully!');
            }
        }
    }

    // 2. CANCEL BOOKING ACTION
    if ($action === 'cancel') {
        $cancelStmt = $pdo->prepare("UPDATE bookings SET booking_status = 'Cancelled' WHERE booking_id = :id");
        $cancelStmt->execute([':id' => $bookingId]);

        setFlash('warning', 'Reservation #' . $bookingId . ' has been marked as Cancelled.');
    }

    // 3. DELETE BOOKING ACTION
    if ($action === 'delete') {
        $delStmt = $pdo->prepare("DELETE FROM bookings WHERE booking_id = :id");
        $delStmt->execute([':id' => $bookingId]);

        setFlash('success', 'Reservation #' . $bookingId . ' has been permanently deleted.');
    }

    header('Location: bookings.php');
    exit;
}

// ----------------------------------------------------------------------------
// Fetch Filtered Bookings
// ----------------------------------------------------------------------------
$filterStatus = trim((string)($_GET['filter_status'] ?? ''));
$searchQuery  = trim((string)($_GET['q'] ?? ''));
$filterRoom   = (int)($_GET['room_id'] ?? 0);

$query = "SELECT b.*, r.room_name, r.room_type, r.image_url, r.price_per_night,
                 DATEDIFF(b.check_out, b.check_in) AS nights
          FROM bookings b
          JOIN rooms r ON b.room_id = r.room_id
          WHERE 1=1";
$params = [];

if (!empty($filterStatus) && in_array($filterStatus, ['Pending', 'Confirmed', 'Cancelled'])) {
    $query .= " AND b.booking_status = :status";
    $params[':status'] = $filterStatus;
}

if ($filterRoom > 0) {
    $query .= " AND b.room_id = :room_id";
    $params[':room_id'] = $filterRoom;
}

if (!empty($searchQuery)) {
    $query .= " AND (b.guest_name LIKE :q OR b.guest_email LIKE :q OR b.guest_contact LIKE :q OR b.booking_id = :exact_id)";
    $params[':q'] = '%' . $searchQuery . '%';
    $params[':exact_id'] = is_numeric($searchQuery) ? (int)$searchQuery : 0;
}

$query .= " ORDER BY b.booking_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// All rooms list for filter dropdown
$allRooms = $pdo->query('SELECT room_id, room_name FROM rooms ORDER BY room_name ASC')->fetchAll();
?>

<div class="container">
    <!-- Header Title -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 25px;">
        <div>
            <span class="section-badge" style="background:#e0f2fe; color:#0369a1;">Reservations Desk</span>
            <h1 style="font-size: 2rem; color: var(--primary-navy); margin-top: 6px;">
                Manage Guest Reservations
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Review incoming requests, confirm non-conflicting stays, and monitor booking statuses.
            </p>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div style="background: #ffffff; border-radius: var(--radius-lg); padding: 22px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); margin-bottom: 30px;">
        <form action="bookings.php" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 16px; align-items: flex-end;">
            <div class="form-group">
                <label for="filter_status">&#9203; Booking Status</label>
                <select name="filter_status" id="filter_status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?= $filterStatus === 'Pending' ? 'selected' : ''; ?>>Pending Only</option>
                    <option value="Confirmed" <?= $filterStatus === 'Confirmed' ? 'selected' : ''; ?>>Confirmed Only</option>
                    <option value="Cancelled" <?= $filterStatus === 'Cancelled' ? 'selected' : ''; ?>>Cancelled Only</option>
                </select>
            </div>

            <div class="form-group">
                <label for="room_id">&#127960; Filter by Room</label>
                <select name="room_id" id="room_id" class="form-control">
                    <option value="">All Rooms</option>
                    <?php foreach ($allRooms as $rm): ?>
                        <option value="<?= (int)$rm['room_id']; ?>" <?= $filterRoom === (int)$rm['room_id'] ? 'selected' : ''; ?>>
                            <?= e($rm['room_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="q">&#128269; Search Guest or ID</label>
                <input type="text" name="q" id="q" class="form-control" value="<?= e($searchQuery); ?>" placeholder="Name, Email, or Ref #">
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary">&#128269; Apply</button>
                <a href="bookings.php" class="btn btn-outline-navy">&#8635; Clear</a>
            </div>
        </form>
    </div>

    <!-- Bookings Table -->
    <div style="background: #ffffff; border-radius: var(--radius-lg); padding: 25px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Ref #</th>
                        <th>Guest Info</th>
                        <th>Room Allocated</th>
                        <th>Stay Dates</th>
                        <th>Nights / Guests</th>
                        <th>Total (LKR)</th>
                        <th>Status</th>
                        <th>Booked At</th>
                        <th style="text-align: right;">Review Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bookings)): ?>
                        <?php foreach ($bookings as $b): ?>
                            <?php 
                            $badge = match($b['booking_status']) {
                                'Confirmed' => 'badge-confirmed',
                                'Cancelled' => 'badge-cancelled',
                                default     => 'badge-pending'
                            };
                            ?>
                            <tr id="booking-<?= (int)$b['booking_id']; ?>">
                                <td>
                                    <strong>#<?= (int)$b['booking_id']; ?></strong>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary-navy);"><?= e($b['guest_name']); ?></div>
                                    <small style="color: var(--text-muted); display:block;"><?= e($b['guest_email']); ?></small>
                                    <small style="color: var(--text-muted);">&#128222; <?= e($b['guest_contact']); ?></small>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--primary-navy);"><?= e($b['room_name']); ?></div>
                                    <small style="color: var(--text-muted);"><?= e($b['room_type']); ?></small>
                                </td>
                                <td>
                                    <strong><?= date('M d, Y', strtotime($b['check_in'])); ?></strong><br>
                                    <small style="color: var(--text-muted);">to <?= date('M d, Y', strtotime($b['check_out'])); ?></small>
                                </td>
                                <td>
                                    <?= (int)$b['nights']; ?> Night(s)<br>
                                    <small style="color: var(--text-muted);">&#128101; <?= (int)$b['guests_count']; ?> Guests</small>
                                </td>
                                <td>
                                    <strong style="color: var(--accent-teal);"><?= formatLKR((float)$b['total_amount']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge <?= $badge; ?>"><?= e($b['booking_status']); ?></span>
                                </td>
                                <td>
                                    <small><?= date('M d, H:i', strtotime($b['booking_date'])); ?></small>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <?php if ($b['booking_status'] !== 'Confirmed'): ?>
                                        <!-- Confirm Form with Overlap Protection -->
                                        <form action="bookings.php" method="POST" style="display: inline-block;">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="action" value="confirm">
                                            <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-sm" style="background: #059669; border-color: #059669;" title="Confirm and Lock Stay">
                                                &#10003; Confirm
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($b['booking_status'] !== 'Cancelled'): ?>
                                        <!-- Cancel Form -->
                                        <form action="bookings.php" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to cancel booking #<?= (int)$b['booking_id']; ?>?');">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id']; ?>">
                                            <button type="submit" class="btn btn-sm" style="background: #d97706; border-color: #d97706; color: #fff;" title="Mark as Cancelled">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Delete Form -->
                                    <form action="bookings.php" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to permanently delete reservation #<?= (int)$b['booking_id']; ?>? This action cannot be undone.');">
                                        <?= csrfField(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Booking Permanently">
                                            &#128465; Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php if (!empty($b['special_requests'])): ?>
                                <tr style="background: #f8fafc;">
                                    <td colspan="9" style="padding: 8px 18px; font-size: 0.82rem; color: #475569; border-bottom: 2px solid var(--border-color);">
                                        <strong>Special Guest Requests:</strong> <?= e($b['special_requests']); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 35px;">
                                No bookings found matching your selected criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
