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
) ENGINE=InnoDB AUTO_INCREMENT=475 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (89,38,'Account Created','Walk-in account created by Admin','2026-02-26 01:25:30','System','System'),(92,40,'Account Created','Walk-in account created by Admin','2026-02-26 01:57:54','System','System'),(93,40,'Walk-in Booking','Reservation #51 created by Admin','2026-02-26 01:57:54','System','System'),(108,40,'Payment Confirmed','Payment #55 marked as Paid by Admin.','2026-02-28 13:54:57','System','System'),(217,40,'Profile Updated','Admin updated user details.','2026-03-01 12:29:35','System','System'),(218,40,'Profile Updated','Admin updated user details.','2026-03-01 12:36:44','System','System'),(232,40,'Profile Updated','Admin updated user details.','2026-03-02 07:19:43','System','System'),(339,40,'Penalty Applied','Late fee of 30.00 applied for Payment #103','2026-03-08 13:41:45','System','System'),(357,40,'Parking Ended','Parking reservation #2 ended by Super Admin','2026-03-08 14:16:54','Super Admin','Super Admin'),(381,40,'Payment Confirmed','Payment #103 marked as Paid by Super Admin.','2026-03-08 20:19:32','Super Admin','Super Admin'),(382,40,'Payment Confirmed','Payment #110 marked as Paid by Super Admin.','2026-03-08 20:19:38','Super Admin','Super Admin'),(383,40,'Signature Requested','Signature requested for Reservation #51 by Super Admin','2026-03-08 20:19:51','Super Admin','Super Admin'),(384,40,'Room Re-assigned','Moved to 203 (Any) by Super Admin','2026-03-08 20:20:41','Super Admin','Super Admin'),(392,40,'Parking Assigned','Assigned to Car Slot 1 by Super Admin','2026-03-09 12:49:32','Super Admin','Super Admin'),(393,40,'Parking Ended','Parking reservation #11 ended by Super Admin','2026-03-09 12:49:38','Super Admin','Super Admin'),(394,40,'Room Re-assigned','Moved to 201 (Any) by Super Admin','2026-03-09 14:06:31','Super Admin','Super Admin'),(395,40,'Room Re-assigned','Moved to 203 (Lower Bunk) by Super Admin','2026-03-09 16:28:34','Super Admin','Super Admin'),(396,40,'Room Re-assigned','Moved to 201 (Any) by Super Admin','2026-03-09 17:01:47','Super Admin','Super Admin'),(397,40,'Room Re-assigned','Moved to 1 Bed (Any) by Super Admin','2026-03-09 19:02:19','Super Admin','Super Admin'),(428,40,'Payment Confirmed','Payment #123 marked as Paid by Super Admin.','2026-03-12 16:39:27','Super Admin','Super Admin'),(432,71,'Account Created','Walk-in account created by Super Admin','2026-03-12 16:44:00','Super Admin','Super Admin'),(433,71,'Walk-in Booking','Reservation #103 created by Super Admin','2026-03-12 16:44:00','Super Admin','Super Admin'),(434,72,'Account Created','Walk-in account created by Super Admin','2026-03-12 16:46:35','Super Admin','Super Admin'),(435,72,'Walk-in Booking','Reservation #104 created by Super Admin','2026-03-12 16:46:35','Super Admin','Super Admin'),(436,73,'Account Created','Walk-in account created by Super Admin','2026-03-12 16:48:23','Super Admin','Super Admin'),(437,73,'Walk-in Booking','Reservation #105 created by Super Admin','2026-03-12 16:48:23','Super Admin','Super Admin'),(438,74,'Account Created','Walk-in account created by Super Admin','2026-03-12 16:51:04','Super Admin','Super Admin'),(439,74,'Walk-in Booking','Reservation #106 created by Super Admin','2026-03-12 16:51:04','Super Admin','Super Admin'),(440,75,'Account Created','Walk-in account created by Super Admin','2026-03-12 16:52:31','Super Admin','Super Admin'),(441,75,'Walk-in Booking','Reservation #107 created by Super Admin','2026-03-12 16:52:31','Super Admin','Super Admin'),(442,76,'Account Created','Walk-in account created by Super Admin','2026-03-12 16:54:38','Super Admin','Super Admin'),(443,76,'Walk-in Booking','Reservation #108 created by Super Admin','2026-03-12 16:54:38','Super Admin','Super Admin'),(444,79,'Reservation Submitted','Room: Single | Status: Pending','2026-03-12 17:00:18','Super Admin','Super Admin'),(445,80,'Reservation Submitted','Room: Single | Status: Pending','2026-03-12 17:04:35','Super Admin','Super Admin'),(446,81,'Reservation Submitted','Room: Single | Status: Pending','2026-03-12 17:07:45','Super Admin','Super Admin'),(447,82,'Account Created','Walk-in account created by admin','2026-03-12 22:24:00','admin','Admin'),(448,82,'Walk-in Booking','Reservation #112 created by admin','2026-03-12 22:24:00','admin','Admin'),(449,76,'Key Released','Key ID 9 released to user by Super Admin','2026-03-13 00:11:29','Super Admin','Super Admin'),(450,76,'Key Returned','Key ID 9 marked as returned by Super Admin','2026-03-13 00:11:45','Super Admin','Super Admin'),(451,83,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-03-13 00:39:07','Super Admin','Super Admin'),(452,73,'Parking Assigned','Assigned to Car Slot 1 by Super Admin','2026-03-13 00:47:14','Super Admin','Super Admin'),(453,79,'Reservation Cancelled','Reservation #109 auto-expired due to non-payment.','2026-04-14 00:49:23','System','System'),(454,80,'Reservation Cancelled','Reservation #110 auto-expired due to non-payment.','2026-04-14 00:49:23','System','System'),(455,81,'Reservation Cancelled','Reservation #111 auto-expired due to non-payment.','2026-04-14 00:49:23','System','System'),(456,83,'Reservation Cancelled','Reservation #113 auto-expired due to non-payment.','2026-04-14 00:49:23','System','System'),(457,73,'Penalty Applied','Late fee of 30.00 applied for Payment #142','2026-04-14 00:49:23','System','System'),(458,74,'Penalty Applied','Late fee of 480.00 applied for Payment #134','2026-04-14 00:49:25','System','System'),(459,75,'Penalty Applied','Late fee of 449.95 applied for Payment #135','2026-04-14 00:49:27','System','System'),(460,76,'Penalty Applied','Late fee of 480.00 applied for Payment #136','2026-04-14 00:49:29','Super Admin','Super Admin'),(461,76,'Penalty Applied','Late fee of 480.00 applied for Payment #136','2026-04-14 00:49:29','System','System'),(462,79,'Penalty Applied','Late fee of 850.00 applied for Payment #137','2026-04-14 00:49:32','Super Admin','Super Admin'),(463,79,'Penalty Applied','Late fee of 850.00 applied for Payment #137','2026-04-14 00:49:32','System','System'),(464,80,'Penalty Applied','Late fee of 60.00 applied for Payment #138','2026-04-14 00:49:34','System','System'),(465,80,'Penalty Applied','Late fee of 60.00 applied for Payment #138','2026-04-14 00:49:34','Super Admin','Super Admin'),(466,82,'Penalty Applied','Late fee of 23.33 applied for Payment #140','2026-04-14 00:49:36','Super Admin','Super Admin'),(467,82,'Penalty Applied','Late fee of 23.33 applied for Payment #140','2026-04-14 00:49:36','System','System'),(468,73,'Contract Ended','Reservation #105 marked as Completed by Super Admin.','2026-03-13 00:56:36','Super Admin','Super Admin'),(469,73,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-03-13 00:57:41','Super Admin','Super Admin'),(470,73,'Reservation Verifying','Reservation #114 moved to Verifying status by Super Admin.','2026-03-13 00:57:56','Super Admin','Super Admin'),(471,73,'Payment Confirmed','Payment #154 marked as Paid by Super Admin.','2026-03-13 00:58:06','Super Admin','Super Admin'),(472,73,'Reservation Approved','Reservation #114 approved by Super Admin.','2026-03-13 00:58:47','Super Admin','Super Admin'),(473,73,'Signature Requested','Signature requested for Reservation #114 by Super Admin from receipt view.','2026-03-13 00:59:10','Super Admin','Super Admin'),(474,73,'Lease Signed','Reservation #114','2026-03-13 00:59:19','Super Admin','Super Admin');
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
INSERT INTO `housekeeping_requests` VALUES (13,40,7,'Weekly Routine Cleaning','Scheduled','2026-03-28','2026-03-08 14:31:21',0.00);
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
  `holder_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `key_id` (`key_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `key_transactions_ibfk_1` FOREIGN KEY (`key_id`) REFERENCES `keys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `key_transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `key_transactions`
--

LOCK TABLES `key_transactions` WRITE;
/*!40000 ALTER TABLE `key_transactions` DISABLE KEYS */;
INSERT INTO `key_transactions` VALUES (9,9,76,'2026-03-13 08:11:27','2026-03-13 08:11:43','Returned','');
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
) ENGINE=InnoDB AUTO_INCREMENT=265 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `keys`
--

