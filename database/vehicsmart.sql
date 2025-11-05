-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3307
-- Généré le : sam. 12 juil. 2025 à 20:45
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `vehicsmart`
--

-- --------------------------------------------------------

--
-- Structure de la table `ai_chat_history`
--

CREATE TABLE `ai_chat_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `response` text NOT NULL,
  `context_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `alerts`
--

CREATE TABLE `alerts` (
  `id` int(11) NOT NULL,
  `type` enum('maintenance','contract_expiry','payment_due','inspection','general') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `target_vehicle_id` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `is_read` tinyint(1) DEFAULT 0,
  `action_required` tinyint(1) DEFAULT 0,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `maintenance`
--

CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `type` enum('scheduled','repair','inspection') DEFAULT 'scheduled',
  `description` text NOT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `service_provider` varchar(100) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `mileage_at_service` decimal(10,2) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `maintenance_records`
--

CREATE TABLE `maintenance_records` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `service_date` date NOT NULL,
  `next_service_date` date DEFAULT NULL,
  `mileage` int(11) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `message_type` enum('general','rental','purchase','maintenance','support') DEFAULT 'general',
  `reference_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages_conversations`
--

CREATE TABLE `messages_conversations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `status` enum('open','closed','resolved') NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `purchase_price` decimal(12,2) NOT NULL,
  `down_payment` decimal(10,2) DEFAULT 0.00,
  `financing` tinyint(1) DEFAULT 0,
  `loan_amount` decimal(12,2) DEFAULT NULL,
  `loan_term_months` int(11) DEFAULT NULL,
  `monthly_payment` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','approved','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','partial','paid') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `contract_signed` tinyint(1) DEFAULT 0,
  `delivery_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rentals`
--

CREATE TABLE `rentals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(11) NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `deposit_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','confirmed','active','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_name`, `setting_value`, `setting_description`, `created_at`, `updated_at`) VALUES
(1, 'company_name', 'VehicSmart', 'Company name displayed throughout the application', '2025-07-12 14:12:30', '2025-07-12 14:12:30'),
(2, 'company_email', 'info@vehicsmart.com', 'Main contact email', '2025-07-12 14:12:30', '2025-07-12 14:12:30'),
(3, 'enable_notifications', '1', 'Enable email notifications', '2025-07-12 14:12:30', '2025-07-12 14:12:30'),
(4, 'maintenance_mode', '0', 'Put site in maintenance mode', '2025-07-12 14:12:30', '2025-07-12 14:12:30'),
(5, 'currency', 'USD', 'Default currency for prices', '2025-07-12 14:12:30', '2025-07-12 14:12:30'),
(6, 'date_format', 'Y-m-d', 'Default date format', '2025-07-12 14:12:30', '2025-07-12 14:12:30'),
(7, 'items_per_page', '10', 'Number of items to display per page in listings', '2025-07-12 14:12:30', '2025-07-12 14:12:30');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','client') DEFAULT 'client',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `phone`, `role`, `status`, `email_verified`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'User', 'admin@vehicsmart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'admin', 'active', 1, '2025-07-10 22:04:33', '2025-07-10 22:04:33'),
(3, 'Client', 'Demo', 'client@vehicsmart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-123-4567', 'client', 'active', 1, '2025-07-12 14:45:03', '2025-07-12 14:45:03');

-- --------------------------------------------------------

--
-- Structure de la table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `brand` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `year` int(11) NOT NULL,
  `color` varchar(30) DEFAULT NULL,
  `license_plate` varchar(20) DEFAULT NULL,
  `vin` varchar(50) DEFAULT NULL,
  `engine_type` enum('petrol','diesel','electric','hybrid') DEFAULT 'petrol',
  `fuel_capacity` decimal(5,2) DEFAULT NULL,
  `seating_capacity` int(11) DEFAULT NULL,
  `mileage` decimal(10,2) DEFAULT 0.00,
  `daily_rate` decimal(10,2) NOT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `status` enum('available','rented','maintenance','sold') DEFAULT 'available',
  `description` text DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `vehicles`
--

