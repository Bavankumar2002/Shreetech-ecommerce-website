-- ====================================================================
-- SHOPPULSE E-Commerce Database Schema
-- Compatible with MySQL 8.0+ / MariaDB 10.4+
-- Inspired by SHOP.COM: Marketplace with Cashback, Deals, Categories
-- ====================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- Users table
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('customer', 'admin') DEFAULT 'customer',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `icon` VARCHAR(50) DEFAULT 'shopping-bag',
    `description` TEXT,
    `image_url` VARCHAR(255) DEFAULT '',
    `display_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products table
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `brand` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10,2) NOT NULL,
    `original_price` DECIMAL(10,2) DEFAULT NULL,
    `cashback_pct` DECIMAL(4,1) DEFAULT 2.0,
    `rating` DECIMAL(2,1) DEFAULT 4.5,
    `reviews_count` INT DEFAULT 0,
    `stock` INT DEFAULT 50,
    `badge` VARCHAR(50) DEFAULT '',
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_trending` TINYINT(1) DEFAULT 0,
    `is_deal` TINYINT(1) DEFAULT 0,
    `deal_ends_at` DATETIME DEFAULT NULL,
    `image_url` VARCHAR(500) NOT NULL,
    `gallery` JSON DEFAULT NULL,
    `specs` JSON DEFAULT NULL,
    `model_shape` VARCHAR(50) DEFAULT 'cube',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews table
CREATE TABLE `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `user_name` VARCHAR(100) NOT NULL,
    `rating` INT NOT NULL,
    `comment` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT DEFAULT NULL,
    `customer_name` VARCHAR(150) NOT NULL,
    `customer_email` VARCHAR(150) NOT NULL,
    `customer_phone` VARCHAR(50) NOT NULL,
    `shipping_address` TEXT NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(50) NOT NULL,
    `zip_code` VARCHAR(20) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
    `shipping_fee` DECIMAL(10,2) DEFAULT 0.00,
    `tax_amount` DECIMAL(10,2) DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `cashback_earned` DECIMAL(10,2) DEFAULT 0.00,
    `payment_method` VARCHAR(50) DEFAULT 'credit_card',
    `payment_status` ENUM('pending', 'paid', 'failed') DEFAULT 'paid',
    `order_status` ENUM('processing', 'confirmed', 'shipped', 'out_for_delivery', 'delivered') DEFAULT 'confirmed',
    `tracking_number` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items table
CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `image_url` VARCHAR(500) DEFAULT '',
    `total` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Seed Categories
-- --------------------------------------------------------------------
INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `description`, `image_url`, `display_order`) VALUES
(1, 'Electronics', 'electronics', 'laptop', 'Next-gen gadgets, smartphones, laptops and accessories', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80', 1),
(2, 'Audio & Sound', 'audio-sound', 'headphones', 'Studio sound, noise-canceling headphones & hi-fi speakers', 'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=600&auto=format&fit=crop&q=80', 2),
(3, 'Smart Wearables', 'wearables', 'watch', 'Health-tracking smartwatches, bands and sports wearables', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&auto=format&fit=crop&q=80', 3),
(4, 'Fashion & Apparel', 'fashion-apparel', 'shirt', 'Modern streetwear, designer collections and luxury eyewear', 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600&auto=format&fit=crop&q=80', 4),
(5, 'Home & Living', 'home-living', 'home', 'Minimalist home decor, smart lighting and acoustic design', 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=600&auto=format&fit=crop&q=80', 5),
(6, 'Beauty & Wellness', 'beauty-wellness', 'sparkles', 'Organic skincare, wellness fragrances and self-care tools', 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=600&auto=format&fit=crop&q=80', 6);

-- --------------------------------------------------------------------
-- Seed Demo Admin & Customer Users (password: password123)
-- --------------------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`) VALUES
(1, 'Demo Customer', 'customer@shoppulse.com', '$2y$10$wO8Y2fVfI4d732W7LdQZCeC7e4k3f91Z72Xm8a3B5U0b2s5n7Wqae', 'customer'),
(2, 'Admin User', 'admin@shoppulse.com', '$2y$10$wO8Y2fVfI4d732W7LdQZCeC7e4k3f91Z72Xm8a3B5U0b2s5n7Wqae', 'admin');