LOCK TABLES `keys` WRITE;
/*!40000 ALTER TABLE `keys` DISABLE KEYS */;
INSERT INTO `keys` VALUES (9,'Room 202 Key','Room',6,'Available'),(10,'Room 302 Key','Room',7,'Available'),(11,'Room 203 Key','Room',16,'Available'),(12,'Room 204 Key','Room',17,'Available'),(13,'Room 303 Key','Room',18,'Available'),(14,'Room 203 Key','Room',19,'Available'),(15,'Room 401 Key','Room',20,'Available'),(16,'Room 501 Key','Room',21,'Available'),(17,'Room 601 Key','Room',22,'Available'),(18,'Room 701 Key','Room',23,'Available'),(19,'Room 402 Key','Room',24,'Available'),(20,'Room 502 Key','Room',25,'Available'),(21,'Room 602 Key','Room',26,'Available'),(22,'Room 702 Key','Room',27,'Available'),(23,'Room 403 Key','Room',28,'Available'),(24,'Room 503 Key','Room',29,'Available'),(25,'Room 603 Key','Room',30,'Available'),(26,'Room 703 Key','Room',31,'Available'),(27,'Room 204 Key','Room',32,'Available'),(29,'Room 206 Key','Room',34,'Available'),(30,'Room 207 Key','Room',35,'Available'),(31,'Room 208 Key','Room',36,'Available'),(32,'Room 209 Key','Room',37,'Available'),(33,'Room 308 Key','Room',38,'Available'),(34,'Room 309 Key','Room',39,'Available'),(35,'Room 304 Key','Room',40,'Available'),(36,'Room 305 Key','Room',41,'Available'),(37,'Room 306 Key','Room',42,'Available'),(38,'Room 307 Key','Room',43,'Available'),(39,'Room 205 Key','Room',44,'Available'),(41,'Room 404 Key','Room',46,'Available'),(42,'Room 405 Key','Room',47,'Available'),(43,'Room 406 Key','Room',48,'Available'),(44,'Room 407 Key','Room',49,'Available'),(45,'Room 408 Key','Room',50,'Available'),(46,'Room 409 Key','Room',51,'Available'),(47,'Room 302 Key','Room',52,'Available'),(48,'Room 508 Key','Room',53,'Available'),(49,'Room 509 Key','Room',54,'Available'),(50,'Room 608 Key','Room',55,'Available'),(51,'Room 609 Key','Room',56,'Available'),(52,'Room 708 Key','Room',57,'Available'),(54,'Room 203 Key','Room',59,'Available'),(55,'Room 504 Key','Room',60,'Available'),(56,'Room 505 Key','Room',61,'Available'),(57,'Room 506 Key','Room',62,'Available'),(58,'Room 507 Key','Room',63,'Available'),(59,'Room 604 Key','Room',64,'Available'),(60,'Room 605 Key','Room',65,'Available'),(61,'Room 606 Key','Room',66,'Available'),(62,'Room 607 Key','Room',67,'Available'),(63,'Room 704 Key','Room',68,'Available'),(64,'Room 705 Key','Room',69,'Available'),(67,'Room 201 Key','Room',45,'Available'),(68,'Room 709 Key','Room',58,'Available'),(69,'Room 706 Key','Room',70,'Available'),(70,'Room 707 Key','Room',71,'Available'),(71,'Room 205 Key','Room',72,'Available'),(72,'Room 602 Key','Room',73,'Available'),(73,'Room 202 Key #2','Room',6,'Available'),(74,'Room 202 Key #3','Room',6,'Available'),(75,'Room 202 Key #4','Room',6,'Available'),(76,'Room 202 Key #5','Room',6,'Available'),(77,'Room 202 Key #6','Room',6,'Available'),(78,'Room 303 Key #2','Room',7,'Available'),(79,'Room 303 Key #3','Room',7,'Available'),(80,'Room 303 Key #4','Room',7,'Available'),(81,'Room 402 Key #2','Room',24,'Available'),(82,'Room 402 Key #3','Room',24,'Available'),(83,'Room 402 Key #4','Room',24,'Available'),(84,'Room 402 Key #5','Room',24,'Available'),(85,'Room 402 Key #6','Room',24,'Available'),(86,'Room 502 Key #2','Room',25,'Available'),(87,'Room 502 Key #3','Room',25,'Available'),(88,'Room 502 Key #4','Room',25,'Available'),(89,'Room 502 Key #5','Room',25,'Available'),(90,'Room 502 Key #6','Room',25,'Available'),(91,'Room 602 Key #2','Room',26,'Available'),(92,'Room 602 Key #3','Room',26,'Available'),(93,'Room 602 Key #4','Room',26,'Available'),(94,'Room 602 Key #5','Room',26,'Available'),(95,'Room 602 Key #6','Room',26,'Available'),(96,'Room 702 Key #2','Room',27,'Available'),(97,'Room 702 Key #3','Room',27,'Available'),(98,'Room 702 Key #4','Room',27,'Available'),(99,'Room 702 Key #5','Room',27,'Available'),(100,'Room 702 Key #6','Room',27,'Available'),(101,'Room 403 Key #2','Room',28,'Available'),(102,'Room 403 Key #3','Room',28,'Available'),(103,'Room 403 Key #4','Room',28,'Available'),(104,'Room 503 Key #2','Room',29,'Available'),(105,'Room 503 Key #3','Room',29,'Available'),(106,'Room 503 Key #4','Room',29,'Available'),(107,'Room 603 Key #2','Room',30,'Available'),(108,'Room 603 Key #3','Room',30,'Available'),(109,'Room 603 Key #4','Room',30,'Available'),(110,'Room 703 Key #2','Room',31,'Available'),(111,'Room 703 Key #3','Room',31,'Available'),(112,'Room 703 Key #4','Room',31,'Available'),(113,'Room 204 Key #2','Room',32,'Available'),(114,'Room 204 Key #3','Room',32,'Available'),(115,'Room 204 Key #4','Room',32,'Available'),(116,'Room 206 Key #2','Room',34,'Available'),(117,'Room 206 Key #3','Room',34,'Available'),(118,'Room 206 Key #4','Room',34,'Available'),(119,'Room 207 Key #2','Room',35,'Available'),(120,'Room 207 Key #3','Room',35,'Available'),(121,'Room 207 Key #4','Room',35,'Available'),(122,'Room 208 Key #2','Room',36,'Available'),(123,'Room 208 Key #3','Room',36,'Available'),(124,'Room 208 Key #4','Room',36,'Available'),(125,'Room 208 Key #5','Room',36,'Available'),(126,'Room 208 Key #6','Room',36,'Available'),(127,'Room 209 Key #2','Room',37,'Available'),(128,'Room 209 Key #3','Room',37,'Available'),(129,'Room 209 Key #4','Room',37,'Available'),(130,'Room 209 Key #5','Room',37,'Available'),(131,'Room 209 Key #6','Room',37,'Available'),(132,'Room 308 Key #2','Room',38,'Available'),(133,'Room 308 Key #3','Room',38,'Available'),(134,'Room 308 Key #4','Room',38,'Available'),(135,'Room 308 Key #5','Room',38,'Available'),(136,'Room 308 Key #6','Room',38,'Available'),(137,'Room 309 Key #2','Room',39,'Available'),(138,'Room 309 Key #3','Room',39,'Available'),(139,'Room 309 Key #4','Room',39,'Available'),(140,'Room 309 Key #5','Room',39,'Available'),(141,'Room 309 Key #6','Room',39,'Available'),(142,'Room 304 Key #2','Room',40,'Available'),(143,'Room 304 Key #3','Room',40,'Available'),(144,'Room 304 Key #4','Room',40,'Available'),(145,'Room 305 Key #2','Room',41,'Available'),(146,'Room 305 Key #3','Room',41,'Available'),(147,'Room 305 Key #4','Room',41,'Available'),(148,'Room 306 Key #2','Room',42,'Available'),(149,'Room 306 Key #3','Room',42,'Available'),(150,'Room 306 Key #4','Room',42,'Available'),(151,'Room 307 Key #2','Room',43,'Available'),(152,'Room 307 Key #3','Room',43,'Available'),(153,'Room 307 Key #4','Room',43,'Available'),(154,'Room 205 Key #2','Room',44,'Available'),(155,'Room 205 Key #3','Room',44,'Available'),(156,'Room 205 Key #4','Room',44,'Available'),(157,'Room 404 Key #2','Room',46,'Available'),(158,'Room 404 Key #3','Room',46,'Available'),(159,'Room 404 Key #4','Room',46,'Available'),(160,'Room 405 Key #2','Room',47,'Available'),(161,'Room 405 Key #3','Room',47,'Available'),(162,'Room 405 Key #4','Room',47,'Available'),(163,'Room 406 Key #2','Room',48,'Available'),(164,'Room 406 Key #3','Room',48,'Available'),(165,'Room 406 Key #4','Room',48,'Available'),(166,'Room 407 Key #2','Room',49,'Available'),(167,'Room 407 Key #3','Room',49,'Available'),(168,'Room 407 Key #4','Room',49,'Available'),(169,'Room 408 Key #2','Room',50,'Available'),(170,'Room 408 Key #3','Room',50,'Available'),(171,'Room 408 Key #4','Room',50,'Available'),(172,'Room 408 Key #5','Room',50,'Available'),(173,'Room 408 Key #6','Room',50,'Available'),(174,'Room 409 Key #2','Room',51,'Available'),(175,'Room 409 Key #3','Room',51,'Available'),(176,'Room 409 Key #4','Room',51,'Available'),(177,'Room 409 Key #5','Room',51,'Available'),(178,'Room 409 Key #6','Room',51,'Available'),(179,'Room 302 Key #2','Room',52,'Available'),(180,'Room 302 Key #3','Room',52,'Available'),(181,'Room 302 Key #4','Room',52,'Available'),(182,'Room 302 Key #5','Room',52,'Available'),(183,'Room 302 Key #6','Room',52,'Available'),(184,'Room 508 Key #2','Room',53,'Available'),(185,'Room 508 Key #3','Room',53,'Available'),(186,'Room 508 Key #4','Room',53,'Available'),(187,'Room 508 Key #5','Room',53,'Available'),(188,'Room 508 Key #6','Room',53,'Available'),(189,'Room 509 Key #2','Room',54,'Available'),(190,'Room 509 Key #3','Room',54,'Available'),(191,'Room 509 Key #4','Room',54,'Available'),(192,'Room 509 Key #5','Room',54,'Available'),(193,'Room 509 Key #6','Room',54,'Available'),(194,'Room 608 Key #2','Room',55,'Available'),(195,'Room 608 Key #3','Room',55,'Available'),(196,'Room 608 Key #4','Room',55,'Available'),(197,'Room 608 Key #5','Room',55,'Available'),(198,'Room 608 Key #6','Room',55,'Available'),(199,'Room 609 Key #2','Room',56,'Available'),(200,'Room 609 Key #3','Room',56,'Available'),(201,'Room 609 Key #4','Room',56,'Available'),(202,'Room 609 Key #5','Room',56,'Available'),(203,'Room 609 Key #6','Room',56,'Available'),(204,'Room 708 Key #2','Room',57,'Available'),(205,'Room 708 Key #3','Room',57,'Available'),(206,'Room 708 Key #4','Room',57,'Available'),(207,'Room 708 Key #5','Room',57,'Available'),(208,'Room 708 Key #6','Room',57,'Available'),(209,'Room 709 Key #2','Room',58,'Available'),(210,'Room 709 Key #3','Room',58,'Available'),(211,'Room 709 Key #4','Room',58,'Available'),(212,'Room 709 Key #5','Room',58,'Available'),(213,'Room 709 Key #6','Room',58,'Available'),(214,'Room 203 Key #2','Room',59,'Available'),(215,'Room 203 Key #3','Room',59,'Available'),(216,'Room 203 Key #4','Room',59,'Available'),(217,'Room 504 Key #2','Room',60,'Available'),(218,'Room 504 Key #3','Room',60,'Available'),(219,'Room 504 Key #4','Room',60,'Available'),(220,'Room 505 Key #2','Room',61,'Available'),(221,'Room 505 Key #3','Room',61,'Available'),(222,'Room 505 Key #4','Room',61,'Available'),(223,'Room 506 Key #2','Room',62,'Available'),(224,'Room 506 Key #3','Room',62,'Available'),(225,'Room 506 Key #4','Room',62,'Available'),(226,'Room 507 Key #2','Room',63,'Available'),(227,'Room 507 Key #3','Room',63,'Available'),(228,'Room 507 Key #4','Room',63,'Available'),(229,'Room 604 Key #2','Room',64,'Available'),(230,'Room 604 Key #3','Room',64,'Available'),(231,'Room 604 Key #4','Room',64,'Available'),(232,'Room 605 Key #2','Room',65,'Available'),(233,'Room 605 Key #3','Room',65,'Available'),(234,'Room 605 Key #4','Room',65,'Available'),(235,'Room 606 Key #2','Room',66,'Available'),(236,'Room 606 Key #3','Room',66,'Available'),(237,'Room 606 Key #4','Room',66,'Available'),(238,'Room 607 Key #2','Room',67,'Available'),(239,'Room 607 Key #3','Room',67,'Available'),(240,'Room 607 Key #4','Room',67,'Available'),(241,'Room 704 Key #2','Room',68,'Available'),(242,'Room 704 Key #3','Room',68,'Available'),(243,'Room 704 Key #4','Room',68,'Available'),(244,'Room 705 Key #2','Room',69,'Available'),(245,'Room 705 Key #3','Room',69,'Available'),(246,'Room 705 Key #4','Room',69,'Available'),(247,'Room 706 Key #2','Room',70,'Available'),(248,'Room 706 Key #3','Room',70,'Available'),(249,'Room 706 Key #4','Room',70,'Available'),(250,'Room 707 Key #2','Room',71,'Available'),(251,'Room 707 Key #3','Room',71,'Available'),(252,'Room 707 Key #4','Room',71,'Available'),(253,'Room 205 Key #2','Room',72,'Available'),(254,'Room 205 Key #3','Room',72,'Available'),(255,'Room 205 Key #4','Room',72,'Available'),(256,'Room 302 Key #2','Room',73,'Available'),(257,'Room 302 Key #3','Room',73,'Available'),(258,'Room 302 Key #4','Room',73,'Available'),(259,'Room 302 Key #5','Room',73,'Available'),(260,'Room 302 Key #6','Room',73,'Available'),(261,'Room 205 Key #1','Room',74,'Available'),(262,'Room 205 Key #2','Room',74,'Available'),(263,'Room 205 Key #3','Room',74,'Available'),(264,'Room 205 Key #4','Room',74,'Available');
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
) ENGINE=InnoDB AUTO_INCREMENT=921 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (504,'40','2026-02-26 09:57:57','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(513,'40','2026-02-26 21:31:42','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(526,'40','2026-02-27 15:28:35','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(532,'40','2026-02-28 21:46:01','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(536,'40','2026-02-28 21:54:57','','✅ <strong>Payment Confirmed</strong><br>Your payment #55 has been verified and marked as Paid.','Payment Update'),(707,'40','2026-03-03 13:28:50','','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Car Slot 1. A fee of ₱600.00 has been added to your account.','Parking'),(708,'40','2026-03-03 14:19:47','','🔑 <strong>Key Assigned</strong><br>You have been assigned a key. Please keep it safe.','Key System'),(709,'40','2026-03-03 14:20:14','','🔑 <strong>Key Returned</strong><br>Key has been marked as returned.','Key System'),(710,'40','2026-03-03 14:21:51','','🔑 <strong>Key Assigned</strong><br>You have been assigned a key. Please keep it safe.','Key System'),(711,'40','2026-03-03 14:21:58','','🔑 <strong>Key Returned</strong><br>Key has been marked as returned.','Key System'),(713,'40','2026-03-05 18:01:58','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(726,'40','2026-03-05 22:01:59','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(737,'40','2026-03-06 02:01:59','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(740,'40','2026-03-06 11:25:54','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(743,'40','2026-03-06 15:25:55','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(748,'40','2026-03-06 23:34:31','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(751,'40','2026-03-07 12:58:52','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(754,'40','2026-03-07 17:21:42','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(768,'40','2026-03-07 21:22:35','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(771,'40','2026-03-08 13:05:01','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(774,'40','2026-03-08 21:41:45','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱30.00 has been applied to your account due to overdue payment.','Billing Alert'),(791,'40','2026-03-08 22:16:52','','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #1 has been marked as completed.','Parking'),(800,'40','2026-03-08 22:31:21','','🧹 <strong>Weekly Cleaning</strong><br>Routine housekeeping scheduled for Mar 28, 2026.','Housekeeping'),(821,'40','2026-03-09 04:19:32','','✅ <strong>Payment Confirmed</strong><br>Your payment #103 has been verified and marked as Paid.','Payment Update'),(822,'40','2026-03-09 04:19:38','','✅ <strong>Payment Confirmed</strong><br>Your payment #110 has been verified and marked as Paid.','Payment Update'),(823,'40','2026-03-09 04:19:49','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #51. Please go to My Reservations to sign.','Action Required'),(824,'40','2026-03-09 04:20:41','','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>203</strong>.','System'),(838,'40','2026-03-09 20:49:30','','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Car Slot 1. A fee of ₱600.00 has been added to your account.','Parking'),(839,'40','2026-03-09 20:49:36','','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #11 has been marked as completed.','Parking'),(840,'40','2026-03-09 22:06:31','','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>201</strong>.','System'),(841,'40','2026-03-10 00:28:34','','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>203</strong>.','System'),(842,'40','2026-03-10 01:01:47','','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>201</strong>.','System'),(843,'40','2026-03-10 03:02:19','','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>1 Bed</strong>.','System'),(844,'40','2026-03-10 11:48:49','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 09, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(852,'40','2026-03-10 17:55:44','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 09, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(859,'40','2026-03-12 21:24:55','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 09, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(875,'40','2026-03-13 00:39:27','','✅ <strong>Payment Confirmed</strong><br>Your payment #123 has been verified and marked as Paid.','Payment Update'),(879,'71','2026-03-13 00:44:00','','📅 Reminder: Your stay starts tomorrow (2026-03-13). We look forward to welcoming you!','Reminder'),(880,'72','2026-03-13 00:46:35','','📅 Reminder: Your stay starts tomorrow (2026-03-13). We look forward to welcoming you!','Reminder'),(881,'73','2026-03-13 00:48:23','','📅 Reminder: Your stay starts tomorrow (2026-03-13). We look forward to welcoming you!','Reminder'),(882,'74','2026-03-13 00:51:04','','📅 Reminder: Your stay starts tomorrow (2026-03-13). We look forward to welcoming you!','Reminder'),(883,'75','2026-03-13 00:52:31','','📅 Reminder: Your stay starts tomorrow (2026-03-13). We look forward to welcoming you!','Reminder'),(884,'76','2026-03-13 00:54:38','','📅 Reminder: Your stay starts tomorrow (2026-03-13). We look forward to welcoming you!','Reminder'),(885,'79','2026-03-13 01:00:16','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(886,'79','2026-03-13 01:00:17','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 12, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(887,'80','2026-03-13 01:04:33','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(888,'80','2026-03-13 01:04:35','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱1,200.00 was due on Mar 12, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(889,'81','2026-03-13 01:07:43','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(890,'79','2026-03-13 06:21:36','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 12, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(891,'80','2026-03-13 06:21:36','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱1,200.00 was due on Mar 12, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(892,'82','2026-03-13 06:24:00','','📅 Reminder: Your stay starts tomorrow (2026-03-13). We look forward to welcoming you!','Reminder'),(893,'82','2026-03-13 06:24:02','','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>201</strong> ends on <strong>2026-03-14</strong> (2 days left). Please contact admin to renew.','Expiration Alert'),(894,'74','2026-03-13 08:08:20','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 13, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(895,'75','2026-03-13 08:08:20','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,999.00 was due on Mar 13, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(896,'76','2026-03-13 08:08:22','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 13, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(897,'82','2026-03-13 08:08:22','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱466.67 was due on Mar 13, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(898,'76','2026-03-13 08:11:27','','🔑 <strong>Key Assigned</strong><br>You have been assigned a key (ID: 9). Please keep it safe.','Key System'),(899,'76','2026-03-13 08:11:43','','🔑 <strong>Key Returned</strong><br>Key (ID: 9) has been marked as returned.','Key System'),(900,'83','2026-03-13 08:39:05','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(901,'73','2026-03-13 08:47:12','','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Car Slot 1. A fee of ₱600.00 has been added to your account.','Parking'),(902,'73','2026-04-14 08:49:23','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱30.00 has been applied to your account due to overdue payment.','Billing Alert'),(903,'74','2026-04-14 08:49:25','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱480.00 has been applied to your account due to overdue payment.','Billing Alert'),(904,'75','2026-04-14 08:49:27','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱449.95 has been applied to your account due to overdue payment.','Billing Alert'),(905,'76','2026-04-14 08:49:29','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱480.00 has been applied to your account due to overdue payment.','Billing Alert'),(906,'76','2026-04-14 08:49:29','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱480.00 has been applied to your account due to overdue payment.','Billing Alert'),(907,'79','2026-04-14 08:49:32','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱850.00 has been applied to your account due to overdue payment.','Billing Alert'),(908,'79','2026-04-14 08:49:32','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱850.00 has been applied to your account due to overdue payment.','Billing Alert'),(909,'80','2026-04-14 08:49:34','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱60.00 has been applied to your account due to overdue payment.','Billing Alert'),(910,'80','2026-04-14 08:49:34','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱60.00 has been applied to your account due to overdue payment.','Billing Alert'),(911,'82','2026-04-14 08:49:36','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱23.33 has been applied to your account due to overdue payment.','Billing Alert'),(912,'82','2026-04-14 08:49:36','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱23.33 has been applied to your account due to overdue payment.','Billing Alert'),(913,'73','2026-03-13 08:56:36','','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #105 has been marked as completed. Thank you for staying with us!','Contract Ended'),(914,'73','2026-03-13 08:57:39','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(915,'73','2026-03-13 08:57:39','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 13, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(916,'73','2026-03-13 08:57:56','','🔍 <strong>Reservation Verifying</strong><br>Your booking #114 is now being verified. Please ensure payment and lease signing are completed.','Booking Update'),(917,'73','2026-03-13 08:58:06','','✅ <strong>Payment Confirmed</strong><br>Your payment #154 has been verified and marked as Paid.','Payment Update'),(918,'73','2026-03-13 08:58:47','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(919,'73','2026-03-13 08:59:08','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #114. Please go to My Reservations to sign.','Action Required'),(920,'82','2026-03-13 18:30:01','','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>201</strong> ends on <strong>2026-03-14</strong> (1 days left). Please contact admin to renew.','Expiration Alert');
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_reservations`
--

LOCK TABLES `parking_reservations` WRITE;
/*!40000 ALTER TABLE `parking_reservations` DISABLE KEYS */;
INSERT INTO `parking_reservations` VALUES (11,40,11,'2026-03-09','2026-03-09',600.00,'Monthly','Completed','2026-03-09 12:49:30'),(13,73,11,'2026-03-13',NULL,600.00,'Monthly','Active','2026-03-13 00:47:12');
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
INSERT INTO `parking_slots` VALUES (11,'Car Slot 1','Car','Occupied',600.00,200.00,0),(12,'Car Slot 2','Car','Available',600.00,200.00,0),(13,'Car Slot 3','Car','Available',600.00,200.00,0),(14,'Car Slot 4','Car','Available',600.00,200.00,0),(15,'Motorcycle Slot 1','Motorcycle','Available',1500.00,50.00,0),(16,'Motorcycle Slot 2','Motorcycle','Available',1500.00,50.00,0),(17,'Motorcycle Slot 3','Motorcycle','Available',1500.00,50.00,0),(18,'Motorcycle Slot 4','Motorcycle','Available',1500.00,50.00,0),(19,'Motorcycle Slot 5','Motorcycle','Available',1500.00,50.00,0),(20,'Motorcycle Slot 6','Motorcycle','Available',1500.00,50.00,0),(21,'Motorcycle Slot 7','Motorcycle','Available',1500.00,50.00,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=155 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (55,51,28200.00,'Cash','Paid','2026-02-28 13:54:57',NULL,NULL,'Room Payment',0),(103,51,600.00,'Cash','Paid','2026-03-08 20:19:32',NULL,NULL,'Monthly Parking Fee (March 2026) for Car Slot 1 (Parking ID: 2)',1),(110,51,30.00,'','Paid','2026-03-08 20:19:38',NULL,NULL,'Late Penalty (5%) for Payment #103',0),(123,51,600.00,'Cash','Paid','2026-03-12 16:39:27',NULL,NULL,'Monthly Parking Fee (March 2026) for Car Slot 1 (Parking ID: 11)',0),(131,103,9900.00,'Cash','Paid','2026-03-12 16:44:00',NULL,NULL,'Room Payment',0),(132,104,9300.00,'Cash','Paid','2026-03-12 16:46:35',NULL,NULL,'Room Payment',0),(133,105,9900.00,'Cash','Paid','2026-03-12 16:48:23',NULL,NULL,'Room Payment',0),(134,106,9600.00,'Cash','Unpaid','2026-03-12 16:51:04',NULL,NULL,'Room Payment',1),(135,107,8999.00,'Cash','Unpaid','2026-03-12 16:52:31',NULL,NULL,'Room Payment',1),(136,108,9600.00,'Cash','Unpaid','2026-03-12 16:54:38',NULL,NULL,'Room Payment',1),(137,109,17000.00,'Cash','Unpaid','2026-03-12 17:00:16',NULL,NULL,'Room Payment',1),(138,110,1200.00,'Cash','Unpaid','2026-03-12 17:04:33',NULL,NULL,'Room Payment',1),(139,111,12032.26,'GCash','Paid','2026-03-12 17:07:43','09749234578','1773335263_Woke_logo.jpg','Room Payment',0),(140,112,466.67,'Cash','Unpaid','2026-03-12 22:24:00',NULL,NULL,'Room Payment',1),(141,113,9600.00,'GCash','Paid','2026-03-13 00:39:05','09749234578','1773362345_Gcashqr.jfif','Room Payment',0),(142,105,600.00,'Cash','Unpaid','2026-03-13 00:47:12',NULL,NULL,'Monthly Parking Fee (March 2026) for Car Slot 1 (Parking ID: 13)',1),(143,105,30.00,'','Unpaid','2026-04-14 00:49:23',NULL,NULL,'Late Penalty (5%) for Payment #142',0),(144,106,480.00,'','Unpaid','2026-04-14 00:49:25',NULL,NULL,'Late Penalty (5%) for Payment #134',0),(145,107,449.95,'','Unpaid','2026-04-14 00:49:27',NULL,NULL,'Late Penalty (5%) for Payment #135',0),(146,108,480.00,'','Unpaid','2026-04-14 00:49:29',NULL,NULL,'Late Penalty (5%) for Payment #136',0),(147,108,480.00,'','Unpaid','2026-04-14 00:49:29',NULL,NULL,'Late Penalty (5%) for Payment #136',0),(148,109,850.00,'','Unpaid','2026-04-14 00:49:31',NULL,NULL,'Late Penalty (5%) for Payment #137',0),(149,109,850.00,'','Unpaid','2026-04-14 00:49:32',NULL,NULL,'Late Penalty (5%) for Payment #137',0),(150,110,60.00,'','Unpaid','2026-04-14 00:49:34',NULL,NULL,'Late Penalty (5%) for Payment #138',0),(151,110,60.00,'','Unpaid','2026-04-14 00:49:34',NULL,NULL,'Late Penalty (5%) for Payment #138',0),(152,112,23.33,'','Unpaid','2026-04-14 00:49:36',NULL,NULL,'Late Penalty (5%) for Payment #140',0),(153,112,23.33,'','Unpaid','2026-04-14 00:49:36',NULL,NULL,'Late Penalty (5%) for Payment #140',0),(154,114,9900.00,'Cash','Paid','2026-03-13 00:58:06',NULL,NULL,'Room Payment',0);
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
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (51,40,20,'','',6,28200.00,'Approved','2026-02-26 01:57:54','2026-02-26','2026-08-26',NULL,'Any',NULL,0,NULL,1),(103,71,32,'','',1,9900.00,'Approved','2026-03-12 16:44:00','2026-03-13','2026-04-13',NULL,'Lower Bunk',NULL,0,NULL,0),(104,72,32,'','',1,9300.00,'Approved','2026-03-12 16:46:35','2026-03-13','2026-04-13',NULL,'Upper Bunk',NULL,0,NULL,0),(105,73,32,'','',1,9900.00,'Completed','2026-03-12 16:48:23','2026-03-13','2026-04-13',NULL,'Lower Bunk',NULL,0,NULL,0),(106,74,6,'','',1,9600.00,'Approved','2026-03-12 16:51:04','2026-03-13','2026-04-13',NULL,'Lower Bunk',NULL,0,NULL,0),(107,75,6,'','',1,8999.00,'Approved','2026-03-12 16:52:31','2026-03-13','2026-04-13',NULL,'Upper Bunk',NULL,0,NULL,0),(108,76,6,'','',1,9600.00,'Approved','2026-03-12 16:54:38','2026-03-13','2026-04-13',NULL,'Lower Bunk',NULL,0,NULL,0),(109,79,21,'','',1,17000.00,'Cancelled','2026-03-12 17:00:16','2026-03-12','2026-04-13','Auto-expired due to non-payment','Any',NULL,0,NULL,0),(110,80,22,'','',1,1200.00,'Cancelled','2026-03-12 17:04:33','2026-03-12','2026-03-13','Auto-expired due to non-payment','Any',NULL,0,NULL,0),(111,81,23,'','',6,12032.26,'Cancelled','2026-03-12 17:07:43','2026-03-12','2026-08-30','Auto-expired due to non-payment','Any',NULL,0,NULL,0),(112,82,45,'','',1,466.67,'Approved','2026-03-12 22:24:00','2026-03-13','2026-03-14',NULL,'Any',NULL,0,NULL,0),(113,83,6,'','',1,9600.00,'Cancelled','2026-03-13 00:39:05','2026-03-13','2026-04-11','Auto-expired due to non-payment','Lower Bunk',NULL,0,NULL,0),(114,73,44,'','',1,9900.00,'Approved','2026-03-13 00:57:39','2026-03-13','2026-04-11',NULL,'Lower Bunk','sig_114_1773363559.png',0,NULL,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (6,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,2,'202',37797.00,3500.00,4200.00,24000.00,700.00,0.00,1,0),(7,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,'303',26400.00,4000.00,4500.00,17000.00,700.00,0.00,19,0),(20,'1 Bed','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,4,'401',0.00,0.00,0.00,13000.00,0.00,0.00,2,0),(21,'501','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,5,NULL,0.00,0.00,0.00,13000.00,0.00,0.00,3,0),(22,'601','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,6,NULL,0.00,0.00,0.00,13000.00,0.00,0.00,4,0),(23,'701','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,7,NULL,0.00,0.00,0.00,13000.00,0.00,0.00,5,0),(24,'402','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,4,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,2,0),(25,'502','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,5,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,3,0),(26,'602','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,6,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,4,0),(27,'702','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,7,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,5,0),(28,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,'403',26400.00,4000.00,4500.00,17000.00,0.00,0.00,20,0),(29,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,'503',26400.00,4000.00,4500.00,17000.00,0.00,0.00,14,0),(30,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,'603',26400.00,4000.00,4500.00,17000.00,0.00,0.00,9,0),(31,'703','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,4,0),(32,'204','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,2,0),(34,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,'206',26400.00,4000.00,4500.00,17000.00,0.00,0.00,21,0),(35,'207','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,22,0),(36,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,2,'208',37797.00,3500.00,4200.00,24000.00,0.00,0.00,6,0),(37,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,2,'209',37797.00,3500.00,4200.00,24000.00,0.00,0.00,7,0),(38,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,'308',37797.00,3500.00,4200.00,24000.00,0.00,0.00,9,0),(39,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,'309',37797.00,3500.00,4200.00,24000.00,0.00,0.00,10,0),(40,'304','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,23,0),(41,'305','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,24,0),(42,'306','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,25,0),(43,'307','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,26,0),(44,'205','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,20,1),(45,'201','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,2,NULL,0.00,0.00,0.00,13000.00,0.00,0.00,1,0),(46,'404','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,27,0),(47,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,'405',26400.00,4000.00,4500.00,17000.00,0.00,0.00,28,0),(48,'406','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,29,0),(49,'407','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,30,0),(50,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,4,'408',37797.00,3500.00,4200.00,24000.00,0.00,0.00,11,0),(51,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,4,'409',37797.00,3500.00,4200.00,24000.00,0.00,0.00,12,0),(52,'302','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,8,1),(53,'508','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,5,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,13,0),(54,'509','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,5,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,14,0),(55,'608','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,6,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,15,0),(56,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,6,'609',37797.00,3500.00,4200.00,24000.00,0.00,0.00,16,0),(57,'708','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,7,NULL,37797.00,3500.00,4200.00,24000.00,0.00,0.00,17,0),(58,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,7,'709',37797.00,3500.00,4200.00,24000.00,0.00,0.00,18,0),(59,'203','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,1,0),(60,'504','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,15,0),(61,'505','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,16,0),(62,'506','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,17,0),(63,'507','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,18,0),(64,'604','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,10,0),(65,'605','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,11,0),(66,'606','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,12,0),(67,'607','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,13,0),(68,'704','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,5,0),(69,'705','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,6,0),(70,'706','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,7,0),(71,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,'707',26400.00,4000.00,4500.00,17000.00,0.00,0.00,8,0),(72,'205','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,1,2,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,3,0),(73,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,'302',37797.00,3500.00,4200.00,24000.00,0.00,0.00,0,0),(74,'205','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,NULL,26400.00,4000.00,4500.00,17000.00,0.00,0.00,0,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=627 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'hero_image','[\"1770471778_hero_edit.png\",\"1770447312_hero_edit.png\",\"1772369513_hero.png\",\"1770447047_hero.png\"]'),(125,'living_area_image','living_area_1770486291.jpg'),(126,'last_update','1773363478'),(290,'price_single','14000'),(291,'price_4bed_upper','6300'),(292,'price_4bed_lower','6900'),(293,'price_6bed_upper','5999'),(294,'price_6bed_lower','6600'),(303,'price_4bed_whole','26400'),(306,'price_6bed_whole','37797'),(315,'price_single_long','13000'),(319,'price_4bed_upper_long','4000'),(320,'price_4bed_lower_long','4500'),(321,'price_4bed_whole_long','17000'),(325,'price_6bed_upper_long','3500'),(326,'price_6bed_lower_long','4200'),(327,'price_6bed_whole_long','24000'),(548,'room_type_order','[\"Single\",\"4-Bed\",\"6-Bed\"]');
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
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (40,'bartjavillonar@gmail.com','09304871699',NULL,'$2y$10$X9kKW5UpTXSviWVagyKhAuXEzk2SiwS4GxxgjIjikUt22qpKOwho2','','2026-02-26 01:57:54',0,0,'Male',NULL,NULL,'Employed','','',NULL,'','',NULL,0,0,'JAVILLONAR','BARTOLOME','',1,NULL,NULL,NULL,NULL,NULL,NULL),(71,'fredhenzeltayson1@gmail.com','09907087087',NULL,'$2y$10$/if7Hy.92g49o0cA90hqqeKdXmuGDcRcPmC0Icfh8Z8wivLDIUcpW','user','2026-03-12 16:44:00',0,0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,'mother name','09638366474',NULL,1,0,'tayson','fredhenzel','N',1,NULL,NULL,NULL,NULL,NULL,NULL),(72,'begoza@gmail.com','09368462342',NULL,'$2y$10$1HJmHteLaH.5qzvoNxkSEukpzGAI4TqwDrbat7Do3iKcc5QTwk6dC','user','2026-03-12 16:46:35',0,0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,'mother name','09533884752',NULL,1,0,'Begosa','Stephen Zhane','B.',1,NULL,NULL,NULL,NULL,NULL,NULL),(73,'bryanvalencia1@gmail.com','09284398423',NULL,'$2y$10$CxXModUz7rf1VvtDoM/yh.0AJ5QvxdjXS8xVNBt442BMd/omcx6zi','user','2026-03-12 16:48:23',0,0,'Male',NULL,NULL,'Student','123',NULL,'1773363459_school_553086532_1458289505383792_3468955167122582667_n.jpg','mother name','09234689473',NULL,1,0,'Ablao','Bryan','V',1,NULL,NULL,1,1,1,1),(74,'marwinzsantiago@gmail.com','09254548435',NULL,'$2y$10$h7NRUccnELUZvGEkNeA6ZuTO8wuGSrGzZaEKNwYoULT/9dhECHEoS','user','2026-03-12 16:51:04',0,0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,'mother name','09234142435',NULL,1,0,'Santiago','Marwin Lee','',1,NULL,NULL,NULL,NULL,NULL,NULL),(75,'Mimon@gmail.com','09234672143',NULL,'$2y$10$3bN4YioTHLA9tCnRKI6XuOyUgQW4A1x/ObodpuyRrOaEsoqdFWxNe','user','2026-03-12 16:52:31',0,0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,'mother name','09847892735',NULL,1,0,'Joriza','Mimon','',1,NULL,NULL,NULL,NULL,NULL,NULL),(76,'Alvin@gmail.com','09377826734',NULL,'$2y$10$vnNbaOv1oEuDwRxwBLZPNu9Z1iTLdQSPicfFza85XW5LI9xH1/Oge','user','2026-03-12 16:54:38',0,0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,'mother name','09834287144',NULL,1,0,'Constantino','Alvin','C',1,NULL,NULL,NULL,NULL,NULL,NULL),(77,'johnrasing@gmail.com','09254898372',NULL,'$2y$10$hb7v1iOghlLeMEYjLi211utI08/xJstRz9lFVSEPIHzsWjxNRKpiW','user','2026-03-12 16:55:24',0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'Rasing','john ben','',1,NULL,NULL,NULL,NULL,NULL,NULL),(78,'celmir@gmail.com','09243698742',NULL,'$2y$10$1OEEHDGf8FQVRT69s3Q.eOBtQbO4lIq1k6cge0lvGU.2QJ.qmp9dC','user','2026-03-12 16:56:45',0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'Hernandez','Celmir','',1,NULL,NULL,NULL,NULL,NULL,NULL),(79,'Jomari@gmail.com','09456987263',NULL,'$2y$10$c7LDcrEotRiqE7XqqX7nc.VC.Rsef4gMUN4uCrt//wrsIfOSIk.s6','user','2026-03-12 16:58:23',0,0,'Male',NULL,NULL,'Student','caste',NULL,'1773334816_school_WokeLogo.jpg','mother name','09659323252',NULL,0,0,'Lapeciros','Jomari','A.',1,NULL,NULL,1,1,1,1),(80,'Benito@gmail.com','09347865928',NULL,'$2y$10$sSTx9/jb7eLCh/ebbAGJy.tcLEGX4eve1n1sblD4ScOmF1fHQC6qy','user','2026-03-12 17:01:28',0,0,'Male',NULL,NULL,'Student','barretto ',NULL,'1773335073_school_Woke_logo.jpg','mother name','09234658732',NULL,0,0,'Benito','Michael','A.',1,NULL,NULL,1,1,1,1),(81,'neo@gmail.com','09274132421',NULL,'$2y$10$JX73b/3VHXHN3Gu6sZ1SROsxTeXGimle4AccT9jR.xGTSdoMb6xju','user','2026-03-12 17:05:43',0,0,'Male',NULL,NULL,'Student','Subic',NULL,'1773335263_school_WOKE_COLIVING_LOGO.jpg','mother name','09421954325',NULL,0,0,'Lagunzad','Neo','',1,NULL,NULL,1,1,1,1),(82,'Michael@gmail.com','09627463989',NULL,'$2y$10$eSbA7Dlz07UX5H7dpFwj7uTfJUMn8DPKVRGkXKRUTF.QMYOI2OdG2','user','2026-03-12 22:24:00',0,0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,'mother name','09563483648',NULL,1,0,'Sta Olaya','Michael John','',1,NULL,NULL,NULL,NULL,NULL,NULL),(83,'testuser1@gmail.com','09234324444',NULL,'$2y$10$TR6hhvYcNsXEcsaexV.jMOpfZbJg.DmWndaHsuRKFmdDW5T5nnaOO','user','2026-03-13 00:34:07',0,0,'Male',NULL,NULL,'Student','caste',NULL,'1773362345_school_Woke_logo.jpg','mother name','09423542784',NULL,0,0,'Viloria','Nicole','',1,NULL,NULL,1,1,1,1);
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

-- Dump completed on 2026-03-13 22:05:28
