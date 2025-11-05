<?php
/**
 * Migration: Create Vehicle Images Table
 * 
 * This migration creates the vehicle_images table for storing vehicle image paths.
 */

// Get database instance
$db = Database::getInstance();

try {
    // Read SQL from file
    $sql = file_get_contents(__DIR__ . '/003_create_vehicle_images_table.sql');
    
    // Execute the SQL
    $result = $db->getConnection()->exec($sql);
    
    echo "Vehicle images table created successfully.";
    
} catch (PDOException $e) {
    echo "Error creating vehicle_images table: " . $e->getMessage();
    throw $e;
}
