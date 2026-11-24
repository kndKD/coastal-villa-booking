<?php
/**
 * Serene Shores Coastal Villa - Admin Dashboard
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 * Security: Protected by requireAdmin()
 */

declare(strict_types=1);

$pageTitle = 'Executive Dashboard | Serene Shores Admin';
require_once __DIR__ . '/includes/admin_header.php';

// Calculate Key Dashboard Metrics
// 1. Booking counts
$countsStmt = $pdo->query(
    "SELECT 
        COUNT(*) AS total_bookings,
        SUM(CASE WHEN booking_status = 'Pending' THEN 1 ELSE 0 END) AS pending_bookings,
        SUM(CASE WHEN booking_status = 'Confirmed' THEN 1 ELSE 0 END) AS confirmed_bookings,
        SUM(CASE WHEN booking_status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_bookings,
        SUM(CASE WHEN booking_status = 'Confirmed' THEN total_amount ELSE 0 END) AS total_revenue
     FROM bookings"
);
$metrics = $countsStmt->fetch();

$totalBookings     = (int)($metrics['total_bookings'] ?? 0);
$pendingBookings   = (int)($metrics['pending_bookings'] ?? 0);
$confirmedBookings = (int)($metrics['confirmed_bookings'] ?? 0);
$cancelledBookings = (int)($metrics['cancelled_bookings'] ?? 0);
$totalRevenue      = (float)($metrics['total_revenue'] ?? 0);

// 2. Room counts
$roomStatsStmt = $pdo->query(
    "SELECT 
        COUNT(*) AS total_rooms,
        SUM(CASE WHEN room_status = 'available' THEN 1 ELSE 0 END) AS active_rooms
     FROM rooms"
);
$roomStats = $roomStatsStmt->fetch();
$totalRooms  = (int)($roomStats['total_rooms'] ?? 0);
$activeRooms = (int)($roomStats['active_rooms'] ?? 0);

// 3. Recent Bookings (Latest 6)
$recentStmt = $pdo->query(
    "SELECT b.booking_id, b.guest_name, b.guest_email, b.guest_contact, b.check_in, b.check_out, 
            b.total_amount, b.booking_status, b.booking_date,
            r.room_name, r.room_type
     FROM bookings b
     JOIN rooms r ON b.room_id = r.room_id
     ORDER BY b.booking_date DESC
     LIMIT 6"
);
$recentBookings = $recentStmt->fetchAll();
?>

<div class="container">
    <!-- Header Title -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 28px;">
        <div>
            <span class="section-badge" style="background:#e0f2fe; color:#0369a1;">Administration</span>
            <h1 style="font-size: 2.1rem; color: var(--primary-navy); margin-top: 6px;">
                Operations & Booking Dashboard
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Real-time oversight of guest reservations, revenue, room allocations, and pending requests in Mirissa.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="rooms.php?action=create" class="btn btn-sand btn-sm">&#43; Add New Room</a>
            <a href="bookings.php" class="btn btn-primary btn-sm">&#128220; All Bookings</a>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="admin-metrics-grid">
        <div class="metric-card navy">
            <div class="metric-icon" style="color: var(--primary-navy);">&#128202;</div>
            <div class="metric-info">
                <h3><?= $totalBookings; ?></h3>
                <p>Total Reservations</p>
            </div>
        </div>

        <div class="metric-card orange">
            <div class="metric-icon" style="color: #d97706;">&#9203;</div>
            <div class="metric-info">
                <h3 style="color: #d97706;"><?= $pendingBookings; ?></h3>
                <p>Pending Review</p>
            </div>
        </div>

        <div class="metric-card teal">
            <div class="metric-icon" style="color: #059669;">&#9989;</div>
            <div class="metric-info">
                <h3 style="color: #059669;"><?= $confirmedBookings; ?></h3>
                <p>Confirmed Stays</p>
            </div>
        </div>

        <div class="metric-card sand">
            <div class="metric-icon" style="color: #b45309;">&#128178;</div>
            <div class="metric-info">
                <h3 style="font-size: 1.35rem; color: #b45309;"><?= formatLKR($totalRevenue); ?></h3>
                <p>Confirmed Revenue</p>
            </div>
        </div>

        <div class="metric-card teal">
            <div class="metric-icon" style="color: var(--accent-teal);">&#127960;</div>
            <div class="metric-info">
                <h3><?= $activeRooms; ?> / <?= $totalRooms; ?></h3>
                <p>Active Villa Suites</p>
            </div>
        </div>
    </div>

    <?php if ($pendingBookings > 0): ?>
        <div class="alert alert-warning" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="alert-icon" style="font-size: 1.5rem;">&#9888;</span>
                <span>
                    <strong>Action Required:</strong> You have <strong><?= $pendingBookings; ?></strong> pending booking request(s) awaiting confirmation.
                </span>
            </div>
            <a href="bookings.php?filter_status=Pending" class="btn btn-primary btn-sm" style="background:#b45309; border:none;">
                Review Pending
            </a>
        </div>
    <?php endif; ?>

    <!-- Recent Reservations Section -->
    <div style="background: #ffffff; border-radius: var(--radius-lg); padding: 25px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); margin-top: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="font-size: 1.35rem; color: var(--primary-navy); margin: 0;">
                Recent Booking Inquiries
            </h2>
            <a href="bookings.php" style="font-size: 0.9rem; font-weight: 600;">View All Reservations &rarr;</a>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Ref #</th>
                        <th>Guest</th>
                        <th>Room & Category</th>
                        <th>Dates</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentBookings)): ?>
                        <?php foreach ($recentBookings as $rb): ?>
                            <?php 
                            $badge = match($rb['booking_status']) {
                                'Confirmed' => 'badge-confirmed',
                                'Cancelled' => 'badge-cancelled',
                                default     => 'badge-pending'
                            };
                            ?>
                            <tr>
                                <td><strong>#<?= (int)$rb['booking_id']; ?></strong></td>
                                <td>
                                    <strong><?= e($rb['guest_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($rb['guest_contact']); ?></small>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--primary-navy);"><?= e($rb['room_name']); ?></div>
                                    <small style="color: var(--text-muted);"><?= e($rb['room_type']); ?></small>
                                </td>
                                <td>
                                    <?= date('M d', strtotime($rb['check_in'])); ?> &ndash; <?= date('M d, Y', strtotime($rb['check_out'])); ?>
                                </td>
                                <td>
                                    <strong><?= formatLKR((float)$rb['total_amount']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge <?= $badge; ?>"><?= e($rb['booking_status']); ?></span>
                                </td>
                                <td>
                                    <small><?= date('M d, H:i', strtotime($rb['booking_date'])); ?></small>
                                </td>
                                <td style="text-align: right;">
                                    <a href="bookings.php#booking-<?= (int)$rb['booking_id']; ?>" class="btn btn-outline-navy btn-sm">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No bookings recorded yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
