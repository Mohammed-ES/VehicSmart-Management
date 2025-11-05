-- Migration: Système de stockage d'images en base de données (BLOB)
-- Date: 5 novembre 2025

-- Créer la table pour stocker les images en BLOB
CREATE TABLE IF NOT EXISTS `vehicle_images_blob` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `image_data` LONGBLOB NOT NULL,
  `image_name` varchar(255) NOT NULL,
  `image_type` varchar(50) NOT NULL,
  `image_size` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vehicle_images_vehicle_id` (`vehicle_id`),
  KEY `idx_vehicle_images_primary` (`vehicle_id`, `is_primary`),
  CONSTRAINT `vehicle_images_blob_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter une colonne pour image placeholder/illustration
ALTER TABLE `vehicles` ADD COLUMN IF NOT EXISTS `placeholder_image` LONGBLOB DEFAULT NULL;
ALTER TABLE `vehicles` ADD COLUMN IF NOT EXISTS `placeholder_image_type` varchar(50) DEFAULT NULL;
