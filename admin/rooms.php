<?php
/**
 * Serene Shores Coastal Villa - Admin Room Management (CRUD)
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 * Security: requireAdmin(), CSRF Validation, Referential Integrity Handling
 */

declare(strict_types=1);

$pageTitle = 'Manage Villa Rooms & Suites | Admin Portal';
require_once __DIR__ . '/includes/admin_header.php';

$action = (string)($_GET['action'] ?? 'list');
$editId = (int)($_GET['id'] ?? 0);
$errors = [];

// ----------------------------------------------------------------------------
// POST Request Handlers: Add, Edit, Toggle Status, Delete
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Security validation token expired. Please retry.');
        header('Location: rooms.php');
        exit;
    }

    $postAction = (string)($_POST['post_action'] ?? '');

    // 1. ADD / CREATE ROOM
    if ($postAction === 'create') {
        $name        = trim((string)($_POST['room_name'] ?? ''));
        $type        = trim((string)($_POST['room_type'] ?? ''));
        $desc        = trim((string)($_POST['description'] ?? ''));
        $facilities  = trim((string)($_POST['facilities'] ?? ''));
        $price       = (float)($_POST['price_per_night'] ?? 0);
        $maxGuests   = (int)($_POST['max_guests'] ?? 1);
        $imageUrl    = trim((string)($_POST['image_url'] ?? 'assets/images/room1.jpg'));
        $status      = ($_POST['room_status'] ?? 'available') === 'inactive' ? 'inactive' : 'available';

        if (empty($name) || empty($type) || empty($desc) || empty($facilities) || $price <= 0 || $maxGuests <= 0) {
            $errors[] = 'Please fill out all required fields with valid values.';
        } else {
            $insertStmt = $pdo->prepare(
                'INSERT INTO rooms (room_name, room_type, description, facilities, price_per_night, max_guests, image_url, room_status) 
                 VALUES (:name, :type, :desc, :facilities, :price, :max_guests, :image_url, :status)'
            );
            $insertStmt->execute([
                ':name'       => $name,
                ':type'       => $type,
                ':desc'       => $desc,
                ':facilities' => $facilities,
                ':price'      => $price,
                ':max_guests' => $maxGuests,
                ':image_url'  => $imageUrl,
                ':status'     => $status
            ]);

            setFlash('success', 'New room "' . $name . '" has been successfully registered!');
            header('Location: rooms.php');
            exit;
        }
    }

    // 2. UPDATE / EDIT ROOM
    if ($postAction === 'update') {
        $roomId      = (int)($_POST['room_id'] ?? 0);
        $name        = trim((string)($_POST['room_name'] ?? ''));
        $type        = trim((string)($_POST['room_type'] ?? ''));
        $desc        = trim((string)($_POST['description'] ?? ''));
        $facilities  = trim((string)($_POST['facilities'] ?? ''));
        $price       = (float)($_POST['price_per_night'] ?? 0);
        $maxGuests   = (int)($_POST['max_guests'] ?? 1);
        $imageUrl    = trim((string)($_POST['image_url'] ?? 'assets/images/room1.jpg'));
        $status      = ($_POST['room_status'] ?? 'available') === 'inactive' ? 'inactive' : 'available';

        if ($roomId <= 0 || empty($name) || empty($type) || empty($desc) || empty($facilities) || $price <= 0 || $maxGuests <= 0) {
            $errors[] = 'Please complete all required fields with valid information.';
        } else {
            $updateStmt = $pdo->prepare(
                'UPDATE rooms 
                 SET room_name = :name, room_type = :type, description = :desc, facilities = :facilities, 
                     price_per_night = :price, max_guests = :max_guests, image_url = :image_url, room_status = :status 
                 WHERE room_id = :id'
            );
            $updateStmt->execute([
                ':name'       => $name,
                ':type'       => $type,
                ':desc'       => $desc,
                ':facilities' => $facilities,
                ':price'      => $price,
                ':max_guests' => $maxGuests,
                ':image_url'  => $imageUrl,
                ':status'     => $status,
                ':id'         => $roomId
            ]);

            setFlash('success', 'Room details for "' . $name . '" have been updated.');
            header('Location: rooms.php');
            exit;
        }
    }

    // 3. TOGGLE STATUS (AVAILABLE / INACTIVE)
    if ($postAction === 'toggle_status') {
        $roomId = (int)($_POST['room_id'] ?? 0);
        $toggleStmt = $pdo->prepare(
            "UPDATE rooms 
             SET room_status = CASE WHEN room_status = 'available' THEN 'inactive' ELSE 'available' END 
             WHERE room_id = :id"
        );
        $toggleStmt->execute([':id' => $roomId]);

        setFlash('info', 'Room status updated successfully.');
        header('Location: rooms.php');
        exit;
    }

    // 4. DELETE ROOM (WITH REFERENTIAL INTEGRITY SAFEGUARD)
    if ($postAction === 'delete') {
        $roomId = (int)($_POST['room_id'] ?? 0);

        // Check if any bookings link to this room
        $checkBookings = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE room_id = :id');
        $checkBookings->execute([':id' => $roomId]);
        $bookingCount = (int)$checkBookings->fetchColumn();

        $force = isset($_POST['force']) && $_POST['force'] === '1';

        if ($bookingCount > 0 && !$force) {
            // Soft deactivate to preserve historical guest booking integrity
            $softDeactivate = $pdo->prepare('UPDATE rooms SET room_status = "inactive" WHERE room_id = :id');
            $softDeactivate->execute([':id' => $roomId]);

            setFlash('warning', 'Room #' . $roomId . ' is linked to ' . $bookingCount . ' historical booking(s). It was deactivated to preserve guest booking records. If you wish to permanently delete it, remove its bookings first.');
        } else {
            if ($bookingCount > 0 && $force) {
                $delBookings = $pdo->prepare('DELETE FROM bookings WHERE room_id = :id');
                $delBookings->execute([':id' => $roomId]);
            }
            $delStmt = $pdo->prepare('DELETE FROM rooms WHERE room_id = :id');
            $delStmt->execute([':id' => $roomId]);
            setFlash('success', 'Room #' . $roomId . ' has been permanently deleted.');
        }

        header('Location: rooms.php');
        exit;
    }
}

