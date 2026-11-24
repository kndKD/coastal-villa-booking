<?php
/**
 * Serene Shores Coastal Villa - Rooms & Availability Search
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 * Requirement: Filter active rooms by dates, guests, type, max price. 
 *               Exclude rooms with overlapping Confirmed bookings: 
 *               check_in < :new_check_out AND check_out > :new_check_in
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Rooms & Suites Availability | Serene Shores Villa Mirissa';

// Retrieve and sanitize search query parameters
$checkIn   = trim((string)($_GET['check_in'] ?? ''));
$checkOut  = trim((string)($_GET['check_out'] ?? ''));
$guests    = (int)($_GET['guests'] ?? 0);
$roomType  = trim((string)($_GET['room_type'] ?? ''));
$maxPrice  = (float)($_GET['max_price'] ?? 0);
$sortBy    = trim((string)($_GET['sort_by'] ?? 'recommended'));

// Date validation
$hasDateFilter = false;
$dateError = null;

if (!empty($checkIn) && !empty($checkOut)) {
    $today = date('Y-m-d');
    if ($checkIn < $today) {
        $dateError = 'Check-in date cannot be in the past.';
    } elseif ($checkOut <= $checkIn) {
        $dateError = 'Check-out date must be at least one day after check-in.';
    } else {
        $hasDateFilter = true;
    }
}

// Build dynamic PDO query
$sql = "SELECT room_id, room_name, room_type, description, facilities, price_per_night, max_guests, image_url, room_status 
        FROM rooms 
        WHERE room_status = 'available'";

$params = [];

// 1. Exclude rooms with overlapping Confirmed bookings
if ($hasDateFilter) {
    $sql .= " AND room_id NOT IN (
                SELECT room_id 
                FROM bookings 
                WHERE booking_status = 'Confirmed' 
                  AND check_in < :new_check_out 
                  AND check_out > :new_check_in
              )";
    $params[':new_check_out'] = $checkOut;
    $params[':new_check_in']  = $checkIn;
}

// 2. Capacity filter
if ($guests > 0) {
    $sql .= " AND max_guests >= :guests";
    $params[':guests'] = $guests;
}

// 3. Room Type filter
if (!empty($roomType)) {
    $sql .= " AND room_type = :room_type";
    $params[':room_type'] = $roomType;
}

// 4. Max Price filter
if ($maxPrice > 0) {
    $sql .= " AND price_per_night <= :max_price";
    $params[':max_price'] = $maxPrice;
}

// 5. Sorting
$sql .= match($sortBy) {
    'price_low'   => " ORDER BY price_per_night ASC",
    'price_high'  => " ORDER BY price_per_night DESC",
    'guests_high' => " ORDER BY max_guests DESC",
    'name_asc'    => " ORDER BY room_name ASC",
    default       => " ORDER BY price_per_night DESC"
};

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Fetch distinct room types for dropdown
$typeQuery = $pdo->query("SELECT DISTINCT room_type FROM rooms WHERE room_status = 'available' ORDER BY room_type ASC");
$allRoomTypes = $typeQuery->fetchAll(PDO::FETCH_COLUMN);

// Fetch max room price for slider/range
$maxPriceQuery = $pdo->query("SELECT MAX(price_per_night) FROM rooms WHERE room_status = 'available'");
$highestPrice = (float)($maxPriceQuery->fetchColumn() ?: 150000);

require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top: 35px;">
    <div class="container">
        <!-- Page Title & Intro -->
        <div class="section-header" style="margin-bottom: 30px;">
            <span class="section-badge">Coastal Accommodations</span>
            <h1 class="section-title">Rooms, Suites & Beachfront Villas</h1>
            <div class="divider-accent"></div>
            <p class="section-desc">
                Select your preferred travel dates and guest requirements to discover real-time availability in Mirissa.
            </p>
        </div>

        <?php if (!empty($dateError)): ?>
            <div class="alert alert-warning">
                <span class="alert-icon">&#9888;</span>
                <span class="alert-text"><?= e($dateError); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($hasDateFilter): ?>
            <div class="alert alert-info" style="margin-bottom: 25px;">
                <span class="alert-icon">&#128197;</span>
                <span class="alert-text">
                    Displaying rooms available from <strong><?= e($checkIn); ?></strong> to <strong><?= e($checkOut); ?></strong>. 
                    All conflicting confirmed bookings have been filtered out automatically.
                </span>
            </div>
        <?php endif; ?>

        <!-- Advanced Filter & Search Box -->
        <div style="background: #ffffff; border-radius: var(--radius-lg); padding: 25px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); margin-bottom: 40px;">
            <form action="rooms.php" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap: 16px; align-items: flex-end;">
                <div class="form-group">
                    <label for="check_in">&#128197; Check-In</label>
                    <input type="date" name="check_in" id="check_in" class="form-control" value="<?= e($checkIn); ?>">
                </div>

                <div class="form-group">
                    <label for="check_out">&#128197; Check-Out</label>
                    <input type="date" name="check_out" id="check_out" class="form-control" value="<?= e($checkOut); ?>">
                </div>

                <div class="form-group">
                    <label for="guests">&#128101; Guests</label>
                    <select name="guests" id="guests" class="form-control">
                        <option value="">Any Guests</option>
                        <option value="1" <?= $guests === 1 ? 'selected' : ''; ?>>1+ Guest</option>
                        <option value="2" <?= $guests === 2 ? 'selected' : ''; ?>>2+ Guests</option>
                        <option value="3" <?= $guests === 3 ? 'selected' : ''; ?>>3+ Guests</option>
                        <option value="4" <?= $guests === 4 ? 'selected' : ''; ?>>4+ Guests</option>
                        <option value="6" <?= $guests === 6 ? 'selected' : ''; ?>>6+ Guests</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="room_type">&#128719; Room Type</label>
                    <select name="room_type" id="room_type" class="form-control">
                        <option value="">All Types</option>
                        <?php foreach ($allRoomTypes as $type): ?>
                            <option value="<?= e($type); ?>" <?= $roomType === $type ? 'selected' : ''; ?>><?= e($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="sort_by">&#8645; Sort By</label>
                    <select name="sort_by" id="sort_by" class="form-control">
                        <option value="recommended" <?= $sortBy === 'recommended' ? 'selected' : ''; ?>>Recommended</option>
                        <option value="price_low" <?= $sortBy === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?= $sortBy === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="guests_high" <?= $sortBy === 'guests_high' ? 'selected' : ''; ?>>Capacity: High to Low</option>
                        <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                    </select>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary">&#128269; Filter</button>
                    <a href="rooms.php" class="btn btn-outline-navy" title="Clear All Filters">&#8635; Reset</a>
                </div>
            </form>
        </div>

        <!-- Room Listing Grid -->
        <?php if (!empty($rooms)): ?>
            <div class="rooms-grid">
                <?php foreach ($rooms as $room): ?>
                    <?php
                    // Build booking link preserving date params if set
                    $bookingUrl = 'book.php?room_id=' . (int)$room['room_id'];
                    if ($hasDateFilter) {
                        $bookingUrl .= '&check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . urlencode((string)$guests);
                    }
                    ?>
                    <div class="room-card">
                        <div class="room-image-wrap">
                            <img src="<?= e($room['image_url']); ?>" alt="<?= e($room['room_name']); ?>">
                            <span class="room-badge"><?= e($room['room_type']); ?></span>
                        </div>

                        <div class="room-body">
                            <h3 class="room-title"><?= e($room['room_name']); ?></h3>
                            <div class="room-capacity">
                                &#128101; Accommodates up to <strong><?= (int)$room['max_guests']; ?> Guests</strong>
                            </div>
                            <p class="room-desc"><?= e($room['description']); ?></p>

                            <div class="room-facilities">
                                <?php 
                                $facilitiesList = array_map('trim', explode(',', $room['facilities']));
                                foreach ($facilitiesList as $fac): 
                                ?>
                                    <span class="facility-pill">&#10003; <?= e($fac); ?></span>
                                <?php endforeach; ?>
                            </div>

                            <div class="room-footer">
                                <div class="room-price">
                                    <span class="price-val"><?= formatLKR((float)$room['price_per_night']); ?></span>
                                    <span class="price-sub">per night &bull; all taxes included</span>
                                </div>
                                <a href="<?= e($bookingUrl); ?>" class="btn btn-primary">Book Now &rarr;</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                <div style="font-size: 3rem; margin-bottom: 12px; color: var(--accent-sand);">&#128719;</div>
                <h3 style="color: var(--primary-navy); margin-bottom: 10px;">No Rooms Match Your Selected Criteria</h3>
                <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 20px;">
                    All available suites for this date range or filter combination may already be booked or exceed your guest preferences. Try adjusting your dates or resetting the filters.
                </p>
                <a href="rooms.php" class="btn btn-primary">&#8635; Reset Filters & View All Rooms</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
