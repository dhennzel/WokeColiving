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
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,16,'Lease Signed','Reservation #22','2026-02-07 08:17:42'),(2,16,'Reservation Extended','Room: 6-Bed | Status: Pending','2026-02-07 08:27:32'),(3,17,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-02-07 08:31:59'),(4,17,'Reservation Approved','Reservation #23 has been approved.','2026-02-07 08:32:05'),(5,17,'Lease Signed','Reservation #23','2026-02-07 08:32:16'),(6,17,'Reservation Extended','Room: 6-Bed | Status: Pending','2026-02-07 08:33:03');
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'admin','admin123');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `housekeeping_requests`
--

DROP TABLE IF EXISTS `housekeeping_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `housekeeping_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Pending','Scheduled','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `scheduled_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `fk_hk_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `housekeeping_requests`
--

LOCK TABLES `housekeeping_requests` WRITE;
/*!40000 ALTER TABLE `housekeeping_requests` DISABLE KEYS */;
INSERT INTO `housekeeping_requests` VALUES (1,3,1,'Hi, Im Tysoni my bed has fleas and ticks clean this','Completed','2026-01-20','2026-01-19 14:55:28'),(4,3,1,'Hi fix this error','Completed','2026-01-20','2026-01-19 14:59:14'),(5,3,1,'I have error, fix this please','Cancelled',NULL,'2026-01-19 15:03:58');
/*!40000 ALTER TABLE `housekeeping_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_requests`
--

DROP TABLE IF EXISTS `maintenance_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Pending','Scheduled','Completed','Cancelled') DEFAULT 'Pending',
  `scheduled_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
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
INSERT INTO `maintenance_requests` VALUES (1,3,1,'My bed frame has rusty in it please give a maintenance please','Completed','2026-01-20','2026-01-19 14:57:51'),(2,3,1,'error fix this now','Scheduled','2026-01-20','2026-01-19 15:07:41'),(3,5,1,'My bed broke boi','Completed','2026-01-21','2026-01-20 04:27:04'),(4,7,3,'bed broke','Scheduled','2026-01-21','2026-01-20 05:47:39');
/*!40000 ALTER TABLE `maintenance_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `user_id` varchar(256) NOT NULL,
  `created_at` varchar(256) NOT NULL,
  `is_read` varchar(256) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'System'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES ('16','','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),('16','','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),('16','','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),('17','','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),('17','','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
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
  PRIMARY KEY (`payment_id`),
  KEY `reservation_id` (`reservation_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,13,32.29,'GCash','Paid','2026-02-06 04:27:44',NULL,NULL),(2,14,32.29,'GCash','Paid','2026-02-06 04:31:35',NULL,NULL),(3,15,25.50,'GCash','Paid','2026-02-06 04:38:28',NULL,NULL),(4,16,30.20,'GCash','Paid','2026-02-06 05:02:39',NULL,NULL),(5,17,48.78,'Cash','Unpaid','2026-02-06 19:10:21',NULL,NULL),(6,18,35.69,'GCash','Paid','2026-02-07 07:14:48','434423423','1770448488_Gcashqr.jfif'),(7,19,2099.30,'GCash','Paid','2026-02-07 07:19:31','09876312','1770448771_Gcashqr.jfif'),(8,20,2799.07,'GCash','Paid','2026-02-07 07:53:55','09876312','1770450835_Gcashqr.jfif'),(9,21,2799.07,'GCash','Paid','2026-02-07 07:55:23','09876312','1770450923_Gcashqr.jfif'),(10,22,2799.07,'GCash','Paid','2026-02-07 07:56:54','09876312','1770451014_Gcashqr.jfif'),(11,22,1399.53,'Cash','Unpaid','2026-02-07 08:05:40',NULL,NULL),(12,22,1199.60,'GCash','Paid','2026-02-07 08:27:30','09876312','1770452850_Gcashqr.jfif'),(13,23,2099.30,'GCash','Paid','2026-02-07 08:31:57','098763122','1770453117_Gcashqr.jfif'),(14,23,3098.97,'GCash','Paid','2026-02-07 08:33:01','09876312','1770453181_Gcashqr.jfif');
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
  `status` enum('Pending','Approved','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `bed_preference` varchar(50) DEFAULT 'Any',
  `signature_image` varchar(255) DEFAULT NULL,
  `is_archived` varchar(256) NOT NULL,
  PRIMARY KEY (`reservation_id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (1,3,1,'2026-01-18','2026-01-19',0,1.70,'Approved','2026-01-18 15:12:01',NULL,NULL,NULL,'Any',NULL,''),(5,4,1,'2026-01-20','2026-01-21',0,1.70,'Approved','2026-01-19 16:21:50',NULL,NULL,NULL,'Any',NULL,''),(6,5,1,'2026-01-21','2026-01-22',0,1.70,'Approved','2026-01-20 04:26:09',NULL,NULL,NULL,'Any',NULL,''),(7,6,3,'2026-01-20','2026-01-22',0,4.65,'Approved','2026-01-20 05:45:09',NULL,NULL,NULL,'Any',NULL,''),(8,7,3,'2026-01-20','2026-01-22',0,4.65,'Approved','2026-01-20 05:47:15',NULL,NULL,NULL,'Any',NULL,''),(9,8,1,'2026-02-07','2026-02-16',0,0.00,'Cancelled','2026-02-02 15:39:14',NULL,NULL,NULL,'Any',NULL,''),(10,9,1,'','',1,50.99,'Cancelled','2026-02-04 04:44:09','2026-02-04','2026-03-04',NULL,'Any',NULL,''),(11,10,3,'','',1,53.43,'Cancelled','2026-02-05 12:47:21','2026-02-05','2026-02-28',NULL,'Any',NULL,''),(12,13,1,'','',1,37.39,'Cancelled','2026-02-05 20:00:40','2026-02-05','2026-02-27',NULL,'Any',NULL,''),(13,13,1,'','',1,32.29,'Cancelled','2026-02-06 04:27:44','2026-02-06','2026-02-25',NULL,'Any',NULL,''),(14,14,1,'','',1,32.29,'Cancelled','2026-02-06 04:31:35','2026-02-06','2026-02-25',NULL,'Any',NULL,''),(15,14,1,'','',1,25.50,'Cancelled','2026-02-06 04:38:28','2026-02-06','2026-02-21','Auto-expired due to non-payment','Any',NULL,''),(16,15,3,'','',1,30.20,'Approved','2026-02-06 05:02:39','2026-02-06','2026-02-19',NULL,'Any',NULL,''),(17,16,3,'','',1,48.78,'Cancelled','2026-02-06 19:10:21','2026-02-07','2026-02-28',NULL,'Any',NULL,'1'),(18,16,1,'','',1,35.69,'Cancelled','2026-02-07 07:14:48','2026-02-07','2026-02-28',NULL,'Any',NULL,'1'),(19,16,5,'','',1,2099.30,'Approved','2026-02-07 07:19:31','2026-02-07','2026-02-28',NULL,'Lower Bunk','sig_19_1770449840.png',''),(20,16,5,'','',1,2799.07,'Cancelled','2026-02-07 07:53:55','2026-02-28','2026-03-28',NULL,'Lower Bunk',NULL,'1'),(21,16,5,'','',1,2799.07,'Cancelled','2026-02-07 07:55:23','2026-02-28','2026-03-28',NULL,'Lower Bunk',NULL,'1'),(22,16,5,'','',3,5398.20,'Approved','2026-02-07 07:56:54','2026-02-28','2026-04-23',NULL,'Lower Bunk','sig_22_1770452262.png',''),(23,17,5,'','',2,5198.27,'Approved','2026-02-07 08:31:57','2026-02-07','2026-03-31',NULL,'Lower Bunk','sig_23_1770453136.png','');
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
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (1,'Tyson','Single',50.99,1,0,NULL,'WokeLogo.jpg','Available','Available'),(3,'Rasing','Single',69.69,3,0,NULL,'Screenshot (30).png','Available','Available'),(5,'6 Beds','6-Bed',2999.00,6,0,NULL,'502053110_10074917945917331_5607640182378445538_n.jpg','Available','Available');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'hero_image','[\"1770447047_hero.png\",\"1770447222_hero.png\",\"1770447312_hero_edit.png\"]');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temporary_moves`
--

LOCK TABLES `temporary_moves` WRITE;
/*!40000 ALTER TABLE `temporary_moves` DISABLE KEYS */;
/*!40000 ALTER TABLE `temporary_moves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('guest','admin') DEFAULT 'guest',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` varchar(256) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (3,'Tysoni','tysonicrosini@gmail.com',NULL,NULL,'$2y$10$ergCM68eAQCt/kgGi3twn.iJafo78Y8HMIBwaDHoTzmWFIWsXaBya','guest','2026-01-18 13:27:02',''),(4,'Alvino Alamano','alvinoalamano@gmail.com','0925713123',NULL,'$2y$10$ZcoEeca5EfW7FtzjO8jYXu6En7an7iKkAyLjszNulxr/pkaJLfTp2','guest','2026-01-19 16:05:49',''),(5,'Jenny Angel ','JennyMae@gmail.com','0987628612',NULL,'$2y$10$hJywYvblPSH2GpnnFNx9sev9N869eFIfGQxqczZe8LynhUljs8UTC','guest','2026-01-20 04:25:20',''),(6,'BrayanoValenciano','brianovalenciano@gmail.com','098876329132',NULL,'$2y$10$wWr/U5yvgg0GXA4fCviWH..OBmlP0jwtodRnxbrDseMkPFItMiPXu','guest','2026-01-20 05:44:45',''),(7,'stephenpogi','stephenpogi@gmail.com','093276736233',NULL,'$2y$10$oUBDdnafQ4uAmRwJd1DQj.ZY9Dmp8HzRD2E.jzAAuprteuH1TDtsC','guest','2026-01-20 05:46:08',''),(8,'FRED TAYSON','testuser1@gmail.com','09673101356',NULL,'$2y$10$HK7ho.9TtB33.7DCzDq7muKKtY1mhwZ4Cm4LNtgCJOBNduEl78jXy','guest','2026-02-02 15:38:37',''),(9,'hazel santiago','tysonicrosini@gmal.com','093342',NULL,'$2y$10$z3B4GkSS17nAB0TTRMzfJOW1P6Kt9P2.xAsf5wzUhuhy512R2aNSC','guest','2026-02-04 04:38:44',''),(10,'FRED TAYSON','bernasuncion11@gmail.com','34234',NULL,'$2y$10$6u4ivr3VVpJaBnAvW5Hh2ueNqcvA2k0Joe0UqQrHVw7Lr6Tr.xJOC','guest','2026-02-04 05:44:28',''),(12,'bryan','nicle@gmail.com','09673101356',NULL,'$2y$10$KNndQy2NC0daPij1yMSbgeokMHXVHITVG1.1v4IGBZlpxad9OLsZy','guest','2026-02-05 12:23:10',''),(13,'Steph Begosa','testuser2@gmail.com','0967310135',NULL,'$2y$10$g2IDGozdGvPKQQMaZEj68uNWpZMEiLmPhimYy1qAt/a0TVqffV5JO','guest','2026-02-05 18:04:25',''),(14,'hazel santiago','testuser3@gmail.com','09673101356',NULL,'$2y$10$3vCHIosFadmy21vKhYvMQOy9wJ37Zc5Qj92kNzbNekZT1m9MwDXP.','guest','2026-02-06 04:30:48',''),(15,'FRED TAYSON','fredhenzeltayson1@gmail.com','09673101356',NULL,'$2y$10$pt07BRTrT31wymb.5vuPuOzf1inLEZ5x2jwnRc1xJFjP2MYwkB6Uu','guest','2026-02-06 04:58:54',''),(16,'takerman','6takerman@gmail.com','0962734444',NULL,'$2y$10$fEHRwXCIO8w7l4GsytLu..2yslNSsMyBA1gX6kq5qzxk2GNCRyycC','guest','2026-02-06 19:07:39',''),(17,'Stephen Squad','stephenpogi3@gmail.com','096273444422',NULL,'$2y$10$mSxyWyYo.ysMEVQTxxgXnOgdc0sfqeo1fQJ.bxm/zlheDl.6lct4m','guest','2026-02-07 08:31:10','');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `waitlist`
--

LOCK TABLES `waitlist` WRITE;
/*!40000 ALTER TABLE `waitlist` DISABLE KEYS */;
INSERT INTO `waitlist` VALUES (1,16,'6-Bed','2026-02-07 07:13:49');
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

-- Dump completed on 2026-02-07 16:42:14
