-- ============================================================================
-- Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
-- Project Title: Design and Development of a Dynamic Web-Based Coastal Villa and Room Booking System
-- Organization: Serene Shores Coastal Villa (Mirissa, Southern Coast, Sri Lanka)
-- Database Engine: MySQL / MariaDB (InnoDB, utf8mb4)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `coastal_villa_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `coastal_villa_db`;

-- Drop tables in reverse foreign key order if recreating
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `users`;

-- ----------------------------------------------------------------------------
-- Table structure for `users`
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `contact_number` VARCHAR(25) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('guest', 'admin') NOT NULL DEFAULT 'guest',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table structure for `rooms`
-- ----------------------------------------------------------------------------
CREATE TABLE `rooms` (
  `room_id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_name` VARCHAR(120) NOT NULL,
  `room_type` VARCHAR(60) NOT NULL,
  `description` TEXT NOT NULL,
  `facilities` TEXT NOT NULL,
  `price_per_night` DECIMAL(10,2) NOT NULL,
  `max_guests` INT NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `room_status` ENUM('available', 'inactive') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table structure for `bookings`
-- ----------------------------------------------------------------------------
CREATE TABLE `bookings` (
  `booking_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `guest_name` VARCHAR(100) NOT NULL,
  `guest_email` VARCHAR(150) NOT NULL,
  `guest_contact` VARCHAR(25) NOT NULL,
  `guests_count` INT NOT NULL,
  `special_requests` TEXT DEFAULT NULL,
  `check_in` DATE NOT NULL,
  `check_out` DATE NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `booking_status` ENUM('Pending', 'Confirmed', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `booking_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_room` FOREIGN KEY (`room_id`) 
    REFERENCES `rooms` (`room_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Seed Data: Users
-- Passwords:
-- admin@sereneshores.lk => Admin@2026#
-- kasun.perera@gmail.com => Guest@2026#
-- ----------------------------------------------------------------------------
INSERT INTO `users` (`user_id`, `name`, `email`, `contact_number`, `password`, `role`) VALUES
(1, 'Villa Administrator', 'admin@sereneshores.lk', '+94 41 225 9900', '$2y$10$b1H8Dhz35RoLXGSmY83GQ.9FQaiEdWBj8jgW7wEpW.8qaMX6lPSIG', 'admin'),
(2, 'Kasun Perera', 'kasun.perera@gmail.com', '+94 77 123 4567', '$2y$10$0Kc5j1af5A0b9ZyEgLQKX.EP6Yh2EDcR6V/uAUwBLVIkl53zF1BjC', 'guest');

-- ----------------------------------------------------------------------------
-- Seed Data: Rooms (Mirissa Beachfront Luxury Suites & Villas)
-- ----------------------------------------------------------------------------
INSERT INTO `rooms` (`room_id`, `room_name`, `room_type`, `description`, `facilities`, `price_per_night`, `max_guests`, `image_url`, `room_status`) VALUES
(1, 'Presidential Oceanfront Suite', 'Ocean Suite', 'Experience unparalleled coastal luxury with breathtaking uninterrupted views of Mirissa Bay. Featuring a private heated plunge pool on an expansive teak balcony, direct ocean breeze, and bespoke Ceylon teak furnishings.', 'King Bed, Ocean View Balcony, Private Plunge Pool, Jacuzzi, Free High-Speed Wi-Fi, 55-inch Smart TV, Air Conditioning, Espresso Coffee Machine, Mini Bar, Complimentary Gourmet Breakfast', 85000.00, 2, 'assets/images/room1.jpg', 'available'),

(2, 'Deluxe Ocean View Villa', 'Beachfront Villa', 'A serene sanctuary perched steps away from the golden sands of Mirissa Beach. Includes a spacious sun terrace, open-air tropical rain shower, marble en-suite bathroom, and panoramic sunset viewpoints.', 'King Bed, Private Sun Terrace, Tropical Rain Shower, Air Conditioning, Free Wi-Fi, Mini Refrigerator, Smart TV, Tea/Coffee Maker, Daily Housekeeping, Breakfast Included', 55000.00, 2, 'assets/images/room2.jpg', 'available'),

(3, 'Coconut Grove Garden Pavilion', 'Garden Villa', 'Tucked amid whispering coconut palms and lush tropical flora. Offers soothing garden tranquility, a private covered veranda with artisan rattan swings, and close proximity to Mirissa Whale Watching harbour.', 'Queen Bed, Twin Daybed, Private Garden Veranda, Air Conditioning, Ceiling Fan, Free Wi-Fi, En-suite Bathroom, Safe Deposit Box, Complimentary Tropical Breakfast', 42000.00, 4, 'assets/images/room3.jpg', 'available'),

(4, 'Royal Palm Beachfront Chalet', 'Beachfront Villa', 'Direct barefoot walkout to Mirissa Beach. Designed with high vaulted timber ceilings, floor-to-ceiling glass doors, dual king suites, and an outdoor beachfront lounge pavilion ideal for small families or couples.', '2 King Beds, Direct Beach Access, Private Beach Cabana, Outdoor Rain Shower, Air Conditioning, Free High-Speed Wi-Fi, Mini Bar, Bluetooth Sound System, Breakfast Included', 68000.00, 4, 'assets/images/room4.jpg', 'available'),

(5, 'Tropical Breeze Studio', 'Studio Suite', 'Chic and contemporary studio designed for digital nomads and romantic couples seeking Mirissa coastal charm. Overlooks the main freshwater infinity pool with private balcony and ergonomic workspace.', 'King Bed, Pool View Balcony, Ergonomic Workspace, High-Speed Wi-Fi, Kitchenette, Microwave, Coffee Maker, Air Conditioning, En-suite Rain Shower', 28000.00, 2, 'assets/images/room5.jpg', 'available'),

(6, 'Coral Haven Family Villa', 'Family Villa', 'Spacious 3-bedroom private villa complete with its own private courtyard, sun loungers, dining pavilion, and direct pathway to Mirissa Secret Beach. The ultimate choice for family holidaymakers.', '3 Bedrooms (2 King + 2 Singles), Private Swimming Pool, Full Kitchen & Dining Pavilion, Free Wi-Fi, 65-inch Smart TV, Air Conditioning, Butler Service Available, Daily Breakfast', 110000.00, 6, 'assets/images/room6.jpg', 'available');

-- ----------------------------------------------------------------------------
-- Seed Data: Bookings (Demonstrating Confirmed, Pending, and Cancelled statuses)
-- ----------------------------------------------------------------------------
INSERT INTO `bookings` (`booking_id`, `user_id`, `room_id`, `guest_name`, `guest_email`, `guest_contact`, `guests_count`, `special_requests`, `check_in`, `check_out`, `total_amount`, `booking_status`, `booking_date`) VALUES
(1, 2, 1, 'Kasun Perera', 'kasun.perera@gmail.com', '+94 77 123 4567', 2, 'Honeymoon arrangement requested with fresh tropical flower decor.', '2026-12-10', '2026-12-14', 340000.00, 'Confirmed', '2026-11-20 14:30:00'),
(2, 2, 3, 'Kasun Perera', 'kasun.perera@gmail.com', '+94 77 123 4567', 3, 'Late check-in around 8:00 PM due to southern expressway travel.', '2026-12-24', '2026-12-27', 126000.00, 'Pending', '2026-11-22 09:15:00'),
(3, 2, 2, 'Kasun Perera', 'kasun.perera@gmail.com', '+94 77 123 4567', 2, 'Need airport pickup from Bandaranaike International Airport (CMB).', '2026-11-15', '2026-11-18', 165000.00, 'Cancelled', '2026-11-05 18:45:00');
