<?php
/**
 * Serene Shores Coastal Villa - Home Page
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 * Tech Stack: PHP 8+, MySQL (PDO), HTML5, CSS3, Vanilla JavaScript
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Serene Shores Coastal Villa | Luxury Mirissa Beachfront Retreat';

// Fetch 3 featured available rooms from database
$stmt = $pdo->prepare(
    "SELECT room_id, room_name, room_type, description, facilities, price_per_night, max_guests, image_url 
     FROM rooms 
     WHERE room_status = 'available' 
     ORDER BY price_per_night DESC 
     LIMIT 3"
);
$stmt->execute();
$featuredRooms = $stmt->fetchAll();

// Distinct room types for quick search dropdown
$typeStmt = $pdo->query("SELECT DISTINCT room_type FROM rooms WHERE room_status = 'available'");
$roomTypes = $typeStmt->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<section class="hero">
    <div class="container hero-content">
        <span class="hero-subtitle">Mirissa &bull; Southern Coast &bull; Sri Lanka</span>
        <h1 class="hero-title">Experience Coastal Luxury on Mirissa’s Golden Sands</h1>
        <p class="hero-description">
            Wake up to gentle Indian Ocean waves, private heated plunge pools, and breathtaking tropical sunsets. 
            Serene Shores Villa delivers an authentic beachfront sanctuary engineered for unmatched serenity.
        </p>
        <div class="hero-actions">
            <a href="rooms.php" class="btn btn-sand">&#127960; Browse Rooms & Suites</a>
            <a href="#about" class="btn btn-outline">&#127754; Discover Mirissa</a>
        </div>
    </div>
</section>

<!-- Quick Availability Search Widget -->
<div class="container search-widget-wrapper">
    <div class="search-widget">
        <form action="rooms.php" method="GET" class="search-form">
            <div class="form-group">
                <label for="check_in">&#128197; Check-In Date</label>
                <input type="date" name="check_in" id="check_in" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="check_out">&#128197; Check-Out Date</label>
                <input type="date" name="check_out" id="check_out" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="guests">&#128101; Guests</label>
                <select name="guests" id="guests" class="form-control">
                    <option value="1">1 Guest</option>
                    <option value="2" selected>2 Guests</option>
                    <option value="3">3 Guests</option>
                    <option value="4">4 Guests</option>
                    <option value="5">5+ Guests</option>
                </select>
            </div>

            <div class="form-group">
                <label for="room_type">&#128719; Room Type</label>
                <select name="room_type" id="room_type" class="form-control">
                    <option value="">All Room Types</option>
                    <?php foreach ($roomTypes as $type): ?>
                        <option value="<?= e($type); ?>"><?= e($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-block">&#128269; Check Rates</button>
            </div>
        </form>
    </div>
</div>

<!-- Mirissa Beach & Villa Introduction -->
<section class="section section-light" id="about">
    <div class="container about-grid">
        <div class="about-image">
            <img src="assets/images/about.jpg" alt="Serene Shores Villa Sunset Terrace in Mirissa">
            <div class="about-badge-floating">
                <strong>Mirissa Beachfront</strong>
                <p style="font-size: 0.82rem; margin: 0; color: #cbd5e1;">Zero meters to golden sands & turquoise water</p>
            </div>
        </div>

        <div class="about-content">
            <span class="section-badge">Welcome to Serene Shores</span>
            <h2 class="section-title">Where Southern Coastal Charm Meets Modern Elegance</h2>
            <div class="divider-accent" style="margin: 0 0 20px 0;"></div>
            <p style="color: var(--text-muted); margin-bottom: 16px;">
                Nestled on the untouched coastal strip of Mirissa in Southern Sri Lanka, Serene Shores Coastal Villa is 
                a boutique hospitality enclave crafted for those seeking natural coastal elegance. 
                Mirissa is celebrated globally for its gentle turquoise crescent bay, swaying coconut palms, and world-renowned blue whale watching expeditions.
            </p>
            <p style="color: var(--text-muted); margin-bottom: 24px;">
                Our private suites and beachfront chalets combine sustainable Ceylon teak architecture with contemporary luxury amenities, 
                offering direct barefoot beach access, private terraces, open-air tropical showers, and attentive personalized concierge service.
            </p>

            <div class="about-features">
                <div class="feature-box">
                    <h4>&#127754; Direct Beachfront</h4>
                    <p>Immediate access to Mirissa’s golden beach and warm Indian Ocean waves.</p>
                </div>
                <div class="feature-box">
                    <h4>&#128011; Whale Watching</h4>
                    <p>Minutes from Mirissa Harbor, the premier blue whale watching hub of Sri Lanka.</p>
                </div>
                <div class="feature-box">
                    <h4>&#127860; Seafood Gastronomy</h4>
                    <p>Locally caught Mirissa seafood infused with authentic Ceylon spices.</p>
                </div>
                <div class="feature-box">
                    <h4>&#128134; Ayurvedic Wellness</h4>
                    <p>Rejuvenating herbal wellness rituals with ocean breeze aromatherapy.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Rooms Showcase -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Exclusive Sanctuaries</span>
            <h2 class="section-title">Featured Coastal Rooms & Suites</h2>
            <div class="divider-accent"></div>
            <p class="section-desc">
                Handcrafted suites tailored for couples, families, and mindful travelers seeking authentic seaside luxury.
            </p>
        </div>

        <div class="rooms-grid">
            <?php foreach ($featuredRooms as $room): ?>
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
                            foreach (array_slice($facilitiesList, 0, 4) as $fac): 
                            ?>
                                <span class="facility-pill">&#10003; <?= e($fac); ?></span>
                            <?php endforeach; ?>
                            <?php if (count($facilitiesList) > 4): ?>
                                <span class="facility-pill">+<?= count($facilitiesList) - 4; ?> more</span>
                            <?php endif; ?>
                        </div>

                        <div class="room-footer">
                            <div class="room-price">
                                <span class="price-val"><?= formatLKR((float)$room['price_per_night']); ?></span>
                                <span class="price-sub">per night &bull; taxes & breakfast incl.</span>
                            </div>
                            <a href="book.php?room_id=<?= (int)$room['room_id']; ?>" class="btn btn-primary btn-sm">Reserve</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: 45px;">
            <a href="rooms.php" class="btn btn-outline-navy">View All Rooms & Availability &rarr;</a>
        </div>
    </div>
</section>

<!-- Villa Facilities & Experiences -->
<section class="section section-light" id="facilities">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Bespoke Hospitality</span>
            <h2 class="section-title">Curated Villa Amenities</h2>
            <div class="divider-accent"></div>
            <p class="section-desc">
                Every detail at Serene Shores is curated to deliver effortless relaxation, tropical wellness, and authentic coastal memories.
            </p>
        </div>

        <div class="amenities-grid">
            <div class="amenity-card">
                <div class="amenity-icon">&#127754;</div>
                <h3>Infinity Pool</h3>
                <p>Freshwater infinity swimming pool right against the ocean horizon, surrounded by sun loungers and coconut trees.</p>
            </div>

            <div class="amenity-card">
                <div class="amenity-icon">&#127860;</div>
                <h3>Beachfront Dining</h3>
                <p>Catch-of-the-day Mirissa yellowfin tuna, jumbo prawns, and tropical fruit breakfasts served right on the beach.</p>
            </div>

            <div class="amenity-card">
                <div class="amenity-icon">&#128134;</div>
                <h3>Ayurvedic Spa</h3>
                <p>Traditional Sri Lankan herbal compresses, warm coconut oil massages, and sunset yoga sessions on the lawn.</p>
            </div>

            <div class="amenity-card">
                <div class="amenity-icon">&#128246;</div>
                <h3>High-Speed Wi-Fi</h3>
                <p>Complimentary high-speed fiber internet coverage across all private suites, balconies, and pool deck areas.</p>
            </div>

            <div class="amenity-card">
                <div class="amenity-icon">&#9875;</div>
                <h3>Whale Watching</h3>
                <p>Personalized catamaran excursions to observe Blue Whales, Sperm Whales, and spinner dolphins in their natural habitat.</p>
            </div>

            <div class="amenity-card">
                <div class="amenity-icon">&#128663;</div>
                <h3>Airport Transfers</h3>
                <p>Chauffeured Southern Expressway transfers directly from Colombo Bandaranaike International Airport (CMB) or Mattala.</p>
            </div>
        </div>
    </div>
</section>

<!-- Guest Testimonials -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Guest Experiences</span>
            <h2 class="section-title">What Our Guests Say</h2>
            <div class="divider-accent"></div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">
            <div style="background: #fff; padding: 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
                <div style="color: #f59e0b; margin-bottom: 12px; font-size: 1.2rem;">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                <p style="font-style: italic; color: var(--text-muted); margin-bottom: 18px; font-size: 0.95rem;">
                    "Our stay at the Presidential Oceanfront Suite was magical. Waking up to the Indian Ocean view and sipping Ceylon tea from our plunge pool was an experience we will cherish forever."
                </p>
                <div>
                    <strong style="color: var(--primary-navy); display:block;">Sophie & David Laurent</strong>
                    <small style="color: var(--text-muted);">Paris, France &bull; Stayed Dec 2025</small>
                </div>
            </div>

            <div style="background: #fff; padding: 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
                <div style="color: #f59e0b; margin-bottom: 12px; font-size: 1.2rem;">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                <p style="font-style: italic; color: var(--text-muted); margin-bottom: 18px; font-size: 0.95rem;">
                    "Serene Shores is truly the best beachfront accommodation in Mirissa. The online booking system was effortless, and the staff took care of our whale-watching catamaran bookings with perfection."
                </p>
                <div>
                    <strong style="color: var(--primary-navy); display:block;">Kasun & Anuki Perera</strong>
                    <small style="color: var(--text-muted);">Colombo, Sri Lanka &bull; Stayed Nov 2026</small>
                </div>
            </div>

            <div style="background: #fff; padding: 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
                <div style="color: #f59e0b; margin-bottom: 12px; font-size: 1.2rem;">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                <p style="font-style: italic; color: var(--text-muted); margin-bottom: 18px; font-size: 0.95rem;">
                    "Incredible sunset views, direct walkout onto the sand, and the freshest grilled jumbo prawns I have ever tasted. Highly recommended for couples wanting tranquility."
                </p>
                <div>
                    <strong style="color: var(--primary-navy); display:block;">Liam & Emma Walker</strong>
                    <small style="color: var(--text-muted);">Melbourne, Australia &bull; Stayed Oct 2025</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mirissa Location Preview -->
<section class="section section-light" id="location">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Destination Guide</span>
            <h2 class="section-title">Discover Mirissa, Sri Lanka</h2>
            <div class="divider-accent"></div>
            <p class="section-desc">
                Centrally positioned along Mirissa Beach with easy access to Southern Sri Lanka's finest coastal landmarks.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
            <div style="background: #fff; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
                <h4 style="color: var(--primary-navy); margin-bottom: 6px;">&#127796; Coconut Tree Hill</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted);">5-minute scenic tuk-tuk ride or sunset coastal walk</p>
            </div>
            <div style="background: #fff; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
                <h4 style="color: var(--primary-navy); margin-bottom: 6px;">&#128011; Mirissa Blue Whale Watching </h4>
                <p style="font-size: 0.85rem; color: var(--text-muted);">4 minutes away for morning Blue Whale excursions</p>
            </div>
            <div style="background: #fff; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
                <h4 style="color: var(--primary-navy); margin-bottom: 6px;">&#127754; Secret Beach Mirissa</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Secluded cove for crystal-clear snorkeling and tidal pools</p>
            </div>
            <div style="background: #fff; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
                <h4 style="color: var(--primary-navy); margin-bottom: 6px;">&#127984; Galle Dutch Fort</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted);">35 minutes via the coastal highway (UNESCO Heritage site)</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
