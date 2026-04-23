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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=397 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (89,38,'Account Created','Walk-in account created by Admin','2026-02-26 01:25:30','System','System'),(92,40,'Account Created','Walk-in account created by Admin','2026-02-26 01:57:54','System','System'),(93,40,'Walk-in Booking','Reservation #51 created by Admin','2026-02-26 01:57:54','System','System'),(108,40,'Payment Confirmed','Payment #55 marked as Paid by Admin.','2026-02-28 13:54:57','System','System'),(217,40,'Profile Updated','Admin updated user details.','2026-03-01 12:29:35','System','System'),(218,40,'Profile Updated','Admin updated user details.','2026-03-01 12:36:44','System','System'),(232,40,'Profile Updated','Admin updated user details.','2026-03-02 07:19:43','System','System'),(339,40,'Penalty Applied','Late fee of 30.00 applied for Payment #103','2026-03-08 13:41:45','System','System'),(357,40,'Parking Ended','Parking reservation #2 ended by Super Admin','2026-03-08 14:16:54','Super Admin','Super Admin'),(381,40,'Payment Confirmed','Payment #103 marked as Paid by Super Admin.','2026-03-08 20:19:32','Super Admin','Super Admin'),(382,40,'Payment Confirmed','Payment #110 marked as Paid by Super Admin.','2026-03-08 20:19:38','Super Admin','Super Admin'),(383,40,'Signature Requested','Signature requested for Reservation #51 by Super Admin','2026-03-08 20:19:51','Super Admin','Super Admin'),(384,40,'Room Re-assigned','Moved to 203 (Any) by Super Admin','2026-03-08 20:20:41','Super Admin','Super Admin'),(392,40,'Parking Assigned','Assigned to Car Slot 1 by Super Admin','2026-03-09 12:49:32','Super Admin','Super Admin'),(393,40,'Parking Ended','Parking reservation #11 ended by Super Admin','2026-03-09 12:49:38','Super Admin','Super Admin'),(394,40,'Room Re-assigned','Moved to 201 (Any) by Super Admin','2026-03-09 14:06:31','Super Admin','Super Admin'),(395,40,'Room Re-assigned','Moved to 203 (Lower Bunk) by Super Admin','2026-03-09 16:28:34','Super Admin','Super Admin'),(396,40,'Room Re-assigned','Moved to 201 (Any) by Super Admin','2026-03-09 17:01:47','Super Admin','Super Admin');
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'Super Admin','super123','Super Admin'),(3,'admin','admin123','Admin'),(5,'Super Admin 2','super123','Super Admin');
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `housekeeping_requests`
--