INSERT INTO `vehicles` (`id`, `category_id`, `brand`, `model`, `year`, `color`, `license_plate`, `vin`, `engine_type`, `fuel_capacity`, `seating_capacity`, `mileage`, `daily_rate`, `purchase_price`, `status`, `description`, `features`, `images`, `created_at`, `updated_at`) VALUES
(11, 1, 'Toyota ', 'Corolla', 2025, 'Bleu m&eacute;tallique', 'ABC-1234', 'JH4KA8260MC000000', 'petrol', NULL, 4, 0.00, 45.00, 22000.00, 'available', 'A sleek blue sedan designed for comfort, performance.', NULL, NULL, '2025-07-12 10:18:58', '2025-07-12 10:30:42'),
(12, 2, 'Mercedes-Benz', 'Actros', 2025, 'Blanc m&eacute;tallis&eacute;', 'TRK-7823', 'WDB9634231L123456', 'diesel', NULL, 2, 0.00, 120.00, 89000.00, 'available', 'A powerful Mercedes-Benz Actros truck, built for long-haul transport with cutting-edge performance and driver comfort features.', NULL, NULL, '2025-07-12 11:08:17', '2025-07-12 11:08:17'),
(13, 3, 'Volvo', '7900', 2025, 'Bleu m&eacute;tallique', 'BUS-2205', 'YV3T5U521BA123456', 'petrol', NULL, 40, 0.00, 180.00, 160000.00, 'available', 'A spacious metallic blue Volvo 7900 electric bus, designed for quiet and eco-friendly urban transport.', NULL, NULL, '2025-07-12 11:41:22', '2025-07-12 11:41:22'),
(14, 4, 'John Deere', '8R 370', 2019, 'Vert m&eacute;tallis&eacute;', 'TRC-2019', 'RW8370DJKA123456', 'diesel', NULL, 1, 0.00, 145.00, 295000.00, 'available', 'A robust 2019 John Deere 8R 370 metallic green tractor, engineered for high-performance farming with 370 hp and advanced hydraulic systems.', NULL, NULL, '2025-07-12 11:50:31', '2025-07-12 11:50:31'),
(15, 5, 'Ford', 'Transit Custom', 2021, 'Gris m&eacute;tallis&eacute;', 'VAN-2021', 'WF0YXXTTGYM123456', 'diesel', NULL, 3, 0.00, 89.00, 43500.00, 'available', ' A 2021 Ford Transit Custom L2H1 metallic grey van, equipped with a 2.0L EcoBlue diesel engine, advanced safety features, and spacious cargo capacity.', NULL, NULL, '2025-07-12 11:55:41', '2025-07-12 11:55:41'),
(16, 6, 'Yamaha', 'R3', 2024, 'Bleu m&eacute;tallis&eacute;', 'MTR-0525', 'JYARJ16E5NA123456', 'petrol', NULL, 2, 0.00, 49.00, 6500.00, 'available', 'A sleek 2024 Yamaha R3 in metallic blue, built for agile city riding and spirited weekend getaways. Lightweight, fuel-efficient, and equipped with ABS and LED lighting.', NULL, NULL, '2025-07-12 12:00:39', '2025-07-12 12:00:39'),
(17, 7, 'Kia', 'Picanto', 2023, 'Gris fonc&eacute; m&eacute;tal', 'KIA-2323', 'KNAPM81ACR5123456', 'petrol', NULL, 5, 0.00, 59.00, 16900.00, 'available', ' A sleek 2023 Kia Sportage GT-Line SUV in dark metallic grey, perfect for family trips and urban driving.', NULL, NULL, '2025-07-12 12:06:14', '2025-07-12 12:06:14'),
(18, 1, 'Hyundai', 'IONIQ 6', 2023, 'Noir nacr&eacute; m&eacute;tal', 'EV-0625', 'AMESN81GPRU123456', 'electric', NULL, 5, 0.00, 69.00, 47900.00, 'available', 'A futuristic 2023 Hyundai IONIQ 6 electric sedan with over 300 miles of range, ultra-fast charging, and a sleek aerodynamic design. Ideal for eco-conscious drivers seeking performance and style.', NULL, NULL, '2025-07-12 12:10:56', '2025-07-12 12:11:22');

-- --------------------------------------------------------

--
-- Structure de la table `vehicle_categories`
--

