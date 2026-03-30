-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: woke_coliving
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `account_deletion_requests`
--

DROP TABLE IF EXISTS `account_deletion_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_deletion_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `account_deletion_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_deletion_requests`
--

LOCK TABLES `account_deletion_requests` WRITE;
/*!40000 ALTER TABLE `account_deletion_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_deletion_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `performed_by` varchar(100) DEFAULT 'System',
  `role` varchar(50) DEFAULT 'System',
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=664 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (89,38,'Account Created','Walk-in account created by Admin','2026-02-26 01:25:30','System','System'),(92,40,'Account Created','Walk-in account created by Admin','2026-02-26 01:57:54','System','System'),(93,40,'Walk-in Booking','Reservation #51 created by Admin','2026-02-26 01:57:54','System','System'),(108,40,'Payment Confirmed','Payment #55 marked as Paid by Admin.','2026-02-28 13:54:57','System','System'),(217,40,'Profile Updated','Admin updated user details.','2026-03-01 12:29:35','System','System'),(218,40,'Profile Updated','Admin updated user details.','2026-03-01 12:36:44','System','System'),(232,40,'Profile Updated','Admin updated user details.','2026-03-02 07:19:43','System','System'),(339,40,'Penalty Applied','Late fee of 30.00 applied for Payment #103','2026-03-08 13:41:45','System','System'),(357,40,'Parking Ended','Parking reservation #2 ended by Super Admin','2026-03-08 14:16:54','Super Admin','Super Admin'),(381,40,'Payment Confirmed','Payment #103 marked as Paid by Super Admin.','2026-03-08 20:19:32','Super Admin','Super Admin'),(382,40,'Payment Confirmed','Payment #110 marked as Paid by Super Admin.','2026-03-08 20:19:38','Super Admin','Super Admin'),(383,40,'Signature Requested','Signature requested for Reservation #51 by Super Admin','2026-03-08 20:19:51','Super Admin','Super Admin'),(384,40,'Room Re-assigned','Moved to 203 (Any) by Super Admin','2026-03-08 20:20:41','Super Admin','Super Admin'),(392,40,'Parking Assigned','Assigned to Car Slot 1 by Super Admin','2026-03-09 12:49:32','Super Admin','Super Admin'),(393,40,'Parking Ended','Parking reservation #11 ended by Super Admin','2026-03-09 12:49:38','Super Admin','Super Admin'),(394,40,'Room Re-assigned','Moved to 201 (Any) by Super Admin','2026-03-09 14:06:31','Super Admin','Super Admin'),(395,40,'Room Re-assigned','Moved to 203 (Lower Bunk) by Super Admin','2026-03-09 16:28:34','Super Admin','Super Admin'),(396,40,'Room Re-assigned','Moved to 201 (Any) by Super Admin','2026-03-09 17:01:47','Super Admin','Super Admin'),(397,40,'Room Re-assigned','Moved to 1 Bed (Any) by Super Admin','2026-03-09 19:02:19','Super Admin','Super Admin'),(428,40,'Payment Confirmed','Payment #123 marked as Paid by Super Admin.','2026-03-12 16:39:27','Super Admin','Super Admin'),(491,40,'Key Released','Key ID 15 released to user by Super Admin','2026-03-13 20:13:08','Super Admin','Super Admin'),(492,40,'Key Returned','Key ID 15 marked as returned by Super Admin','2026-03-13 20:13:16','Super Admin','Super Admin'),(593,40,'Signature Requested','Signature requested for Reservation #51 by Super Admin','2026-03-17 18:00:16','Diane Tayson (Super Admin)','Super Admin'),(594,40,'Lease Signed','Reservation #51','2026-03-17 18:00:55','Diane Tayson (Super Admin)','Super Admin'),(595,90,'Reservation Submitted','Room: Single | Status: Pending','2026-03-17 18:18:40','Diane Tayson (Super Admin)','Super Admin'),(596,90,'Payment Submitted','Reservation #125 via Cash for 1 bill(s)','2026-03-17 18:18:51','Diane Tayson (Super Admin)','Super Admin'),(597,90,'Reservation Approved','Reservation #125 approved by Super Admin.','2026-03-17 18:20:42','Diane Tayson (Super Admin)','Super Admin'),(598,90,'Signature Requested','Signature requested for Reservation #125 by Super Admin','2026-03-17 18:20:49','Diane Tayson (Super Admin)','Super Admin'),(599,90,'Lease Signed','Reservation #125','2026-03-17 18:20:58','Diane Tayson (Super Admin)','Super Admin'),(600,90,'Payment Confirmed','Payment #178 marked as Paid by Super Admin.','2026-03-17 18:21:33','Diane Tayson (Super Admin)','Super Admin'),(601,90,'Contract Ended','Reservation #125 marked as Completed by Super Admin.','2026-03-17 18:44:50','Diane Tayson (Super Admin)','Super Admin'),(602,90,'Reservation Submitted','Room: Single | Status: Pending','2026-03-17 18:45:36','Diane Tayson (Super Admin)','Super Admin'),(603,90,'Payment Submitted','Reservation #126 via Cash for 1 bill(s)','2026-03-17 18:45:43','Diane Tayson (Super Admin)','Super Admin'),(604,90,'Reservation Approved','Reservation #126 approved by Super Admin.','2026-03-17 18:46:00','Diane Tayson (Super Admin)','Super Admin'),(605,90,'Signature Requested','Signature requested for Reservation #126 by Super Admin','2026-03-17 18:46:08','Diane Tayson (Super Admin)','Super Admin'),(606,90,'Lease Signed','Reservation #126','2026-03-17 18:46:15','Diane Tayson (Super Admin)','Super Admin'),(607,90,'Payment Confirmed','Payment #179 marked as Paid by Super Admin.','2026-03-17 18:47:31','Diane Tayson (Super Admin)','Super Admin'),(608,91,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-03-18 03:42:54','Diane Tayson (Super Admin)','Super Admin'),(609,91,'Payment Submitted','Reservation #127 via Cash for 1 bill(s)','2026-03-18 03:43:04','Diane Tayson (Super Admin)','Super Admin'),(610,91,'Reservation Approved','Reservation #127 approved by Super Admin.','2026-03-18 03:44:19','Diane Tayson (Super Admin)','Super Admin'),(611,91,'Signature Requested','Signature requested for Reservation #127 by Super Admin','2026-03-18 03:44:29','Diane Tayson (Super Admin)','Super Admin'),(612,91,'Lease Signed','Reservation #127','2026-03-18 03:44:41','Diane Tayson (Super Admin)','Super Admin'),(613,91,'Payment Confirmed','Payment #183 marked as Paid by Super Admin.','2026-03-18 03:45:48','Diane Tayson (Super Admin)','Super Admin'),(614,92,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-03-18 03:56:10','Diane Tayson (Super Admin)','Super Admin'),(615,91,'Signature Requested','Signature requested for Reservation #127 by Super Admin','2026-03-18 03:59:04','Diane Tayson (Super Admin)','Super Admin'),(616,91,'Lease Signed','Reservation #127','2026-03-18 04:01:26','Diane Tayson (Super Admin)','Super Admin'),(617,91,'Contract Ended','Reservation #127 marked as Completed by Super Admin.','2026-03-18 04:11:29','Diane Tayson (Super Admin)','Super Admin'),(618,92,'Reservation Cancelled','Reservation #128 auto-expired due to non-payment.','2026-03-21 08:10:01','System','System'),(619,92,'Reservation Submitted','Room: Single | Status: Pending','2026-03-21 09:32:17','Diane Tayson (Super Admin)','Super Admin'),(620,92,'Reservation Cancelled','Reservation #129 auto-expired due to non-payment.','2026-03-22 17:04:31','System','System'),(621,92,'Penalty Applied','Late fee of 480.00 applied for Payment #184','2026-03-23 03:57:48','System','System'),(622,40,'Penalty Applied','Late fee of 0.31 applied for Payment #180','2026-03-23 11:55:46','System','System'),(623,40,'Penalty Applied','Late fee of 0.31 applied for Payment #181','2026-03-23 11:55:48','System','System'),(624,40,'Penalty Applied','Late fee of 0.31 applied for Payment #182','2026-03-23 11:55:50','System','System'),(625,92,'Penalty Applied','Late fee of 850.00 applied for Payment #185','2026-03-26 05:29:00','System','System'),(626,93,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-03-26 08:01:59','Diane Tayson (Super Admin)','Super Admin'),(627,93,'Payment Submitted','Reservation #130 via GCash for 1 bill(s)','2026-03-26 08:02:42','Diane Tayson (Super Admin)','Super Admin'),(628,93,'Profile Update Approved','Profile changes approved by Super Admin.','2026-03-26 08:24:00','Diane Tayson (Super Admin)','Super Admin'),(629,93,'Reservation Rejected','Reservation #130 cancelled by Super Admin.','2026-03-26 09:04:59','Diane Tayson (Super Admin)','Super Admin'),(630,93,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-03-26 09:06:39','Diane Tayson (Super Admin)','Super Admin'),(631,94,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-03-26 14:13:45','Diane Tayson (Super Admin)','Super Admin'),(632,95,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-03-26 15:31:22','Diane Tayson (Super Admin)','Super Admin'),(633,95,'Reservation Approved','Reservation #133 approved by Super Admin.','2026-03-26 15:32:11','Diane Tayson (Super Admin)','Super Admin'),(634,95,'Signature Requested','Signature requested for Reservation #133 by Super Admin','2026-03-26 15:32:20','Diane Tayson (Super Admin)','Super Admin'),(635,95,'Lease Signed','Reservation #133','2026-03-26 15:32:31','Diane Tayson (Super Admin)','Super Admin'),(636,96,'Reservation Submitted','Room: Single | Status: Pending','2026-03-26 16:27:10','Diane Tayson (Super Admin)','Super Admin'),(637,96,'Reservation Approved','Reservation #134 approved by Super Admin.','2026-03-26 16:34:49','Diane Tayson (Super Admin)','Super Admin'),(638,96,'Signature Requested','Signature requested for Reservation #134 by Super Admin','2026-03-26 16:34:56','Diane Tayson (Super Admin)','Super Admin'),(639,96,'Lease Signed','Reservation #134','2026-03-26 16:35:10','Diane Tayson (Super Admin)','Super Admin'),(640,96,'Payment Submitted','Reservation #134 via Cash for 1 bill(s)','2026-03-26 16:35:13','Diane Tayson (Super Admin)','Super Admin'),(641,96,'Profile Update Approved','Profile changes approved by Super Admin.','2026-03-26 16:59:22','Diane Tayson (Super Admin)','Super Admin'),(642,97,'Reservation Submitted','Room: Single | Status: Pending','2026-03-26 17:01:07','Diane Tayson (Super Admin)','Super Admin'),(643,97,'Payment Submitted','Reservation #135 via Cash for 1 bill(s)','2026-03-26 17:01:11','Diane Tayson (Super Admin)','Super Admin'),(644,97,'Reservation Approved','Reservation #135 approved by Super Admin.','2026-03-26 17:01:26','Diane Tayson (Super Admin)','Super Admin'),(645,93,'Reservation Cancelled','Reservation #131 auto-expired due to non-payment.','2026-03-27 10:47:58','System','System'),(646,94,'Reservation Cancelled','Reservation #132 auto-expired due to non-payment.','2026-03-27 16:18:58','Diane Tayson (Super Admin)','Super Admin'),(647,92,'Reservation Submitted','Room: Single | Status: Pending','2026-03-27 16:22:07','Diane Tayson (Super Admin)','Super Admin'),(648,92,'Payment Submitted','Reservation #136 via Cash for 1 bill(s)','2026-03-27 16:22:17','Diane Tayson (Super Admin)','Super Admin'),(649,92,'Reservation Approved','Reservation #136 approved by Super Admin.','2026-03-27 16:22:30','Diane Tayson (Super Admin)','Super Admin'),(650,92,'Contract Completed','Reservation #136 automatically marked as Completed (End date reached).','2026-03-27 16:22:30','System','System'),(651,92,'Payment Cancelled','Payment #185 cancelled by Super Admin.','2026-03-27 16:22:47','Diane Tayson (Super Admin)','Super Admin'),(652,92,'Payment Cancelled','Payment #184 cancelled by Super Admin.','2026-03-27 16:22:56','Diane Tayson (Super Admin)','Super Admin'),(653,92,'Payment Cancelled','Payment #186 cancelled by Super Admin.','2026-03-27 16:23:05','Diane Tayson (Super Admin)','Super Admin'),(654,92,'Payment Cancelled','Payment #190 cancelled by Super Admin.','2026-03-27 16:23:12','Diane Tayson (Super Admin)','Super Admin'),(655,92,'Payment Confirmed','Payment #197 marked as Paid by Super Admin.','2026-03-27 16:23:34','Diane Tayson (Super Admin)','Super Admin'),(656,98,'Account Created','Walk-in account created by Super Admin','2026-03-27 16:26:32','Diane Tayson (Super Admin)','Super Admin'),(657,99,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-03-28 14:40:51','Diane Tayson (Super Admin)','Super Admin'),(658,99,'Reservation Approved','Reservation #137 approved by Super Admin.','2026-03-28 14:41:16','Diane Tayson (Super Admin)','Super Admin'),(659,99,'Signature Requested','Signature requested for Reservation #137 by Super Admin','2026-03-28 14:41:23','Diane Tayson (Super Admin)','Super Admin'),(660,99,'Lease Signed','Reservation #137','2026-03-28 14:41:31','Diane Tayson (Super Admin)','Super Admin'),(661,99,'Payment Submitted','Reservation #137 via Cash for 1 bill(s)','2026-03-28 14:41:36','Diane Tayson (Super Admin)','Super Admin'),(662,99,'Payment Submitted','Reservation #137 via Cash for 1 bill(s)','2026-03-28 14:51:17','Diane Tayson (Super Admin)','Super Admin'),(663,99,'Contract Completed','Reservation #137 automatically marked as Completed (End date reached).','2026-03-28 16:14:51','Diane Tayson (Super Admin)','Super Admin');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `role` enum('Super Admin','Admin') DEFAULT 'Admin',
  `first_name` varchar(50) DEFAULT '',
  `last_name` varchar(50) DEFAULT '',
  `email` varchar(100) DEFAULT '',
  `phone_number` varchar(20) DEFAULT '',
  `profile_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'Super Admin','super123','Super Admin','Diane','Tayson','dianetyson@gmail.com','09987345621','admin_1_1773634055.jpg'),(6,'Stephen Squad PH','stephensquadph03','Super Admin','Stephen','Begosa','stephenbegosa@gmail.com','09662285702',NULL);
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_password_history`
--

DROP TABLE IF EXISTS `admin_password_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_password_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_password_history`
--

LOCK TABLES `admin_password_history` WRITE;
/*!40000 ALTER TABLE `admin_password_history` DISABLE KEYS */;
INSERT INTO `admin_password_history` VALUES (1,'Super Admin','super123','2026-03-05 17:11:36');
/*!40000 ALTER TABLE `admin_password_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(100) NOT NULL,
  `branch_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`branch_id`),
  UNIQUE KEY `branch_name` (`branch_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'KANLAON','MANDALUYONG','2026-03-11 05:07:40'),(2,'POBLACION','MAKATI','2026-03-11 05:07:40');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `housekeeping_requests`
--

DROP TABLE IF EXISTS `housekeeping_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `housekeeping_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `room_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Pending','Scheduled','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `scheduled_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cost` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`request_id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `fk_hk_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `housekeeping_requests`
--

LOCK TABLES `housekeeping_requests` WRITE;
/*!40000 ALTER TABLE `housekeeping_requests` DISABLE KEYS */;
INSERT INTO `housekeeping_requests` VALUES (13,40,7,'Weekly Routine Cleaning','Scheduled','2026-03-28','2026-03-08 14:31:21',0.00);
/*!40000 ALTER TABLE `housekeeping_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_items`
--

DROP TABLE IF EXISTS `inventory_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT 'General',
  `room_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `status` enum('Good','Damaged','Repair','Lost') DEFAULT 'Good',
  `purchase_date` date DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_items`
--

LOCK TABLES `inventory_items` WRITE;
/*!40000 ALTER TABLE `inventory_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `key_transactions`
--

DROP TABLE IF EXISTS `key_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `key_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `released_at` datetime DEFAULT current_timestamp(),
  `returned_at` datetime DEFAULT NULL,
  `status` enum('Active','Returned') DEFAULT 'Active',
  `holder_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `key_id` (`key_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `key_transactions_ibfk_1` FOREIGN KEY (`key_id`) REFERENCES `keys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `key_transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `key_transactions`
--

LOCK TABLES `key_transactions` WRITE;
/*!40000 ALTER TABLE `key_transactions` DISABLE KEYS */;
INSERT INTO `key_transactions` VALUES (12,15,40,'2026-03-14 04:13:06','2026-03-14 04:13:14','Returned','');
/*!40000 ALTER TABLE `key_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `keys`
--

DROP TABLE IF EXISTS `keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL,
  `type` enum('Room','Parking') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `status` enum('Available','Released') DEFAULT 'Available',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=271 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `keys`
--

LOCK TABLES `keys` WRITE;
/*!40000 ALTER TABLE `keys` DISABLE KEYS */;
INSERT INTO `keys` VALUES (9,'Room 202 Key','Room',6,'Available'),(10,'Room 302 Key','Room',7,'Available'),(11,'Room 203 Key','Room',16,'Available'),(12,'Room 204 Key','Room',17,'Available'),(13,'Room 303 Key','Room',18,'Available'),(14,'Room 203 Key','Room',19,'Available'),(15,'Room 401 Key','Room',20,'Available'),(16,'Room 501 Key','Room',21,'Available'),(17,'Room 601 Key','Room',22,'Available'),(18,'Room 701 Key','Room',23,'Available'),(19,'Room 402 Key','Room',24,'Available'),(20,'Room 502 Key','Room',25,'Available'),(21,'Room 602 Key','Room',26,'Available'),(22,'Room 702 Key','Room',27,'Available'),(23,'Room 403 Key','Room',28,'Available'),(24,'Room 503 Key','Room',29,'Available'),(25,'Room 603 Key','Room',30,'Available'),(26,'Room 703 Key','Room',31,'Available'),(27,'Room 204 Key','Room',32,'Available'),(29,'Room 206 Key','Room',34,'Available'),(30,'Room 207 Key','Room',35,'Available'),(31,'Room 208 Key','Room',36,'Available'),(32,'Room 209 Key','Room',37,'Available'),(33,'Room 308 Key','Room',38,'Available'),(34,'Room 309 Key','Room',39,'Available'),(35,'Room 304 Key','Room',40,'Available'),(36,'Room 305 Key','Room',41,'Available'),(37,'Room 306 Key','Room',42,'Available'),(38,'Room 307 Key','Room',43,'Available'),(39,'Room 205 Key','Room',44,'Available'),(41,'Room 404 Key','Room',46,'Available'),(42,'Room 405 Key','Room',47,'Available'),(43,'Room 406 Key','Room',48,'Available'),(44,'Room 407 Key','Room',49,'Available'),(45,'Room 408 Key','Room',50,'Available'),(46,'Room 409 Key','Room',51,'Available'),(47,'Room 302 Key','Room',52,'Available'),(48,'Room 508 Key','Room',53,'Available'),(49,'Room 509 Key','Room',54,'Available'),(50,'Room 608 Key','Room',55,'Available'),(51,'Room 609 Key','Room',56,'Available'),(52,'Room 708 Key','Room',57,'Available'),(54,'Room 203 Key','Room',59,'Available'),(55,'Room 504 Key','Room',60,'Available'),(56,'Room 505 Key','Room',61,'Available'),(57,'Room 506 Key','Room',62,'Available'),(58,'Room 507 Key','Room',63,'Available'),(59,'Room 604 Key','Room',64,'Available'),(60,'Room 605 Key','Room',65,'Available'),(61,'Room 606 Key','Room',66,'Available'),(62,'Room 607 Key','Room',67,'Available'),(63,'Room 704 Key','Room',68,'Available'),(64,'Room 705 Key','Room',69,'Available'),(67,'Room 201 Key','Room',45,'Available'),(68,'Room 709 Key','Room',58,'Available'),(69,'Room 706 Key','Room',70,'Available'),(70,'Room 707 Key','Room',71,'Available'),(73,'Room 202 Key #2','Room',6,'Released'),(74,'Room 202 Key #3','Room',6,'Released'),(75,'Room 202 Key #4','Room',6,'Available'),(76,'Room 202 Key #5','Room',6,'Available'),(77,'Room 202 Key #6','Room',6,'Available'),(78,'Room 303 Key #2','Room',7,'Available'),(79,'Room 303 Key #3','Room',7,'Available'),(80,'Room 303 Key #4','Room',7,'Available'),(81,'Room 402 Key #2','Room',24,'Available'),(82,'Room 402 Key #3','Room',24,'Available'),(83,'Room 402 Key #4','Room',24,'Available'),(84,'Room 402 Key #5','Room',24,'Available'),(85,'Room 402 Key #6','Room',24,'Available'),(86,'Room 502 Key #2','Room',25,'Available'),(87,'Room 502 Key #3','Room',25,'Available'),(88,'Room 502 Key #4','Room',25,'Available'),(89,'Room 502 Key #5','Room',25,'Available'),(90,'Room 502 Key #6','Room',25,'Available'),(91,'Room 602 Key #2','Room',26,'Available'),(92,'Room 602 Key #3','Room',26,'Available'),(93,'Room 602 Key #4','Room',26,'Available'),(94,'Room 602 Key #5','Room',26,'Available'),(95,'Room 602 Key #6','Room',26,'Available'),(96,'Room 702 Key #2','Room',27,'Available'),(97,'Room 702 Key #3','Room',27,'Available'),(98,'Room 702 Key #4','Room',27,'Available'),(99,'Room 702 Key #5','Room',27,'Available'),(100,'Room 702 Key #6','Room',27,'Available'),(101,'Room 403 Key #2','Room',28,'Available'),(102,'Room 403 Key #3','Room',28,'Available'),(103,'Room 403 Key #4','Room',28,'Available'),(104,'Room 503 Key #2','Room',29,'Available'),(105,'Room 503 Key #3','Room',29,'Available'),(106,'Room 503 Key #4','Room',29,'Available'),(107,'Room 603 Key #2','Room',30,'Available'),(108,'Room 603 Key #3','Room',30,'Available'),(109,'Room 603 Key #4','Room',30,'Available'),(110,'Room 703 Key #2','Room',31,'Available'),(111,'Room 703 Key #3','Room',31,'Available'),(112,'Room 703 Key #4','Room',31,'Available'),(113,'Room 204 Key #2','Room',32,'Available'),(114,'Room 204 Key #3','Room',32,'Available'),(115,'Room 204 Key #4','Room',32,'Available'),(116,'Room 206 Key #2','Room',34,'Available'),(117,'Room 206 Key #3','Room',34,'Available'),(118,'Room 206 Key #4','Room',34,'Available'),(119,'Room 207 Key #2','Room',35,'Available'),(120,'Room 207 Key #3','Room',35,'Available'),(121,'Room 207 Key #4','Room',35,'Available'),(122,'Room 208 Key #2','Room',36,'Available'),(123,'Room 208 Key #3','Room',36,'Available'),(124,'Room 208 Key #4','Room',36,'Available'),(125,'Room 208 Key #5','Room',36,'Available'),(126,'Room 208 Key #6','Room',36,'Available'),(127,'Room 209 Key #2','Room',37,'Available'),(128,'Room 209 Key #3','Room',37,'Available'),(129,'Room 209 Key #4','Room',37,'Available'),(130,'Room 209 Key #5','Room',37,'Available'),(131,'Room 209 Key #6','Room',37,'Available'),(132,'Room 308 Key #2','Room',38,'Available'),(133,'Room 308 Key #3','Room',38,'Available'),(134,'Room 308 Key #4','Room',38,'Available'),(135,'Room 308 Key #5','Room',38,'Available'),(136,'Room 308 Key #6','Room',38,'Available'),(137,'Room 309 Key #2','Room',39,'Available'),(138,'Room 309 Key #3','Room',39,'Available'),(139,'Room 309 Key #4','Room',39,'Available'),(140,'Room 309 Key #5','Room',39,'Available'),(141,'Room 309 Key #6','Room',39,'Available'),(142,'Room 304 Key #2','Room',40,'Available'),(143,'Room 304 Key #3','Room',40,'Available'),(144,'Room 304 Key #4','Room',40,'Available'),(145,'Room 305 Key #2','Room',41,'Available'),(146,'Room 305 Key #3','Room',41,'Available'),(147,'Room 305 Key #4','Room',41,'Available'),(148,'Room 306 Key #2','Room',42,'Available'),(149,'Room 306 Key #3','Room',42,'Available'),(150,'Room 306 Key #4','Room',42,'Available'),(151,'Room 307 Key #2','Room',43,'Available'),(152,'Room 307 Key #3','Room',43,'Available'),(153,'Room 307 Key #4','Room',43,'Available'),(154,'Room 205 Key #2','Room',44,'Available'),(155,'Room 205 Key #3','Room',44,'Available'),(156,'Room 205 Key #4','Room',44,'Available'),(157,'Room 404 Key #2','Room',46,'Available'),(158,'Room 404 Key #3','Room',46,'Available'),(159,'Room 404 Key #4','Room',46,'Available'),(160,'Room 405 Key #2','Room',47,'Available'),(161,'Room 405 Key #3','Room',47,'Available'),(162,'Room 405 Key #4','Room',47,'Available'),(163,'Room 406 Key #2','Room',48,'Available'),(164,'Room 406 Key #3','Room',48,'Available'),(165,'Room 406 Key #4','Room',48,'Available'),(166,'Room 407 Key #2','Room',49,'Available'),(167,'Room 407 Key #3','Room',49,'Available'),(168,'Room 407 Key #4','Room',49,'Available'),(169,'Room 408 Key #2','Room',50,'Available'),(170,'Room 408 Key #3','Room',50,'Available'),(171,'Room 408 Key #4','Room',50,'Available'),(172,'Room 408 Key #5','Room',50,'Available'),(173,'Room 408 Key #6','Room',50,'Available'),(174,'Room 409 Key #2','Room',51,'Available'),(175,'Room 409 Key #3','Room',51,'Available'),(176,'Room 409 Key #4','Room',51,'Available'),(177,'Room 409 Key #5','Room',51,'Available'),(178,'Room 409 Key #6','Room',51,'Available'),(179,'Room 302 Key #2','Room',52,'Available'),(180,'Room 302 Key #3','Room',52,'Available'),(181,'Room 302 Key #4','Room',52,'Available'),(182,'Room 302 Key #5','Room',52,'Available'),(183,'Room 302 Key #6','Room',52,'Available'),(184,'Room 508 Key #2','Room',53,'Available'),(185,'Room 508 Key #3','Room',53,'Available'),(186,'Room 508 Key #4','Room',53,'Available'),(187,'Room 508 Key #5','Room',53,'Available'),(188,'Room 508 Key #6','Room',53,'Available'),(189,'Room 509 Key #2','Room',54,'Available'),(190,'Room 509 Key #3','Room',54,'Available'),(191,'Room 509 Key #4','Room',54,'Available'),(192,'Room 509 Key #5','Room',54,'Available'),(193,'Room 509 Key #6','Room',54,'Available'),(194,'Room 608 Key #2','Room',55,'Available'),(195,'Room 608 Key #3','Room',55,'Available'),(196,'Room 608 Key #4','Room',55,'Available'),(197,'Room 608 Key #5','Room',55,'Available'),(198,'Room 608 Key #6','Room',55,'Available'),(199,'Room 609 Key #2','Room',56,'Available'),(200,'Room 609 Key #3','Room',56,'Available'),(201,'Room 609 Key #4','Room',56,'Available'),(202,'Room 609 Key #5','Room',56,'Available'),(203,'Room 609 Key #6','Room',56,'Available'),(204,'Room 708 Key #2','Room',57,'Available'),(205,'Room 708 Key #3','Room',57,'Available'),(206,'Room 708 Key #4','Room',57,'Available'),(207,'Room 708 Key #5','Room',57,'Available'),(208,'Room 708 Key #6','Room',57,'Available'),(209,'Room 709 Key #2','Room',58,'Available'),(210,'Room 709 Key #3','Room',58,'Available'),(211,'Room 709 Key #4','Room',58,'Available'),(212,'Room 709 Key #5','Room',58,'Available'),(213,'Room 709 Key #6','Room',58,'Available'),(214,'Room 203 Key #2','Room',59,'Available'),(215,'Room 203 Key #3','Room',59,'Available'),(216,'Room 203 Key #4','Room',59,'Available'),(217,'Room 504 Key #2','Room',60,'Available'),(218,'Room 504 Key #3','Room',60,'Available'),(219,'Room 504 Key #4','Room',60,'Available'),(220,'Room 505 Key #2','Room',61,'Available'),(221,'Room 505 Key #3','Room',61,'Available'),(222,'Room 505 Key #4','Room',61,'Available'),(223,'Room 506 Key #2','Room',62,'Available'),(224,'Room 506 Key #3','Room',62,'Available'),(225,'Room 506 Key #4','Room',62,'Available'),(226,'Room 507 Key #2','Room',63,'Available'),(227,'Room 507 Key #3','Room',63,'Available'),(228,'Room 507 Key #4','Room',63,'Available'),(229,'Room 604 Key #2','Room',64,'Available'),(230,'Room 604 Key #3','Room',64,'Available'),(231,'Room 604 Key #4','Room',64,'Available'),(232,'Room 605 Key #2','Room',65,'Available'),(233,'Room 605 Key #3','Room',65,'Available'),(234,'Room 605 Key #4','Room',65,'Available'),(235,'Room 606 Key #2','Room',66,'Available'),(236,'Room 606 Key #3','Room',66,'Available'),(237,'Room 606 Key #4','Room',66,'Available'),(238,'Room 607 Key #2','Room',67,'Available'),(239,'Room 607 Key #3','Room',67,'Available'),(240,'Room 607 Key #4','Room',67,'Available'),(241,'Room 704 Key #2','Room',68,'Available'),(242,'Room 704 Key #3','Room',68,'Available'),(243,'Room 704 Key #4','Room',68,'Available'),(244,'Room 705 Key #2','Room',69,'Available'),(245,'Room 705 Key #3','Room',69,'Available'),(246,'Room 705 Key #4','Room',69,'Available'),(247,'Room 706 Key #2','Room',70,'Available'),(248,'Room 706 Key #3','Room',70,'Available'),(249,'Room 706 Key #4','Room',70,'Available'),(250,'Room 707 Key #2','Room',71,'Available'),(251,'Room 707 Key #3','Room',71,'Available'),(252,'Room 707 Key #4','Room',71,'Available'),(261,'Room 205 Key #1','Room',74,'Available'),(262,'Room 205 Key #2','Room',74,'Available'),(263,'Room 205 Key #3','Room',74,'Available'),(264,'Room 205 Key #4','Room',74,'Available'),(265,'Room Room 302 Key #1','Room',75,'Available'),(266,'Room Room 302 Key #2','Room',75,'Available'),(267,'Room Room 302 Key #3','Room',75,'Available'),(268,'Room Room 302 Key #4','Room',75,'Available'),(269,'Room Room 302 Key #5','Room',75,'Available'),(270,'Room Room 302 Key #6','Room',75,'Available');
/*!40000 ALTER TABLE `keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_requests`
--

DROP TABLE IF EXISTS `maintenance_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `room_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Pending','Scheduled','Completed','Cancelled') DEFAULT 'Pending',
  `scheduled_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cost` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`request_id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `maintenance_requests_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_requests`
--

LOCK TABLES `maintenance_requests` WRITE;
/*!40000 ALTER TABLE `maintenance_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenance_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(256) NOT NULL,
  `created_at` varchar(256) NOT NULL,
  `is_read` varchar(256) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'System',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1143 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (504,'40','2026-02-26 09:57:57','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(513,'40','2026-02-26 21:31:42','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(526,'40','2026-02-27 15:28:35','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(532,'40','2026-02-28 21:46:01','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(536,'40','2026-02-28 21:54:57','1','✅ <strong>Payment Confirmed</strong><br>Your payment #55 has been verified and marked as Paid.','Payment Update'),(707,'40','2026-03-03 13:28:50','1','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Car Slot 1. A fee of ₱600.00 has been added to your account.','Parking'),(708,'40','2026-03-03 14:19:47','1','🔑 <strong>Key Assigned</strong><br>You have been assigned a key. Please keep it safe.','Key System'),(709,'40','2026-03-03 14:20:14','1','🔑 <strong>Key Returned</strong><br>Key has been marked as returned.','Key System'),(710,'40','2026-03-03 14:21:51','1','🔑 <strong>Key Assigned</strong><br>You have been assigned a key. Please keep it safe.','Key System'),(711,'40','2026-03-03 14:21:58','1','🔑 <strong>Key Returned</strong><br>Key has been marked as returned.','Key System'),(713,'40','2026-03-05 18:01:58','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(726,'40','2026-03-05 22:01:59','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(737,'40','2026-03-06 02:01:59','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(740,'40','2026-03-06 11:25:54','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(743,'40','2026-03-06 15:25:55','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(748,'40','2026-03-06 23:34:31','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(751,'40','2026-03-07 12:58:52','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(754,'40','2026-03-07 17:21:42','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(768,'40','2026-03-07 21:22:35','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(771,'40','2026-03-08 13:05:01','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(774,'40','2026-03-08 21:41:45','1','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱30.00 has been applied to your account due to overdue payment.','Billing Alert'),(791,'40','2026-03-08 22:16:52','1','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #1 has been marked as completed.','Parking'),(800,'40','2026-03-08 22:31:21','1','🧹 <strong>Weekly Cleaning</strong><br>Routine housekeeping scheduled for Mar 28, 2026.','Housekeeping'),(821,'40','2026-03-09 04:19:32','1','✅ <strong>Payment Confirmed</strong><br>Your payment #103 has been verified and marked as Paid.','Payment Update'),(822,'40','2026-03-09 04:19:38','1','✅ <strong>Payment Confirmed</strong><br>Your payment #110 has been verified and marked as Paid.','Payment Update'),(823,'40','2026-03-09 04:19:49','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #51. Please go to My Reservations to sign.','Action Required'),(824,'40','2026-03-09 04:20:41','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>203</strong>.','System'),(838,'40','2026-03-09 20:49:30','1','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Car Slot 1. A fee of ₱600.00 has been added to your account.','Parking'),(839,'40','2026-03-09 20:49:36','1','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #11 has been marked as completed.','Parking'),(840,'40','2026-03-09 22:06:31','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>201</strong>.','System'),(841,'40','2026-03-10 00:28:34','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>203</strong>.','System'),(842,'40','2026-03-10 01:01:47','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>201</strong>.','System'),(843,'40','2026-03-10 03:02:19','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>1 Bed</strong>.','System'),(844,'40','2026-03-10 11:48:49','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 09, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(852,'40','2026-03-10 17:55:44','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 09, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(859,'40','2026-03-12 21:24:55','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 09, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(875,'40','2026-03-13 00:39:27','1','✅ <strong>Payment Confirmed</strong><br>Your payment #123 has been verified and marked as Paid.','Payment Update'),(938,'40','2026-03-14 04:13:06','1','🔑 <strong>Key Assigned</strong><br>You have been assigned a key (ID: 15). Please keep it safe.','Key System'),(939,'40','2026-03-14 04:13:14','1','🔑 <strong>Key Returned</strong><br>Key (ID: 15) has been marked as returned.','Key System'),(1032,'40','2026-03-18 02:00:14','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #51. Please go to My Reservations to sign.','Action Required'),(1033,'90','2026-03-18 02:18:38','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1034,'90','2026-03-18 02:18:38','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 17, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1035,'90','2026-03-18 02:20:42','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1036,'90','2026-03-18 02:20:47','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #125. Please go to My Reservations to sign.','Action Required'),(1037,'90','2026-03-18 02:21:33','','✅ <strong>Payment Confirmed</strong><br>Your payment #178 has been verified and marked as Paid.','Payment Update'),(1038,'90','2026-03-18 02:44:50','','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #125 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1039,'90','2026-03-18 02:45:34','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1040,'90','2026-03-18 02:46:00','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1041,'90','2026-03-18 02:46:06','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #126. Please go to My Reservations to sign.','Action Required'),(1042,'90','2026-03-18 02:47:31','','✅ <strong>Payment Confirmed</strong><br>Your payment #179 has been verified and marked as Paid.','Payment Update'),(1043,'40','2026-03-18 11:32:20','','🧾 <strong>New Utility Bill</strong><br>A utility bill of ₱6.24 has been generated for your room.','Billing'),(1044,'40','2026-03-18 11:32:22','','🧾 <strong>New Utility Bill</strong><br>A utility bill of ₱6.24 has been generated for your room.','Billing'),(1045,'40','2026-03-18 11:32:24','','🧾 <strong>New Utility Bill</strong><br>A utility bill of ₱6.24 has been generated for your room.','Billing'),(1046,'91','2026-03-18 11:42:52','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1047,'91','2026-03-18 11:42:53','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1048,'91','2026-03-18 11:44:19','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1049,'91','2026-03-18 11:44:27','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #127. Please go to My Reservations to sign.','Action Required'),(1050,'91','2026-03-18 11:45:48','','✅ <strong>Payment Confirmed</strong><br>Your payment #183 has been verified and marked as Paid.','Payment Update'),(1051,'92','2026-03-18 11:56:08','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1052,'92','2026-03-18 11:56:10','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1053,'91','2026-03-18 11:59:02','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #127. Please go to My Reservations to sign.','Action Required'),(1054,'91','2026-03-18 12:11:29','','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #127 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1055,'40','2026-03-21 16:10:01','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱6.24 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1056,'92','2026-03-21 16:10:03','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1057,'92','2026-03-21 17:32:15','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1058,'40','2026-03-21 20:28:16','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱6.24 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1059,'92','2026-03-21 20:28:16','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1060,'40','2026-03-23 01:04:31','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱6.24 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1061,'92','2026-03-23 01:04:33','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1062,'40','2026-03-23 11:57:46','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱6.24 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1063,'92','2026-03-23 11:57:48','1','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱480.00 has been applied to your account due to overdue payment.','Billing Alert'),(1064,'92','2026-03-23 11:57:50','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 21, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1065,'40','2026-03-23 19:55:46','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱0.31 has been applied to your account due to overdue payment.','Billing Alert'),(1066,'40','2026-03-23 19:55:48','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱0.31 has been applied to your account due to overdue payment.','Billing Alert'),(1067,'40','2026-03-23 19:55:50','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱0.31 has been applied to your account due to overdue payment.','Billing Alert'),(1068,'92','2026-03-23 19:55:52','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 21, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1069,'92','2026-03-25 17:44:36','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 21, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1070,'92','2026-03-25 21:45:09','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 21, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1071,'92','2026-03-26 13:29:00','1','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱850.00 has been applied to your account due to overdue payment.','Billing Alert'),(1072,'93','2026-03-26 16:01:57','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1073,'93','2026-03-26 16:01:58','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1074,'93','2026-03-26 16:24:00','1','✅ <strong>Profile Update Approved</strong><br>Your profile information has been updated.','System'),(1075,'93','2026-03-26 17:04:59','1','❌ <strong>Reservation Rejected</strong><br>Your booking #130 has been cancelled. Please contact support for details.','Booking Rejected'),(1076,'93','2026-03-26 17:06:37','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1077,'93','2026-03-26 20:02:02','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1078,'94','2026-03-26 22:13:42','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1079,'94','2026-03-26 22:13:45','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1080,'95','2026-03-26 23:31:20','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1081,'95','2026-03-26 23:31:21','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1082,'95','2026-03-26 23:32:11','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1083,'95','2026-03-26 23:32:18','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #133. Please go to My Reservations to sign.','Action Required'),(1084,'93','2026-03-27 00:02:58','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1085,'96','2026-03-27 00:27:08','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1086,'96','2026-03-27 00:27:08','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1087,'96','2026-03-27 00:34:49','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1088,'96','2026-03-27 00:34:54','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #134. Please go to My Reservations to sign.','Action Required'),(1089,'96','2026-03-27 00:59:22','','✅ <strong>Profile Update Approved</strong><br>Your profile information has been updated.','System'),(1090,'97','2026-03-27 01:01:05','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1091,'97','2026-03-27 01:01:06','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1092,'97','2026-03-27 01:01:26','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1093,'93','2026-03-27 11:05:55','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1094,'94','2026-03-27 11:05:55','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1095,'95','2026-03-27 11:05:57','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1096,'96','2026-03-27 11:05:57','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1097,'97','2026-03-27 11:05:59','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1098,'93','2026-03-27 18:47:58','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1099,'94','2026-03-27 18:48:00','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1100,'95','2026-03-27 18:48:02','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1101,'96','2026-03-27 18:48:04','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1102,'97','2026-03-27 18:48:06','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1103,'93','2026-03-28 00:18:58','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1104,'94','2026-03-28 00:19:00','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1105,'95','2026-03-28 00:19:02','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1106,'96','2026-03-28 00:19:04','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1107,'97','2026-03-28 00:19:06','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1108,'92','2026-03-28 00:22:05','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1109,'92','2026-03-28 00:22:07','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱1,200.00 was due on Mar 27, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1110,'92','2026-03-28 00:22:30','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1111,'92','2026-03-28 00:22:30','1','🏁 <strong>Stay Completed</strong><br>Your stay for reservation #136 has reached its scheduled end date and is now marked as Completed. Thank you for staying with us!','Contract Ended'),(1112,'92','2026-03-28 00:22:47','1','❌ <strong>Payment Cancelled</strong><br>Your payment #185 has been cancelled.','Payment Update'),(1113,'92','2026-03-28 00:22:56','1','❌ <strong>Payment Cancelled</strong><br>Your payment #184 has been cancelled.','Payment Update'),(1114,'92','2026-03-28 00:23:05','1','❌ <strong>Payment Cancelled</strong><br>Your payment #186 has been cancelled.','Payment Update'),(1115,'92','2026-03-28 00:23:12','1','❌ <strong>Payment Cancelled</strong><br>Your payment #190 has been cancelled.','Payment Update'),(1116,'92','2026-03-28 00:23:34','1','✅ <strong>Payment Confirmed</strong><br>Your payment #197 has been verified and marked as Paid.','Payment Update'),(1117,'93','2026-03-28 12:30:10','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1118,'94','2026-03-28 12:30:12','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1119,'95','2026-03-28 12:30:13','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1120,'96','2026-03-28 12:30:14','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1121,'97','2026-03-28 12:30:15','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1122,'93','2026-03-28 18:55:45','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1123,'94','2026-03-28 18:55:47','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1124,'95','2026-03-28 18:55:49','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1125,'96','2026-03-28 18:55:51','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1126,'97','2026-03-28 18:55:53','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1127,'99','2026-03-28 22:40:49','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1128,'99','2026-03-28 22:41:16','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1129,'99','2026-03-28 22:41:17','','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>4 Beds</strong> ends on <strong>2026-03-29</strong> (1 days left). Please contact admin to renew.','Expiration Alert'),(1130,'99','2026-03-28 22:41:21','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #137. Please go to My Reservations to sign.','Action Required'),(1131,'93','2026-03-28 23:21:36','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1132,'94','2026-03-28 23:21:36','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1133,'95','2026-03-28 23:21:38','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1134,'96','2026-03-28 23:21:39','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1135,'97','2026-03-28 23:21:40','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1136,'99','2026-03-29 00:14:51','','🏁 <strong>Stay Completed</strong><br>Your stay for reservation #137 has reached its scheduled end date and is now marked as Completed. Thank you for staying with us!','Contract Ended'),(1137,'93','2026-03-30 11:57:20','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1138,'94','2026-03-30 11:57:22','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1139,'95','2026-03-30 11:57:24','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1140,'96','2026-03-30 11:57:26','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1141,'97','2026-03-30 11:57:28','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1142,'99','2026-03-30 11:57:30','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱700.00 was due on Mar 28, 2026. Please pay immediately to avoid penalties.','Payment Warning');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parking_reservations`
--

DROP TABLE IF EXISTS `parking_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parking_reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `slot_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `billing_type` enum('Monthly','Daily') NOT NULL,
  `status` enum('Active','Completed') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `slot_id` (`slot_id`),
  CONSTRAINT `parking_reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `parking_reservations_ibfk_2` FOREIGN KEY (`slot_id`) REFERENCES `parking_slots` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_reservations`
--

LOCK TABLES `parking_reservations` WRITE;
/*!40000 ALTER TABLE `parking_reservations` DISABLE KEYS */;
INSERT INTO `parking_reservations` VALUES (11,40,11,'2026-03-09','2026-03-09',600.00,'Monthly','Completed','2026-03-09 12:49:30');
/*!40000 ALTER TABLE `parking_reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parking_slots`
--

DROP TABLE IF EXISTS `parking_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parking_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slot_name` varchar(50) NOT NULL,
  `slot_type` enum('Car','Motorcycle') NOT NULL,
  `status` enum('Available','Occupied') DEFAULT 'Available',
  `monthly_rate` decimal(10,2) NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_slots`
--

LOCK TABLES `parking_slots` WRITE;
/*!40000 ALTER TABLE `parking_slots` DISABLE KEYS */;
INSERT INTO `parking_slots` VALUES (11,'Car Slot 1','Car','Occupied',600.00,200.00,0),(12,'Car Slot 2','Car','Occupied',600.00,200.00,0),(13,'Car Slot 3','Car','Occupied',600.00,200.00,0),(14,'Car Slot 4','Car','Available',600.00,200.00,0),(15,'Motorcycle Slot 1','Motorcycle','Available',1500.00,50.00,0),(16,'Motorcycle Slot 2','Motorcycle','Available',1500.00,50.00,0),(17,'Motorcycle Slot 3','Motorcycle','Available',1500.00,50.00,0),(18,'Motorcycle Slot 4','Motorcycle','Available',1500.00,50.00,0),(19,'Motorcycle Slot 5','Motorcycle','Occupied',1500.00,50.00,0),(20,'Motorcycle Slot 6','Motorcycle','Available',1500.00,50.00,0),(21,'Motorcycle Slot 7','Motorcycle','Available',1500.00,50.00,0);
/*!40000 ALTER TABLE `parking_slots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','GCash','Bank Transfer') DEFAULT NULL,
  `payment_status` enum('Paid','Unpaid','Cancelled') DEFAULT 'Unpaid',
  `payment_date` timestamp NULL DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT 'Room Payment',
  `is_penalized` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`payment_id`),
  KEY `reservation_id` (`reservation_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (55,51,28200.00,'Cash','Paid','2026-02-28 13:54:57',NULL,NULL,'Room Payment',0),(103,51,600.00,'Cash','Paid','2026-03-08 20:19:32',NULL,NULL,'Monthly Parking Fee (March 2026) for Car Slot 1 (Parking ID: 2)',1),(110,51,30.00,'','Paid','2026-03-08 20:19:38',NULL,NULL,'Late Penalty (5%) for Payment #103',0),(123,51,600.00,'Cash','Paid','2026-03-12 16:39:27',NULL,NULL,'Monthly Parking Fee (March 2026) for Car Slot 1 (Parking ID: 11)',0),(178,125,17000.00,'Cash','Paid','2026-03-17 18:21:33',NULL,NULL,'Room Payment',0),(179,126,17000.00,'Cash','Paid','2026-03-17 18:47:31',NULL,NULL,'Room Payment',0),(180,51,6.24,'Cash','Unpaid','2026-03-18 03:32:20',NULL,NULL,'Utility Bill (2026-03-18) - Split 1/1',1),(181,51,6.24,'Cash','Unpaid','2026-03-18 03:32:22',NULL,NULL,'Utility Bill (2026-03-18) - Split 1/1',1),(182,51,6.24,'Cash','Unpaid','2026-03-18 03:32:24',NULL,NULL,'Utility Bill (2026-03-18) - Split 1/1',1),(183,127,9900.00,'Cash','Paid','2026-03-18 03:45:48',NULL,NULL,'Room Payment',0),(184,128,9600.00,'Cash','Cancelled','2026-03-18 03:56:08',NULL,NULL,'Room Payment',1),(185,129,17000.00,'Cash','Cancelled','2026-03-21 09:32:15',NULL,NULL,'Room Payment',1),(186,128,480.00,'','Cancelled','2026-03-23 03:57:48',NULL,NULL,'Late Penalty (5%) for Payment #184',0),(187,51,0.31,'','Unpaid','2026-03-23 11:55:46',NULL,NULL,'Late Penalty (5%) for Payment #180',0),(188,51,0.31,'','Unpaid','2026-03-23 11:55:48',NULL,NULL,'Late Penalty (5%) for Payment #181',0),(189,51,0.31,'','Unpaid','2026-03-23 11:55:50',NULL,NULL,'Late Penalty (5%) for Payment #182',0),(190,129,850.00,'','Cancelled','2026-03-26 05:29:00',NULL,NULL,'Late Penalty (5%) for Payment #185',0),(191,130,8370.97,'GCash','Unpaid','2026-03-26 08:02:42','PENDING_DRAGONPAY',NULL,'Room Payment',0),(192,131,9600.00,'Cash','Unpaid','2026-03-26 09:06:37',NULL,NULL,'Room Payment',0),(193,132,9900.00,'Cash','Unpaid','2026-03-26 14:13:42',NULL,NULL,'Room Payment',0),(194,133,9600.00,'Cash','Unpaid','2026-03-26 15:31:20',NULL,NULL,'Room Payment',0),(195,134,17000.00,'Cash','Unpaid','2026-03-26 16:35:13',NULL,NULL,'Room Payment',0),(196,135,17000.00,'Cash','Unpaid','2026-03-26 17:01:11',NULL,NULL,'Room Payment',0),(197,136,1200.00,'Cash','Paid','2026-03-27 16:23:34',NULL,NULL,'Room Payment',0),(198,137,700.00,'Cash','Unpaid','2026-03-28 14:51:17',NULL,NULL,'Initial Booking Payment',0);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `Email` varchar(64) NOT NULL,
  `Phone_number` varchar(64) NOT NULL,
  `months` int(11) NOT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `status` enum('Pending','Verifying','Approved','Cancelled','Completed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `bed_preference` varchar(50) DEFAULT 'Any',
  `signature_image` varchar(255) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `extended_from` int(11) DEFAULT NULL,
  `signature_required` tinyint(1) DEFAULT 0,
  `auto_assigned` tinyint(1) DEFAULT 1,
  `occupation` varchar(50) DEFAULT NULL,
  `company_or_school` varchar(100) DEFAULT NULL,
  `contact_person_name` varchar(100) DEFAULT NULL,
  `contact_person_number` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`reservation_id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (51,40,20,'','',6,28200.00,'Approved','2026-02-26 01:57:54','2026-02-26','2026-08-26',NULL,'Any','sig_51_1773770455.png',0,NULL,1,1,NULL,NULL,NULL,NULL),(125,90,21,'','',1,17000.00,'Completed','2026-03-17 18:18:38','2026-03-17','2026-04-15',NULL,'Any','sig_125_1773771658.png',0,NULL,1,1,NULL,NULL,NULL,NULL),(126,90,21,'','',1,17000.00,'Approved','2026-03-17 18:45:34','2026-03-17','2026-04-15',NULL,'Any','sig_126_1773773175.png',0,NULL,1,1,NULL,NULL,NULL,NULL),(127,91,41,'','',1,9900.00,'Completed','2026-03-18 03:42:52','2026-03-18','2026-04-16',NULL,'Lower Bunk','sig_127_1773806486.png',0,NULL,1,1,NULL,NULL,NULL,NULL),(128,92,24,'','',1,9600.00,'Cancelled','2026-03-18 03:56:08','2026-03-18','2026-04-16','Auto-expired due to non-payment','Lower Bunk',NULL,1,NULL,0,1,NULL,NULL,NULL,NULL),(129,92,45,'','',1,17000.00,'Cancelled','2026-03-21 09:32:15','2026-03-21','2026-04-19','Auto-expired due to non-payment','Any',NULL,1,NULL,0,1,NULL,NULL,NULL,NULL),(130,93,28,'','',6,8370.97,'Cancelled','2026-03-26 08:01:57','2026-03-26','2026-09-29',NULL,'Lower Bunk',NULL,0,NULL,0,1,NULL,NULL,NULL,NULL),(131,93,24,'','',1,9600.00,'Cancelled','2026-03-26 09:06:37','2026-03-26','2026-04-24','Auto-expired due to non-payment','Lower Bunk',NULL,0,NULL,0,1,NULL,NULL,NULL,NULL),(132,94,7,'','',1,9900.00,'Cancelled','2026-03-26 14:13:42','2026-03-26','2026-04-24','Auto-expired due to non-payment','Lower Bunk',NULL,0,NULL,0,1,NULL,NULL,NULL,NULL),(133,95,6,'','',1,9600.00,'Approved','2026-03-26 15:31:20','2026-03-26','2026-04-24',NULL,'Lower Bunk','sig_133_1774539151.png',0,NULL,1,1,NULL,NULL,NULL,NULL),(134,96,23,'','',1,17000.00,'Approved','2026-03-26 16:27:08','2026-03-26','2026-04-24',NULL,'Any','sig_134_1774542910.png',0,NULL,1,1,NULL,NULL,NULL,NULL),(135,97,22,'','',1,17000.00,'Approved','2026-03-26 17:01:05','2026-03-26','2026-04-24',NULL,'Any',NULL,0,NULL,0,1,'Student','KOLEHIYO NG SUBIC','mother name','09405355552'),(136,92,45,'','',1,1200.00,'Completed','2026-03-27 16:22:05','2026-03-27','2026-03-28',NULL,'Any',NULL,0,NULL,0,1,'Student','KOLEHIYO NG SUBIC','mother name','09358548575'),(137,99,7,'','',1,700.00,'Completed','2026-03-28 14:40:49','2026-03-28','2026-03-29',NULL,'Lower Bunk','sig_137_1774708891.png',0,NULL,1,1,'Student','KOLEHIYO NG SUBIC','mother name','09353456734');
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `residents`
--

DROP TABLE IF EXISTS `residents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `residents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `occupation` varchar(50) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `school_id_image` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'user',
  `is_walkin` tinyint(1) DEFAULT 0,
  `do_not_renew` tinyint(1) DEFAULT 0,
  `is_archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `residents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `residents`
--

LOCK TABLES `residents` WRITE;
/*!40000 ALTER TABLE `residents` DISABLE KEYS */;
INSERT INTO `residents` VALUES (1,97,'Jomari','Lapeciros','','Jomari@gmail.com','09850646646','Male','Student','KOLEHIYO NG SUBIC',NULL,'mother name','09405355552',NULL,'1774544465_school_FB_IMG_1737467323891.jpg','user',0,0,0,'2026-03-26 17:01:26'),(2,92,'bugoygirl','bugoy','','testuser1@gmail.com','09435673450','Female','Student','KOLEHIYO NG SUBIC',NULL,'mother name','09358548575',NULL,'1773806168_school_614346928_33198446529802608_1565591098433792128_n.jpg','user',0,0,0,'2026-03-27 16:22:30'),(3,99,'dhenzel','tayson','','fredhenzeltayson1@gmail.com','09242748742','Male','Student','KOLEHIYO NG SUBIC',NULL,'mother name','09353456734',NULL,'1774708849_school_88cf2736-019f-471d-8b01-ba282e144f93 (1).jfif','user',0,0,0,'2026-03-28 14:41:16');
/*!40000 ALTER TABLE `residents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_category`
--

DROP TABLE IF EXISTS `room_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_category` (
  `room_type` varchar(128) NOT NULL,
  `room_price` int(128) NOT NULL,
  `branch` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_category`
--

LOCK TABLES `room_category` WRITE;
/*!40000 ALTER TABLE `room_category` DISABLE KEYS */;
INSERT INTO `room_category` VALUES ('Single Bed Room',0,'KANLAON'),('Single Bed Room',0,'POBLACION'),('4-Beds Room',0,'KANLAON'),('4-Beds Room',0,'POBLACION'),('6-Beds Room',0,'KANLAON'),('6-Beds Room',0,'POBLACION'),('Test 1',0,'KANLAON'),('Test 1',0,'POBLACION');
/*!40000 ALTER TABLE `room_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_images`
--

DROP TABLE IF EXISTS `room_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`image_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `room_images_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_images`
--

LOCK TABLES `room_images` WRITE;
/*!40000 ALTER TABLE `room_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_name` varchar(100) DEFAULT NULL,
  `room_type` enum('Single','4-Bed','6-Bed') NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `total_beds` int(11) NOT NULL,
  `available_beds` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('Available','Full','Maintenance') DEFAULT 'Available',
  `availability` varchar(50) NOT NULL DEFAULT 'Available',
  `price_upper` decimal(10,2) DEFAULT 0.00,
  `price_lower` decimal(10,2) DEFAULT 0.00,
  `is_archived` tinyint(1) DEFAULT 0,
  `floor` int(11) DEFAULT 2,
  `room_number` varchar(50) DEFAULT NULL,
  `price_whole` decimal(10,2) DEFAULT 0.00,
  `long_term_price_upper` decimal(10,2) DEFAULT 0.00,
  `long_term_price_lower` decimal(10,2) DEFAULT 0.00,
  `long_term_price_whole` decimal(10,2) DEFAULT 0.00,
  `daily_price_bed` decimal(10,2) DEFAULT 0.00,
  `daily_price_room` decimal(10,2) DEFAULT 0.00,
  `display_order` int(11) DEFAULT 0,
  `is_hidden` tinyint(1) DEFAULT 0,
  `gender` varchar(128) NOT NULL,
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (6,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,2,'202',37797.00,3500.00,4200.00,24000.00,700.00,0.00,1,0,'Male'),(7,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,'303',26400.00,4000.00,4500.00,17000.00,700.00,0.00,19,0,'Male'),(20,'1 Bed','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,4,'401',0.00,0.00,0.00,13000.00,0.00,0.00,2,0,'Male'),(21,'1 Bed','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,5,'501',0.00,0.00,0.00,13000.00,0.00,0.00,3,0,'Male'),(22,'1 Bed','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,6,'601',0.00,0.00,0.00,13000.00,0.00,0.00,4,0,'Male'),(23,'1 Bed','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,7,'701',0.00,0.00,0.00,13000.00,0.00,0.00,5,0,'Male'),(24,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,4,'402',37797.00,3500.00,4200.00,24000.00,0.00,0.00,2,0,'Female'),(25,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,5,'502',37797.00,3500.00,4200.00,24000.00,0.00,0.00,3,0,'Female'),(26,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,6,'602',37797.00,3500.00,4200.00,24000.00,0.00,0.00,4,0,'Male'),(27,'702','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,7,'702',37797.00,3500.00,4200.00,24000.00,0.00,0.00,5,0,''),(28,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,'403',26400.00,4000.00,4500.00,17000.00,0.00,0.00,20,0,'Female'),(29,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,'503',26400.00,4000.00,4500.00,17000.00,0.00,0.00,14,0,'Male'),(30,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,'603',26400.00,4000.00,4500.00,17000.00,0.00,0.00,9,0,'Female'),(31,'703','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,'703',26400.00,4000.00,4500.00,17000.00,0.00,0.00,4,0,''),(32,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,'204',26400.00,4000.00,4500.00,17000.00,0.00,0.00,2,0,'Male'),(34,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,'206',26400.00,4000.00,4500.00,17000.00,0.00,0.00,21,0,'Male'),(35,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,'207',26400.00,4000.00,4500.00,17000.00,0.00,0.00,22,0,'Male'),(36,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,2,'208',37797.00,3500.00,4200.00,24000.00,0.00,0.00,6,0,'Male'),(37,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,2,'209',37797.00,3500.00,4200.00,24000.00,0.00,0.00,7,0,'Female'),(38,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,'308',37797.00,3500.00,4200.00,24000.00,0.00,0.00,9,0,'Female'),(39,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,'309',37797.00,3500.00,4200.00,24000.00,0.00,0.00,10,0,'Male'),(40,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,'304',26400.00,4000.00,4500.00,17000.00,0.00,0.00,23,0,'Male'),(41,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,'305',26400.00,4000.00,4500.00,17000.00,0.00,0.00,24,0,'Female'),(42,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,'306',26400.00,4000.00,4500.00,17000.00,0.00,0.00,25,0,'Female'),(43,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,'307',26400.00,4000.00,4500.00,17000.00,0.00,0.00,26,0,'Female'),(44,'205','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,'205',26400.00,4000.00,4500.00,17000.00,0.00,0.00,20,1,''),(45,'1 Bed','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,2,'201',0.00,0.00,0.00,13000.00,0.00,0.00,1,0,'Female'),(46,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,'404',26400.00,4000.00,4500.00,17000.00,0.00,0.00,27,0,'Male'),(47,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,'405',26400.00,4000.00,4500.00,17000.00,0.00,0.00,28,0,'Male'),(48,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,'406',26400.00,4000.00,4500.00,17000.00,0.00,0.00,29,0,'Female'),(49,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,'407',26400.00,4000.00,4500.00,17000.00,0.00,0.00,30,0,'Female'),(50,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,4,'408',37797.00,3500.00,4200.00,24000.00,0.00,0.00,11,0,'Male'),(51,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,4,'409',37797.00,3500.00,4200.00,24000.00,0.00,0.00,12,0,'Female'),(52,'302','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,'302',37797.00,3500.00,4200.00,24000.00,0.00,0.00,8,1,''),(53,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,5,'508',37797.00,3500.00,4200.00,24000.00,0.00,0.00,13,0,'Male'),(54,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,5,'509',37797.00,3500.00,4200.00,24000.00,0.00,0.00,14,0,'Female'),(55,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,6,'608',37797.00,3500.00,4200.00,24000.00,0.00,0.00,15,0,'Male'),(56,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,6,'609',37797.00,3500.00,4200.00,24000.00,0.00,0.00,16,0,'Female'),(57,'708','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,7,'708',37797.00,3500.00,4200.00,24000.00,0.00,0.00,17,0,''),(58,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,7,'709',37797.00,3500.00,4200.00,24000.00,0.00,0.00,18,0,''),(59,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,'203',26400.00,4000.00,4500.00,17000.00,0.00,0.00,1,0,'Male'),(60,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,'504',26400.00,4000.00,4500.00,17000.00,0.00,0.00,15,0,'Female'),(61,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,'505',26400.00,4000.00,4500.00,17000.00,0.00,0.00,16,0,'Male'),(62,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,'506',26400.00,4000.00,4500.00,17000.00,0.00,0.00,17,0,'Male'),(63,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,'507',26400.00,4000.00,4500.00,17000.00,0.00,0.00,18,0,'Female'),(64,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,'604',26400.00,4000.00,4500.00,17000.00,0.00,0.00,10,0,'Female'),(65,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,'605',26400.00,4000.00,4500.00,17000.00,0.00,0.00,11,0,'Female'),(66,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,'606',26400.00,4000.00,4500.00,17000.00,0.00,0.00,12,0,'Female'),(67,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,'607',26400.00,4000.00,4500.00,17000.00,0.00,0.00,13,0,'Male'),(68,'704','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,'704',26400.00,4000.00,4500.00,17000.00,0.00,0.00,5,0,''),(69,'705','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,'705',26400.00,4000.00,4500.00,17000.00,0.00,0.00,6,0,''),(70,'706','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,'706',26400.00,4000.00,4500.00,17000.00,0.00,0.00,7,0,''),(71,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,'707',26400.00,4000.00,4500.00,17000.00,0.00,0.00,8,0,''),(74,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,'205',26400.00,4000.00,4500.00,17000.00,0.00,0.00,3,0,'Male'),(75,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,'302',37797.00,3500.00,4200.00,24000.00,0.00,0.00,0,0,'Female');
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=868 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'hero_image','[\"1770471778_hero_edit.png\",\"1770447312_hero_edit.png\",\"1772369513_hero.png\",\"1770447047_hero.png\"]'),(125,'living_area_image','living_area_1770486291.jpg'),(126,'last_update','1774709477'),(290,'price_single','14000'),(291,'price_4bed_upper','6300'),(292,'price_4bed_lower','6900'),(293,'price_6bed_upper','5999'),(294,'price_6bed_lower','6600'),(303,'price_4bed_whole','26400'),(306,'price_6bed_whole','37797'),(315,'price_single_long','13000'),(319,'price_4bed_upper_long','4000'),(320,'price_4bed_lower_long','4500'),(321,'price_4bed_whole_long','17000'),(325,'price_6bed_upper_long','3500'),(326,'price_6bed_lower_long','4200'),(327,'price_6bed_whole_long','24000'),(548,'room_type_order','[\"Single\",\"4-Bed\",\"6-Bed\"]'),(681,'theme_primary','#34b875'),(682,'theme_dark','#1b5e20'),(683,'theme_accent','#ffb700'),(687,'migration_fix_dupe_rooms_v2','1'),(688,'migration_cleanup_v3','1'),(848,'login_bg','login_bg_1774085356.jpg');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_updates`
--

DROP TABLE IF EXISTS `system_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `release_date` date DEFAULT curdate(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_updates`
--

LOCK TABLES `system_updates` WRITE;
/*!40000 ALTER TABLE `system_updates` DISABLE KEYS */;
INSERT INTO `system_updates` VALUES (1,'1.2.0','Profile Enhancements','Added Bio, Social Links, and Newsletter subscription options.','2026-03-03'),(2,'1.1.0','Waitlist Feature','Introduced room waitlists and automated notifications.','2026-02-24'),(3,'1.0.0','Initial Release','Core booking and management system launch.','2026-02-01'),(4,'1.2.0','Profile Enhancements','Added Bio, Social Links, and Newsletter subscription options.','2026-03-03'),(5,'1.1.0','Waitlist Feature','Introduced room waitlists and automated notifications.','2026-02-24'),(6,'1.0.0','Initial Release','Core booking and management system launch.','2026-02-01'),(7,'1.2.0','Profile Enhancements','Added Bio, Social Links, and Newsletter subscription options.','2026-03-03'),(8,'1.1.0','Waitlist Feature','Introduced room waitlists and automated notifications.','2026-02-24'),(9,'1.0.0','Initial Release','Core booking and management system launch.','2026-02-01'),(10,'1.3.4','Bug Fixes & UI Polish','Fixed night mode styling issues. Improved responsive layout for mobile devices.','2026-03-03'),(11,'1.3.3','Security Update','Added Change Password feature for enhanced account security.','2026-03-02'),(12,'1.3.2','Support Features','Added \"Other Request\" option to contact support directly via Messenger.','2026-03-01'),(13,'1.3.1','System Integrity','Implemented strict feature access control based on system version.','2026-02-28'),(14,'1.3.0','Dashboard Customization','Added ability to hide and reorder dashboard cards. Preferences are saved automatically.','2026-02-27'),(15,'1.3.5','Account Management','Added ability for users to update their email address securely.','2026-03-03'),(16,'1.3.5','Account Management','Added ability for users to update their email address securely.','2026-03-03'),(17,'1.3.6','User Control','Added option for users to permanently delete their account (requires no active bookings).','2026-03-03'),(18,'1.3.6','User Control','Added option for users to permanently delete their account (requires no active bookings).','2026-03-03');
/*!40000 ALTER TABLE `system_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temporary_moves`
--

DROP TABLE IF EXISTS `temporary_moves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temporary_moves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `original_room_id` int(11) NOT NULL,
  `temp_room_id` int(11) NOT NULL,
  `move_date` datetime DEFAULT current_timestamp(),
  `status` enum('Active','Returned') DEFAULT 'Active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temporary_moves`
--

LOCK TABLES `temporary_moves` WRITE;
/*!40000 ALTER TABLE `temporary_moves` DISABLE KEYS */;
/*!40000 ALTER TABLE `temporary_moves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_update_requests`
--

DROP TABLE IF EXISTS `user_update_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_update_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `occupation` varchar(50) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `school_id_image` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_update_requests`
--

LOCK TABLES `user_update_requests` WRITE;
/*!40000 ALTER TABLE `user_update_requests` DISABLE KEYS */;
INSERT INTO `user_update_requests` VALUES (5,93,'Female','Employed','','bicol','puregold','09235432546',NULL,'Approved','2026-03-26 08:23:44'),(6,96,'Male','Student','KOLEHIYO NG SUBIC','ddd','mother name','09234567897','1774544334_school_266ecb72-025b-41ac-ab1d-dbb74101fd8a.jfif','Approved','2026-03-26 16:58:54');
/*!40000 ALTER TABLE `user_update_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  `do_not_renew` tinyint(1) DEFAULT 0,
  `gender` varchar(20) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `occupation` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `school_id_image` varchar(255) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `is_walkin` tinyint(1) DEFAULT 0,
  `night_mode` tinyint(1) DEFAULT 0,
  `last_name` varchar(256) NOT NULL,
  `first_name` varchar(256) NOT NULL,
  `middle_name` varchar(256) NOT NULL,
  `newsletter` tinyint(1) DEFAULT 1,
  `bio` text DEFAULT NULL,
  `social_link` varchar(255) DEFAULT NULL,
  `other_request_feature` tinyint(1) DEFAULT NULL,
  `change_password_feature` tinyint(1) DEFAULT NULL,
  `change_email_feature` tinyint(1) DEFAULT NULL,
  `delete_account_feature` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (40,'bartjavillonar@gmail.com','09304871699',NULL,'$2y$10$X9kKW5UpTXSviWVagyKhAuXEzk2SiwS4GxxgjIjikUt22qpKOwho2','','2026-02-26 01:57:54',0,0,'Male',NULL,NULL,'Employed','','',NULL,'','',NULL,0,1,'JAVILLONAR','BARTOLOME','',1,NULL,NULL,NULL,NULL,NULL,NULL),(90,'FRED@gmail.com','09435638478',NULL,'$2y$10$iUmw.165jLGP7fcS3nYyiumK8niQbNHzBljo33lb6WeoJnnlP6aNS','user','2026-03-17 18:17:13',0,0,'Male',NULL,NULL,NULL,NULL,'KOLEHIYO NG SUBIC','1773771518_school_614346928_33198446529802608_1565591098433792128_n.jpg',NULL,NULL,NULL,0,1,'TAYSON','FRED','',1,NULL,NULL,NULL,NULL,NULL,NULL),(91,'Angelene@gmail.com','09425673493',NULL,'$2y$10$2rYri8FwRKFYY3VV0I3k.OzdnruTmQi2k3YnlKGkdknn/uf3IM.Wa','user','2026-03-18 03:37:49',0,0,'Female',NULL,NULL,NULL,NULL,'KOLEHIYO NG SUBIC','1773805372_school_434612699_2697344013763217_6695140230318829305_n.jpg',NULL,NULL,NULL,0,0,'banate','Angelene','',1,NULL,NULL,NULL,NULL,NULL,NULL),(92,'testuser1@gmail.com','09435673450',NULL,'$2y$10$l6LziTTpI3wnbFLV.jb0EOHjmX6c1rzpcfssZibUPZ1MuaEIqweD.','user','2026-03-18 03:51:47',0,0,'Female',NULL,NULL,NULL,NULL,'KOLEHIYO NG SUBIC','1773806168_school_614346928_33198446529802608_1565591098433792128_n.jpg',NULL,NULL,NULL,0,0,'bugoy','bugoygirl','',1,NULL,NULL,NULL,NULL,NULL,NULL),(93,'keysha@gmail.com','09234623546',NULL,'$2y$10$.AneKyHxaVJ4F99TDjiqG.3hxZfH9V8v8waWwQrWUuEYnPo0yGmfS','user','2026-03-26 06:24:43',0,0,'Female',NULL,NULL,'Employed','bicol','',NULL,'puregold','09235432546',NULL,0,0,'sarto','keysha','',1,NULL,NULL,NULL,NULL,NULL,NULL),(94,'testuser2@gmail.com','09234287467',NULL,'$2y$10$wdh33NGQ3p4GvIQ.UQvsEOEefEWFBWLAuS8m1boX13wXU1VqXa78u','user','2026-03-26 14:12:35',0,0,'Male',NULL,NULL,NULL,NULL,'KOLEHIYO NG SUBIC','1774534422_school_Plants, vepoware, art, 2160x3840 wallpaper.jpg',NULL,NULL,NULL,0,0,'Viloria','Nicole','',1,NULL,NULL,NULL,NULL,NULL,NULL),(95,'testuser3@gmail.com','09345345678',NULL,'$2y$10$w9U6HbY8zTll4ah3rq4E6.hGhLYyLvESPy0wQov7r9WO0IPL004J2','user','2026-03-26 15:24:00',0,0,'Male',NULL,NULL,NULL,NULL,'KOLEHIYO NG SUBIC','1774539080_school_Plants, vepoware, art, 2160x3840 wallpaper.jpg',NULL,NULL,NULL,0,0,'santiago','Michael','',1,NULL,NULL,NULL,NULL,NULL,NULL),(96,'johnrasing@gmail.com','09573465734',NULL,'$2y$10$MZSIIUfE4Xf3EPy.uLlP4.xJsV2tBoukseu5MtnIkYBjm09Id1Fnu','user','2026-03-26 16:24:23',0,0,'Male',NULL,NULL,'Student','ddd','KOLEHIYO NG SUBIC','1774544334_school_266ecb72-025b-41ac-ab1d-dbb74101fd8a.jfif','mother name','09234567897',NULL,0,0,'Rasing','john ben','',1,NULL,NULL,NULL,NULL,NULL,NULL),(97,'Jomari@gmail.com','09850646646',NULL,'$2y$10$Co0u6Z2qxzCrEkMuS2U52OvbW5FTbI8TbIjhDU96xrMGwZxc0IpYu','user','2026-03-26 17:00:21',0,0,'Male',NULL,NULL,NULL,NULL,'KOLEHIYO NG SUBIC','1774544465_school_FB_IMG_1737467323891.jpg',NULL,NULL,NULL,0,1,'Lapeciros','Jomari','',1,NULL,NULL,NULL,NULL,NULL,NULL),(98,'bryanvalencia1@gmail.com','09345654685',NULL,'$2y$10$3Ohzdgbdw6CrUjr8Wz8GcunK12RpIvwBqS6Web/6THfMx2j3g4.Qe','user','2026-03-27 16:26:32',0,0,'Male',NULL,NULL,'Student',NULL,'kolehiyo ng subic','1774628791_sid_Plants, vepoware, art, 2160x3840 wallpaper.jpg','mother name','09645867546',NULL,1,0,'Ablao','bryan','',1,NULL,NULL,NULL,NULL,NULL,NULL),(99,'fredhenzeltayson1@gmail.com','09242748742',NULL,'$2y$10$7rwnz/oMga7qQZ9ZgniIt.cd4292cGMtYiMDP/Sp0nOlVq0U6xHdG','user','2026-03-28 14:40:05',0,0,'Male',NULL,NULL,NULL,NULL,'KOLEHIYO NG SUBIC','1774708849_school_88cf2736-019f-471d-8b01-ba282e144f93 (1).jfif',NULL,NULL,NULL,0,0,'tayson','dhenzel','',1,NULL,NULL,NULL,NULL,NULL,NULL),(100,'testuser4@gmail.com','09458643582',NULL,'$2y$10$Fx8Hdy0Yxsj1R99BebOdyugNgjae.N/wMGkWbKXIWaIdN19JqnPOK','user','2026-03-30 04:05:33',0,0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'test','ing','',1,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utility_bills`
--

DROP TABLE IF EXISTS `utility_bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utility_bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) DEFAULT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `bill_date` date NOT NULL,
  `electric_start` decimal(10,2) DEFAULT 0.00,
  `electric_end` decimal(10,2) DEFAULT 0.00,
  `electric_rate` decimal(10,2) DEFAULT 0.00,
  `water_start` decimal(10,2) DEFAULT 0.00,
  `water_end` decimal(10,2) DEFAULT 0.00,
  `water_rate` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `split_count` int(11) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utility_bills`
--

LOCK TABLES `utility_bills` WRITE;
/*!40000 ALTER TABLE `utility_bills` DISABLE KEYS */;
INSERT INTO `utility_bills` VALUES (2,20,NULL,'2026-03-18',1.05,1.57,12.00,1.12,0.40,35.00,6.24,'2026-03-18 03:32:20',1),(3,20,NULL,'2026-03-18',1.05,1.57,12.00,1.12,0.40,35.00,6.24,'2026-03-18 03:32:22',1),(4,20,NULL,'2026-03-18',1.05,1.57,12.00,1.12,0.40,35.00,6.24,'2026-03-18 03:32:24',1);
/*!40000 ALTER TABLE `utility_bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `waitlist`
--

DROP TABLE IF EXISTS `waitlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `waitlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notified_at` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `waitlist`
--

LOCK TABLES `waitlist` WRITE;
/*!40000 ALTER TABLE `waitlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `waitlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-30 12:11:43
