<?php
/**
 * Serene Shores Coastal Villa - Master Footer Component
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 */

declare(strict_types=1);
?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-col">
            <h4>Serene Shores Coastal Villa</h4>
            <p style="margin-bottom: 14px; font-size: 0.92rem; line-height: 1.6;">
                An exquisite beachfront sanctuary along Mirissa’s golden coast in Southern Sri Lanka. 
                Experience tropical luxury, private plunge pools, gourmet seafood dining, and panoramic Indian Ocean views.
            </p>
            <p style="font-size: 0.88rem; color: #cbd5e1;">
                <strong>Location:</strong> Beach Road, Mirissa 81740, Southern Province, Sri Lanka<br>
                <strong>Inquiries:</strong> info@sereneshores.lk | +94 41 225 9900
            </p>
        </div>

        <div class="footer-col">
            <h4>Quick Navigation</h4>
            <ul class="footer-links">
                <li><a href="index.php">Villa Home</a></li>
                <li><a href="rooms.php">Rooms & Suites</a></li>
                <li><a href="index.php#facilities">Guest Facilities</a></li>
                <li><a href="index.php#location">Mirissa Experiences</a></li>
                <li><a href="my_bookings.php">My Reservations</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Guest Privileges</h4>
            <ul class="footer-links">
                <li><a href="rooms.php">Direct Oceanfront Suites</a></li>
                <li><a href="rooms.php">Private Plunge Pools</a></li>
                <li><a href="#facilities">Whale Watching Excursions</a></li>
                <li><a href="#facilities">Ayurvedic Wellness Spa</a></li>
                <li><a href="login.php">Guest Portal Access</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Villa & Reception</h4>
            <ul class="footer-links" style="font-size: 0.88rem; line-height: 1.8; color: #cbd5e1;">
                <li><strong style="color: #fff;">Check-in:</strong> From 2:00 PM</li>
                <li><strong style="color: #fff;">Check-out:</strong> Until 11:00 AM</li>
                <li><strong style="color: #fff;">Reception Hotline:</strong> +94 41 225 9900</li>
                <li><strong style="color: #fff;">Reservations:</strong> reservations@sereneshores.lk</li>
                <li><strong style="color: #fff;">Concierge Desk:</strong> 24/7 Guest Assistance</li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container footer-bottom-flex">
            <div>
                &copy; <?= date('Y'); ?> Serene Shores Coastal Villa (Pvt) Ltd. All Rights Reserved.
            </div>
            <div>
                Mirissa, Southern Coast &bull; Sri Lanka
            </div>
        </div>
    </div>
</footer>

<script src="assets/js/app.js"></script>
</body>
</html>