-- --------------------------------------------------------------------
-- Seed Products with Realistic Marketplace Details & Cashback
-- --------------------------------------------------------------------
INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `brand`, `description`, `price`, `original_price`, `cashback_pct`, `rating`, `reviews_count`, `stock`, `badge`, `is_featured`, `is_trending`, `is_deal`, `deal_ends_at`, `image_url`, `gallery`, `specs`, `model_shape`) VALUES
(1, 2, 'Aura ANC Wireless Studio Headphones', 'aura-anc-wireless-studio-headphones', 'AuraSound', 'Flagship over-ear hybrid active noise-canceling headphones with 45-hour battery life, custom 40mm titanium drivers, and spatial audio with dynamic head tracking.', 249.99, 329.99, 6.0, 4.9, 128, 42, 'Hot Deal', 1, 1, 1, DATE_ADD(NOW(), INTERVAL 24 HOUR), 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&auto=format&fit=crop&q=80", "https://images.unsplash.com/photo-1484704849700-f032a568e944?w=800&auto=format&fit=crop&q=80", "https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=800&auto=format&fit=crop&q=80"]', '{"Driver": "40mm Titanium", "Battery": "45 Hours", "Connectivity": "Bluetooth 5.3 + 3.5mm", "Weight": "250g"}', 'cylinder'),

(2, 3, 'Chronos Elite Titanium Smartwatch', 'chronos-elite-titanium-smartwatch', 'ChronosTech', 'Aerospace-grade titanium chassis with scratch-resistant sapphire crystal display, ECG monitoring, continuous SpO2, and dual-frequency GPS with 14-day battery.', 379.00, 449.00, 4.5, 4.8, 94, 25, 'Trending', 1, 1, 0, NULL, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&auto=format&fit=crop&q=80", "https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=800&auto=format&fit=crop&q=80"]', '{"Material": "Grade 5 Titanium", "Display": "1.43\\" AMOLED 466x466", "Water Resistance": "5 ATM + IP68", "Sensors": "BioActive 3-in-1"}', 'cylinder'),

(3, 1, 'PulseBook Pro 16 Ultralight Laptop', 'pulsebook-pro-16-ultralight-laptop', 'PulseTech', 'Featherlight aerospace aluminum alloy workstation packing a 14-core processor, 32GB LPDDR5X RAM, 1TB NVMe Gen4 SSD, and a color-calibrated 120Hz Mini-LED display.', 1499.00, 1799.00, 5.0, 4.9, 67, 18, 'Best Seller', 1, 1, 1, DATE_ADD(NOW(), INTERVAL 36 HOUR), 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&auto=format&fit=crop&q=80", "https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&auto=format&fit=crop&q=80"]', '{"CPU": "14-Core Gen 14", "RAM": "32GB LPDDR5X", "Storage": "1TB Gen4 PCIe", "Display": "16\\" 3.2K 120Hz OLED"}', 'cube'),

(4, 5, 'Orbital Smart Ambient Acoustic Speaker', 'orbital-smart-ambient-acoustic-speaker', 'LumenAudio', 'Sculptural omni-directional acoustic speaker with magnetic suspension levitating diaphragm, ambient warm glow lighting, and Lossless AirPlay 2 + Cast support.', 189.50, 249.00, 3.5, 4.7, 52, 30, 'Design Award', 1, 0, 1, DATE_ADD(NOW(), INTERVAL 18 HOUR), 'https://images.unsplash.com/photo-1545454675-3531b543be5d?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1545454675-3531b543be5d?w=800&auto=format&fit=crop&q=80"]', '{"Acoustics": "360-Degree Spatial", "Power": "60W RMS", "Lighting": "Infinite RGB + 2700K Warm Glow", "Voice Assistant": "Universal"}', 'sphere'),

(5, 4, 'Urban Minimalist Waterproof Rolltop Backpack', 'urban-minimalist-waterproof-rolltop-backpack', 'Kovert', 'Crafted from 100% recycled Cordura with seam-sealed waterproof lining, magnetic Fidlock buckle closure, padded 16-inch laptop compartment, and luggage pass-through.', 119.00, 159.00, 4.0, 4.6, 88, 60, 'Staff Pick', 0, 1, 0, NULL, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&auto=format&fit=crop&q=80"]', '{"Volume": "25L Expandable to 32L", "Material": "Cordura 500D Recycled", "Hardware": "German Fidlock V-Buckles", "Weight": "850g"}', 'cube'),

(6, 6, 'Botanical Radiant Glow Peptide Serum', 'botanical-radiant-glow-peptide-serum', 'PureAura', 'Multi-active facial concentrate featuring triple-molecular hyaluronic acid, copper peptides, and certified cold-pressed botanical extracts for cellular rejuvenation.', 58.00, 75.00, 8.0, 4.9, 142, 75, 'Top Rated', 1, 1, 0, NULL, 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=800&auto=format&fit=crop&q=80"]', '{"Key Actives": "Copper Tripeptide-1, 2% HA", "Volume": "50ml / 1.7 fl oz", "Certifications": "Cruelty-Free, Vegan, Organic", "Origin": "France"}', 'cylinder'),

(7, 1, 'ProGrip Ergonomic Wireless Precision Mouse', 'progrip-ergonomic-wireless-precision-mouse', 'PulseTech', 'Sculpted natural handshake contour mouse featuring MagSpeed electromagnetic scroll wheel, 8000 DPI Darkfield sensor on glass, and multi-device flow switching.', 89.99, 119.99, 3.0, 4.8, 115, 45, 'Hot', 0, 1, 1, DATE_ADD(NOW(), INTERVAL 48 HOUR), 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=800&auto=format&fit=crop&q=80"]', '{"Sensor": "Darkfield 8000 DPI", "Battery": "70 Days fast USB-C", "Buttons": "7 Programmable Quiet Clicks", "Weight": "141g"}', 'cube'),

(8, 4, 'Nordic Handcrafted Acetate Polarized Sunglasses', 'nordic-handcrafted-acetate-polarized-sunglasses', 'Solaria', 'Custom cellulose acetate frames handcrafted in Italy with Carl Zeiss polarized lenses offering 100% UVA/UVB protection and Japanese 5-barrel barrel hinges.', 145.00, 195.00, 5.0, 4.7, 39, 35, 'Exclusive', 0, 1, 0, NULL, 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=800&auto=format&fit=crop&q=80"]', '{"Lens": "Carl Zeiss CR-39 Polarized", "Frame": "Italian Mazzucchelli Acetate", "Hinges": "5-Barrel Nickel Alloy", "UV Protection": "UV400"}', 'torus'),

(9, 2, 'Nova SoundTrue True Wireless Earbuds', 'nova-soundtrue-true-wireless-earbuds', 'AuraSound', 'Next-gen wireless earbuds with adaptive spatial transparency mode, 6-mic beamforming beamforming crystal calls, and wireless Qi charge case with 36hr playback.', 129.99, 169.99, 4.0, 4.7, 210, 50, 'Save 25%', 1, 1, 1, DATE_ADD(NOW(), INTERVAL 12 HOUR), 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=800&auto=format&fit=crop&q=80"]', '{"ANC": "Adaptive Active Noise Cancellation", "Battery": "8h + 28h Case", "Resistance": "IPX5 Splashproof", "Codec": "LDAC, AAC, aptX Adaptive"}', 'sphere'),

(10, 5, 'Ceramic Minimalist Ultrasonic Aroma Diffuser', 'ceramic-minimalist-ultrasonic-aroma-diffuser', 'PureAura', 'Hand-thrown matte porcelain stone diffuser that gently disperses pure essential oils via ultrasonic mist while radiating a warm candle-like LED gradient.', 65.00, 85.00, 2.5, 4.8, 77, 40, 'Popular', 0, 0, 0, NULL, 'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=800&auto=format&fit=crop&q=80"]', '{"Capacity": "180ml (Up to 8 Hours)", "Material": "Matte Ceramic & BPA-Free Reservoir", "Coverage": "500 sq ft", "Auto Shut-off": "Yes"}', 'cylinder'),

(11, 3, 'Apex Pro Smart Fitness & Sleep Ring', 'apex-pro-smart-fitness-and-sleep-ring', 'ChronosTech', 'Ultralight aerospace grade titanium smart ring tracking sleep stages, readiness score, HRV, heart rate variability, and body temperature with 7-day battery.', 299.00, 349.00, 5.0, 4.6, 61, 20, 'New Release', 1, 1, 0, NULL, 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=800&auto=format&fit=crop&q=80"]', '{"Weight": "4 grams", "Sensors": "Optical PPG, 3D Accelerometer, Skin Temp", "Battery": "Up to 7 Days", "Waterproof": "100m Submersible"}', 'torus'),

(12, 1, 'Lumina 4K UHD Ultra Short Throw Projector', 'lumina-4k-uhd-ultra-short-throw-projector', 'LumenAudio', 'Laser cinema projector delivering up to 150-inch 4K HDR10+ visuals from just 7 inches away with integrated 40W Dolby Atmos soundbar and Google TV.', 1899.00, 2299.00, 6.0, 4.9, 33, 10, 'Featured Deal', 1, 0, 1, DATE_ADD(NOW(), INTERVAL 30 HOUR), 'https://images.unsplash.com/photo-1593784991095-a205069470b6?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1593784991095-a205069470b6?w=800&auto=format&fit=crop&q=80"]', '{"Resolution": "4K UHD 3840x2160", "Brightness": "2600 ANSI Lumens", "Audio": "Dolby Atmos 40W Harman Kardon", "Throw Ratio": "0.22:1"}', 'cube');

-- --------------------------------------------------------------------
-- Seed Sample Reviews
-- --------------------------------------------------------------------
INSERT INTO `reviews` (`product_id`, `user_name`, `rating`, `comment`) VALUES
(1, 'Alex M.', 5, 'The soundstage on the Aura ANC is nothing short of breathtaking. Noise cancellation easily rivals top studio headphones.'),
(1, 'Elena R.', 5, 'Super comfortable memory foam ear cushions, and the 6% cashback made this purchase an absolute steal!'),
(2, 'Marcus T.', 5, 'The titanium case feels ultra high-end. Battery genuinely lasts 2 full weeks with workouts logged daily.'),
(3, 'Sophia L.', 5, 'Handles 4K video rendering and intense code compilation without breaking a sweat or spinning loud fans.'),
(4, 'David K.', 4, 'Beautiful industrial design! It acts as both home sculpture and an incredible room-filling speaker.');
