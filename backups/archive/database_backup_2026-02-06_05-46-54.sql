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
  PRIMARY KEY (`payment_id`),
  KEY `reservation_id` (`reservation_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,13,32.29,'GCash','Paid','2026-02-06 04:27:44'),(2,14,32.29,'GCash','Paid','2026-02-06 04:31:35'),(3,15,25.50,'GCash','Paid','2026-02-06 04:38:28');
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
  PRIMARY KEY (`reservation_id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (1,3,1,'2026-01-18','2026-01-19',0,1.70,'Approved','2026-01-18 15:12:01',NULL,NULL),(5,4,1,'2026-01-20','2026-01-21',0,1.70,'Approved','2026-01-19 16:21:50',NULL,NULL),(6,5,1,'2026-01-21','2026-01-22',0,1.70,'Approved','2026-01-20 04:26:09',NULL,NULL),(7,6,3,'2026-01-20','2026-01-22',0,4.65,'Approved','2026-01-20 05:45:09',NULL,NULL),(8,7,3,'2026-01-20','2026-01-22',0,4.65,'Approved','2026-01-20 05:47:15',NULL,NULL),(9,8,1,'2026-02-07','2026-02-16',0,0.00,'Cancelled','2026-02-02 15:39:14',NULL,NULL),(10,9,1,'','',1,50.99,'Cancelled','2026-02-04 04:44:09','2026-02-04','2026-03-04'),(11,10,3,'','',1,53.43,'Pending','2026-02-05 12:47:21','2026-02-05','2026-02-28'),(12,13,1,'','',1,37.39,'Cancelled','2026-02-05 20:00:40','2026-02-05','2026-02-27'),(13,13,1,'','',1,32.29,'Cancelled','2026-02-06 04:27:44','2026-02-06','2026-02-25'),(14,14,1,'','',1,32.29,'Cancelled','2026-02-06 04:31:35','2026-02-06','2026-02-25'),(15,14,1,'','',1,25.50,'Pending','2026-02-06 04:38:28','2026-02-06','2026-02-21');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (1,'Tyson','Single',50.99,1,0,NULL,'WokeLogo.jpg','Available','Available'),(3,'Rasing','Single',69.69,3,0,NULL,'Screenshot (30).png','Available','Available'),(4,'marwino cayetano','',1.99,1,0,NULL,'Screenshot 2026-01-20 041733.png','Available','Available');
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
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
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (3,'Tysoni','tysonicrosini@gmail.com',NULL,NULL,'$2y$10$ergCM68eAQCt/kgGi3twn.iJafo78Y8HMIBwaDHoTzmWFIWsXaBya','guest','2026-01-18 13:27:02'),(4,'Alvino Alamano','alvinoalamano@gmail.com','0925713123',NULL,'$2y$10$ZcoEeca5EfW7FtzjO8jYXu6En7an7iKkAyLjszNulxr/pkaJLfTp2','guest','2026-01-19 16:05:49'),(5,'Jenny Angel ','JennyMae@gmail.com','0987628612',NULL,'$2y$10$hJywYvblPSH2GpnnFNx9sev9N869eFIfGQxqczZe8LynhUljs8UTC','guest','2026-01-20 04:25:20'),(6,'BrayanoValenciano','brianovalenciano@gmail.com','098876329132',NULL,'$2y$10$wWr/U5yvgg0GXA4fCviWH..OBmlP0jwtodRnxbrDseMkPFItMiPXu','guest','2026-01-20 05:44:45'),(7,'stephenpogi','stephenpogi@gmail.com','093276736233',NULL,'$2y$10$oUBDdnafQ4uAmRwJd1DQj.ZY9Dmp8HzRD2E.jzAAuprteuH1TDtsC','guest','2026-01-20 05:46:08'),(8,'FRED TAYSON','testuser1@gmail.com','09673101356',NULL,'$2y$10$HK7ho.9TtB33.7DCzDq7muKKtY1mhwZ4Cm4LNtgCJOBNduEl78jXy','guest','2026-02-02 15:38:37'),(9,'hazel santiago','tysonicrosini@gmal.com','093342',NULL,'$2y$10$z3B4GkSS17nAB0TTRMzfJOW1P6Kt9P2.xAsf5wzUhuhy512R2aNSC','guest','2026-02-04 04:38:44'),(10,'FRED TAYSON','bernasuncion11@gmail.com','34234',NULL,'$2y$10$6u4ivr3VVpJaBnAvW5Hh2ueNqcvA2k0Joe0UqQrHVw7Lr6Tr.xJOC','guest','2026-02-04 05:44:28'),(12,'bryan','nicle@gmail.com','09673101356',NULL,'$2y$10$KNndQy2NC0daPij1yMSbgeokMHXVHITVG1.1v4IGBZlpxad9OLsZy','guest','2026-02-05 12:23:10'),(13,'Steph Begosa','testuser2@gmail.com','0967310135',NULL,'$2y$10$g2IDGozdGvPKQQMaZEj68uNWpZMEiLmPhimYy1qAt/a0TVqffV5JO','guest','2026-02-05 18:04:25'),(14,'hazel santiago','testuser3@gmail.com','09673101356',NULL,'$2y$10$3vCHIosFadmy21vKhYvMQOy9wJ37Zc5Qj92kNzbNekZT1m9MwDXP.','guest','2026-02-06 04:30:48');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-06 12:46:55
