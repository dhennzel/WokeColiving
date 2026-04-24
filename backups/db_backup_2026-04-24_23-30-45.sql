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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_deletion_requests`
--

LOCK TABLES `account_deletion_requests` WRITE;
/*!40000 ALTER TABLE `account_deletion_requests` DISABLE KEYS */;
INSERT INTO `account_deletion_requests` VALUES (4,112,'Rejected','2026-04-23 03:37:04');
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
) ENGINE=InnoDB AUTO_INCREMENT=990 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (89,38,'Account Created','Walk-in account created by Admin','2026-02-26 01:25:30','System','System'),(92,40,'Account Created','Walk-in account created by Admin','2026-02-26 01:57:54','System','System'),(93,40,'Walk-in Booking','Reservation #51 created by Admin','2026-02-26 01:57:54','System','System'),(108,40,'Payment Confirmed','Payment #55 marked as Paid by Admin.','2026-02-28 13:54:57','System','System'),(217,40,'Profile Updated','Admin updated user details.','2026-03-01 12:29:35','System','System'),(218,40,'Profile Updated','Admin updated user details.','2026-03-01 12:36:44','System','System'),(232,40,'Profile Updated','Admin updated user details.','2026-03-02 07:19:43','System','System'),(339,40,'Penalty Applied','Late fee of 30.00 applied for Payment #103','2026-03-08 13:41:45','System','System'),(357,40,'Parking Ended','Parking reservation #2 ended by Super Admin','2026-03-08 14:16:54','Super Admin','Super Admin'),(381,40,'Payment Confirmed','Payment #103 marked as Paid by Super Admin.','2026-03-08 20:19:32','Super Admin','Super Admin'),(382,40,'Payment Confirmed','Payment #110 marked as Paid by Super Admin.','2026-03-08 20:19:38','Super Admin','Super Admin'),(383,40,'Signature Requested','Signature requested for Reservation #51 by Super Admin','2026-03-08 20:19:51','Super Admin','Super Admin'),(384,40,'Room Re-assigned','Moved to 203 (Any) by Super Admin','2026-03-08 20:20:41','Super Admin','Super Admin'),(392,40,'Parking Assigned','Assigned to Car Slot 1 by Super Admin','2026-03-09 12:49:32','Super Admin','Super Admin'),(393,40,'Parking Ended','Parking reservation #11 ended by Super Admin','2026-03-09 12:49:38','Super Admin','Super Admin'),(394,40,'Room Re-assigned','Moved to 201 (Any) by Super Admin','2026-03-09 14:06:31','Super Admin','Super Admin'),(395,40,'Room Re-assigned','Moved to 203 (Lower Bunk) by Super Admin','2026-03-09 16:28:34','Super Admin','Super Admin'),(396,40,'Room Re-assigned','Moved to 201 (Any) by Super Admin','2026-03-09 17:01:47','Super Admin','Super Admin'),(397,40,'Room Re-assigned','Moved to 1 Bed (Any) by Super Admin','2026-03-09 19:02:19','Super Admin','Super Admin'),(428,40,'Payment Confirmed','Payment #123 marked as Paid by Super Admin.','2026-03-12 16:39:27','Super Admin','Super Admin'),(491,40,'Key Released','Key ID 15 released to user by Super Admin','2026-03-13 20:13:08','Super Admin','Super Admin'),(492,40,'Key Returned','Key ID 15 marked as returned by Super Admin','2026-03-13 20:13:16','Super Admin','Super Admin'),(593,40,'Signature Requested','Signature requested for Reservation #51 by Super Admin','2026-03-17 18:00:16','Diane Tayson (Super Admin)','Super Admin'),(594,40,'Lease Signed','Reservation #51','2026-03-17 18:00:55','Diane Tayson (Super Admin)','Super Admin'),(622,40,'Penalty Applied','Late fee of 0.31 applied for Payment #180','2026-03-23 11:55:46','System','System'),(623,40,'Penalty Applied','Late fee of 0.31 applied for Payment #181','2026-03-23 11:55:48','System','System'),(624,40,'Penalty Applied','Late fee of 0.31 applied for Payment #182','2026-03-23 11:55:50','System','System'),(656,98,'Account Created','Walk-in account created by Super Admin','2026-03-27 16:26:32','Diane Tayson (Super Admin)','Super Admin'),(676,40,'Payment Submitted','Reservation #51 via Cash for 6 bill(s)','2026-04-06 01:02:25','Diane Tayson (Super Admin)','Super Admin'),(832,40,'Key Released','Key ID 15 released to user by Super Admin','2026-04-21 16:04:36','Diane Tayson (Super Admin)','Super Admin'),(890,40,'Contract Ended','Reservation #51 marked as Completed by Super Admin.','2026-04-22 17:14:39','Diane Tayson (Super Admin)','Super Admin'),(891,111,'Account Created','Walk-in account created by Super Admin','2026-04-22 17:28:03','Diane Tayson (Super Admin)','Super Admin'),(892,111,'Walk-in Booking','Reservation #170 created by Super Admin','2026-04-22 17:28:03','Diane Tayson (Super Admin)','Super Admin'),(893,111,'Signature Requested','Signature requested for Reservation #170 by Super Admin','2026-04-22 17:28:59','Diane Tayson (Super Admin)','Super Admin'),(894,111,'Lease Signed','Reservation #170','2026-04-22 17:29:06','Diane Tayson (Super Admin)','Super Admin'),(895,40,'Key Returned','Key ID 15 marked as returned by Super Admin','2026-04-22 18:17:38','Diane Tayson (Super Admin)','Super Admin'),(896,112,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-23 02:47:57','Diane Tayson (Super Admin)','Super Admin'),(897,112,'Reservation Rejected','Reservation #171 cancelled by Super Admin.','2026-04-23 02:52:12','Diane Tayson (Super Admin)','Super Admin'),(898,112,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-04-23 02:59:18','Diane Tayson (Super Admin)','Super Admin'),(899,112,'Payment Submitted','Reservation #172 via Cash for: Security Deposit, First Month Rent','2026-04-23 03:02:53','Diane Tayson (Super Admin)','Super Admin'),(900,112,'Reservation Rejected','Reservation #172 cancelled by Super Admin.','2026-04-23 03:03:09','Diane Tayson (Super Admin)','Super Admin'),(901,112,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-23 03:03:31','Diane Tayson (Super Admin)','Super Admin'),(902,112,'Payment Submitted','Reservation #173 via Cash for: First Month Rent','2026-04-23 03:04:31','Diane Tayson (Super Admin)','Super Admin'),(903,112,'Payment Submitted','Reservation #173 via Cash for: Security Deposit, First Month Rent','2026-04-23 03:05:19','Diane Tayson (Super Admin)','Super Admin'),(904,112,'Payment Submitted','Reservation #173 via Cash for: Security Deposit, First Month Rent','2026-04-23 03:09:06','Diane Tayson (Super Admin)','Super Admin'),(905,112,'Payment Submitted','Reservation #173 via GCash for: Security Deposit','2026-04-23 03:10:37','Diane Tayson (Super Admin)','Super Admin'),(906,112,'Payment Submitted','Reservation #173 via GCash for: First Month Rent, Security Deposit','2026-04-23 03:11:14','Diane Tayson (Super Admin)','Super Admin'),(907,112,'Reservation Approved','Reservation #173 approved by Super Admin.','2026-04-23 03:14:17','Diane Tayson (Super Admin)','Super Admin'),(908,112,'Lease Signed','Reservation #173','2026-04-23 03:14:31','Diane Tayson (Super Admin)','Super Admin'),(909,112,'Room Re-assigned','Moved to 1 Bed (Any) by Super Admin','2026-04-23 03:15:56','Diane Tayson (Super Admin)','Super Admin'),(910,112,'Room Returned','Returned to 4 Beds by Super Admin','2026-04-23 03:17:06','Diane Tayson (Super Admin)','Super Admin'),(911,112,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-04-23 03:19:32','Diane Tayson (Super Admin)','Super Admin'),(912,112,'Reservation Extended','Contract #173 updated by Super Admin.','2026-04-23 03:21:54','Diane Tayson (Super Admin)','Super Admin'),(913,112,'Parking Assigned','Assigned to Motorcycle Slot 1 by Super Admin','2026-04-23 03:23:52','Diane Tayson (Super Admin)','Super Admin'),(914,112,'Payment Submitted','Reservation #173 via Cash for: Monthly Parking Fee (April 2026) for Motorcycle Slot 1, Extension Rent Payment','2026-04-23 03:24:45','Diane Tayson (Super Admin)','Super Admin'),(915,112,'Contract Ended','Reservation #173 marked as Completed by admin1.','2026-04-23 03:36:28','Stephen Squad (admin1)','Admin'),(916,112,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-23 03:38:19','Diane Tayson (Super Admin)','Super Admin'),(917,112,'Payment Submitted','Reservation #175 via Cash for: Security Deposit, First Month Rent','2026-04-23 03:39:01','Diane Tayson (Super Admin)','Super Admin'),(918,112,'Reservation Approved','Reservation #175 approved by Super Admin.','2026-04-23 03:39:14','Diane Tayson (Super Admin)','Super Admin'),(919,112,'Signature Requested','Signature requested for Reservation #175 by Super Admin','2026-04-23 03:39:20','Diane Tayson (Super Admin)','Super Admin'),(920,112,'Lease Signed','Reservation #175','2026-04-23 03:39:27','Diane Tayson (Super Admin)','Super Admin'),(921,112,'Payment Submitted','Reservation #175 via Cash for: Utility Bill (2026-04-23) - Split 1/1','2026-04-23 03:41:34','Diane Tayson (Super Admin)','Super Admin'),(922,112,'Payment Rejected','Payment proof for #299 was rejected by Super Admin.','2026-04-23 03:42:45','Diane Tayson (Super Admin)','Super Admin'),(923,112,'Payment Submitted','Reservation #175 via Cash for: Utility Bill (2026-04-23) - Split 1/1','2026-04-23 03:42:56','Diane Tayson (Super Admin)','Super Admin'),(924,112,'Payment Confirmed','Payment #299 marked as Paid by Super Admin.','2026-04-23 03:43:03','Diane Tayson (Super Admin)','Super Admin'),(925,112,'Payment Submitted','Reservation #175 via Cash for: Utility Bill (2026-04-23) - Split 1/1','2026-04-23 03:48:46','Diane Tayson (Super Admin)','Super Admin'),(926,112,'Payment Rejected','Payment proof for #300 was rejected by Super Admin.','2026-04-23 03:49:05','Diane Tayson (Super Admin)','Super Admin'),(927,112,'Payment Submitted','Reservation #175 via Cash for: Utility Bill (2026-04-23) - Split 1/1','2026-04-23 03:49:15','Diane Tayson (Super Admin)','Super Admin'),(928,112,'Payment Confirmed','Payment #300 marked as Paid by Super Admin.','2026-04-23 03:49:19','Diane Tayson (Super Admin)','Super Admin'),(929,111,'Contract Ended','Reservation #170 marked as Completed by Super Admin.','2026-04-23 03:50:49','Diane Tayson (Super Admin)','Super Admin'),(930,111,'Reservation Submitted','Room: Single | Status: Pending','2026-04-23 03:51:24','Diane Tayson (Super Admin)','Super Admin'),(931,111,'Payment Submitted','Reservation #176 via Cash for: Security Deposit, First Month Rent','2026-04-23 03:51:33','Diane Tayson (Super Admin)','Super Admin'),(932,111,'Reservation Approved','Reservation #176 approved by Super Admin.','2026-04-23 03:51:50','Diane Tayson (Super Admin)','Super Admin'),(933,111,'Contract Ended','Reservation #176 marked as Completed by Super Admin.','2026-04-23 03:52:30','Diane Tayson (Super Admin)','Super Admin'),(934,111,'Reservation Submitted','Room: Single | Status: Pending','2026-04-23 03:52:57','Diane Tayson (Super Admin)','Super Admin'),(935,111,'Payment Submitted','Reservation #177 via Cash for: Security Deposit, First Month Rent','2026-04-23 03:53:02','Diane Tayson (Super Admin)','Super Admin'),(936,111,'Payment Submitted','Reservation #177 via GCash for: Security Deposit','2026-04-23 03:53:25','Diane Tayson (Super Admin)','Super Admin'),(937,111,'Payment Submitted','Reservation #177 via GCash for: First Month Rent, Security Deposit','2026-04-23 03:53:56','Diane Tayson (Super Admin)','Super Admin'),(938,111,'Reservation Approved','Reservation #177 approved by Super Admin.','2026-04-23 03:55:11','Diane Tayson (Super Admin)','Super Admin'),(939,111,'Signature Requested','Signature requested for Reservation #177 by Super Admin','2026-04-23 03:55:18','Diane Tayson (Super Admin)','Super Admin'),(940,111,'Lease Signed','Reservation #177','2026-04-23 03:55:23','Diane Tayson (Super Admin)','Super Admin'),(941,112,'Reminder Sent','Admin sent a balance reminder for ₱22,500.00.','2026-04-23 04:07:32','Diane Tayson (Super Admin)','Super Admin'),(942,112,'Reminder Sent','Admin sent a balance reminder for ₱22,500.00.','2026-04-23 04:08:00','Diane Tayson (Super Admin)','Super Admin'),(943,112,'Reminder Sent','Admin sent a balance reminder for ₱22,500.00.','2026-04-23 04:13:25','Diane Tayson (Super Admin)','Super Admin'),(944,112,'Reminder Sent','Admin sent a balance reminder for ₱22,500.00.','2026-04-23 04:13:29','Diane Tayson (Super Admin)','Super Admin'),(945,112,'Contract Ended','Reservation #175 marked as Completed by Super Admin.','2026-04-23 04:42:23','Diane Tayson (Super Admin)','Super Admin'),(946,112,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-23 04:52:10','Diane Tayson (Super Admin)','Super Admin'),(947,112,'Payment Submitted','Reservation #178 via Cash for: First Month Rent, Security Deposit','2026-04-23 04:56:21','Diane Tayson (Super Admin)','Super Admin'),(948,112,'Reservation Approved','Reservation #178 approved by Super Admin.','2026-04-23 05:04:54','Diane Tayson (Super Admin)','Super Admin'),(949,112,'Signature Requested','Signature requested for Reservation #178 by Super Admin','2026-04-23 05:05:00','Diane Tayson (Super Admin)','Super Admin'),(950,112,'Lease Signed','Reservation #178','2026-04-23 05:05:05','Diane Tayson (Super Admin)','Super Admin'),(951,113,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-23 13:03:31','Diane Tayson (Super Admin)','Super Admin'),(952,113,'Payment Submitted','Reservation #179 via Cash for: Security Deposit, First Month Rent','2026-04-23 13:08:39','Diane Tayson (Super Admin)','Super Admin'),(953,113,'Reservation Approved','Reservation #179 approved by Super Admin.','2026-04-23 13:09:08','Diane Tayson (Super Admin)','Super Admin'),(954,113,'Signature Requested','Signature requested for Reservation #179 by Super Admin','2026-04-23 13:09:14','Diane Tayson (Super Admin)','Super Admin'),(955,113,'Lease Signed','Reservation #179','2026-04-23 13:09:21','Diane Tayson (Super Admin)','Super Admin'),(956,113,'Contract Ended','Reservation #179 marked as Completed by Super Admin.','2026-04-23 13:09:27','Diane Tayson (Super Admin)','Super Admin'),(957,113,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-23 13:10:43','Diane Tayson (Super Admin)','Super Admin'),(958,112,'Payment Submitted','Reservation #178 via Cash for: Utility Bill (2026-04-23) - Split 1/1','2026-04-23 13:13:35','Diane Tayson (Super Admin)','Super Admin'),(959,112,'Payment Confirmed','Payment #316 marked as Paid by Super Admin.','2026-04-23 13:13:45','Diane Tayson (Super Admin)','Super Admin'),(960,112,'Payment Submitted','Reservation #178 via Cash for: Utility Bill (2026-04-23) - Split 1/1','2026-04-23 13:14:51','Diane Tayson (Super Admin)','Super Admin'),(961,112,'Payment Confirmed','Payment #317 marked as Paid by Super Admin.','2026-04-23 13:15:05','Diane Tayson (Super Admin)','Super Admin'),(962,113,'Reservation Rejected','Reservation #180 cancelled by Super Admin.','2026-04-23 13:15:32','Diane Tayson (Super Admin)','Super Admin'),(963,112,'Maintenance Update','Request #5 updated to \'Completed\' by Super Admin','2026-04-23 13:25:15','Diane Tayson (Super Admin)','Super Admin'),(964,112,'Housekeeping Scheduled','Admin Super Admin scheduled cleaning for 2026-04-23','2026-04-23 13:53:34','Diane Tayson (Super Admin)','Super Admin'),(965,112,'Housekeeping Scheduled','Admin Super Admin scheduled cleaning for 2026-04-23','2026-04-23 13:53:37','Diane Tayson (Super Admin)','Super Admin'),(966,112,'Housekeeping Update','Request #20 updated to \'Cancelled\' by Super Admin','2026-04-23 13:53:46','Diane Tayson (Super Admin)','Super Admin'),(967,114,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-23 15:30:00','Diane Tayson (Super Admin)','Super Admin'),(968,114,'Payment Submitted','Reservation #181 via Cash for: First Month Rent, Security Deposit','2026-04-23 15:30:17','Diane Tayson (Super Admin)','Super Admin'),(969,114,'Reservation Approved','Reservation #181 approved by Super Admin.','2026-04-23 15:30:31','Diane Tayson (Super Admin)','Super Admin'),(970,114,'Signature Requested','Signature requested for Reservation #181 by Super Admin','2026-04-23 15:30:38','Diane Tayson (Super Admin)','Super Admin'),(971,114,'Lease Signed','Reservation #181','2026-04-23 15:31:55','Diane Tayson (Super Admin)','Super Admin'),(972,114,'Contract Ended','Reservation #181 marked as Completed by Super Admin.','2026-04-23 16:24:18','Diane Tayson (Super Admin)','Super Admin'),(973,115,'Restored from Companion','User was previously a companion of John Benedict Rasing.','2026-04-23 17:01:32','Diane Tayson (Super Admin)','Super Admin'),(974,116,'Restored from Companion','User was previously a companion of John Benedict Rasing.','2026-04-23 17:04:08','Diane Tayson (Super Admin)','Super Admin'),(975,117,'Restored from Companion','User was previously a companion of John Benedict Rasing.','2026-04-23 17:15:06','Diane Tayson (Super Admin)','Super Admin'),(976,112,'Contract Ended','Reservation #178 marked as Completed by Super Admin.','2026-04-24 14:42:52','Diane Tayson (Super Admin)','Super Admin'),(977,119,'Reservation Submitted','Room: Single | Status: Pending','2026-04-24 14:53:49','Diane Tayson (Super Admin)','Super Admin'),(978,112,'Reservation Submitted','Room: Single | Status: Pending','2026-04-24 15:04:01','Diane Tayson (Super Admin)','Super Admin'),(979,112,'Payment Submitted','Reservation #183 via Cash for: First Month Rent, Security Deposit','2026-04-24 15:04:19','Diane Tayson (Super Admin)','Super Admin'),(980,112,'Reservation Approved','Reservation #183 approved by Super Admin.','2026-04-24 15:04:31','Diane Tayson (Super Admin)','Super Admin'),(981,112,'Signature Requested','Signature requested for Reservation #183 by Super Admin','2026-04-24 15:04:36','Diane Tayson (Super Admin)','Super Admin'),(982,112,'Lease Signed','Reservation #183','2026-04-24 15:04:41','Diane Tayson (Super Admin)','Super Admin'),(983,112,'Contract Ended','Reservation #183 marked as Completed by Super Admin.','2026-04-24 15:04:47','Diane Tayson (Super Admin)','Super Admin'),(984,112,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-04-24 15:05:01','Diane Tayson (Super Admin)','Super Admin'),(985,112,'Payment Submitted','Reservation #184 via Cash for: First Month Rent, Security Deposit','2026-04-24 15:05:04','Diane Tayson (Super Admin)','Super Admin'),(986,112,'Reservation Approved','Reservation #184 approved by Super Admin.','2026-04-24 15:05:16','Diane Tayson (Super Admin)','Super Admin'),(987,112,'Signature Requested','Signature requested for Reservation #184 by Super Admin','2026-04-24 15:05:20','Diane Tayson (Super Admin)','Super Admin'),(988,112,'Lease Signed','Reservation #184','2026-04-24 15:05:24','Diane Tayson (Super Admin)','Super Admin'),(989,120,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-24 15:07:46','Diane Tayson (Super Admin)','Super Admin');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'Super Admin','super123','Super Admin','Diane','Tayson','dianetyson@gmail.com','09987345621','admin_1_1773634055.jpg'),(6,'Stephen Squad PH','stephensquadph03','Super Admin','Stephen','Begosa','stephenbegosa@gmail.com','09662285702',NULL),(7,'admin1','admin123','Admin','Stephen','Squad','stephen@gmail.com','09874612545',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `housekeeping_requests`
--

LOCK TABLES `housekeeping_requests` WRITE;
/*!40000 ALTER TABLE `housekeeping_requests` DISABLE KEYS */;
INSERT INTO `housekeeping_requests` VALUES (13,40,7,'Weekly Routine Cleaning','Cancelled','2026-03-28','2026-03-08 14:31:21',0.00),(20,112,40,'Routine Cleaning (Admin Scheduled)','Cancelled','2026-04-23','2026-04-23 13:53:34',400.00),(21,112,40,'Routine Cleaning (Admin Scheduled)','Scheduled','2026-04-23','2026-04-23 13:53:37',0.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=221 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_items`
--

