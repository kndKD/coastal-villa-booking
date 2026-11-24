# Serene Shores Coastal Villa - Web-Based Villa & Room Booking System

**Academic Project:** IT1208 – Web Technologies (Continuous Assessment 2 – 2026)  
**Location:** Mirissa, Southern Coast, Sri Lanka  
**Tech Stack:** PHP 8+, MySQL (PDO with Prepared Statements), Vanilla JavaScript (ES6+), HTML5, Responsive CSS3  
**Local Environment:** XAMPP (Apache + MySQL), served under `http://localhost/coastal-villa-booking-system/` (or `d:\Villa`)

---

## 1. Project Architecture & Directory Structure

```
d:\Villa/
├── config/
│   └── db.php                  # PDO connection with automatic multi-port (3307 / 3306) detection
├── includes/
│   ├── auth.php                # Session management, role authorization, CSRF, and XSS sanitization
│   ├── header.php              # Global guest navigation, brand badge, and flash messages
│   └── footer.php              # Mirissa coastal footer, academic CA2 credits, scripts
├── admin/
│   ├── includes/
│   │   ├── admin_header.php    # Admin control center navigation & role guard
│   │   └── admin_footer.php    # Admin layout footer
│   ├── dashboard.php           # Operations KPI metrics (revenue, bookings, urgent pending alerts)
│   ├── rooms.php               # Room CRUD (Create, Read, Update, Soft Deactivate / Delete)
│   └── bookings.php            # Booking management with double-booking overlap guard & cancellations
├── assets/
│   ├── css/
│   │   └── style.css           # Responsive modern coastal luxury stylesheet
│   ├── js/
│   │   └── app.js              # Live price calculator, date validation, and interaction scripts
│   └── images/                 # High-resolution coastal villa photography (hero, about, rooms 1-6)
├── index.php                   # Landing page, quick availability search widget, amenities, testimonials
├── rooms.php                   # Real-time room availability search with non-overlapping confirmed check
├── book.php                    # Authenticated booking form with dynamic JS calculation & server validation
├── my_bookings.php             # Guest portal: reservation history and pending booking cancellation
├── register.php                # Secure guest registration with password hashing
├── login.php                   # Role-based authentication with password verification & session regeneration
├── logout.php                  # Safe session destruction
├── db_structure.sql            # Full relational database schema, tables, foreign keys & seed data
└── README.md                   # Project documentation & execution guide
```

---

## 2. Relational Database Schema (`coastal_villa_db`)

### A. `users` Table
| Column | Type | Constraints | Description |
|---|---|---|---|
| `user_id` | INT | AUTO_INCREMENT, PRIMARY KEY | Unique user identifier |
| `name` | VARCHAR(100) | NOT NULL | User's full name |
| `email` | VARCHAR(150) | NOT NULL, UNIQUE | User email address (login credential) |
| `contact_number`| VARCHAR(25) | NOT NULL | Telephone / WhatsApp contact |
| `password` | VARCHAR(255) | NOT NULL | Hashed using PHP `password_hash()` |
| `role` | ENUM('guest','admin') | DEFAULT 'guest' | Role-based authorization |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Account creation timestamp |

### B. `rooms` Table
| Column | Type | Constraints | Description |
|---|---|---|---|
| `room_id` | INT | AUTO_INCREMENT, PRIMARY KEY | Unique room identifier |
| `room_name` | VARCHAR(120) | NOT NULL | Room / Suite designation |
| `room_type` | VARCHAR(60) | NOT NULL | Category (Ocean Suite, Beachfront Villa, etc.) |
| `description` | TEXT | NOT NULL | Architectural and coastal view description |
| `facilities` | TEXT | NOT NULL | Comma-separated list of amenities |
| `price_per_night`| DECIMAL(10,2)| NOT NULL | Daily rate in Sri Lankan Rupees (LKR) |
| `max_guests` | INT | NOT NULL | Maximum permitted occupancy |
| `image_url` | VARCHAR(255) | NOT NULL | Local path or URL to room photography |
| `room_status` | ENUM('available','inactive') | DEFAULT 'available' | Operational availability |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Registration timestamp |

