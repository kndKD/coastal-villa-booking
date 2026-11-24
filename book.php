<?php
/**
 * Serene Shores Coastal Villa - Room Reservation Form
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 * Security: requireLogin(), Overlap Prevention, Server-Side Cost Recalculation, CSRF
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Guest authentication required
$roomId = (int)($_GET['room_id'] ?? $_POST['room_id'] ?? 0);
if ($roomId <= 0) {
    setFlash('warning', 'Please select a room to reserve.');
    header('Location: rooms.php');
    exit;
}

requireLogin('book.php?room_id=' . $roomId);

// Fetch selected room details
$roomStmt = $pdo->prepare('SELECT * FROM rooms WHERE room_id = :room_id AND room_status = "available" LIMIT 1');
$roomStmt->execute([':room_id' => $roomId]);
$room = $roomStmt->fetch();

if (!$room) {
    setFlash('danger', 'The selected room is currently unavailable or inactive.');
    header('Location: rooms.php');
    exit;
}

$pageTitle = 'Reserve ' . e($room['room_name']) . ' | Serene Shores Villa';
$user = currentUser();

$errors = [];
$checkIn   = trim((string)($_POST['check_in'] ?? $_GET['check_in'] ?? ''));
$checkOut  = trim((string)($_POST['check_out'] ?? $_GET['check_out'] ?? ''));
$guests    = (int)($_POST['guests_count'] ?? $_GET['guests'] ?? 2);
$specialReq = trim((string)($_POST['special_requests'] ?? ''));
$guestName = trim((string)($_POST['guest_name'] ?? $user['name'] ?? ''));
$guestEmail = trim((string)($_POST['guest_email'] ?? $user['email'] ?? ''));
$guestContact = trim((string)($_POST['guest_contact'] ?? $user['contact_number'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Security token invalid or expired. Please submit the form again.';
    }

    $today = date('Y-m-d');

    // Input Validation
    if (empty($checkIn)) {
        $errors[] = 'Please select a valid check-in date.';
    } elseif ($checkIn < $today) {
        $errors[] = 'Check-in date cannot be in the past.';
    }

    if (empty($checkOut)) {
        $errors[] = 'Please select a valid check-out date.';
    } elseif ($checkOut <= $checkIn) {
        $errors[] = 'Check-out date must be at least one day after check-in.';
    }

    if ($guests < 1) {
        $errors[] = 'Guest count must be at least 1.';
    } elseif ($guests > (int)$room['max_guests']) {
        $errors[] = 'This room accommodates a maximum of ' . (int)$room['max_guests'] . ' guests.';
    }

    if (empty($guestName)) {
        $errors[] = 'Guest contact name is required.';
    }

    if (empty($guestEmail) || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid guest email address is required.';
    }

    if (empty($guestContact)) {
        $errors[] = 'A valid phone number is required for booking confirmation.';
    }

    // Check for Overlapping Confirmed Bookings
    if (empty($errors)) {
        $overlapStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM bookings 
             WHERE room_id = :room_id 
               AND booking_status = 'Confirmed' 
               AND check_in < :check_out 
               AND check_out > :check_in"
        );
        $overlapStmt->execute([
            ':room_id'   => $roomId,
            ':check_out' => $checkOut,
            ':check_in'  => $checkIn
        ]);

        if ((int)$overlapStmt->fetchColumn() > 0) {
            $errors[] = 'Unfortunately, this room has already been confirmed for another guest during your selected dates. Please adjust your dates or choose another luxury suite.';
        }
    }

    // Process Booking
    if (empty($errors)) {
        $d1 = new DateTime($checkIn);
        $d2 = new DateTime($checkOut);
        $numNights = (int)$d1->diff($d2)->days;

        if ($numNights <= 0) {
            $errors[] = 'Invalid date range calculation.';
        } else {
            // Strict server-side cost calculation
            $serverCalculatedTotal = $numNights * (float)$room['price_per_night'];

            $insertBooking = $pdo->prepare(
                'INSERT INTO bookings 
                 (user_id, room_id, guest_name, guest_email, guest_contact, guests_count, special_requests, check_in, check_out, total_amount, booking_status) 
                 VALUES 
                 (:user_id, :room_id, :guest_name, :guest_email, :guest_contact, :guests_count, :special_requests, :check_in, :check_out, :total_amount, "Pending")'
            );

            $executed = $insertBooking->execute([
                ':user_id'          => $user['user_id'],
                ':room_id'          => $roomId,
                ':guest_name'       => $guestName,
                ':guest_email'      => $guestEmail,
                ':guest_contact'    => $guestContact,
                ':guests_count'     => $guests,
                ':special_requests' => $specialReq ?: null,
                ':check_in'         => $checkIn,
                ':check_out'        => $checkOut,
                ':total_amount'     => $serverCalculatedTotal
            ]);

            if ($executed) {
                $bookingId = $pdo->lastInsertId();
                setFlash(
                    'success',
                    'Reservation request received successfully! Booking Reference #' . $bookingId . 
                    ' is currently marked as Pending. Our Mirissa reservations desk will review and confirm shortly.'
                );
                header('Location: my_bookings.php');
                exit;
            } else {
                $errors[] = 'Could not save reservation due to a database error. Please retry.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 20px;">
    <!-- Breadcrumb -->
    <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 20px;">
        <a href="index.php">Home</a> &rsaquo; 
        <a href="rooms.php">Rooms</a> &rsaquo; 
        <span style="color: var(--primary-navy); font-weight: 600;">Reserve <?= e($room['room_name']); ?></span>
    </p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <span class="alert-icon">&#9888;</span>
            <div class="alert-text">
                <ul style="margin: 0; padding-left: 18px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="booking-grid">
        <!-- Room Overview Card (Sticky Sidebar) -->
        <div class="booking-summary-card">
            <div style="height: 220px; overflow: hidden; position: relative;">
                <img src="<?= e($room['image_url']); ?>" alt="<?= e($room['room_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <span class="room-badge"><?= e($room['room_type']); ?></span>
            </div>

            <div style="padding: 24px;">
                <h3 style="font-size: 1.35rem; color: var(--primary-navy); margin-bottom: 8px;">
                    <?= e($room['room_name']); ?>
                </h3>
                <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 15px;">
                    &#128101; Maximum Capacity: <strong><?= (int)$room['max_guests']; ?> Guests</strong>
                </p>
                <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 18px;">
                    <?= e($room['description']); ?>
                </p>

                <h4 style="font-size: 0.88rem; text-transform: uppercase; letter-spacing: 1px; color: var(--primary-navy); margin-bottom: 10px;">
                    Included Amenities:
                </h4>
                <div class="room-facilities" style="margin-bottom: 20px;">
                    <?php 
                    $facList = array_map('trim', explode(',', $room['facilities']));
                    foreach ($facList as $f): 
                    ?>
                        <span class="facility-pill">&#10003; <?= e($f); ?></span>
                    <?php endforeach; ?>
                </div>

                <div style="padding-top: 15px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: baseline;">
                    <span style="font-size: 0.88rem; color: var(--text-muted);">Rate per Night:</span>
                    <span style="font-size: 1.3rem; font-weight: 800; color: var(--accent-teal);">
                        <?= formatLKR((float)$room['price_per_night']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Interactive Booking Form -->
        <div class="booking-form-card">
            <h2 style="font-size: 1.6rem; color: var(--primary-navy); margin-bottom: 6px;">
                Complete Your Villa Stay
            </h2>
            <p style="font-size: 0.92rem; color: var(--text-muted); margin-bottom: 25px;">
                Enter your travel dates and stay preferences. Total cost is dynamically calculated below.
            </p>

            <form action="book.php" method="POST" id="bookingForm" novalidate>
                <?= csrfField(); ?>
                <input type="hidden" name="room_id" value="<?= (int)$room['room_id']; ?>">
                <input type="hidden" id="price_per_night_input" value="<?= (float)$room['price_per_night']; ?>">
                <input type="hidden" name="total_amount" id="total_amount_input" value="0">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                    <div class="form-group">
                        <label for="bookCheckIn">&#128197; Check-In Date *</label>
                        <input type="date" name="check_in" id="bookCheckIn" class="form-control" value="<?= e($checkIn); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="bookCheckOut">&#128197; Check-Out Date *</label>
                        <input type="date" name="check_out" id="bookCheckOut" class="form-control" value="<?= e($checkOut); ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label for="guests_count">&#128101; Number of Guests * (Max <?= (int)$room['max_guests']; ?>)</label>
                    <select name="guests_count" id="guests_count" class="form-control" max="<?= (int)$room['max_guests']; ?>" required>
                        <?php for ($i = 1; $i <= (int)$room['max_guests']; $i++): ?>
                            <option value="<?= $i; ?>" <?= $guests === $i ? 'selected' : ''; ?>>
                                <?= $i; ?> <?= $i === 1 ? 'Guest' : 'Guests'; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Live Dynamic Cost Breakdown Box -->
                <div class="price-summary-box">
                    <h4 style="color: var(--primary-navy); font-size: 0.95rem; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        &#129534; Dynamic Pricing Breakdown
                    </h4>
                    <div class="price-row">
                        <span>Nightly Room Rate:</span>
                        <span><?= formatLKR((float)$room['price_per_night']); ?></span>
                    </div>
                    <div class="price-row">
                        <span>Calculated Stay Duration:</span>
                        <span><strong id="numNights">0</strong> Night(s)</span>
                    </div>
                    <div class="price-row">
                        <span>Government Tourism Tax & Service:</span>
                        <span style="color: #10b981;">Included (0%)</span>
                    </div>
                    <div class="price-row total">
                        <span>Estimated Total Amount:</span>
                        <span id="totalCostDisplay">LKR 0.00</span>
                    </div>
                </div>

                <h3 style="font-size: 1.15rem; color: var(--primary-navy); margin: 25px 0 15px;">
                    Guest Contact Details
                </h3>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="guest_name">&#128100; Primary Guest Full Name *</label>
                    <input type="text" name="guest_name" id="guest_name" class="form-control" value="<?= e($guestName); ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="guest_email">&#9993; Confirmation Email *</label>
                        <input type="email" name="guest_email" id="guest_email" class="form-control" value="<?= e($guestEmail); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="guest_contact">&#128222; Mobile / WhatsApp *</label>
                        <input type="tel" name="guest_contact" id="guest_contact" class="form-control" value="<?= e($guestContact); ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="special_requests">&#9997; Special Requests or Arrival Notes (Optional)</label>
                    <textarea name="special_requests" id="special_requests" rows="3" class="form-control" placeholder="e.g. Honeymoon setup, airport transfer request from CMB, dietary requirements, or estimated check-in time"><?= e($specialReq); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 14px; font-size: 1.05rem;">
                    &#128274; Confirm Reservation Request
                </button>
                <p style="font-size: 0.78rem; text-align: center; color: var(--text-muted); margin-top: 10px;">
                    No advance payment charged today. Reservation will be created as <strong>Pending</strong> awaiting villa desk review.
                </p>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