CREATE TABLE `vehicle_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `vehicle_categories`
--

INSERT INTO `vehicle_categories` (`id`, `name`, `description`, `icon`, `created_at`) VALUES
(1, 'Car', 'Standard passenger cars for personal transportation', 'fa-car', '2025-07-10 22:04:33'),
(2, 'Truck', 'Commercial trucks for cargo and freight transport', 'fa-truck', '2025-07-10 22:04:33'),
(3, 'Bus', 'Passenger buses for group transportation', 'fa-bus', '2025-07-10 22:04:33'),
(4, 'Tractor', 'Agricultural and industrial tractors', 'fa-tractor', '2025-07-10 22:04:33'),
(5, 'Van', 'Commercial vans for cargo and passenger transport', 'fa-van-shuttle', '2025-07-10 22:04:33'),
(6, 'Motorcycle', 'Two-wheeled motor vehicles', 'fa-motorcycle', '2025-07-10 22:04:33'),
(7, 'SUV', 'Sport Utility Vehicles for versatile transportation', 'fa-car-side', '2025-07-10 22:04:33');

-- --------------------------------------------------------

--
-- Structure de la table `vehicle_images`
--

CREATE TABLE `vehicle_images` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `vehicle_images`
--

INSERT INTO `vehicle_images` (`id`, `vehicle_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES
(1, 11, '6872369298bf2_Toyota Corolla_Bleu métallique.jpg', 1, 0, '2025-07-12 10:18:58'),
(2, 12, '68724221d38a6_Mercedes-Benz_Blanc métallisé.jpeg', 1, 0, '2025-07-12 11:08:17'),
(3, 13, '687249e273c42_Bus-Volvo-7900-Electric.jpg', 1, 0, '2025-07-12 11:41:22'),
(4, 14, '68724c074ce9a_Tracteur-Vert métallisé.avif', 1, 0, '2025-07-12 11:50:31'),
(5, 15, '68724d3dc8649_Ford-Transit Custom.jpg', 1, 0, '2025-07-12 11:55:41'),
(6, 16, '68724e673be68_Yamaha-Yz125-Bleu-Team.webp', 1, 0, '2025-07-12 12:00:39'),
(7, 17, '68724fb61fd38_Kia-Picanto-Gris foncé métallisé.jpg', 1, 0, '2025-07-12 12:06:14'),
(8, 18, '687250d0726e8_Hyundai_IONIQ_6_MY24_2023.avif', 1, 0, '2025-07-12 12:10:56');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `ai_chat_history`
--
ALTER TABLE `ai_chat_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `target_vehicle_id` (`target_vehicle_id`),
  ADD KEY `idx_alerts_user` (`target_user_id`);

--
-- Index pour la table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_maintenance_vehicle` (`vehicle_id`);

--
-- Index pour la table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `idx_messages_receiver` (`receiver_id`);

--
-- Index pour la table `messages_conversations`
--
ALTER TABLE `messages_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_otp` (`email`,`otp`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Index pour la table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `idx_purchases_user` (`user_id`);

--
-- Index pour la table `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rentals_user` (`user_id`),
  ADD KEY `idx_rentals_vehicle` (`vehicle_id`),
  ADD KEY `idx_rentals_dates` (`start_date`,`end_date`);

--
-- Index pour la table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_plate` (`license_plate`),
  ADD UNIQUE KEY `vin` (`vin`),
  ADD KEY `idx_vehicles_status` (`status`),
  ADD KEY `idx_vehicles_category` (`category_id`);

--
-- Index pour la table `vehicle_categories`
--
ALTER TABLE `vehicle_categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `ai_chat_history`
--
ALTER TABLE `ai_chat_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `messages_conversations`
--
ALTER TABLE `messages_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rentals`
--
ALTER TABLE `rentals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `vehicle_categories`
--
ALTER TABLE `vehicle_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `ai_chat_history`
--
ALTER TABLE `ai_chat_history`
  ADD CONSTRAINT `ai_chat_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `alerts_ibfk_2` FOREIGN KEY (`target_vehicle_id`) REFERENCES `vehicles` (`id`);

--
-- Contraintes pour la table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);

--
-- Contraintes pour la table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  ADD CONSTRAINT `maintenance_records_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `messages_conversations`
--
ALTER TABLE `messages_conversations`
  ADD CONSTRAINT `messages_conversations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);

--
-- Contraintes pour la table `rentals`
--
ALTER TABLE `rentals`
  ADD CONSTRAINT `rentals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `rentals_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);

--
-- Contraintes pour la table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `vehicle_categories` (`id`);

--
-- Contraintes pour la table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  ADD CONSTRAINT `vehicle_images_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