### C. `bookings` Table
| Column | Type | Constraints | Description |
|---|---|---|---|
| `booking_id` | INT | AUTO_INCREMENT, PRIMARY KEY | Unique reservation reference number |
| `user_id` | INT | FK -> `users.user_id` (CASCADE) | Associated registered user |
| `room_id` | INT | FK -> `rooms.room_id` (RESTRICT)| Reserved room |
| `guest_name` | VARCHAR(100) | NOT NULL | Primary contact person |
| `guest_email` | VARCHAR(150) | NOT NULL | Guest communication email |
| `guest_contact` | VARCHAR(25) | NOT NULL | Contact telephone |
| `guests_count` | INT | NOT NULL | Number of staying guests |
| `special_requests`| TEXT | DEFAULT NULL | Guest arrival notes and preferences |
| `check_in` | DATE | NOT NULL | Reservation arrival date |
| `check_out` | DATE | NOT NULL | Reservation departure date |
| `total_amount` | DECIMAL(10,2)| NOT NULL | Server-calculated total stay price (LKR) |
| `booking_status`| ENUM('Pending','Confirmed','Cancelled') | DEFAULT 'Pending' | Booking lifecycle status |
| `booking_date` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date and time booking was placed |

---

## 3. Seed Accounts & Testing Credentials

| Role | Email | Password | Access Capabilities |
|---|---|---|---|
| **Administrator** | `admin@sereneshores.lk` | `Admin@2026#` | Full CRUD on rooms, booking approvals/cancellations, operations dashboard |
| **Guest** | `kasun.perera@gmail.com` | `Guest@2026#` | Room search, dynamic reservation form, cancel own pending bookings |

---

## 4. Key Functional & Algorithmic Implementations

### Overlapping Booking Exclusion Algorithm
To prevent double-booking, the search query in `rooms.php` filters out any room that has an active `Confirmed` booking overlapping the requested dates:
```sql
SELECT * FROM rooms 
WHERE room_status = 'available'
  AND room_id NOT IN (
    SELECT room_id FROM bookings 
    WHERE booking_status = 'Confirmed' 
      AND check_in < :new_check_out 
      AND check_out > :new_check_in
  );
```

### Administrative Double-Booking Guard (`admin/bookings.php`)
Before an administrator can mark a booking as `Confirmed`, the system runs a conflict query:
```sql
SELECT COUNT(*) FROM bookings 
WHERE room_id = :room_id 
  AND booking_id != :current_booking_id 
  AND booking_status = 'Confirmed' 
  AND check_in < :check_out 
  AND check_out > :check_in;
```
If `COUNT(*) > 0`, the confirmation is blocked with an explicit security alert, preventing conflicting guest reservations.

### Dynamic Client & Server Cost Calculation (`book.php`, `assets/js/app.js`)
- **Client-Side (ES6 JS):** Automatically computes stay duration in nights upon date selection and updates total cost preview in real time: `nights = (check_out - check_in) in days`, `total = nights * price_per_night`.
- **Server-Side (PHP 8):** Recalculates `DateTime::diff()` independently from POST parameters to ensure total cost cannot be tampered with in transit.

### Guest Booking Cancellation Security (`my_bookings.php`)
Guests are strictly restricted to cancelling their own reservations and only if the reservation is still in `Pending` status:
```sql
UPDATE bookings 
SET booking_status = 'Cancelled' 
WHERE booking_id = :booking_id 
  AND user_id = :user_id 
  AND booking_status = 'Pending';
```

---

## 5. Security Best Practices Enforced
1. **PDO Prepared Statements:** Parameterized queries across all database operations completely eliminate SQL Injection risks.
2. **Output Escaping:** Universal `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` (via helper `e()`) protects against Cross-Site Scripting (XSS).
3. **Session Fixation Countermeasures:** `session_regenerate_id(true)` executed upon authentication.
4. **CSRF Protection:** Anti-CSRF tokens generated and validated on all POST requests.
5. **Secure Passwords:** Salted BCrypt hashing using PHP's native `password_hash()` and `password_verify()`.
6. **Referential Integrity Protection:** Rooms linked to booking history are soft-deactivated (`inactive`) rather than hard-deleted.

---

## 6. How to Run on XAMPP

1. Launch **XAMPP Control Panel**.
2. Start **Apache** and **MySQL**.
3. Import `db_structure.sql` using phpMyAdmin or MySQL CLI:
   ```bash
   mysql -u root -p coastal_villa_db < db_structure.sql
   ```
4. Access the web application in any modern browser:
   ```
   http://localhost/coastal-villa-booking-system/
   ```