// ----------------------------------------------------------------------------
// Room Edit Target Data Fetching
// ----------------------------------------------------------------------------
$editRoom = null;
if ($action === 'edit' && $editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE room_id = :id LIMIT 1');
    $stmt->execute([':id' => $editId]);
    $editRoom = $stmt->fetch();
}

// Fetch all rooms for table listing
$allRoomsStmt = $pdo->query('SELECT * FROM rooms ORDER BY room_id ASC');
$allRooms = $allRoomsStmt->fetchAll();
?>

<div class="container">
    <!-- Breadcrumb & Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 25px;">
        <div>
            <span class="section-badge" style="background:#e0f2fe; color:#0369a1;">Inventory Management</span>
            <h1 style="font-size: 2rem; color: var(--primary-navy); margin-top: 6px;">
                Manage Villa Rooms & Suites
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Add new suites, update nightly pricing, modify amenities, and toggle availability.
            </p>
        </div>
        <div>
            <?php if ($action !== 'create' && $action !== 'edit'): ?>
                <a href="rooms.php?action=create" class="btn btn-primary">&#43; Add New Villa Suite</a>
            <?php else: ?>
                <a href="rooms.php" class="btn btn-outline-navy">&larr; Back to Room List</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <span class="alert-icon">&#9888;</span>
            <div class="alert-text">
                <ul style="margin: 0; padding-left: 18px;">
                    <?php foreach ($errors as $e): ?>
                        <li><?= e($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- ADD OR EDIT FORM VIEW -->
    <?php if ($action === 'create' || ($action === 'edit' && $editRoom)): ?>
        <div style="background: #ffffff; border-radius: var(--radius-lg); padding: 35px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); margin-bottom: 40px; max-width: 860px; margin: 0 auto 40px;">
            <h2 style="font-size: 1.4rem; color: var(--primary-navy); margin-bottom: 20px;">
                <?= $action === 'edit' ? 'Edit Suite: ' . e($editRoom['room_name']) : 'Register New Villa Suite'; ?>
            </h2>

            <form action="rooms.php" method="POST">
                <?= csrfField(); ?>
                <input type="hidden" name="post_action" value="<?= $action === 'edit' ? 'update' : 'create'; ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="room_id" value="<?= (int)$editRoom['room_id']; ?>">
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 18px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="room_name">Suite / Villa Name *</label>
                        <input type="text" name="room_name" id="room_name" class="form-control" required 
                               value="<?= e($editRoom['room_name'] ?? ''); ?>" placeholder="e.g. Mirissa Sunset Lagoon Villa">
                    </div>

                    <div class="form-group">
                        <label for="room_type">Category / Type *</label>
                        <input type="text" name="room_type" id="room_type" class="form-control" required 
                               value="<?= e($editRoom['room_type'] ?? 'Beachfront Villa'); ?>" placeholder="e.g. Ocean Suite, Beachfront Villa">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="description">Detailed Description *</label>
                    <textarea name="description" id="description" rows="3" class="form-control" required placeholder="Describe the layout, ambiance, and coastal views..."><?= e($editRoom['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="facilities">Amenities & Facilities (Comma-separated) *</label>
                    <input type="text" name="facilities" id="facilities" class="form-control" required 
                           value="<?= e($editRoom['facilities'] ?? 'King Bed, Ocean View, Free Wi-Fi, Air Conditioning, Breakfast Included'); ?>"
                           placeholder="e.g. King Bed, Ocean View, Plunge Pool, Wi-Fi, A/C, Jacuzzi">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="price_per_night">Price per Night (LKR) *</label>
                        <input type="number" step="0.01" name="price_per_night" id="price_per_night" class="form-control" required 
                               value="<?= e((string)($editRoom['price_per_night'] ?? '45000.00')); ?>">
                    </div>

                    <div class="form-group">
                        <label for="max_guests">Max Guests Allowed *</label>
                        <input type="number" name="max_guests" id="max_guests" min="1" max="15" class="form-control" required 
                               value="<?= e((string)($editRoom['max_guests'] ?? '2')); ?>">
                    </div>

                    <div class="form-group">
                        <label for="room_status">Availability Status *</label>
                        <select name="room_status" id="room_status" class="form-control">
                            <option value="available" <?= ($editRoom['room_status'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="inactive" <?= ($editRoom['room_status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive / Maintenance</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label for="image_url">Image Path / URL *</label>
                    <input type="text" name="image_url" id="image_url" class="form-control" required 
                           value="<?= e($editRoom['image_url'] ?? 'assets/images/room1.jpg'); ?>"
                           placeholder="assets/images/room1.jpg or external photo URL">
                </div>

                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary">
                        <?= $action === 'edit' ? '&#128190; Update Room Details' : '&#43; Save New Suite'; ?>
                    </button>
                    <a href="rooms.php" class="btn btn-outline-navy">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- ALL ROOMS TABLE VIEW -->
    <div style="background: #ffffff; border-radius: var(--radius-lg); padding: 25px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Preview</th>
                        <th>Room Name & Category</th>
                        <th>Capacity</th>
                        <th>Price / Night</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($allRooms)): ?>
                        <?php foreach ($allRooms as $rm): ?>
                            <tr>
                                <td><strong>#<?= (int)$rm['room_id']; ?></strong></td>
                                <td>
                                    <img src="../<?= e($rm['image_url']); ?>" alt="<?= e($rm['room_name']); ?>" 
                                         style="width: 60px; height: 45px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border-color);">
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary-navy);"><?= e($rm['room_name']); ?></div>
                                    <small style="color: var(--text-muted);"><?= e($rm['room_type']); ?></small>
                                </td>
                                <td>
                                    &#128101; Up to <?= (int)$rm['max_guests']; ?> Guests
                                </td>
                                <td>
                                    <strong style="color: var(--accent-teal);"><?= formatLKR((float)$rm['price_per_night']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge <?= $rm['room_status'] === 'available' ? 'badge-confirmed' : 'badge-inactive'; ?>">
                                        <?= e($rm['room_status']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="rooms.php?action=edit&id=<?= (int)$rm['room_id']; ?>" class="btn btn-outline-navy btn-sm" title="Edit Suite Information">
                                        Edit
                                    </a>

                                    <!-- Quick Status Toggle -->
                                    <form action="rooms.php" method="POST" style="display: inline-block;">
                                        <?= csrfField(); ?>
                                        <input type="hidden" name="post_action" value="toggle_status">
                                        <input type="hidden" name="room_id" value="<?= (int)$rm['room_id']; ?>">
                                        <button type="submit" class="btn btn-sm <?= $rm['room_status'] === 'available' ? 'btn-sand' : 'btn-primary'; ?>" 
                                                title="Toggle Active / Inactive Status">
                                            <?= $rm['room_status'] === 'available' ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>

                                    <!-- Safe Delete -->
                                    <form action="rooms.php" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this room? If historical bookings exist, it will be deactivated to preserve audit records.');">
                                        <?= csrfField(); ?>
                                        <input type="hidden" name="post_action" value="delete">
                                        <input type="hidden" name="room_id" value="<?= (int)$rm['room_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Room">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No rooms currently exist in the database.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
