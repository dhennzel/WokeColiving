<?php
// Run this script ONCE after deploying to a new server to prepare all tables and columns.
require_once "db.php";
echo "<h1>Woke Coliving Database Setup & Migration</h1>";

echo "Setting up Core Tables...<br>";
setup_reservations_table($conn);
setup_payments_table($conn);
setup_withdrawal_requests_table($conn);
setup_deletion_requests_table($conn);
setup_parking_tables($conn);
setup_key_tables($conn);
setup_updates_table($conn);
setup_inventory_table($conn);
setup_property_inventory_table($conn);
setup_residents_table($conn);

echo "Adding missing schema columns...<br>";
// Admin
mysqli_query($conn, "ALTER TABLE admin ADD COLUMN IF NOT EXISTS role ENUM('Super Admin', 'Admin') DEFAULT 'Admin'");
mysqli_query($conn, "ALTER TABLE admin ADD COLUMN IF NOT EXISTS first_name VARCHAR(50) DEFAULT ''");
mysqli_query($conn, "ALTER TABLE admin ADD COLUMN IF NOT EXISTS last_name VARCHAR(50) DEFAULT ''");
mysqli_query($conn, "ALTER TABLE admin ADD COLUMN IF NOT EXISTS email VARCHAR(100) DEFAULT ''");
mysqli_query($conn, "ALTER TABLE admin ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) DEFAULT ''");
mysqli_query($conn, "ALTER TABLE admin ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL");
// Rooms
mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS price_upper DECIMAL(10,2) DEFAULT 0.00");
mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS price_lower DECIMAL(10,2) DEFAULT 0.00");
mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS price_whole DECIMAL(10,2) DEFAULT 0.00");
mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS long_term_price_upper DECIMAL(10,2) DEFAULT 0.00");
mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS long_term_price_lower DECIMAL(10,2) DEFAULT 0.00");
mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS long_term_price_whole DECIMAL(10,2) DEFAULT 0.00");
mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS daily_price_bed DECIMAL(10,2) DEFAULT 0.00");
mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS daily_price_room DECIMAL(10,2) DEFAULT 0.00");
mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS is_archived TINYINT(1) DEFAULT 0");
mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS gender ENUM('Male', 'Female', 'Any') DEFAULT 'Any'");
mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS display_order INT DEFAULT 0");
mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS floor INT DEFAULT 2");
mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS room_number VARCHAR(50) DEFAULT NULL AFTER room_name");
mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS is_hidden TINYINT(1) DEFAULT 0");
// Users
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_name VARCHAR(50) DEFAULT ''");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS first_name VARCHAR(50) DEFAULT ''");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS middle_name VARCHAR(50) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS suffix VARCHAR(10) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_archived TINYINT(1) DEFAULT 0");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS do_not_renew TINYINT(1) DEFAULT 0");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS gender VARCHAR(20) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS occupation VARCHAR(50) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS company VARCHAR(100) DEFAULT NULL");
// Payments & Reservations
mysqli_query($conn, "ALTER TABLE payments MODIFY COLUMN payment_status ENUM('Paid','Unpaid','Cancelled') DEFAULT 'Unpaid'");
mysqli_query($conn, "ALTER TABLE payments ADD COLUMN IF NOT EXISTS is_penalized TINYINT(1) DEFAULT 0");
mysqli_query($conn, "ALTER TABLE payments ADD COLUMN IF NOT EXISTS reference_number VARCHAR(100) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE payments ADD COLUMN IF NOT EXISTS proof_image VARCHAR(255) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE payments ADD COLUMN IF NOT EXISTS description VARCHAR(255) DEFAULT 'Room Payment'");
mysqli_query($conn, "ALTER TABLE reservations MODIFY COLUMN status ENUM('Pending', 'Verifying', 'Approved', 'Cancelled', 'Completed') DEFAULT 'Pending'");
// Optional Logs
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (log_id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, action VARCHAR(100) NOT NULL, details TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, performed_by VARCHAR(100) DEFAULT 'System', role VARCHAR(50) DEFAULT 'System')");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifications (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, message TEXT NOT NULL, type VARCHAR(50) DEFAULT 'System', is_read TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

echo "<h3 style='color:green'>Done! The database is fully optimized and safe.</h3>";
?>