LOCK TABLES `inventory_items` WRITE;
/*!40000 ALTER TABLE `inventory_items` DISABLE KEYS */;
INSERT INTO `inventory_items` VALUES (1,'Beds','Furniture',6,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(2,'Bed Sheets','Linens',6,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(3,'Pillows','Linens',6,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(4,'Pillow Cases','Linens',6,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(5,'Beds','Furniture',7,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(6,'Bed Sheets','Linens',7,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(7,'Pillows','Linens',7,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(8,'Pillow Cases','Linens',7,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(9,'Beds','Furniture',20,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(10,'Bed Sheets','Linens',20,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(11,'Pillows','Linens',20,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(12,'Pillow Cases','Linens',20,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(13,'Beds','Furniture',21,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(14,'Bed Sheets','Linens',21,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(15,'Pillows','Linens',21,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(16,'Pillow Cases','Linens',21,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(17,'Beds','Furniture',22,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(18,'Bed Sheets','Linens',22,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(19,'Pillows','Linens',22,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(20,'Pillow Cases','Linens',22,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(21,'Beds','Furniture',23,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(22,'Bed Sheets','Linens',23,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(23,'Pillows','Linens',23,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(24,'Pillow Cases','Linens',23,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(25,'Beds','Furniture',24,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(26,'Bed Sheets','Linens',24,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(27,'Pillows','Linens',24,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(28,'Pillow Cases','Linens',24,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(29,'Beds','Furniture',25,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(30,'Bed Sheets','Linens',25,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(31,'Pillows','Linens',25,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(32,'Pillow Cases','Linens',25,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(33,'Beds','Furniture',26,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(34,'Bed Sheets','Linens',26,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(35,'Pillows','Linens',26,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(36,'Pillow Cases','Linens',26,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(37,'Beds','Furniture',27,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(38,'Bed Sheets','Linens',27,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(39,'Pillows','Linens',27,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(40,'Pillow Cases','Linens',27,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(41,'Beds','Furniture',28,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(42,'Bed Sheets','Linens',28,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(43,'Pillows','Linens',28,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(44,'Pillow Cases','Linens',28,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(45,'Beds','Furniture',29,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(46,'Bed Sheets','Linens',29,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(47,'Pillows','Linens',29,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(48,'Pillow Cases','Linens',29,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(49,'Beds','Furniture',30,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(50,'Bed Sheets','Linens',30,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(51,'Pillows','Linens',30,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(52,'Pillow Cases','Linens',30,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(53,'Beds','Furniture',31,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(54,'Bed Sheets','Linens',31,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(55,'Pillows','Linens',31,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(56,'Pillow Cases','Linens',31,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(57,'Beds','Furniture',32,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(58,'Bed Sheets','Linens',32,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(59,'Pillows','Linens',32,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(60,'Pillow Cases','Linens',32,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(61,'Beds','Furniture',34,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(62,'Bed Sheets','Linens',34,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(63,'Pillows','Linens',34,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(64,'Pillow Cases','Linens',34,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(65,'Beds','Furniture',35,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(66,'Bed Sheets','Linens',35,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(67,'Pillows','Linens',35,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(68,'Pillow Cases','Linens',35,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(69,'Beds','Furniture',36,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(70,'Bed Sheets','Linens',36,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(71,'Pillows','Linens',36,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(72,'Pillow Cases','Linens',36,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(73,'Beds','Furniture',37,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(74,'Bed Sheets','Linens',37,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(75,'Pillows','Linens',37,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(76,'Pillow Cases','Linens',37,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(77,'Beds','Furniture',38,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(78,'Bed Sheets','Linens',38,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(79,'Pillows','Linens',38,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(80,'Pillow Cases','Linens',38,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(81,'Beds','Furniture',39,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(82,'Bed Sheets','Linens',39,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(83,'Pillows','Linens',39,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(84,'Pillow Cases','Linens',39,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(85,'Beds','Furniture',40,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(86,'Bed Sheets','Linens',40,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(87,'Pillows','Linens',40,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(88,'Pillow Cases','Linens',40,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(89,'Beds','Furniture',41,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(90,'Bed Sheets','Linens',41,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(91,'Pillows','Linens',41,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(92,'Pillow Cases','Linens',41,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(93,'Beds','Furniture',42,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(94,'Bed Sheets','Linens',42,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(95,'Pillows','Linens',42,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(96,'Pillow Cases','Linens',42,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(97,'Beds','Furniture',43,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(98,'Bed Sheets','Linens',43,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(99,'Pillows','Linens',43,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(100,'Pillow Cases','Linens',43,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(101,'Beds','Furniture',44,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(102,'Bed Sheets','Linens',44,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(103,'Pillows','Linens',44,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(104,'Pillow Cases','Linens',44,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(105,'Beds','Furniture',45,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(106,'Bed Sheets','Linens',45,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(107,'Pillows','Linens',45,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(108,'Pillow Cases','Linens',45,1,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(109,'Beds','Furniture',46,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(110,'Bed Sheets','Linens',46,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(111,'Pillows','Linens',46,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(112,'Pillow Cases','Linens',46,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(113,'Beds','Furniture',47,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(114,'Bed Sheets','Linens',47,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(115,'Pillows','Linens',47,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(116,'Pillow Cases','Linens',47,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(117,'Beds','Furniture',48,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(118,'Bed Sheets','Linens',48,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(119,'Pillows','Linens',48,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(120,'Pillow Cases','Linens',48,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(121,'Beds','Furniture',49,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(122,'Bed Sheets','Linens',49,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(123,'Pillows','Linens',49,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(124,'Pillow Cases','Linens',49,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(125,'Beds','Furniture',50,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(126,'Bed Sheets','Linens',50,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(127,'Pillows','Linens',50,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(128,'Pillow Cases','Linens',50,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(129,'Beds','Furniture',51,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(130,'Bed Sheets','Linens',51,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(131,'Pillows','Linens',51,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(132,'Pillow Cases','Linens',51,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(133,'Beds','Furniture',52,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(134,'Bed Sheets','Linens',52,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(135,'Pillows','Linens',52,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(136,'Pillow Cases','Linens',52,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(137,'Beds','Furniture',53,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(138,'Bed Sheets','Linens',53,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(139,'Pillows','Linens',53,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(140,'Pillow Cases','Linens',53,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(141,'Beds','Furniture',54,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(142,'Bed Sheets','Linens',54,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(143,'Pillows','Linens',54,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(144,'Pillow Cases','Linens',54,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(145,'Beds','Furniture',55,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(146,'Bed Sheets','Linens',55,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(147,'Pillows','Linens',55,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(148,'Pillow Cases','Linens',55,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(149,'Beds','Furniture',56,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(150,'Bed Sheets','Linens',56,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(151,'Pillows','Linens',56,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(152,'Pillow Cases','Linens',56,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(153,'Beds','Furniture',57,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(154,'Bed Sheets','Linens',57,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(155,'Pillows','Linens',57,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(156,'Pillow Cases','Linens',57,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(157,'Beds','Furniture',58,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(158,'Bed Sheets','Linens',58,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(159,'Pillows','Linens',58,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(160,'Pillow Cases','Linens',58,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(161,'Beds','Furniture',59,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(162,'Bed Sheets','Linens',59,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(163,'Pillows','Linens',59,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(164,'Pillow Cases','Linens',59,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(165,'Beds','Furniture',60,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(166,'Bed Sheets','Linens',60,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(167,'Pillows','Linens',60,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(168,'Pillow Cases','Linens',60,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(169,'Beds','Furniture',61,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(170,'Bed Sheets','Linens',61,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(171,'Pillows','Linens',61,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(172,'Pillow Cases','Linens',61,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(173,'Beds','Furniture',62,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(174,'Bed Sheets','Linens',62,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(175,'Pillows','Linens',62,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(176,'Pillow Cases','Linens',62,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(177,'Beds','Furniture',63,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(178,'Bed Sheets','Linens',63,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(179,'Pillows','Linens',63,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(180,'Pillow Cases','Linens',63,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(181,'Beds','Furniture',64,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(182,'Bed Sheets','Linens',64,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(183,'Pillows','Linens',64,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(184,'Pillow Cases','Linens',64,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(185,'Beds','Furniture',65,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(186,'Bed Sheets','Linens',65,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(187,'Pillows','Linens',65,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(188,'Pillow Cases','Linens',65,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(189,'Beds','Furniture',66,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(190,'Bed Sheets','Linens',66,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(191,'Pillows','Linens',66,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(192,'Pillow Cases','Linens',66,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(193,'Beds','Furniture',67,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(194,'Bed Sheets','Linens',67,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(195,'Pillows','Linens',67,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(196,'Pillow Cases','Linens',67,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(197,'Beds','Furniture',68,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(198,'Bed Sheets','Linens',68,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(199,'Pillows','Linens',68,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(200,'Pillow Cases','Linens',68,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(201,'Beds','Furniture',69,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(202,'Bed Sheets','Linens',69,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(203,'Pillows','Linens',69,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(204,'Pillow Cases','Linens',69,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(205,'Beds','Furniture',70,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(206,'Bed Sheets','Linens',70,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(207,'Pillows','Linens',70,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(208,'Pillow Cases','Linens',70,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(209,'Beds','Furniture',71,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(210,'Bed Sheets','Linens',71,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(211,'Pillows','Linens',71,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(212,'Pillow Cases','Linens',71,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(213,'Beds','Furniture',74,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(214,'Bed Sheets','Linens',74,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(215,'Pillows','Linens',74,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(216,'Pillow Cases','Linens',74,4,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(217,'Beds','Furniture',75,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(218,'Bed Sheets','Linens',75,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(219,'Pillows','Linens',75,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48'),(220,'Pillow Cases','Linens',75,6,'Good',NULL,0.00,NULL,'2026-04-10 23:34:48');
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `key_transactions`
--

LOCK TABLES `key_transactions` WRITE;
/*!40000 ALTER TABLE `key_transactions` DISABLE KEYS */;
INSERT INTO `key_transactions` VALUES (12,15,40,'2026-03-14 04:13:06','2026-03-14 04:13:14','Returned',''),(16,15,40,'2026-04-22 00:04:34','2026-04-23 02:17:35','Returned','');
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
INSERT INTO `keys` VALUES (9,'Room 202 Key','Room',6,'Available'),(10,'Room 302 Key','Room',7,'Available'),(11,'Room 203 Key','Room',16,'Available'),(12,'Room 204 Key','Room',17,'Available'),(13,'Room 303 Key','Room',18,'Available'),(14,'Room 203 Key','Room',19,'Available'),(15,'Room 401 Key','Room',20,'Available'),(16,'Room 501 Key','Room',21,'Available'),(17,'Room 601 Key','Room',22,'Available'),(18,'Room 701 Key','Room',23,'Available'),(19,'Room 402 Key','Room',24,'Available'),(20,'Room 502 Key','Room',25,'Available'),(21,'Room 602 Key','Room',26,'Available'),(22,'Room 702 Key','Room',27,'Available'),(23,'Room 403 Key','Room',28,'Available'),(24,'Room 503 Key','Room',29,'Available'),(25,'Room 603 Key','Room',30,'Available'),(26,'Room 703 Key','Room',31,'Available'),(27,'Room 204 Key','Room',32,'Available'),(29,'Room 206 Key','Room',34,'Available'),(30,'Room 207 Key','Room',35,'Available'),(31,'Room 208 Key','Room',36,'Available'),(32,'Room 209 Key','Room',37,'Available'),(33,'Room 308 Key','Room',38,'Available'),(34,'Room 309 Key','Room',39,'Available'),(35,'Room 304 Key','Room',40,'Available'),(36,'Room 305 Key','Room',41,'Available'),(37,'Room 306 Key','Room',42,'Available'),(38,'Room 307 Key','Room',43,'Available'),(39,'Room 205 Key','Room',44,'Available'),(41,'Room 404 Key','Room',46,'Available'),(42,'Room 405 Key','Room',47,'Available'),(43,'Room 406 Key','Room',48,'Available'),(44,'Room 407 Key','Room',49,'Available'),(45,'Room 408 Key','Room',50,'Available'),(46,'Room 409 Key','Room',51,'Available'),(47,'Room 302 Key','Room',52,'Available'),(48,'Room 508 Key','Room',53,'Available'),(49,'Room 509 Key','Room',54,'Available'),(50,'Room 608 Key','Room',55,'Available'),(51,'Room 609 Key','Room',56,'Available'),(52,'Room 708 Key','Room',57,'Available'),(54,'Room 203 Key','Room',59,'Available'),(55,'Room 504 Key','Room',60,'Available'),(56,'Room 505 Key','Room',61,'Available'),(57,'Room 506 Key','Room',62,'Available'),(58,'Room 507 Key','Room',63,'Available'),(59,'Room 604 Key','Room',64,'Available'),(60,'Room 605 Key','Room',65,'Available'),(61,'Room 606 Key','Room',66,'Available'),(62,'Room 607 Key','Room',67,'Available'),(63,'Room 704 Key','Room',68,'Available'),(64,'Room 705 Key','Room',69,'Available'),(67,'Room 201 Key','Room',45,'Available'),(68,'Room 709 Key','Room',58,'Available'),(69,'Room 706 Key','Room',70,'Available'),(70,'Room 707 Key','Room',71,'Available'),(73,'Room 202 Key #2','Room',6,'Available'),(74,'Room 202 Key #3','Room',6,'Available'),(75,'Room 202 Key #4','Room',6,'Available'),(76,'Room 202 Key #5','Room',6,'Available'),(77,'Room 202 Key #6','Room',6,'Available'),(78,'Room 303 Key #2','Room',7,'Available'),(79,'Room 303 Key #3','Room',7,'Available'),(80,'Room 303 Key #4','Room',7,'Available'),(81,'Room 402 Key #2','Room',24,'Available'),(82,'Room 402 Key #3','Room',24,'Available'),(83,'Room 402 Key #4','Room',24,'Available'),(84,'Room 402 Key #5','Room',24,'Available'),(85,'Room 402 Key #6','Room',24,'Available'),(86,'Room 502 Key #2','Room',25,'Available'),(87,'Room 502 Key #3','Room',25,'Available'),(88,'Room 502 Key #4','Room',25,'Available'),(89,'Room 502 Key #5','Room',25,'Available'),(90,'Room 502 Key #6','Room',25,'Available'),(91,'Room 602 Key #2','Room',26,'Available'),(92,'Room 602 Key #3','Room',26,'Available'),(93,'Room 602 Key #4','Room',26,'Available'),(94,'Room 602 Key #5','Room',26,'Available'),(95,'Room 602 Key #6','Room',26,'Available'),(96,'Room 702 Key #2','Room',27,'Available'),(97,'Room 702 Key #3','Room',27,'Available'),(98,'Room 702 Key #4','Room',27,'Available'),(99,'Room 702 Key #5','Room',27,'Available'),(100,'Room 702 Key #6','Room',27,'Available'),(101,'Room 403 Key #2','Room',28,'Available'),(102,'Room 403 Key #3','Room',28,'Available'),(103,'Room 403 Key #4','Room',28,'Available'),(104,'Room 503 Key #2','Room',29,'Available'),(105,'Room 503 Key #3','Room',29,'Available'),(106,'Room 503 Key #4','Room',29,'Available'),(107,'Room 603 Key #2','Room',30,'Available'),(108,'Room 603 Key #3','Room',30,'Available'),(109,'Room 603 Key #4','Room',30,'Available'),(110,'Room 703 Key #2','Room',31,'Available'),(111,'Room 703 Key #3','Room',31,'Available'),(112,'Room 703 Key #4','Room',31,'Available'),(113,'Room 204 Key #2','Room',32,'Available'),(114,'Room 204 Key #3','Room',32,'Available'),(115,'Room 204 Key #4','Room',32,'Available'),(116,'Room 206 Key #2','Room',34,'Available'),(117,'Room 206 Key #3','Room',34,'Available'),(118,'Room 206 Key #4','Room',34,'Available'),(119,'Room 207 Key #2','Room',35,'Available'),(120,'Room 207 Key #3','Room',35,'Available'),(121,'Room 207 Key #4','Room',35,'Available'),(122,'Room 208 Key #2','Room',36,'Available'),(123,'Room 208 Key #3','Room',36,'Available'),(124,'Room 208 Key #4','Room',36,'Available'),(125,'Room 208 Key #5','Room',36,'Available'),(126,'Room 208 Key #6','Room',36,'Available'),(127,'Room 209 Key #2','Room',37,'Available'),(128,'Room 209 Key #3','Room',37,'Available'),(129,'Room 209 Key #4','Room',37,'Available'),(130,'Room 209 Key #5','Room',37,'Available'),(131,'Room 209 Key #6','Room',37,'Available'),(132,'Room 308 Key #2','Room',38,'Available'),(133,'Room 308 Key #3','Room',38,'Available'),(134,'Room 308 Key #4','Room',38,'Available'),(135,'Room 308 Key #5','Room',38,'Available'),(136,'Room 308 Key #6','Room',38,'Available'),(137,'Room 309 Key #2','Room',39,'Available'),(138,'Room 309 Key #3','Room',39,'Available'),(139,'Room 309 Key #4','Room',39,'Available'),(140,'Room 309 Key #5','Room',39,'Available'),(141,'Room 309 Key #6','Room',39,'Available'),(142,'Room 304 Key #2','Room',40,'Available'),(143,'Room 304 Key #3','Room',40,'Available'),(144,'Room 304 Key #4','Room',40,'Available'),(145,'Room 305 Key #2','Room',41,'Available'),(146,'Room 305 Key #3','Room',41,'Available'),(147,'Room 305 Key #4','Room',41,'Available'),(148,'Room 306 Key #2','Room',42,'Available'),(149,'Room 306 Key #3','Room',42,'Available'),(150,'Room 306 Key #4','Room',42,'Available'),(151,'Room 307 Key #2','Room',43,'Available'),(152,'Room 307 Key #3','Room',43,'Available'),(153,'Room 307 Key #4','Room',43,'Available'),(154,'Room 205 Key #2','Room',44,'Available'),(155,'Room 205 Key #3','Room',44,'Available'),(156,'Room 205 Key #4','Room',44,'Available'),(157,'Room 404 Key #2','Room',46,'Available'),(158,'Room 404 Key #3','Room',46,'Available'),(159,'Room 404 Key #4','Room',46,'Available'),(160,'Room 405 Key #2','Room',47,'Available'),(161,'Room 405 Key #3','Room',47,'Available'),(162,'Room 405 Key #4','Room',47,'Available'),(163,'Room 406 Key #2','Room',48,'Available'),(164,'Room 406 Key #3','Room',48,'Available'),(165,'Room 406 Key #4','Room',48,'Available'),(166,'Room 407 Key #2','Room',49,'Available'),(167,'Room 407 Key #3','Room',49,'Available'),(168,'Room 407 Key #4','Room',49,'Available'),(169,'Room 408 Key #2','Room',50,'Available'),(170,'Room 408 Key #3','Room',50,'Available'),(171,'Room 408 Key #4','Room',50,'Available'),(172,'Room 408 Key #5','Room',50,'Available'),(173,'Room 408 Key #6','Room',50,'Available'),(174,'Room 409 Key #2','Room',51,'Available'),(175,'Room 409 Key #3','Room',51,'Available'),(176,'Room 409 Key #4','Room',51,'Available'),(177,'Room 409 Key #5','Room',51,'Available'),(178,'Room 409 Key #6','Room',51,'Available'),(179,'Room 302 Key #2','Room',52,'Available'),(180,'Room 302 Key #3','Room',52,'Available'),(181,'Room 302 Key #4','Room',52,'Available'),(182,'Room 302 Key #5','Room',52,'Available'),(183,'Room 302 Key #6','Room',52,'Available'),(184,'Room 508 Key #2','Room',53,'Available'),(185,'Room 508 Key #3','Room',53,'Available'),(186,'Room 508 Key #4','Room',53,'Available'),(187,'Room 508 Key #5','Room',53,'Available'),(188,'Room 508 Key #6','Room',53,'Available'),(189,'Room 509 Key #2','Room',54,'Available'),(190,'Room 509 Key #3','Room',54,'Available'),(191,'Room 509 Key #4','Room',54,'Available'),(192,'Room 509 Key #5','Room',54,'Available'),(193,'Room 509 Key #6','Room',54,'Available'),(194,'Room 608 Key #2','Room',55,'Available'),(195,'Room 608 Key #3','Room',55,'Available'),(196,'Room 608 Key #4','Room',55,'Available'),(197,'Room 608 Key #5','Room',55,'Available'),(198,'Room 608 Key #6','Room',55,'Available'),(199,'Room 609 Key #2','Room',56,'Available'),(200,'Room 609 Key #3','Room',56,'Available'),(201,'Room 609 Key #4','Room',56,'Available'),(202,'Room 609 Key #5','Room',56,'Available'),(203,'Room 609 Key #6','Room',56,'Available'),(204,'Room 708 Key #2','Room',57,'Available'),(205,'Room 708 Key #3','Room',57,'Available'),(206,'Room 708 Key #4','Room',57,'Available'),(207,'Room 708 Key #5','Room',57,'Available'),(208,'Room 708 Key #6','Room',57,'Available'),(209,'Room 709 Key #2','Room',58,'Available'),(210,'Room 709 Key #3','Room',58,'Available'),(211,'Room 709 Key #4','Room',58,'Available'),(212,'Room 709 Key #5','Room',58,'Available'),(213,'Room 709 Key #6','Room',58,'Available'),(214,'Room 203 Key #2','Room',59,'Available'),(215,'Room 203 Key #3','Room',59,'Available'),(216,'Room 203 Key #4','Room',59,'Available'),(217,'Room 504 Key #2','Room',60,'Available'),(218,'Room 504 Key #3','Room',60,'Available'),(219,'Room 504 Key #4','Room',60,'Available'),(220,'Room 505 Key #2','Room',61,'Available'),(221,'Room 505 Key #3','Room',61,'Available'),(222,'Room 505 Key #4','Room',61,'Available'),(223,'Room 506 Key #2','Room',62,'Available'),(224,'Room 506 Key #3','Room',62,'Available'),(225,'Room 506 Key #4','Room',62,'Available'),(226,'Room 507 Key #2','Room',63,'Available'),(227,'Room 507 Key #3','Room',63,'Available'),(228,'Room 507 Key #4','Room',63,'Available'),(229,'Room 604 Key #2','Room',64,'Available'),(230,'Room 604 Key #3','Room',64,'Available'),(231,'Room 604 Key #4','Room',64,'Available'),(232,'Room 605 Key #2','Room',65,'Available'),(233,'Room 605 Key #3','Room',65,'Available'),(234,'Room 605 Key #4','Room',65,'Available'),(235,'Room 606 Key #2','Room',66,'Available'),(236,'Room 606 Key #3','Room',66,'Available'),(237,'Room 606 Key #4','Room',66,'Available'),(238,'Room 607 Key #2','Room',67,'Available'),(239,'Room 607 Key #3','Room',67,'Available'),(240,'Room 607 Key #4','Room',67,'Available'),(241,'Room 704 Key #2','Room',68,'Available'),(242,'Room 704 Key #3','Room',68,'Available'),(243,'Room 704 Key #4','Room',68,'Available'),(244,'Room 705 Key #2','Room',69,'Available'),(245,'Room 705 Key #3','Room',69,'Available'),(246,'Room 705 Key #4','Room',69,'Available'),(247,'Room 706 Key #2','Room',70,'Available'),(248,'Room 706 Key #3','Room',70,'Available'),(249,'Room 706 Key #4','Room',70,'Available'),(250,'Room 707 Key #2','Room',71,'Available'),(251,'Room 707 Key #3','Room',71,'Available'),(252,'Room 707 Key #4','Room',71,'Available'),(261,'Room 205 Key #1','Room',74,'Available'),(262,'Room 205 Key #2','Room',74,'Available'),(263,'Room 205 Key #3','Room',74,'Available'),(264,'Room 205 Key #4','Room',74,'Available'),(265,'Room Room 302 Key #1','Room',75,'Available'),(266,'Room Room 302 Key #2','Room',75,'Available'),(267,'Room Room 302 Key #3','Room',75,'Available'),(268,'Room Room 302 Key #4','Room',75,'Available'),(269,'Room Room 302 Key #5','Room',75,'Available'),(270,'Room Room 302 Key #6','Room',75,'Available');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_requests`
--

LOCK TABLES `maintenance_requests` WRITE;
/*!40000 ALTER TABLE `maintenance_requests` DISABLE KEYS */;
INSERT INTO `maintenance_requests` VALUES (5,112,40,'AC repair','Completed','2026-04-23','2026-04-23 13:23:59',400.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=1397 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (504,'40','2026-02-26 09:57:57','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(513,'40','2026-02-26 21:31:42','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(526,'40','2026-02-27 15:28:35','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(532,'40','2026-02-28 21:46:01','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(536,'40','2026-02-28 21:54:57','1','✅ <strong>Payment Confirmed</strong><br>Your payment #55 has been verified and marked as Paid.','Payment Update'),(707,'40','2026-03-03 13:28:50','1','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Car Slot 1. A fee of ₱600.00 has been added to your account.','Parking'),(708,'40','2026-03-03 14:19:47','1','🔑 <strong>Key Assigned</strong><br>You have been assigned a key. Please keep it safe.','Key System'),(709,'40','2026-03-03 14:20:14','1','🔑 <strong>Key Returned</strong><br>Key has been marked as returned.','Key System'),(710,'40','2026-03-03 14:21:51','1','🔑 <strong>Key Assigned</strong><br>You have been assigned a key. Please keep it safe.','Key System'),(711,'40','2026-03-03 14:21:58','1','🔑 <strong>Key Returned</strong><br>Key has been marked as returned.','Key System'),(713,'40','2026-03-05 18:01:58','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(726,'40','2026-03-05 22:01:59','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(737,'40','2026-03-06 02:01:59','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(740,'40','2026-03-06 11:25:54','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(743,'40','2026-03-06 15:25:55','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(748,'40','2026-03-06 23:34:31','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(751,'40','2026-03-07 12:58:52','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(754,'40','2026-03-07 17:21:42','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(768,'40','2026-03-07 21:22:35','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(771,'40','2026-03-08 13:05:01','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(774,'40','2026-03-08 21:41:45','1','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱30.00 has been applied to your account due to overdue payment.','Billing Alert'),(791,'40','2026-03-08 22:16:52','1','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #1 has been marked as completed.','Parking'),(800,'40','2026-03-08 22:31:21','1','🧹 <strong>Weekly Cleaning</strong><br>Routine housekeeping scheduled for Mar 28, 2026.','Housekeeping'),(821,'40','2026-03-09 04:19:32','1','✅ <strong>Payment Confirmed</strong><br>Your payment #103 has been verified and marked as Paid.','Payment Update'),(822,'40','2026-03-09 04:19:38','1','✅ <strong>Payment Confirmed</strong><br>Your payment #110 has been verified and marked as Paid.','Payment Update'),(823,'40','2026-03-09 04:19:49','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #51. Please go to My Reservations to sign.','Action Required'),(824,'40','2026-03-09 04:20:41','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>203</strong>.','System'),(838,'40','2026-03-09 20:49:30','1','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Car Slot 1. A fee of ₱600.00 has been added to your account.','Parking'),(839,'40','2026-03-09 20:49:36','1','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #11 has been marked as completed.','Parking'),(840,'40','2026-03-09 22:06:31','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>201</strong>.','System'),(841,'40','2026-03-10 00:28:34','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>203</strong>.','System'),(842,'40','2026-03-10 01:01:47','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>201</strong>.','System'),(843,'40','2026-03-10 03:02:19','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>1 Bed</strong>.','System'),(844,'40','2026-03-10 11:48:49','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 09, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(852,'40','2026-03-10 17:55:44','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 09, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(859,'40','2026-03-12 21:24:55','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 09, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(875,'40','2026-03-13 00:39:27','1','✅ <strong>Payment Confirmed</strong><br>Your payment #123 has been verified and marked as Paid.','Payment Update'),(938,'40','2026-03-14 04:13:06','1','🔑 <strong>Key Assigned</strong><br>You have been assigned a key (ID: 15). Please keep it safe.','Key System'),(939,'40','2026-03-14 04:13:14','1','🔑 <strong>Key Returned</strong><br>Key (ID: 15) has been marked as returned.','Key System'),(1032,'40','2026-03-18 02:00:14','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #51. Please go to My Reservations to sign.','Action Required'),(1043,'40','2026-03-18 11:32:20','1','🧾 <strong>New Utility Bill</strong><br>A utility bill of ₱6.24 has been generated for your room.','Billing'),(1044,'40','2026-03-18 11:32:22','1','🧾 <strong>New Utility Bill</strong><br>A utility bill of ₱6.24 has been generated for your room.','Billing'),(1045,'40','2026-03-18 11:32:24','1','🧾 <strong>New Utility Bill</strong><br>A utility bill of ₱6.24 has been generated for your room.','Billing'),(1055,'40','2026-03-21 16:10:01','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱6.24 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1058,'40','2026-03-21 20:28:16','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱6.24 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1060,'40','2026-03-23 01:04:31','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱6.24 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1062,'40','2026-03-23 11:57:46','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱6.24 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1065,'40','2026-03-23 19:55:46','1','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱0.31 has been applied to your account due to overdue payment.','Billing Alert'),(1066,'40','2026-03-23 19:55:48','1','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱0.31 has been applied to your account due to overdue payment.','Billing Alert'),(1067,'40','2026-03-23 19:55:50','1','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱0.31 has been applied to your account due to overdue payment.','Billing Alert'),(1282,'40','2026-04-22 00:04:34','','🔑 <strong>Key Assigned</strong><br>You have been assigned a key (ID: 15). Please keep it safe.','Key System'),(1330,'40','2026-04-23 01:14:39','','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #51 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1331,'111','2026-04-23 01:28:57','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #170. Please go to My Reservations to sign.','Action Required'),(1332,'40','2026-04-23 02:17:35','','🔑 <strong>Key Returned</strong><br>Key (ID: 15) has been marked as returned.','Key System'),(1333,'112','2026-04-23 10:47:55','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1334,'112','2026-04-23 10:52:12','1','❌ <strong>Reservation Rejected</strong><br>Your booking #171 has been cancelled. Please contact support for details.','Booking Rejected'),(1335,'112','2026-04-23 10:59:16','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1336,'112','2026-04-23 11:03:09','1','❌ <strong>Reservation Rejected</strong><br>Your booking #172 has been cancelled. Please contact support for details.','Booking Rejected'),(1337,'112','2026-04-23 11:03:29','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1338,'112','2026-04-23 11:14:17','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1339,'112','2026-04-23 11:15:56','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>1 Bed</strong>.','System'),(1340,'112','2026-04-23 11:17:06','1','🏠 <strong>Room Returned</strong><br>You have been returned to your previous room: <strong>4 Beds</strong>.','System'),(1341,'112','2026-04-23 11:19:30','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1342,'112','2026-04-23 11:21:54','1','🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.','Extension Approved'),(1343,'112','2026-04-23 11:23:50','1','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Motorcycle Slot 1. A fee of ₱600.00 has been added to your account.','Parking'),(1344,'112','2026-04-23 11:23:52','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Apr 23, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1345,'112','2026-04-23 11:36:28','1','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #173 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1346,'112','2026-04-23 11:37:12','1','❌ <strong>Deletion Request Rejected</strong><br>Your request to delete your account has been rejected by the admin.','System'),(1347,'112','2026-04-23 11:38:17','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1348,'112','2026-04-23 11:39:14','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1349,'112','2026-04-23 11:39:18','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #175. Please go to My Reservations to sign.','Action Required'),(1350,'112','2026-04-23 11:41:17','1','🧾 <strong>New Utility Bill</strong><br>A utility bill of ₱488.00 has been generated for your room.','Billing'),(1351,'112','2026-04-23 11:41:37','1','🧾 <strong>New Utility Bill</strong><br>A utility bill of ₱488.00 has been generated for your room.','Billing'),(1352,'112','2026-04-23 11:42:45','1','❌ <strong>Payment Rejected</strong><br>Your uploaded payment proof for Payment #299 was rejected. Please re-upload a valid proof of payment.','Payment Update'),(1353,'112','2026-04-23 11:43:03','1','✅ <strong>Payment Confirmed</strong><br>Your payment #299 has been verified and marked as Paid.','Payment Update'),(1354,'112','2026-04-23 11:49:05','1','❌ <strong>Payment Rejected</strong><br>Your uploaded payment proof for Payment #300 was rejected. Please re-upload a valid proof of payment.','Payment Update'),(1355,'112','2026-04-23 11:49:19','1','✅ <strong>Payment Confirmed</strong><br>Your payment #300 has been verified and marked as Paid.','Payment Update'),(1356,'111','2026-04-23 11:50:49','1','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #170 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1357,'111','2026-04-23 11:51:21','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1358,'111','2026-04-23 11:51:50','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1359,'111','2026-04-23 11:52:30','1','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #176 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1360,'111','2026-04-23 11:52:55','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1361,'111','2026-04-23 11:55:11','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1362,'111','2026-04-23 11:55:16','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #177. Please go to My Reservations to sign.','Action Required'),(1363,'112','2026-04-23 12:07:30','1','⚠️ <strong>Outstanding Balance Reminder</strong><br>Dear Tyson, Fredhenzel, this is a friendly reminder from Woke Coliving that you have an outstanding balance of <strong>₱22,500.00</strong>. Please settle this amount at your earliest convenience to ensure continued access to services. Thank you!','Billing Reminder'),(1364,'112','2026-04-23 12:07:58','1','⚠️ <strong>Outstanding Balance Reminder</strong><br>Dear Tyson, Fredhenzel, this is a friendly reminder from Woke Coliving that you have an outstanding balance of <strong>₱22,500.00</strong>. Please settle this amount at your earliest convenience to ensure continued access to services. Thank you!','Billing Reminder'),(1365,'112','2026-04-23 12:13:22','1','⚠️ <strong>Billing Reminder</strong><br>Dear Tyson, Fredhenzel, this is a friendly reminder from Woke Coliving regarding your current and upcoming dues. Your payable amount for this billing cycle is <strong>₱22,500.00</strong>. Please settle this amount on or before the due date to avoid penalties. Thank you!','Billing Reminder'),(1366,'112','2026-04-23 12:13:27','1','⚠️ <strong>Billing Reminder</strong><br>Dear Tyson, Fredhenzel, this is a friendly reminder from Woke Coliving regarding your current and upcoming dues. Your payable amount for this billing cycle is <strong>₱22,500.00</strong>. Please settle this amount on or before the due date to avoid penalties. Thank you!','Billing Reminder'),(1367,'112','2026-04-23 12:42:23','1','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #175 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1368,'112','2026-04-23 12:52:08','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1369,'112','2026-04-23 13:04:54','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1370,'112','2026-04-23 13:04:58','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #178. Please go to My Reservations to sign.','Action Required'),(1371,'113','2026-04-23 21:03:29','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1372,'113','2026-04-23 21:09:08','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1373,'113','2026-04-23 21:09:12','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #179. Please go to My Reservations to sign.','Action Required'),(1374,'113','2026-04-23 21:09:27','','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #179 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1375,'113','2026-04-23 21:10:41','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1376,'112','2026-04-23 21:12:56','1','🧾 <strong>New Utility Bill</strong><br>A utility bill of ₱775.00 has been generated for your room.','Billing'),(1377,'112','2026-04-23 21:13:35','1','🧾 <strong>New Utility Bill</strong><br>A utility bill of ₱775.00 has been generated for your room.','Billing'),(1378,'112','2026-04-23 21:13:45','1','✅ <strong>Payment Confirmed</strong><br>Your payment #316 has been verified and marked as Paid.','Payment Update'),(1379,'112','2026-04-23 21:15:05','1','✅ <strong>Payment Confirmed</strong><br>Your payment #317 has been verified and marked as Paid.','Payment Update'),(1380,'113','2026-04-23 21:15:32','','❌ <strong>Reservation Rejected</strong><br>Your booking #180 has been cancelled. Please contact support for details.','Booking Rejected'),(1381,'112','2026-04-23 21:53:34','','🧹 <strong>Housekeeping Scheduled</strong><br>Admin has scheduled a routine cleaning for your room on Apr 23, 2026.','Housekeeping'),(1382,'112','2026-04-23 21:53:37','','🧹 <strong>Housekeeping Scheduled</strong><br>Admin has scheduled a routine cleaning for your room on Apr 23, 2026.','Housekeeping'),(1383,'114','2026-04-23 23:29:58','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1384,'114','2026-04-23 23:30:31','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1385,'114','2026-04-23 23:30:35','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #181. Please go to My Reservations to sign.','Action Required'),(1386,'114','2026-04-24 00:24:18','1','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #181 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1387,'112','2026-04-24 22:42:52','','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #178 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1388,'119','2026-04-24 22:53:49','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1389,'112','2026-04-24 23:04:01','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1390,'112','2026-04-24 23:04:31','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1391,'112','2026-04-24 23:04:36','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #183. Please go to My Reservations to sign.','Action Required'),(1392,'112','2026-04-24 23:04:47','','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #183 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1393,'112','2026-04-24 23:05:01','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1394,'112','2026-04-24 23:05:16','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1395,'112','2026-04-24 23:05:20','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #184. Please go to My Reservations to sign.','Action Required'),(1396,'120','2026-04-24 23:07:46','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status');
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
  `vehicle_plate` varchar(50) DEFAULT NULL,
  `vehicle_details` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `slot_id` (`slot_id`),
  CONSTRAINT `parking_reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `parking_reservations_ibfk_2` FOREIGN KEY (`slot_id`) REFERENCES `parking_slots` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_reservations`
--

LOCK TABLES `parking_reservations` WRITE;
/*!40000 ALTER TABLE `parking_reservations` DISABLE KEYS */;
INSERT INTO `parking_reservations` VALUES (11,40,11,'2026-03-09','2026-03-09',600.00,'Monthly','Completed',NULL,NULL,'2026-03-09 12:49:30'),(33,112,15,'2026-04-23',NULL,600.00,'Monthly','Active','Ty20N','Motobi Evo 200','2026-04-23 03:23:50');
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
INSERT INTO `parking_slots` VALUES (11,'Car Slot 1','Car','Available',600.00,50.00,0),(12,'Car Slot 2','Car','Available',600.00,50.00,0),(13,'Car Slot 3','Car','Available',600.00,50.00,0),(14,'Car Slot 4','Car','Available',600.00,50.00,0),(15,'Motorcycle Slot 1','Motorcycle','Occupied',600.00,50.00,0),(16,'Motorcycle Slot 2','Motorcycle','Available',600.00,50.00,0),(17,'Motorcycle Slot 3','Motorcycle','Available',600.00,50.00,0),(18,'Motorcycle Slot 4','Motorcycle','Available',600.00,50.00,0),(19,'Motorcycle Slot 5','Motorcycle','Available',600.00,50.00,0),(20,'Motorcycle Slot 6','Motorcycle','Available',600.00,50.00,0),(21,'Motorcycle Slot 7','Motorcycle','Available',600.00,50.00,0);
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
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`payment_id`),
  KEY `reservation_id` (`reservation_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=328 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (55,51,28200.00,'Cash','Paid','2026-02-28 13:54:57',NULL,NULL,'Room Payment',0,0),(103,51,600.00,'Cash','Paid','2026-03-08 20:19:32',NULL,NULL,'Monthly Parking Fee (March 2026) for Car Slot 1 (Parking ID: 2)',1,0),(110,51,30.00,'','Paid','2026-03-08 20:19:38',NULL,NULL,'Late Penalty (5%) for Payment #103',0,0),(123,51,600.00,'Cash','Paid','2026-03-12 16:39:27',NULL,NULL,'Monthly Parking Fee (March 2026) for Car Slot 1 (Parking ID: 11)',0,0),(187,51,0.31,'Cash','Paid','2026-04-06 01:14:08',NULL,NULL,'Late Penalty (5%) for Payment #180',0,0),(188,51,0.31,'Cash','Paid','2026-04-06 01:14:08',NULL,NULL,'Late Penalty (5%) for Payment #181',0,0),(189,51,0.31,'Cash','Paid','2026-04-06 01:14:08',NULL,NULL,'Late Penalty (5%) for Payment #182',0,0),(284,171,1000.00,'GCash','Cancelled','2026-04-23 02:47:55','09876312122','1776912475_gcash_671549365_2397717717406408_7555397030948241540_n.jpg','Security Deposit (Voided - Reservation Cancelled)',0,0),(285,171,6900.00,'GCash','Cancelled','2026-04-23 02:47:55','09876312122','1776912475_gcash_671549365_2397717717406408_7555397030948241540_n.jpg','First Month Rent (Voided - Reservation Cancelled)',0,0),(286,172,1000.00,'Cash','Cancelled','2026-04-23 03:02:53','',NULL,'Security Deposit [FULL] (Voided - Reservation Cancelled)',0,0),(287,172,5999.00,'Cash','Cancelled','2026-04-23 03:02:53','',NULL,'First Month Rent [FULL] (Voided - Reservation Cancelled)',0,0),(288,173,1000.00,'GCash','Paid','2026-04-23 03:13:23','09876312235','1776913874_gcash_671549365_2397717717406408_7555397030948241540_n.jpg','Security Deposit [FULL]',0,0),(289,173,6900.00,'GCash','Paid','2026-04-23 03:13:23','09876312235','1776913874_gcash_671549365_2397717717406408_7555397030948241540_n.jpg','First Month Rent [FULL]',0,0),(290,173,700.00,'Cash','Paid','2026-04-23 03:24:54','Pay at Property','Cash','Extension Rent Payment [FULL]',0,0),(291,173,600.00,'Cash','Paid','2026-04-23 03:24:54','Pay at Property','Cash','Monthly Parking Fee (April 2026) for Motorcycle Slot 1 (Parking ID: 33) [FULL]',0,0),(292,175,3000.00,'Cash','Paid','2026-04-23 03:39:08','Pay at Property','Cash','Security Deposit',0,0),(293,175,4500.00,'Cash','Paid','2026-04-23 03:39:08','Pay at Property','Cash','First Month Rent',0,0),(294,175,4500.00,'Cash','Cancelled','2026-05-22 16:00:00',NULL,NULL,'Month 2 Rent (Voided - Reservation Cancelled)',0,0),(295,175,4500.00,'Cash','Cancelled','2026-06-22 16:00:00',NULL,NULL,'Month 3 Rent (Voided - Reservation Cancelled)',0,0),(296,175,4500.00,'Cash','Cancelled','2026-07-22 16:00:00',NULL,NULL,'Month 4 Rent (Voided - Reservation Cancelled)',0,0),(297,175,4500.00,'Cash','Cancelled','2026-08-22 16:00:00',NULL,NULL,'Month 5 Rent (Voided - Reservation Cancelled)',0,0),(298,175,4500.00,'Cash','Cancelled','2026-09-22 16:00:00',NULL,NULL,'Month 6 Rent (Voided - Reservation Cancelled)',0,0),(299,175,488.00,'Cash','Paid','2026-04-23 03:43:03','Pay at Property','Cash','Utility Bill (2026-04-23) - Split 1/1',0,1),(300,175,488.00,'Cash','Paid','2026-04-23 03:49:19','Pay at Property','Cash','Utility Bill (2026-04-23) - Split 1/1',0,0),(303,177,8000.00,'GCash','Paid','2026-04-23 03:54:11','09876312236','1776916436_gcash_671549365_2397717717406408_7555397030948241540_n.jpg','Security Deposit [FULL]',0,0),(304,177,14000.00,'GCash','Paid','2026-04-23 03:54:11','09876312236','1776916436_gcash_671549365_2397717717406408_7555397030948241540_n.jpg','First Month Rent [FULL]',0,0),(305,178,3000.00,'Cash','Paid','2026-04-23 05:03:52','Pay at Property','Cash','Security Deposit',0,0),(306,178,17000.00,'Cash','Paid','2026-04-23 05:03:52','Pay at Property','Cash','First Month Rent',0,0),(307,178,17000.00,'','Cancelled','2026-05-22 16:00:00',NULL,NULL,'Month 2 Rent (Voided - Reservation Completed)',0,0),(308,178,17000.00,'','Cancelled','2026-06-22 16:00:00',NULL,NULL,'Month 3 Rent (Voided - Reservation Completed)',0,0),(309,178,17000.00,'','Cancelled','2026-07-22 16:00:00',NULL,NULL,'Month 4 Rent (Voided - Reservation Completed)',0,0),(310,178,17000.00,'','Cancelled','2026-08-22 16:00:00',NULL,NULL,'Month 5 Rent (Voided - Reservation Completed)',0,0),(311,178,17000.00,'','Cancelled','2026-09-22 16:00:00',NULL,NULL,'Month 6 Rent (Voided - Reservation Completed)',0,0),(312,179,1000.00,'Cash','Paid','2026-04-23 13:09:02','Pay at Property','Cash','Security Deposit [FULL]',0,0),(313,179,6300.00,'Cash','Paid','2026-04-23 13:09:02','Pay at Property','Cash','First Month Rent [FULL]',0,0),(314,180,1000.00,'','Cancelled','2026-04-23 13:10:41',NULL,NULL,'Security Deposit (Voided - Reservation Cancelled)',0,0),(315,180,6300.00,'','Cancelled','2026-04-23 13:10:41',NULL,NULL,'First Month Rent (Voided - Reservation Cancelled)',0,0),(316,178,775.00,'Cash','Paid','2026-04-23 13:13:45','Pay at Property','Cash','Utility Bill (2026-04-23) - Split 1/1',0,1),(317,178,775.00,'Cash','Paid','2026-04-23 13:15:05','Pay at Property','Cash','Utility Bill (2026-04-23) - Split 1/1',0,0),(318,181,1000.00,'Cash','Paid','2026-04-23 15:30:26','Pay at Property','Cash','Security Deposit [FULL]',0,0),(319,181,26400.00,'Cash','Paid','2026-04-23 15:30:26','Pay at Property','Cash','First Month Rent [FULL]',0,0),(320,182,8000.00,'','Unpaid','2026-04-24 14:53:49',NULL,NULL,'Security Deposit',0,0),(321,182,14000.00,'','Unpaid','2026-04-24 14:53:49',NULL,NULL,'First Month Rent',0,0),(322,183,8000.00,'Cash','Paid','2026-04-24 15:04:25','Pay at Property','Cash','Security Deposit [FULL]',0,0),(323,183,14000.00,'Cash','Paid','2026-04-24 15:04:25','Pay at Property','Cash','First Month Rent [FULL]',0,0),(324,184,1000.00,'Cash','Paid','2026-04-24 15:05:11','Pay at Property','Cash','Security Deposit [FULL]',0,0),(325,184,6600.00,'Cash','Paid','2026-04-24 15:05:11','Pay at Property','Cash','First Month Rent [FULL]',0,0),(326,185,1000.00,'','Unpaid','2026-04-24 15:07:46',NULL,NULL,'Security Deposit',0,0),(327,185,6900.00,'','Unpaid','2026-04-24 15:07:46',NULL,NULL,'First Month Rent',0,0);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_inventory`
--

DROP TABLE IF EXISTS `property_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT 'General',
  `total_quantity` int(11) DEFAULT 0,
  `in_use` int(11) DEFAULT 0,
  `in_maintenance` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_inventory`
--

LOCK TABLES `property_inventory` WRITE;
/*!40000 ALTER TABLE `property_inventory` DISABLE KEYS */;
INSERT INTO `property_inventory` VALUES (1,'Beds','Furniture',243,243,0,'2026-04-10 23:27:28'),(3,'Bed Sheets','Linens',243,243,0,'2026-04-10 23:27:28'),(5,'Pillows','Linens',243,243,0,'2026-04-10 23:27:28'),(7,'Pillow Cases','Linens',243,243,0,'2026-04-10 23:27:28');
/*!40000 ALTER TABLE `property_inventory` ENABLE KEYS */;
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
  `status` enum('Pending','Verifying','Approved','Cancelled','Completed','Incomplete') DEFAULT 'Pending',
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
  `security_deposit` decimal(10,2) DEFAULT 0.00,
  `companions` text DEFAULT NULL,
  PRIMARY KEY (`reservation_id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=186 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (51,40,20,'','',6,28200.00,'Completed','2026-02-26 01:57:54','2026-02-26','2026-08-26',NULL,'Any','sig_51_1773770455.png',0,NULL,1,1,NULL,NULL,NULL,NULL,0.00,NULL),(171,112,7,'','',1,7900.00,'Cancelled','2026-04-23 02:47:55','2026-04-23','2026-05-22',NULL,'Lower Bunk',NULL,1,NULL,0,1,'Student','KNS','Stephen Squad','09974612451',1000.00,NULL),(172,112,6,'','',1,6999.00,'Cancelled','2026-04-23 02:59:16','2026-04-23','2026-05-22',NULL,'Upper Bunk',NULL,1,NULL,0,1,'Student','KNS','Stephen Squad','09974612451',1000.00,NULL),(173,112,7,'','',2,8600.00,'Completed','2026-04-23 03:03:29','2026-04-23','2026-05-23',NULL,'Any','sig_173_1776914071.png',1,NULL,0,1,'Student','KNS','Stephen Squad','09974612451',1000.00,NULL),(175,112,59,'','',6,30000.00,'Completed','2026-04-23 03:38:17','2026-04-23','2026-10-30',NULL,'Lower Bunk','sig_175_1776915567.png',1,NULL,1,1,'Student','KNS','Stephen Squad','09974612451',3000.00,NULL),(177,111,20,'','',1,22000.00,'Approved','2026-04-23 03:52:55','2026-04-23','2026-05-22',NULL,'Solo','sig_177_1776916523.png',0,NULL,1,1,'Student','Kolehiyo Ng Subic','Sandrino Martin','09857151251',8000.00,NULL),(178,112,40,'','',6,105000.00,'Completed','2026-04-23 04:52:08','2026-04-23','2026-10-30',NULL,'Whole Room','sig_178_1776920705.png',1,NULL,1,1,'Student','KNS','Stephen Squad','09974612451',3000.00,NULL),(179,113,63,'','',1,7300.00,'Completed','2026-04-23 13:03:29','2026-04-23','2026-05-22',NULL,'Upper Bunk','sig_179_1776949761.png',1,NULL,1,1,'Student','Kolehiyo Ng Subic','Stephen Begosa','09976754512',1000.00,NULL),(180,113,28,'','',1,7300.00,'Cancelled','2026-04-23 13:10:41','2026-04-23','2026-05-22',NULL,'Upper Bunk',NULL,0,NULL,0,1,'Student','Kolehiyo Ng Subic','Stephen Begosa','09976754512',1000.00,NULL),(181,114,7,'','',1,27400.00,'Completed','2026-04-23 15:29:58','2026-04-23','2026-05-22',NULL,'Whole Room','sig_181_1776958315.png',1,NULL,1,1,'Student','Kolehiyo Ng Subic','Stephen Squad','09989746214',1000.00,'[{\"name\":\"Santiago, Marwino\",\"first_name\":\"Marwino\",\"last_name\":\"Santiago\",\"middle_name\":\"\",\"gender\":\"Male\",\"email\":\"\",\"phone\":\"09986521412\",\"id_image\":\"1776958198_comp_0_received_1998664277751362.webp\",\"restored\":true,\"restored_user_id\":\"115\"},{\"name\":\"Delima, Layla\",\"first_name\":\"Layla\",\"last_name\":\"Delima\",\"middle_name\":\"\",\"gender\":\"Female\",\"email\":\"\",\"phone\":\"09975214123\",\"id_image\":\"1776958198_comp_1_Client_Profile.jpg\",\"restored\":true,\"restored_user_id\":117}]'),(182,119,21,'','',1,22000.00,'Pending','2026-04-24 14:53:49','2026-04-24','2026-05-23',NULL,'Solo',NULL,0,NULL,0,1,'Student','Kolehiyo Ng Subic','Stephen Squad','09954634132',8000.00,NULL),(183,112,22,'','',1,22000.00,'Completed','2026-04-24 15:04:01','2026-04-24','2026-05-23',NULL,'Solo','sig_183_1777043081.png',1,NULL,1,1,'Student','KNS','Stephen Squad','09974612451',8000.00,NULL),(184,112,6,'','',1,7600.00,'Approved','2026-04-24 15:05:01','2026-04-24','2026-05-23',NULL,'Lower Bunk','sig_184_1777043124.png',0,NULL,1,1,'Student','KNS','Stephen Squad','09974612451',1000.00,NULL),(185,120,7,'','',1,7900.00,'Pending','2026-04-24 15:07:46','2026-04-24','2026-05-23',NULL,'Lower Bunk',NULL,0,NULL,0,1,'Student','Kolehiyo Ng Subic','Stephen Squad','09956663452',1000.00,NULL);
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
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `suffix` varchar(10) DEFAULT NULL,
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
  `is_companion` tinyint(1) DEFAULT 0,
  `primary_res_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `residents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `residents`
--

LOCK TABLES `residents` WRITE;
/*!40000 ALTER TABLE `residents` DISABLE KEYS */;
INSERT INTO `residents` VALUES (13,112,'Fredhenzel','Tyson','','','tysonicrosini@gmail.com','09997416245','Male','Student','KNS','San Isidro, Buhawen, San Marcelino, Zambales, Region III (Central Luzon)','Stephen Squad','09974612451',NULL,'1776912475_school_99b79f7f-06d2-4fcf-ae61-c5911a0397f4.jpg','user',0,0,0,'2026-04-23 03:14:17',0,NULL),(15,111,'Stephen','Squad','B','','stephen123@gmail.com','09874612345','Male','Student','Kolehiyo Ng Subic','San Isidro, San Isidro, Subic, Zambales, Region III (Central Luzon)','Sandrino Martin','09857151251',NULL,'1776916281_school_Woke_logo.jpg','user',1,0,0,'2026-04-23 03:51:50',0,NULL),(18,113,'Jenny Angel','Quilapio','M.','','Jenny@gmail.com','09421452352','Female','Student','Kolehiyo Ng Subic','Purok 4 Ibayo, San Isidro, Subic, Zambales, San Isidro, Subic, Zambales, Region III (Central Luzon)','Stephen Begosa','09976754512',NULL,'1776949409_school_Logo.png','user',0,0,0,'2026-04-23 13:09:08',0,NULL),(19,114,'John Benedict','Rasing','','','Rasingman@gmail.com','09874646125','Male','Student','Kolehiyo Ng Subic','San Isidro, San Isidro, Subic, Zambales, Region III (Central Luzon)','Stephen Squad','09989746214',NULL,'1776958198_id_0c25b0fc-a77e-4168-89fb-239780ef55a3.jpg','user',0,0,0,'2026-04-23 15:30:31',0,NULL),(20,NULL,'Marwino','Santiago','',NULL,'','09986521412','Male',NULL,NULL,NULL,NULL,NULL,NULL,'1776958198_comp_0_received_1998664277751362.webp','companion',0,0,0,'2026-04-23 15:30:31',1,181),(21,NULL,'Layla','Delima','',NULL,'','09975214123','Female',NULL,NULL,NULL,NULL,NULL,NULL,'1776958198_comp_1_Client_Profile.jpg','companion',0,0,0,'2026-04-23 15:30:31',1,181);
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
-- Table structure for table `room_transfers`
--

DROP TABLE IF EXISTS `room_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `old_room_id` int(11) NOT NULL,
  `new_room_id` int(11) NOT NULL,
  `transfer_date` datetime DEFAULT current_timestamp(),
  `status` enum('Moved','Returned') DEFAULT 'Moved',
  `return_requested` tinyint(1) DEFAULT 0,
  `return_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_transfers`
--

LOCK TABLES `room_transfers` WRITE;
/*!40000 ALTER TABLE `room_transfers` DISABLE KEYS */;
INSERT INTO `room_transfers` VALUES (1,155,74,6,'2026-04-11 19:18:30','Returned',1,NULL),(2,155,74,6,'2026-04-11 19:29:18','Returned',1,'2026-04-11 19:32:05'),(3,173,32,45,'2026-04-23 11:15:56','Returned',0,'2026-04-23 11:17:06');
/*!40000 ALTER TABLE `room_transfers` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=1200 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'hero_image','[\"1770471778_hero_edit.png\",\"1770447312_hero_edit.png\",\"1772369513_hero.png\",\"1770447047_hero.png\"]'),(125,'living_area_image','living_area_1770486291.jpg'),(126,'last_update','1777043266'),(290,'price_single','14000'),(291,'price_4bed_upper','6300'),(292,'price_4bed_lower','6900'),(293,'price_6bed_upper','5999'),(294,'price_6bed_lower','6600'),(303,'price_4bed_whole','26400'),(306,'price_6bed_whole','37797'),(315,'price_single_long','13000'),(319,'price_4bed_upper_long','4000'),(320,'price_4bed_lower_long','4500'),(321,'price_4bed_whole_long','17000'),(325,'price_6bed_upper_long','3500'),(326,'price_6bed_lower_long','4200'),(327,'price_6bed_whole_long','24000'),(548,'room_type_order','[\"Single\",\"4-Bed\",\"6-Bed\"]'),(687,'migration_fix_dupe_rooms_v2','1'),(688,'migration_cleanup_v3','1'),(848,'login_bg','login_bg_1774085356.jpg'),(894,'migration_parking_rates_v1','1'),(929,'price_housekeeping_standard','400'),(939,'price_maintenance_standard','400'),(1071,'house_rules','house_rules_1776910379.pdf'),(1082,'gcash_qr','gcash_qr_1776912371.jpg'),(1133,'clearance_file','clearance_form_1776919302.pdf'),(1142,'price_parking_car_monthly','600'),(1143,'price_parking_car_daily','50'),(1144,'price_parking_motor_monthly','600'),(1145,'price_parking_motor_daily','50'),(1183,'migration_companions_v2','1'),(1188,'clearance_template','<div style=\"text-align: center; margin-bottom: 20px;\">\r\n<h2>Tenant Clearance Form</h2>\r\n<p>Woke Coliving INC. | 205 Kanlaon St. Mandaluyong, Philippines</p>\r\n</div>\r\n<table style=\"width: 100%; border-collapse: collapse; margin-bottom: 20px;\" border=\"1\">\r\n<tbody>\r\n<tr>\r\n<td style=\"padding: 8px;\"><strong>Name:</strong> {TENANT_NAME}</td>\r\n<td style=\"padding: 8px;\"><strong>Room:</strong> {ROOM}</td>\r\n</tr>\r\n<tr>\r\n<td style=\"padding: 8px;\"><strong>Stay Period:</strong> {START_DATE} to {END_DATE}</td>\r\n<td style=\"padding: 8px;\"><strong>Clearance Date:</strong> {CLEARANCE_DATE}</td>\r\n</tr>\r\n</tbody>\r\n</table>\r\n<p>This is to certify that <strong>{TENANT_NAME}</strong> has successfully completed their stay and is hereby cleared of all property and room accountabilities as of {CLEARANCE_DATE}.</p>\r\n<p>The security deposit will be refunded minus any deductions for property damages, lost items, or unpaid utility bills as detailed below.</p>\r\n<table style=\"width: 100%; border-collapse: collapse; margin-top: 20px;\" border=\"1\">\r\n<tbody>\r\n<tr>\r\n<td style=\"padding: 8px;\"><strong>Security Deposit Amount</strong></td>\r\n<td style=\"padding: 8px; text-align: right;\">Php {DEPOSIT_AMOUNT}</td>\r\n</tr>\r\n<tr>\r\n<td style=\"padding: 8px;\"><strong>Less: Deductions</strong><br><small>{DEDUCTION_REMARKS}</small></td>\r\n<td style=\"padding: 8px; text-align: right; color: red;\">- Php {DEDUCTION_AMOUNT}</td>\r\n</tr>\r\n<tr style=\"background-color: #f8f9fa;\">\r\n<td style=\"padding: 8px;\"><strong>Net Refundable Amount</strong></td>\r\n<td style=\"padding: 8px; text-align: right; font-weight: bold;\">Php {NET_REFUND}</td>\r\n</tr>\r\n</tbody>\r\n</table>\r\n<p><br><br></p>\r\n<table style=\"width: 100%; border: none; margin-top: 40px;\">\r\n<tbody>\r\n<tr>\r\n<td style=\"text-align: center; width: 45%; border-top: 1px solid #000; padding-top: 5px;\">{TENANT_NAME}<br><small>Tenant Signature</small></td>\r\n<td style=\"width: 10%;\">&nbsp;</td>\r\n<td style=\"text-align: center; width: 45%; border-top: 1px solid #000; padding-top: 5px;\">WOKE COLIVING ADMIN<br><small>Authorized Representative</small></td>\r\n</tr>\r\n</tbody>\r\n</table>');
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
  `suffix` varchar(10) DEFAULT NULL,
  `newsletter` tinyint(1) DEFAULT 1,
  `bio` text DEFAULT NULL,
  `social_link` varchar(255) DEFAULT NULL,
  `other_request_feature` tinyint(1) DEFAULT NULL,
  `change_password_feature` tinyint(1) DEFAULT NULL,
  `change_email_feature` tinyint(1) DEFAULT NULL,
  `delete_account_feature` tinyint(1) DEFAULT NULL,
  `id_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (40,'bartjavillonar@gmail.com','09304871699',NULL,'$2y$10$X9kKW5UpTXSviWVagyKhAuXEzk2SiwS4GxxgjIjikUt22qpKOwho2','','2026-02-26 01:57:54',1,0,'Male',NULL,NULL,'Employed','','',NULL,'','',NULL,0,1,'JAVILLONAR','BARTOLOME','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(111,'stephen123@gmail.com','09874612345',NULL,'$2y$10$AKqThGvZzT7IfLIeeXVg6O064o6hH0kCVrOVnMGDmSS1qvazpVsVu','user','2026-04-22 17:28:03',0,0,'Male',NULL,NULL,'Student','San Isidro, San Isidro, Subic, Zambales, Region III (Central Luzon)','Kolehiyo Ng Subic','1776916281_school_Woke_logo.jpg','Sandrino Martin','09857151251',NULL,1,0,'Squad','Stephen','B','',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(112,'tysonicrosini@gmail.com','09997416245',NULL,'$2y$10$k8HxB1VcGWHGC9WFzqFL/.KkLjCZ0CDPe054exGY0ndTpAWgr6IXm','user','2026-04-23 02:00:52',0,0,'Male',NULL,NULL,'Student','San Isidro, Buhawen, San Marcelino, Zambales, Region III (Central Luzon)','KNS','1776912475_school_99b79f7f-06d2-4fcf-ae61-c5911a0397f4.jpg','Stephen Squad','09974612451',NULL,0,1,'Tyson','Fredhenzel','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'School ID'),(113,'Jenny@gmail.com','09421452352',NULL,'$2y$10$oZ4WFjcVJ/p6zApFH6hW2uRaXCq4LubEUELSL4TC2R/PqODBZN61y','user','2026-04-23 13:00:27',0,0,'Female',NULL,NULL,'Student','Purok 4 Ibayo, San Isidro, Subic, Zambales, San Isidro, Subic, Zambales, Region III (Central Luzon)','Kolehiyo Ng Subic','1776949409_school_Logo.png','Stephen Begosa','09976754512',NULL,0,0,'Quilapio','Jenny Angel','M.','',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(114,'Rasingman@gmail.com','09874646125',NULL,'$2y$10$8MfAMArjmoHtaUsL0zp7reRQe.AhB32OiIAtLEm.gV6.xupCD.3oS','user','2026-04-23 14:31:18',0,0,'Male',NULL,NULL,'Student','San Isidro, San Isidro, Subic, Zambales, Region III (Central Luzon)','Kolehiyo Ng Subic','1776958198_id_0c25b0fc-a77e-4168-89fb-239780ef55a3.jpg','Stephen Squad','09989746214',NULL,0,1,'Rasing','John Benedict','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'School ID'),(115,'marwino642@wokecoliving.com','09986521412',NULL,'$2y$10$pd3D1mXbQ8EwkhMriUbL7Od0UD8N5W.fhqJocPUxaME7FmEd2F8sS','user','2026-04-23 17:01:32',0,0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,'Santiago','Marwino','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(116,'marwino934@wokecoliving.com','09986521412',NULL,'$2y$10$OA1BygKq6g1Pdp9MrUnueunewyfPtOJzVgdTFdSVmo/CPkuskv832','user','2026-04-23 17:04:08',1,0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,'Santiago','Marwino','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(117,'layla798@wokecoliving.com','09975214123',NULL,'$2y$10$40YTDXrk2GHeIU/LB8zrFuj2dHrNiWk.o4PShQyMkOrswaloqeUq6','user','2026-04-23 17:15:06',0,0,'Female',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'user_117_1776964933.png',0,0,'Delima','Layla','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(118,'testsub@gmail.com','09977412412',NULL,'$2y$10$0QlQA4eGEVsmYQemS5/qTumJ1aTPV9w9Gz4zwzDdqW4eI9etTKOT.','user','2026-04-24 14:51:20',0,0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'Malik','Stephen','','',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(119,'marwin@gmail.com','09975771251',NULL,'$2y$10$bPqGIw9owpmAeC2reA62qOy50VKGXd9QfKcbDR7oKe8d.RYCJrw3G','user','2026-04-24 14:52:56',0,0,'Male',NULL,NULL,'Student','San Isidro, Del Pilar, Castillejos, Zambales, Region III (Central Luzon)','Kolehiyo Ng Subic','1777042429_id_ent1-nonoy-zuiga_2019-03-11_17-52-00.jpg','Stephen Squad','09954634132',NULL,0,0,'Santiago','Marwin Lee','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'School ID'),(120,'hernandez@gmail.com','09986765634',NULL,'$2y$10$soJiLYeqIUNSayUzulo3EOSzvT0zC2HuY1ZIwm1zHZ2YBFDXSK1P.','user','2026-04-24 15:07:06',0,0,'Male',NULL,NULL,'Student','San Isidro, San Isidro, Subic, Zambales, Region III (Central Luzon)','Kolehiyo Ng Subic','1777043266_id_images.webp','Stephen Squad','09956663452',NULL,0,0,'Hernandez','John Benedict','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'School ID'),(121,'stephenpogi@gmail.com','09627344434',NULL,'$2y$10$uXhgJU34VnCBWoHaOSgNhOhaBDcBCoIq7rmlrCD4hKQXuVbv6xS0m','user','2026-04-24 15:08:54',0,0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'Squada','Stephend','','',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utility_bills`
--

LOCK TABLES `utility_bills` WRITE;
/*!40000 ALTER TABLE `utility_bills` DISABLE KEYS */;
INSERT INTO `utility_bills` VALUES (2,20,NULL,'2026-03-18',1.05,1.57,12.00,1.12,0.40,35.00,6.24,'2026-03-18 03:32:20',1),(3,20,NULL,'2026-03-18',1.05,1.57,12.00,1.12,0.40,35.00,6.24,'2026-03-18 03:32:22',1),(4,20,NULL,'2026-03-18',1.05,1.57,12.00,1.12,0.40,35.00,6.24,'2026-03-18 03:32:24',1),(5,59,NULL,'2026-04-23',1022.00,1054.00,9.00,300.00,310.00,20.00,488.00,'2026-04-23 03:41:17',1),(6,59,NULL,'2026-04-23',1022.00,1054.00,9.00,300.00,310.00,20.00,488.00,'2026-04-23 03:41:37',1),(7,40,NULL,'2026-04-23',100.00,150.00,12.00,100.00,105.00,35.00,775.00,'2026-04-23 13:12:56',1),(8,40,NULL,'2026-04-23',100.00,150.00,12.00,100.00,105.00,35.00,775.00,'2026-04-23 13:13:35',1);
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
  `notified_at` datetime DEFAULT NULL,
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

--
-- Table structure for table `withdrawal_requests`
--

DROP TABLE IF EXISTS `withdrawal_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `withdrawal_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gcash_name` varchar(100) NOT NULL,
  `gcash_number` varchar(20) NOT NULL,
  `status` enum('Pending','Processed','Rejected') DEFAULT 'Pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `withdrawal_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `withdrawal_requests`
--

LOCK TABLES `withdrawal_requests` WRITE;
/*!40000 ALTER TABLE `withdrawal_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `withdrawal_requests` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-24 23:30:46