LOCK TABLES `housekeeping_requests` WRITE;
/*!40000 ALTER TABLE `housekeeping_requests` DISABLE KEYS */;
INSERT INTO `housekeeping_requests` VALUES (11,NULL,19,'Routine Cleaning (Admin Scheduled)','Completed','2026-03-13','2026-03-08 14:30:10',0.00),(13,40,7,'Weekly Routine Cleaning','Scheduled','2026-03-28','2026-03-08 14:31:21',0.00),(17,NULL,19,'Weekly Routine Cleaning','Scheduled','2026-03-28','2026-03-08 14:31:27',0.00);
/*!40000 ALTER TABLE `housekeeping_requests` ENABLE KEYS */;
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
  PRIMARY KEY (`id`),
  KEY `key_id` (`key_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `key_transactions_ibfk_1` FOREIGN KEY (`key_id`) REFERENCES `keys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `key_transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `key_transactions`
--

LOCK TABLES `key_transactions` WRITE;
/*!40000 ALTER TABLE `key_transactions` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `keys`
--

LOCK TABLES `keys` WRITE;
/*!40000 ALTER TABLE `keys` DISABLE KEYS */;
INSERT INTO `keys` VALUES (9,'Room 202 Key','Room',6,'Available'),(10,'Room 302 Key','Room',7,'Available'),(11,'Room 203 Key','Room',16,'Available'),(12,'Room 204 Key','Room',17,'Available'),(13,'Room 303 Key','Room',18,'Available'),(14,'Room 203 Key','Room',19,'Available'),(15,'Room 401 Key','Room',20,'Available'),(16,'Room 501 Key','Room',21,'Available'),(17,'Room 601 Key','Room',22,'Available'),(18,'Room 701 Key','Room',23,'Available'),(19,'Room 402 Key','Room',24,'Available'),(20,'Room 502 Key','Room',25,'Available'),(21,'Room 602 Key','Room',26,'Available'),(22,'Room 702 Key','Room',27,'Available'),(23,'Room 403 Key','Room',28,'Available'),(24,'Room 503 Key','Room',29,'Available'),(25,'Room 603 Key','Room',30,'Available'),(26,'Room 703 Key','Room',31,'Available'),(27,'Room 204 Key','Room',32,'Available'),(28,'Room 205 Key','Room',33,'Available'),(29,'Room 206 Key','Room',34,'Available'),(30,'Room 207 Key','Room',35,'Available'),(31,'Room 208 Key','Room',36,'Available'),(32,'Room 209 Key','Room',37,'Available'),(33,'Room 308 Key','Room',38,'Available'),(34,'Room 309 Key','Room',39,'Available'),(35,'Room 304 Key','Room',40,'Available'),(36,'Room 305 Key','Room',41,'Available'),(37,'Room 306 Key','Room',42,'Available'),(38,'Room 307 Key','Room',43,'Available'),(39,'Room 205 Key','Room',44,'Available'),(40,'Room 201 Key','Room',45,'Available'),(41,'Room 404 Key','Room',46,'Available'),(42,'Room 405 Key','Room',47,'Available'),(43,'Room 406 Key','Room',48,'Available'),(44,'Room 407 Key','Room',49,'Available'),(45,'Room 408 Key','Room',50,'Available'),(46,'Room 409 Key','Room',51,'Available'),(47,'Room 302 Key','Room',52,'Available'),(48,'Room 508 Key','Room',53,'Available'),(49,'Room 509 Key','Room',54,'Available'),(50,'Room 608 Key','Room',55,'Available'),(51,'Room 609 Key','Room',56,'Available'),(52,'Room 708 Key','Room',57,'Available'),(53,'Room 709 Key','Room',58,'Available'),(54,'Room 203 Key','Room',59,'Available'),(55,'Room 504 Key','Room',60,'Available'),(56,'Room 505 Key','Room',61,'Available'),(57,'Room 506 Key','Room',62,'Available'),(58,'Room 507 Key','Room',63,'Available'),(59,'Room 604 Key','Room',64,'Available'),(60,'Room 605 Key','Room',65,'Available'),(61,'Room 606 Key','Room',66,'Available'),(62,'Room 607 Key','Room',67,'Available'),(63,'Room 704 Key','Room',68,'Available'),(64,'Room 705 Key','Room',69,'Available'),(65,'Room 706 Key','Room',70,'Available'),(66,'Room 707 Key','Room',71,'Available');
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
) ENGINE=InnoDB AUTO_INCREMENT=843 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (504,'40','2026-02-26 09:57:57','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(513,'40','2026-02-26 21:31:42','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(526,'40','2026-02-27 15:28:35','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(532,'40','2026-02-28 21:46:01','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(536,'40','2026-02-28 21:54:57','','✅ <strong>Payment Confirmed</strong><br>Your payment #55 has been verified and marked as Paid.','Payment Update'),(707,'40','2026-03-03 13:28:50','','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Car Slot 1. A fee of ₱600.00 has been added to your account.','Parking'),(708,'40','2026-03-03 14:19:47','','🔑 <strong>Key Assigned</strong><br>You have been assigned a key. Please keep it safe.','Key System'),(709,'40','2026-03-03 14:20:14','','🔑 <strong>Key Returned</strong><br>Key has been marked as returned.','Key System'),(710,'40','2026-03-03 14:21:51','','🔑 <strong>Key Assigned</strong><br>You have been assigned a key. Please keep it safe.','Key System'),(711,'40','2026-03-03 14:21:58','','🔑 <strong>Key Returned</strong><br>Key has been marked as returned.','Key System'),(713,'40','2026-03-05 18:01:58','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(726,'40','2026-03-05 22:01:59','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(737,'40','2026-03-06 02:01:59','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(740,'40','2026-03-06 11:25:54','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(743,'40','2026-03-06 15:25:55','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(748,'40','2026-03-06 23:34:31','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(751,'40','2026-03-07 12:58:52','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(754,'40','2026-03-07 17:21:42','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(768,'40','2026-03-07 21:22:35','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(771,'40','2026-03-08 13:05:01','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(774,'40','2026-03-08 21:41:45','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱30.00 has been applied to your account due to overdue payment.','Billing Alert'),(791,'40','2026-03-08 22:16:52','','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #1 has been marked as completed.','Parking'),(800,'40','2026-03-08 22:31:21','','🧹 <strong>Weekly Cleaning</strong><br>Routine housekeeping scheduled for Mar 28, 2026.','Housekeeping'),(821,'40','2026-03-09 04:19:32','','✅ <strong>Payment Confirmed</strong><br>Your payment #103 has been verified and marked as Paid.','Payment Update'),(822,'40','2026-03-09 04:19:38','','✅ <strong>Payment Confirmed</strong><br>Your payment #110 has been verified and marked as Paid.','Payment Update'),(823,'40','2026-03-09 04:19:49','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #51. Please go to My Reservations to sign.','Action Required'),(824,'40','2026-03-09 04:20:41','','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>203</strong>.','System'),(838,'40','2026-03-09 20:49:30','','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Car Slot 1. A fee of ₱600.00 has been added to your account.','Parking'),(839,'40','2026-03-09 20:49:36','','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #11 has been marked as completed.','Parking'),(840,'40','2026-03-09 22:06:31','','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>201</strong>.','System'),(841,'40','2026-03-10 00:28:34','','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>203</strong>.','System'),(842,'40','2026-03-10 01:01:47','','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>201</strong>.','System');
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
INSERT INTO `parking_slots` VALUES (11,'Car Slot 1','Car','Available',600.00,200.00,0),(12,'Car Slot 2','Car','Available',600.00,200.00,0),(13,'Car Slot 3','Car','Available',600.00,200.00,0),(14,'Car Slot 4','Car','Available',600.00,200.00,0),(15,'Motorcycle Slot 1','Motorcycle','Available',1500.00,50.00,0),(16,'Motorcycle Slot 2','Motorcycle','Available',1500.00,50.00,0),(17,'Motorcycle Slot 3','Motorcycle','Available',1500.00,50.00,0),(18,'Motorcycle Slot 4','Motorcycle','Available',1500.00,50.00,0),(19,'Motorcycle Slot 5','Motorcycle','Available',1500.00,50.00,0),(20,'Motorcycle Slot 6','Motorcycle','Available',1500.00,50.00,0),(21,'Motorcycle Slot 7','Motorcycle','Available',1500.00,50.00,0);
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
  `payment_status` enum('Unpaid','Paid') DEFAULT 'Unpaid',
  `payment_date` timestamp NULL DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT 'Room Payment',
  `is_penalized` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`payment_id`),
  KEY `reservation_id` (`reservation_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=124 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (55,51,28200.00,'Cash','Paid','2026-02-28 13:54:57',NULL,NULL,'Room Payment',0),(103,51,600.00,'Cash','Paid','2026-03-08 20:19:32',NULL,NULL,'Monthly Parking Fee (March 2026) for Car Slot 1 (Parking ID: 2)',1),(110,51,30.00,'','Paid','2026-03-08 20:19:38',NULL,NULL,'Late Penalty (5%) for Payment #103',0),(123,51,600.00,'Cash','Unpaid','2026-03-09 12:49:30',NULL,NULL,'Monthly Parking Fee (March 2026) for Car Slot 1 (Parking ID: 11)',0);
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
  PRIMARY KEY (`reservation_id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (51,40,45,'','',6,28200.00,'Approved','2026-02-26 01:57:54','2026-02-26','2026-08-26',NULL,'Any',NULL,0,NULL,1);
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
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
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (6,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,2,'202',37797.00,3500.00,4200.00,24000.00,700.00,0.00,1,0),(7,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,'303',26400.00,4000.00,4500.00,17000.00,700.00,0.00,16,0),(16,'1 Bed','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,1,2,'201',0.00,0.00,0.00,13000.00,0.00,1200.00,16,1),(18,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,1,3,'302',37797.00,3500.00,4200.00,24000.00,700.00,0.00,18,0),(19,'203','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,1,2,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,19,1),(20,'1 Bed','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,4,'401',0.00,0.00,0.00,13000.00,0.00,0.00,2,0),(21,'501','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,5,NULL,0.00,0.00,0.00,13000.00,0.00,0.00,3,0),(22,'601','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,6,NULL,0.00,0.00,0.00,13000.00,0.00,0.00,4,0),(23,'701','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,7,NULL,0.00,0.00,0.00,13000.00,0.00,0.00,5,0),(24,'402','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,4,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,2,0),(25,'502','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,5,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,3,0),(26,'602','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,6,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,4,0),(27,'702','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,7,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,5,0),(28,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,'403',26400.00,4000.00,4500.00,17000.00,0.00,0.00,17,0),(29,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,'503',26400.00,4000.00,4500.00,17000.00,0.00,0.00,11,0),(30,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,'603',26400.00,4000.00,4500.00,17000.00,0.00,0.00,6,0),(31,'703','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,1,0),(32,'204','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,19,0),(33,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,1,2,'205',26400.00,4000.00,4500.00,17000.00,0.00,0.00,33,0),(34,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,'206',26400.00,4000.00,4500.00,17000.00,0.00,0.00,21,0),(35,'207','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,22,0),(36,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,2,'208',37797.00,3500.00,4200.00,24000.00,0.00,0.00,6,0),(37,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,2,'209',37797.00,3500.00,4200.00,24000.00,0.00,0.00,7,0),(38,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,'308',37797.00,3500.00,4200.00,24000.00,0.00,0.00,9,0),(39,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,'309',37797.00,3500.00,4200.00,24000.00,0.00,0.00,10,0),(40,'304','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,23,0),(41,'305','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,24,0),(42,'306','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,25,0),(43,'307','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,26,0),(44,'205','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,20,1),(45,'201','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,2,NULL,0.00,0.00,0.00,13000.00,0.00,0.00,1,0),(46,'404','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,27,0),(47,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,'405',26400.00,4000.00,4500.00,17000.00,0.00,0.00,28,0),(48,'406','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,29,0),(49,'407','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,30,0),(50,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,4,'408',37797.00,3500.00,4200.00,24000.00,0.00,0.00,11,0),(51,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,4,'409',37797.00,3500.00,4200.00,24000.00,0.00,0.00,12,0),(52,'302','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,8,1),(53,'508','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,5,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,13,0),(54,'509','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,5,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,14,0),(55,'608','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,6,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,15,0),(56,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,6,'609',37797.00,3500.00,4200.00,24000.00,0.00,0.00,16,0),(57,'708','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,7,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,17,0),(58,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,7,'709',37797.00,3500.00,4200.00,24000.00,0.00,0.00,18,0),(59,'203','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,18,0),(60,'504','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,12,0),(61,'505','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,13,0),(62,'506','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,14,0),(63,'507','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,15,0),(64,'604','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,7,0),(65,'605','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,8,0),(66,'606','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,9,0),(67,'607','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,10,0),(68,'704','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,2,0),(69,'705','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,3,0),(70,'706','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,4,0),(71,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,'707',26400.00,4000.00,4500.00,17000.00,0.00,0.00,5,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=572 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'hero_image','[\"1770471778_hero_edit.png\",\"1770447312_hero_edit.png\",\"1772369513_hero.png\",\"1770447047_hero.png\"]'),(125,'living_area_image','living_area_1770486291.jpg'),(126,'last_update','1773075709'),(290,'price_single','14000'),(291,'price_4bed_upper','6300'),(292,'price_4bed_lower','6900'),(293,'price_6bed_upper','5999'),(294,'price_6bed_lower','6600'),(303,'price_4bed_whole','26400'),(306,'price_6bed_whole','37797'),(315,'price_single_long','13000'),(319,'price_4bed_upper_long','4000'),(320,'price_4bed_lower_long','4500'),(321,'price_4bed_whole_long','17000'),(325,'price_6bed_upper_long','3500'),(326,'price_6bed_lower_long','4200'),(327,'price_6bed_whole_long','24000'),(548,'room_type_order','[\"Single\",\"4-Bed\",\"6-Bed\"]');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_update_requests`
--

LOCK TABLES `user_update_requests` WRITE;
/*!40000 ALTER TABLE `user_update_requests` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (40,'bartjavillonar@gmail.com','09304871699',NULL,'$2y$10$X9kKW5UpTXSviWVagyKhAuXEzk2SiwS4GxxgjIjikUt22qpKOwho2','','2026-02-26 01:57:54',0,0,'Male',NULL,NULL,'Employed','','',NULL,'','',NULL,0,0,'JAVILLONAR','BARTOLOME','',1,NULL,NULL,NULL,NULL,NULL,NULL);
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
  `reservation_id` int(11) NOT NULL,
  `bill_date` date NOT NULL,
  `electric_start` decimal(10,2) DEFAULT 0.00,
  `electric_end` decimal(10,2) DEFAULT 0.00,
  `electric_rate` decimal(10,2) DEFAULT 0.00,
  `water_start` decimal(10,2) DEFAULT 0.00,
  `water_end` decimal(10,2) DEFAULT 0.00,
  `water_rate` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utility_bills`
--

LOCK TABLES `utility_bills` WRITE;
/*!40000 ALTER TABLE `utility_bills` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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

-- Dump completed on 2026-03-10  1:48:32
