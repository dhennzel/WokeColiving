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
) ENGINE=InnoDB AUTO_INCREMENT=826 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (89,38,'Account Created','Walk-in account created by Admin','2026-02-26 01:25:30','System','System'),(92,40,'Account Created','Walk-in account created by Admin','2026-02-26 01:57:54','System','System'),(93,40,'Walk-in Booking','Reservation #51 created by Admin','2026-02-26 01:57:54','System','System'),(108,40,'Payment Confirmed','Payment #55 marked as Paid by Admin.','2026-02-28 13:54:57','System','System'),(217,40,'Profile Updated','Admin updated user details.','2026-03-01 12:29:35','System','System'),(218,40,'Profile Updated','Admin updated user details.','2026-03-01 12:36:44','System','System'),(232,40,'Profile Updated','Admin updated user details.','2026-03-02 07:19:43','System','System'),(339,40,'Penalty Applied','Late fee of 30.00 applied for Payment #103','2026-03-08 13:41:45','System','System'),(357,40,'Parking Ended','Parking reservation #2 ended by Super Admin','2026-03-08 14:16:54','Super Admin','Super Admin'),(381,40,'Payment Confirmed','Payment #103 marked as Paid by Super Admin.','2026-03-08 20:19:32','Super Admin','Super Admin'),(382,40,'Payment Confirmed','Payment #110 marked as Paid by Super Admin.','2026-03-08 20:19:38','Super Admin','Super Admin'),(383,40,'Signature Requested','Signature requested for Reservation #51 by Super Admin','2026-03-08 20:19:51','Super Admin','Super Admin'),(384,40,'Room Re-assigned','Moved to 203 (Any) by Super Admin','2026-03-08 20:20:41','Super Admin','Super Admin'),(392,40,'Parking Assigned','Assigned to Car Slot 1 by Super Admin','2026-03-09 12:49:32','Super Admin','Super Admin'),(393,40,'Parking Ended','Parking reservation #11 ended by Super Admin','2026-03-09 12:49:38','Super Admin','Super Admin'),(394,40,'Room Re-assigned','Moved to 201 (Any) by Super Admin','2026-03-09 14:06:31','Super Admin','Super Admin'),(395,40,'Room Re-assigned','Moved to 203 (Lower Bunk) by Super Admin','2026-03-09 16:28:34','Super Admin','Super Admin'),(396,40,'Room Re-assigned','Moved to 201 (Any) by Super Admin','2026-03-09 17:01:47','Super Admin','Super Admin'),(397,40,'Room Re-assigned','Moved to 1 Bed (Any) by Super Admin','2026-03-09 19:02:19','Super Admin','Super Admin'),(428,40,'Payment Confirmed','Payment #123 marked as Paid by Super Admin.','2026-03-12 16:39:27','Super Admin','Super Admin'),(491,40,'Key Released','Key ID 15 released to user by Super Admin','2026-03-13 20:13:08','Super Admin','Super Admin'),(492,40,'Key Returned','Key ID 15 marked as returned by Super Admin','2026-03-13 20:13:16','Super Admin','Super Admin'),(593,40,'Signature Requested','Signature requested for Reservation #51 by Super Admin','2026-03-17 18:00:16','Diane Tayson (Super Admin)','Super Admin'),(594,40,'Lease Signed','Reservation #51','2026-03-17 18:00:55','Diane Tayson (Super Admin)','Super Admin'),(595,90,'Reservation Submitted','Room: Single | Status: Pending','2026-03-17 18:18:40','Diane Tayson (Super Admin)','Super Admin'),(596,90,'Payment Submitted','Reservation #125 via Cash for 1 bill(s)','2026-03-17 18:18:51','Diane Tayson (Super Admin)','Super Admin'),(597,90,'Reservation Approved','Reservation #125 approved by Super Admin.','2026-03-17 18:20:42','Diane Tayson (Super Admin)','Super Admin'),(598,90,'Signature Requested','Signature requested for Reservation #125 by Super Admin','2026-03-17 18:20:49','Diane Tayson (Super Admin)','Super Admin'),(599,90,'Lease Signed','Reservation #125','2026-03-17 18:20:58','Diane Tayson (Super Admin)','Super Admin'),(600,90,'Payment Confirmed','Payment #178 marked as Paid by Super Admin.','2026-03-17 18:21:33','Diane Tayson (Super Admin)','Super Admin'),(601,90,'Contract Ended','Reservation #125 marked as Completed by Super Admin.','2026-03-17 18:44:50','Diane Tayson (Super Admin)','Super Admin'),(602,90,'Reservation Submitted','Room: Single | Status: Pending','2026-03-17 18:45:36','Diane Tayson (Super Admin)','Super Admin'),(603,90,'Payment Submitted','Reservation #126 via Cash for 1 bill(s)','2026-03-17 18:45:43','Diane Tayson (Super Admin)','Super Admin'),(604,90,'Reservation Approved','Reservation #126 approved by Super Admin.','2026-03-17 18:46:00','Diane Tayson (Super Admin)','Super Admin'),(605,90,'Signature Requested','Signature requested for Reservation #126 by Super Admin','2026-03-17 18:46:08','Diane Tayson (Super Admin)','Super Admin'),(606,90,'Lease Signed','Reservation #126','2026-03-17 18:46:15','Diane Tayson (Super Admin)','Super Admin'),(607,90,'Payment Confirmed','Payment #179 marked as Paid by Super Admin.','2026-03-17 18:47:31','Diane Tayson (Super Admin)','Super Admin'),(608,91,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-03-18 03:42:54','Diane Tayson (Super Admin)','Super Admin'),(609,91,'Payment Submitted','Reservation #127 via Cash for 1 bill(s)','2026-03-18 03:43:04','Diane Tayson (Super Admin)','Super Admin'),(610,91,'Reservation Approved','Reservation #127 approved by Super Admin.','2026-03-18 03:44:19','Diane Tayson (Super Admin)','Super Admin'),(611,91,'Signature Requested','Signature requested for Reservation #127 by Super Admin','2026-03-18 03:44:29','Diane Tayson (Super Admin)','Super Admin'),(612,91,'Lease Signed','Reservation #127','2026-03-18 03:44:41','Diane Tayson (Super Admin)','Super Admin'),(613,91,'Payment Confirmed','Payment #183 marked as Paid by Super Admin.','2026-03-18 03:45:48','Diane Tayson (Super Admin)','Super Admin'),(614,92,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-03-18 03:56:10','Diane Tayson (Super Admin)','Super Admin'),(615,91,'Signature Requested','Signature requested for Reservation #127 by Super Admin','2026-03-18 03:59:04','Diane Tayson (Super Admin)','Super Admin'),(616,91,'Lease Signed','Reservation #127','2026-03-18 04:01:26','Diane Tayson (Super Admin)','Super Admin'),(617,91,'Contract Ended','Reservation #127 marked as Completed by Super Admin.','2026-03-18 04:11:29','Diane Tayson (Super Admin)','Super Admin'),(618,92,'Reservation Cancelled','Reservation #128 auto-expired due to non-payment.','2026-03-21 08:10:01','System','System'),(619,92,'Reservation Submitted','Room: Single | Status: Pending','2026-03-21 09:32:17','Diane Tayson (Super Admin)','Super Admin'),(620,92,'Reservation Cancelled','Reservation #129 auto-expired due to non-payment.','2026-03-22 17:04:31','System','System'),(621,92,'Penalty Applied','Late fee of 480.00 applied for Payment #184','2026-03-23 03:57:48','System','System'),(622,40,'Penalty Applied','Late fee of 0.31 applied for Payment #180','2026-03-23 11:55:46','System','System'),(623,40,'Penalty Applied','Late fee of 0.31 applied for Payment #181','2026-03-23 11:55:48','System','System'),(624,40,'Penalty Applied','Late fee of 0.31 applied for Payment #182','2026-03-23 11:55:50','System','System'),(625,92,'Penalty Applied','Late fee of 850.00 applied for Payment #185','2026-03-26 05:29:00','System','System'),(626,93,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-03-26 08:01:59','Diane Tayson (Super Admin)','Super Admin'),(627,93,'Payment Submitted','Reservation #130 via GCash for 1 bill(s)','2026-03-26 08:02:42','Diane Tayson (Super Admin)','Super Admin'),(628,93,'Profile Update Approved','Profile changes approved by Super Admin.','2026-03-26 08:24:00','Diane Tayson (Super Admin)','Super Admin'),(629,93,'Reservation Rejected','Reservation #130 cancelled by Super Admin.','2026-03-26 09:04:59','Diane Tayson (Super Admin)','Super Admin'),(630,93,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-03-26 09:06:39','Diane Tayson (Super Admin)','Super Admin'),(631,94,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-03-26 14:13:45','Diane Tayson (Super Admin)','Super Admin'),(632,95,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-03-26 15:31:22','Diane Tayson (Super Admin)','Super Admin'),(633,95,'Reservation Approved','Reservation #133 approved by Super Admin.','2026-03-26 15:32:11','Diane Tayson (Super Admin)','Super Admin'),(634,95,'Signature Requested','Signature requested for Reservation #133 by Super Admin','2026-03-26 15:32:20','Diane Tayson (Super Admin)','Super Admin'),(635,95,'Lease Signed','Reservation #133','2026-03-26 15:32:31','Diane Tayson (Super Admin)','Super Admin'),(636,96,'Reservation Submitted','Room: Single | Status: Pending','2026-03-26 16:27:10','Diane Tayson (Super Admin)','Super Admin'),(637,96,'Reservation Approved','Reservation #134 approved by Super Admin.','2026-03-26 16:34:49','Diane Tayson (Super Admin)','Super Admin'),(638,96,'Signature Requested','Signature requested for Reservation #134 by Super Admin','2026-03-26 16:34:56','Diane Tayson (Super Admin)','Super Admin'),(639,96,'Lease Signed','Reservation #134','2026-03-26 16:35:10','Diane Tayson (Super Admin)','Super Admin'),(640,96,'Payment Submitted','Reservation #134 via Cash for 1 bill(s)','2026-03-26 16:35:13','Diane Tayson (Super Admin)','Super Admin'),(641,96,'Profile Update Approved','Profile changes approved by Super Admin.','2026-03-26 16:59:22','Diane Tayson (Super Admin)','Super Admin'),(642,97,'Reservation Submitted','Room: Single | Status: Pending','2026-03-26 17:01:07','Diane Tayson (Super Admin)','Super Admin'),(643,97,'Payment Submitted','Reservation #135 via Cash for 1 bill(s)','2026-03-26 17:01:11','Diane Tayson (Super Admin)','Super Admin'),(644,97,'Reservation Approved','Reservation #135 approved by Super Admin.','2026-03-26 17:01:26','Diane Tayson (Super Admin)','Super Admin'),(645,93,'Reservation Cancelled','Reservation #131 auto-expired due to non-payment.','2026-03-27 10:47:58','System','System'),(646,94,'Reservation Cancelled','Reservation #132 auto-expired due to non-payment.','2026-03-27 16:18:58','Diane Tayson (Super Admin)','Super Admin'),(647,92,'Reservation Submitted','Room: Single | Status: Pending','2026-03-27 16:22:07','Diane Tayson (Super Admin)','Super Admin'),(648,92,'Payment Submitted','Reservation #136 via Cash for 1 bill(s)','2026-03-27 16:22:17','Diane Tayson (Super Admin)','Super Admin'),(649,92,'Reservation Approved','Reservation #136 approved by Super Admin.','2026-03-27 16:22:30','Diane Tayson (Super Admin)','Super Admin'),(650,92,'Contract Completed','Reservation #136 automatically marked as Completed (End date reached).','2026-03-27 16:22:30','System','System'),(651,92,'Payment Cancelled','Payment #185 cancelled by Super Admin.','2026-03-27 16:22:47','Diane Tayson (Super Admin)','Super Admin'),(652,92,'Payment Cancelled','Payment #184 cancelled by Super Admin.','2026-03-27 16:22:56','Diane Tayson (Super Admin)','Super Admin'),(653,92,'Payment Cancelled','Payment #186 cancelled by Super Admin.','2026-03-27 16:23:05','Diane Tayson (Super Admin)','Super Admin'),(654,92,'Payment Cancelled','Payment #190 cancelled by Super Admin.','2026-03-27 16:23:12','Diane Tayson (Super Admin)','Super Admin'),(655,92,'Payment Confirmed','Payment #197 marked as Paid by Super Admin.','2026-03-27 16:23:34','Diane Tayson (Super Admin)','Super Admin'),(656,98,'Account Created','Walk-in account created by Super Admin','2026-03-27 16:26:32','Diane Tayson (Super Admin)','Super Admin'),(657,99,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-03-28 14:40:51','Diane Tayson (Super Admin)','Super Admin'),(658,99,'Reservation Approved','Reservation #137 approved by Super Admin.','2026-03-28 14:41:16','Diane Tayson (Super Admin)','Super Admin'),(659,99,'Signature Requested','Signature requested for Reservation #137 by Super Admin','2026-03-28 14:41:23','Diane Tayson (Super Admin)','Super Admin'),(660,99,'Lease Signed','Reservation #137','2026-03-28 14:41:31','Diane Tayson (Super Admin)','Super Admin'),(661,99,'Payment Submitted','Reservation #137 via Cash for 1 bill(s)','2026-03-28 14:41:36','Diane Tayson (Super Admin)','Super Admin'),(662,99,'Payment Submitted','Reservation #137 via Cash for 1 bill(s)','2026-03-28 14:51:17','Diane Tayson (Super Admin)','Super Admin'),(663,99,'Contract Completed','Reservation #137 automatically marked as Completed (End date reached).','2026-03-28 16:14:51','Diane Tayson (Super Admin)','Super Admin'),(664,101,'Account Created','Walk-in account created by Super Admin','2026-03-30 04:27:26','Diane Tayson (Super Admin)','Super Admin'),(665,102,'Account Created','Walk-in account created by Super Admin','2026-03-30 04:51:48','Diane Tayson (Super Admin)','Super Admin'),(666,93,'Penalty Applied','Late fee of 418.55 applied for Payment #191','2026-04-06 00:28:09','System','System'),(667,93,'Penalty Applied','Late fee of 480.00 applied for Payment #192','2026-04-06 00:28:11','System','System'),(668,94,'Penalty Applied','Late fee of 495.00 applied for Payment #193','2026-04-06 00:28:13','System','System'),(669,95,'Penalty Applied','Late fee of 480.00 applied for Payment #194','2026-04-06 00:28:15','System','System'),(670,96,'Penalty Applied','Late fee of 850.00 applied for Payment #195','2026-04-06 00:28:17','System','System'),(671,97,'Penalty Applied','Late fee of 850.00 applied for Payment #196','2026-04-06 00:28:19','System','System'),(672,99,'Penalty Applied','Late fee of 35.00 applied for Payment #198','2026-04-06 00:28:21','System','System'),(673,97,'Payment Confirmed','Payment #196 marked as Paid by Super Admin.','2026-04-06 00:47:51','Diane Tayson (Super Admin)','Super Admin'),(674,96,'Payment Confirmed','Payment #195 marked as Paid by Super Admin.','2026-04-06 00:49:40','Diane Tayson (Super Admin)','Super Admin'),(675,96,'Payment Confirmed','Payment #203 marked as Paid by Super Admin.','2026-04-06 00:49:48','Diane Tayson (Super Admin)','Super Admin'),(676,40,'Payment Submitted','Reservation #51 via Cash for 6 bill(s)','2026-04-06 01:02:25','Diane Tayson (Super Admin)','Super Admin'),(677,103,'Account Created','Walk-in account created by Super Admin','2026-04-06 01:17:16','Diane Tayson (Super Admin)','Super Admin'),(678,103,'Walk-in Booking','Reservation #138 created by Super Admin','2026-04-06 01:17:16','Diane Tayson (Super Admin)','Super Admin'),(679,103,'Payment Submitted','Reservation #138 via Cash for 1 bill(s)','2026-04-06 01:17:43','Diane Tayson (Super Admin)','Super Admin'),(680,103,'Payment Confirmed','Payment #206 marked as Paid by Super Admin.','2026-04-06 01:19:47','Diane Tayson (Super Admin)','Super Admin'),(681,103,'Signature Requested','Signature requested for Reservation #138 by Super Admin','2026-04-06 01:22:45','Diane Tayson (Super Admin)','Super Admin'),(682,103,'Lease Signed','Reservation #138','2026-04-06 01:26:22','Diane Tayson (Super Admin)','Super Admin'),(683,103,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-04-08 02:55:08','Diane Tayson (Super Admin)','Super Admin'),(684,103,'Reservation Rejected','Reservation #139 cancelled by Super Admin.','2026-04-08 03:05:00','Diane Tayson (Super Admin)','Super Admin'),(685,103,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-04-08 03:10:57','Diane Tayson (Super Admin)','Super Admin'),(686,103,'Contract Ended','Reservation #138 marked as Completed by Super Admin.','2026-04-08 03:48:34','Diane Tayson (Super Admin)','Super Admin'),(687,103,'Reservation Rejected','Reservation #140 cancelled by Super Admin.','2026-04-08 03:48:41','Diane Tayson (Super Admin)','Super Admin'),(688,103,'Reservation Submitted','Room: Single | Status: Pending','2026-04-08 03:50:18','Diane Tayson (Super Admin)','Super Admin'),(689,103,'Payment Submitted','Reservation #141 via Cash for 1 bill(s)','2026-04-08 03:56:34','Diane Tayson (Super Admin)','Super Admin'),(690,103,'Reservation Approved','Reservation #141 approved by Super Admin.','2026-04-08 04:13:42','Diane Tayson (Super Admin)','Super Admin'),(691,103,'Signature Requested','Signature requested for Reservation #141 by Super Admin','2026-04-08 04:13:53','Diane Tayson (Super Admin)','Super Admin'),(692,103,'Payment Confirmed','Payment #214 marked as Paid by Super Admin.','2026-04-08 04:13:57','Diane Tayson (Super Admin)','Super Admin'),(693,103,'Lease Signed','Reservation #141','2026-04-08 04:14:03','Diane Tayson (Super Admin)','Super Admin'),(694,103,'Parking Assigned','Assigned to Motorcycle Slot 1 by Super Admin','2026-04-08 04:14:34','Diane Tayson (Super Admin)','Super Admin'),(695,103,'Parking Ended','Parking reservation #24 ended by Super Admin','2026-04-08 04:18:58','Diane Tayson (Super Admin)','Super Admin'),(696,103,'Parking Assigned','Assigned to Motorcycle Slot 1 by Super Admin','2026-04-08 04:20:31','Diane Tayson (Super Admin)','Super Admin'),(697,103,'Payment Cancelled','Payment #216 cancelled by Super Admin.','2026-04-08 04:22:05','Diane Tayson (Super Admin)','Super Admin'),(698,103,'Payment Cancelled','Payment #215 cancelled by Super Admin.','2026-04-08 04:22:09','Diane Tayson (Super Admin)','Super Admin'),(699,103,'Parking Ended','Parking reservation #25 ended by Super Admin','2026-04-08 04:26:33','Diane Tayson (Super Admin)','Super Admin'),(700,103,'Parking Assigned','Assigned to Car Slot 4 by Super Admin','2026-04-08 04:29:55','Diane Tayson (Super Admin)','Super Admin'),(701,103,'Parking Ended','Parking reservation #26 ended by Super Admin','2026-04-08 04:39:11','Diane Tayson (Super Admin)','Super Admin'),(702,103,'Payment Submitted','Reservation #141 via Cash for 1 bill(s)','2026-04-08 04:48:04','Diane Tayson (Super Admin)','Super Admin'),(703,103,'Parking Assigned','Assigned to Car Slot 4 by Super Admin','2026-04-08 04:52:07','Diane Tayson (Super Admin)','Super Admin'),(704,103,'Parking Ended','Parking reservation #27 ended by Super Admin','2026-04-08 04:53:43','Diane Tayson (Super Admin)','Super Admin'),(705,103,'Payment Confirmed','Payment #217 marked as Paid by Super Admin.','2026-04-08 05:00:07','Diane Tayson (Super Admin)','Super Admin'),(706,103,'Payment Cancelled','Payment #218 cancelled by Super Admin.','2026-04-08 05:00:12','Diane Tayson (Super Admin)','Super Admin'),(707,103,'Reservation Extended','Room: Single | Status: Pending','2026-04-08 05:00:32','Diane Tayson (Super Admin)','Super Admin'),(708,103,'Payment Submitted','Reservation #142 via Cash for 1 bill(s)','2026-04-08 05:00:42','Diane Tayson (Super Admin)','Super Admin'),(709,103,'Payment Confirmed','Payment #219 marked as Paid by Super Admin.','2026-04-08 05:00:48','Diane Tayson (Super Admin)','Super Admin'),(710,103,'Reservation Extended','Contract #141 updated by Super Admin.','2026-04-08 05:01:56','Diane Tayson (Super Admin)','Super Admin'),(711,103,'Contract Ended','Reservation #141 marked as Completed by Super Admin.','2026-04-08 05:02:02','Diane Tayson (Super Admin)','Super Admin'),(712,103,'Reservation Submitted','Room: Single | Status: Pending','2026-04-08 05:02:37','Diane Tayson (Super Admin)','Super Admin'),(713,103,'Payment Submitted','Reservation #143 via Cash for 1 bill(s)','2026-04-08 05:04:19','Diane Tayson (Super Admin)','Super Admin'),(714,103,'Payment Confirmed','Payment #220 marked as Paid by Super Admin.','2026-04-08 05:09:25','Diane Tayson (Super Admin)','Super Admin'),(715,103,'Signature Requested','Signature requested for Reservation #143 by Super Admin','2026-04-08 05:13:32','Diane Tayson (Super Admin)','Super Admin'),(716,103,'Lease Signed','Reservation #143','2026-04-08 05:13:42','Diane Tayson (Super Admin)','Super Admin'),(717,103,'Contract Completed','Reservation #143 automatically marked as Completed (End date reached).','2026-04-10 13:01:53','System','System'),(718,103,'Reservation Rejected','Reservation #144 cancelled by Super Admin.','2026-04-11 03:25:16','Diane Tayson (Super Admin)','Super Admin'),(719,103,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-04-11 03:25:48','Diane Tayson (Super Admin)','Super Admin'),(720,103,'Payment Submitted','Reservation #145 via Cash for 2 bill(s)','2026-04-11 03:26:01','Diane Tayson (Super Admin)','Super Admin'),(721,103,'Reservation Approved','Reservation #145 approved by Super Admin.','2026-04-11 03:26:21','Diane Tayson (Super Admin)','Super Admin'),(722,103,'Signature Requested','Signature requested for Reservation #145 by Super Admin','2026-04-11 03:26:28','Diane Tayson (Super Admin)','Super Admin'),(723,103,'Lease Signed','Reservation #145','2026-04-11 03:26:32','Diane Tayson (Super Admin)','Super Admin'),(724,103,'Deposit Refunded','Security Deposit of ₱1,000.00 refunded and credited to account.','2026-04-11 03:32:33','Diane Tayson (Super Admin)','Super Admin'),(725,103,'Payment Submitted','Reservation #145 via Cash for 1 bill(s)','2026-04-11 03:33:44','Diane Tayson (Super Admin)','Super Admin'),(726,103,'Deposit Refunded','Security Deposit of ₱1,200.00 refunded and credited to account.','2026-04-11 03:36:16','Diane Tayson (Super Admin)','Super Admin'),(727,103,'Payment Confirmed','Payment #223 marked as Paid by Super Admin.','2026-04-11 03:38:31','Diane Tayson (Super Admin)','Super Admin'),(728,103,'Deposit Refunded','Security Deposit of ₱-1,000.00 refunded and credited to account.','2026-04-11 03:42:35','Diane Tayson (Super Admin)','Super Admin'),(729,103,'Deposit Refunded','Security Deposit of ₱1,000.00 refunded via Cash.','2026-04-11 04:02:15','Diane Tayson (Super Admin)','Super Admin'),(730,103,'Deposit Refunded','Security Deposit of ₱1,000.00 refunded via Cash.','2026-04-11 04:02:17','Diane Tayson (Super Admin)','Super Admin'),(731,103,'Contract Ended','Reservation #145 marked as Completed by Super Admin.','2026-04-11 04:02:48','Diane Tayson (Super Admin)','Super Admin'),(732,103,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-04-11 04:03:11','Diane Tayson (Super Admin)','Super Admin'),(733,103,'Payment Submitted','Reservation #146 via Cash for 2 bill(s)','2026-04-11 04:03:17','Diane Tayson (Super Admin)','Super Admin'),(734,103,'Payment Confirmed','Payment #227 marked as Paid by Super Admin.','2026-04-11 04:03:25','Diane Tayson (Super Admin)','Super Admin'),(735,103,'Payment Confirmed','Payment #226 marked as Paid by Super Admin.','2026-04-11 04:03:29','Diane Tayson (Super Admin)','Super Admin'),(736,103,'Reservation Approved','Reservation #146 approved by Super Admin.','2026-04-11 04:03:35','Diane Tayson (Super Admin)','Super Admin'),(737,103,'Signature Requested','Signature requested for Reservation #146 by Super Admin','2026-04-11 04:03:42','Diane Tayson (Super Admin)','Super Admin'),(738,103,'Lease Signed','Reservation #146','2026-04-11 04:03:47','Diane Tayson (Super Admin)','Super Admin'),(739,103,'Deposit Refunded','Security Deposit of ₱1,000.00 refunded to account wallet.','2026-04-11 04:04:01','Diane Tayson (Super Admin)','Super Admin'),(740,103,'Deposit Refunded','Security Deposit of ₱-1,000.00 refunded to account wallet.','2026-04-11 04:05:18','Diane Tayson (Super Admin)','Super Admin'),(741,103,'Deposit Refunded','Security Deposit of ₱1,000.00 refunded to account wallet.','2026-04-11 04:06:51','Diane Tayson (Super Admin)','Super Admin'),(742,103,'Payment Confirmed','Payment #230 marked as Paid by Super Admin.','2026-04-11 04:09:29','Diane Tayson (Super Admin)','Super Admin'),(743,103,'Payment Confirmed','Payment #224 marked as Paid by Super Admin.','2026-04-11 04:09:36','Diane Tayson (Super Admin)','Super Admin'),(744,103,'Deposit Refunded','Security Deposit of ₱-1,000.00 refunded to account wallet.','2026-04-11 04:09:42','Diane Tayson (Super Admin)','Super Admin'),(745,103,'Payment Confirmed','Payment #231 marked as Paid by Super Admin.','2026-04-11 04:09:57','Diane Tayson (Super Admin)','Super Admin'),(746,103,'Deposit Refunded','Security Deposit of ₱1,000.00 refunded to account wallet.','2026-04-11 04:10:14','Diane Tayson (Super Admin)','Super Admin'),(747,103,'Payment Cancelled','Payment #232 cancelled by Super Admin.','2026-04-11 04:11:06','Diane Tayson (Super Admin)','Super Admin'),(748,103,'Payment Confirmed','Payment #224 marked as Paid by Super Admin.','2026-04-11 04:11:12','Diane Tayson (Super Admin)','Super Admin'),(749,103,'Contract Ended','Reservation #146 marked as Completed by Super Admin.','2026-04-11 04:11:37','Diane Tayson (Super Admin)','Super Admin'),(750,103,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-04-11 04:15:24','Diane Tayson (Super Admin)','Super Admin'),(751,103,'Payment Submitted','Reservation #147 via Cash for 2 bill(s)','2026-04-11 04:15:33','Diane Tayson (Super Admin)','Super Admin'),(752,103,'Reservation Approved','Reservation #147 approved by Super Admin.','2026-04-11 04:16:00','Diane Tayson (Super Admin)','Super Admin'),(753,103,'Signature Requested','Signature requested for Reservation #147 by Super Admin','2026-04-11 04:16:07','Diane Tayson (Super Admin)','Super Admin'),(754,103,'Lease Signed','Reservation #147','2026-04-11 04:16:12','Diane Tayson (Super Admin)','Super Admin'),(755,103,'Deposit Refunded','Security Deposit of ₱1,000.00 refunded via Cash.','2026-04-11 04:16:42','Diane Tayson (Super Admin)','Super Admin'),(756,103,'Contract Renewed','Contract #147 extended by 1 months by Super Admin.','2026-04-11 04:22:23','Diane Tayson (Super Admin)','Super Admin'),(757,103,'Payment Confirmed','Payment #235 marked as Paid by Super Admin.','2026-04-11 04:22:28','Diane Tayson (Super Admin)','Super Admin'),(758,103,'Reservation Extended','Room: 6-Bed | Status: Pending','2026-04-11 04:23:55','Diane Tayson (Super Admin)','Super Admin'),(759,103,'Reservation Rejected','Reservation #148 cancelled by Super Admin.','2026-04-11 04:28:10','Diane Tayson (Super Admin)','Super Admin'),(760,103,'Reservation Extended','Room: 6-Bed | Status: Pending','2026-04-11 04:28:24','Diane Tayson (Super Admin)','Super Admin'),(761,103,'Payment Submitted','Reservation #149 via Cash for 1 bill(s)','2026-04-11 04:28:29','Diane Tayson (Super Admin)','Super Admin'),(762,103,'Payment Confirmed','Payment #236 marked as Paid by Super Admin.','2026-04-11 04:28:33','Diane Tayson (Super Admin)','Super Admin'),(763,103,'Reservation Extended','Contract #147 updated by Super Admin.','2026-04-11 04:28:44','Diane Tayson (Super Admin)','Super Admin'),(764,103,'Contract Ended','Reservation #147 marked as Completed by Super Admin.','2026-04-11 04:38:23','Diane Tayson (Super Admin)','Super Admin'),(765,103,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-11 04:38:52','Diane Tayson (Super Admin)','Super Admin'),(766,103,'Payment Submitted','Reservation #150 via Cash for 2 bill(s)','2026-04-11 04:38:56','Diane Tayson (Super Admin)','Super Admin'),(767,103,'Reservation Approved','Reservation #150 approved by Super Admin.','2026-04-11 04:39:21','Diane Tayson (Super Admin)','Super Admin'),(768,103,'Signature Requested','Signature requested for Reservation #150 by Super Admin','2026-04-11 04:39:29','Diane Tayson (Super Admin)','Super Admin'),(769,103,'Lease Signed','Reservation #150','2026-04-11 04:39:32','Diane Tayson (Super Admin)','Super Admin'),(770,103,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-04-11 04:40:12','Diane Tayson (Super Admin)','Super Admin'),(771,103,'Payment Submitted','Reservation #151 via Cash for 1 bill(s)','2026-04-11 04:40:16','Diane Tayson (Super Admin)','Super Admin'),(772,103,'Payment Confirmed','Payment #239 marked as Paid by Super Admin.','2026-04-11 04:40:23','Diane Tayson (Super Admin)','Super Admin'),(773,103,'Reservation Extended','Contract #150 updated by Super Admin.','2026-04-11 04:40:30','Diane Tayson (Super Admin)','Super Admin'),(774,103,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-04-11 04:40:53','Diane Tayson (Super Admin)','Super Admin'),(775,103,'Payment Cancelled','Payment #240 cancelled by Super Admin.','2026-04-11 04:40:58','Diane Tayson (Super Admin)','Super Admin'),(776,103,'Reservation Rejected','Reservation #152 cancelled by Super Admin.','2026-04-11 04:41:05','Diane Tayson (Super Admin)','Super Admin'),(777,103,'Parking Assigned','Assigned to Motorcycle Slot 2 by Super Admin','2026-04-11 04:51:30','Diane Tayson (Super Admin)','Super Admin'),(778,103,'Payment Submitted','Reservation #150 via Cash for 1 bill(s)','2026-04-11 04:53:42','Diane Tayson (Super Admin)','Super Admin'),(779,103,'Payment Confirmed','Payment #241 marked as Paid by Super Admin.','2026-04-11 04:53:53','Diane Tayson (Super Admin)','Super Admin'),(780,103,'Contract Ended','Reservation #150 marked as Completed by Super Admin.','2026-04-11 05:57:24','Diane Tayson (Super Admin)','Super Admin'),(781,103,'Profile Updated','User details updated by Super Admin.','2026-04-11 06:00:23','Diane Tayson (Super Admin)','Super Admin'),(782,103,'Profile Updated','User details updated by Super Admin.','2026-04-11 06:02:49','Diane Tayson (Super Admin)','Super Admin'),(783,103,'Profile Updated','User details updated by Super Admin.','2026-04-11 06:10:01','Diane Tayson (Super Admin)','Super Admin'),(784,103,'Profile Updated','User details updated by Super Admin.','2026-04-11 06:10:11','Diane Tayson (Super Admin)','Super Admin'),(785,103,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-11 06:19:04','Diane Tayson (Super Admin)','Super Admin'),(786,103,'Payment Submitted','Reservation #153 via Cash for 2 bill(s)','2026-04-11 06:19:09','Diane Tayson (Super Admin)','Super Admin'),(787,103,'Reservation Approved','Reservation #153 approved by Super Admin.','2026-04-11 06:19:42','Diane Tayson (Super Admin)','Super Admin'),(788,103,'Signature Requested','Signature requested for Reservation #153 by Super Admin','2026-04-11 06:19:53','Diane Tayson (Super Admin)','Super Admin'),(789,103,'Lease Signed','Reservation #153','2026-04-11 06:20:00','Diane Tayson (Super Admin)','Super Admin'),(790,105,'Account Created','Walk-in account created by Super Admin','2026-04-11 10:48:25','Diane Tayson (Super Admin)','Super Admin'),(791,105,'Walk-in Booking','Reservation #154 created by Super Admin','2026-04-11 10:48:25','Diane Tayson (Super Admin)','Super Admin'),(792,105,'Signature Requested','Signature requested for Reservation #154 by Super Admin from receipt view.','2026-04-11 10:48:43','Diane Tayson (Super Admin)','Super Admin'),(793,105,'Contract Ended','Reservation #154 marked as Completed by Super Admin.','2026-04-11 10:53:04','Diane Tayson (Super Admin)','Super Admin'),(794,106,'Account Created','Walk-in account created by Super Admin','2026-04-11 10:56:42','Diane Tayson (Super Admin)','Super Admin'),(795,106,'Walk-in Booking','Reservation #155 created by Super Admin','2026-04-11 10:56:42','Diane Tayson (Super Admin)','Super Admin'),(796,106,'Signature Requested','Signature requested for Reservation #155 by Super Admin','2026-04-11 10:57:18','Diane Tayson (Super Admin)','Super Admin'),(797,106,'Lease Signed','Reservation #155','2026-04-11 10:57:22','Diane Tayson (Super Admin)','Super Admin'),(798,106,'Room Re-assigned','Moved to 4 Beds (Lower Bunk) by Super Admin','2026-04-11 11:09:49','Diane Tayson (Super Admin)','Super Admin'),(799,106,'Room Re-assigned','Moved to 6 Beds (Lower Bunk) by Super Admin','2026-04-11 11:18:30','Diane Tayson (Super Admin)','Super Admin'),(800,106,'Room Return Requested','User requested to return to their old room (Transfer ID: 1)','2026-04-11 11:24:59','Diane Tayson (Super Admin)','Super Admin'),(801,106,'Room Returned','Returned to 4 Beds by Super Admin','2026-04-11 11:25:10','Diane Tayson (Super Admin)','Super Admin'),(802,106,'Room Re-assigned','Moved to 6 Beds (Lower Bunk) by Super Admin','2026-04-11 11:29:18','Diane Tayson (Super Admin)','Super Admin'),(803,106,'Room Return Requested','User requested to return to their old room (Transfer ID: 2)','2026-04-11 11:29:32','Diane Tayson (Super Admin)','Super Admin'),(804,106,'Room Returned','Returned to 4 Beds by Super Admin','2026-04-11 11:29:39','Diane Tayson (Super Admin)','Super Admin'),(805,106,'Room Returned','Returned to 4 Beds by Super Admin','2026-04-11 11:32:05','Diane Tayson (Super Admin)','Super Admin'),(806,103,'Deposit Refunded','Security Deposit of ₱1,000.00 refunded via Cash.','2026-04-13 01:34:42','Diane Tayson (Super Admin)','Super Admin'),(807,103,'Contract Ended','Reservation #153 marked as Completed by Super Admin.','2026-04-13 01:35:09','Diane Tayson (Super Admin)','Super Admin'),(808,103,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-04-13 01:37:16','Diane Tayson (Super Admin)','Super Admin'),(809,103,'Reservation Rejected','Reservation #156 cancelled by Super Admin.','2026-04-13 01:37:35','Diane Tayson (Super Admin)','Super Admin'),(810,103,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-13 01:47:24','Diane Tayson (Super Admin)','Super Admin'),(811,103,'Payment Confirmed','Payment #256 marked as Paid by Super Admin.','2026-04-13 01:49:43','Diane Tayson (Super Admin)','Super Admin'),(812,103,'Payment Cancelled','Payment #255 cancelled by Super Admin.','2026-04-13 01:49:48','Diane Tayson (Super Admin)','Super Admin'),(813,103,'Reservation Approved','Reservation #157 approved by Super Admin.','2026-04-13 02:31:04','Diane Tayson (Super Admin)','Super Admin'),(814,103,'Signature Requested','Signature requested for Reservation #157 by Super Admin','2026-04-13 02:31:12','Diane Tayson (Super Admin)','Super Admin'),(815,103,'Lease Signed','Reservation #157','2026-04-13 02:31:16','Diane Tayson (Super Admin)','Super Admin'),(816,103,'Payment Submitted','Reservation #157 via Cash for 1 bill(s)','2026-04-13 02:31:38','Diane Tayson (Super Admin)','Super Admin'),(817,103,'Payment Confirmed','Payment #255 marked as Paid by Super Admin.','2026-04-13 02:31:45','Diane Tayson (Super Admin)','Super Admin'),(818,103,'Deposit Refunded','Security Deposit of ₱1,000.00 refunded via Cash.','2026-04-13 02:31:59','Diane Tayson (Super Admin)','Super Admin'),(819,103,'Contract Ended','Reservation #157 marked as Completed by Super Admin.','2026-04-13 02:32:17','Diane Tayson (Super Admin)','Super Admin'),(820,103,'Parking Ended','Parking reservation #28 ended by Super Admin','2026-04-13 02:34:53','Diane Tayson (Super Admin)','Super Admin'),(821,103,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-04-13 02:36:30','Diane Tayson (Super Admin)','Super Admin'),(822,103,'Reservation Approved','Reservation #158 approved by Super Admin.','2026-04-13 02:43:24','Diane Tayson (Super Admin)','Super Admin'),(823,103,'Signature Requested','Signature requested for Reservation #158 by Super Admin','2026-04-13 02:44:00','Diane Tayson (Super Admin)','Super Admin'),(824,103,'Lease Signed','Reservation #158','2026-04-13 02:44:03','Diane Tayson (Super Admin)','Super Admin'),(825,106,'Housekeeping Update','Request #19 updated to \'Completed\' by Super Admin','2026-04-13 03:02:23','Diane Tayson (Super Admin)','Super Admin');
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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `housekeeping_requests`
--

LOCK TABLES `housekeeping_requests` WRITE;
/*!40000 ALTER TABLE `housekeeping_requests` DISABLE KEYS */;
INSERT INTO `housekeeping_requests` VALUES (13,40,7,'Weekly Routine Cleaning','Scheduled','2026-03-28','2026-03-08 14:31:21',0.00),(19,106,32,'Test Cleaning','Completed',NULL,'2026-04-11 11:07:34',400.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=1273 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (504,'40','2026-02-26 09:57:57','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(513,'40','2026-02-26 21:31:42','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(526,'40','2026-02-27 15:28:35','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(532,'40','2026-02-28 21:46:01','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(536,'40','2026-02-28 21:54:57','1','✅ <strong>Payment Confirmed</strong><br>Your payment #55 has been verified and marked as Paid.','Payment Update'),(707,'40','2026-03-03 13:28:50','1','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Car Slot 1. A fee of ₱600.00 has been added to your account.','Parking'),(708,'40','2026-03-03 14:19:47','1','🔑 <strong>Key Assigned</strong><br>You have been assigned a key. Please keep it safe.','Key System'),(709,'40','2026-03-03 14:20:14','1','🔑 <strong>Key Returned</strong><br>Key has been marked as returned.','Key System'),(710,'40','2026-03-03 14:21:51','1','🔑 <strong>Key Assigned</strong><br>You have been assigned a key. Please keep it safe.','Key System'),(711,'40','2026-03-03 14:21:58','1','🔑 <strong>Key Returned</strong><br>Key has been marked as returned.','Key System'),(713,'40','2026-03-05 18:01:58','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(726,'40','2026-03-05 22:01:59','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(737,'40','2026-03-06 02:01:59','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(740,'40','2026-03-06 11:25:54','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(743,'40','2026-03-06 15:25:55','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(748,'40','2026-03-06 23:34:31','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(751,'40','2026-03-07 12:58:52','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(754,'40','2026-03-07 17:21:42','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(768,'40','2026-03-07 21:22:35','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(771,'40','2026-03-08 13:05:01','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 03, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(774,'40','2026-03-08 21:41:45','1','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱30.00 has been applied to your account due to overdue payment.','Billing Alert'),(791,'40','2026-03-08 22:16:52','1','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #1 has been marked as completed.','Parking'),(800,'40','2026-03-08 22:31:21','1','🧹 <strong>Weekly Cleaning</strong><br>Routine housekeeping scheduled for Mar 28, 2026.','Housekeeping'),(821,'40','2026-03-09 04:19:32','1','✅ <strong>Payment Confirmed</strong><br>Your payment #103 has been verified and marked as Paid.','Payment Update'),(822,'40','2026-03-09 04:19:38','1','✅ <strong>Payment Confirmed</strong><br>Your payment #110 has been verified and marked as Paid.','Payment Update'),(823,'40','2026-03-09 04:19:49','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #51. Please go to My Reservations to sign.','Action Required'),(824,'40','2026-03-09 04:20:41','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>203</strong>.','System'),(838,'40','2026-03-09 20:49:30','1','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Car Slot 1. A fee of ₱600.00 has been added to your account.','Parking'),(839,'40','2026-03-09 20:49:36','1','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #11 has been marked as completed.','Parking'),(840,'40','2026-03-09 22:06:31','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>201</strong>.','System'),(841,'40','2026-03-10 00:28:34','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>203</strong>.','System'),(842,'40','2026-03-10 01:01:47','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>201</strong>.','System'),(843,'40','2026-03-10 03:02:19','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>1 Bed</strong>.','System'),(844,'40','2026-03-10 11:48:49','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 09, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(852,'40','2026-03-10 17:55:44','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 09, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(859,'40','2026-03-12 21:24:55','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Mar 09, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(875,'40','2026-03-13 00:39:27','1','✅ <strong>Payment Confirmed</strong><br>Your payment #123 has been verified and marked as Paid.','Payment Update'),(938,'40','2026-03-14 04:13:06','1','🔑 <strong>Key Assigned</strong><br>You have been assigned a key (ID: 15). Please keep it safe.','Key System'),(939,'40','2026-03-14 04:13:14','1','🔑 <strong>Key Returned</strong><br>Key (ID: 15) has been marked as returned.','Key System'),(1032,'40','2026-03-18 02:00:14','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #51. Please go to My Reservations to sign.','Action Required'),(1033,'90','2026-03-18 02:18:38','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1034,'90','2026-03-18 02:18:38','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 17, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1035,'90','2026-03-18 02:20:42','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1036,'90','2026-03-18 02:20:47','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #125. Please go to My Reservations to sign.','Action Required'),(1037,'90','2026-03-18 02:21:33','','✅ <strong>Payment Confirmed</strong><br>Your payment #178 has been verified and marked as Paid.','Payment Update'),(1038,'90','2026-03-18 02:44:50','','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #125 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1039,'90','2026-03-18 02:45:34','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1040,'90','2026-03-18 02:46:00','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1041,'90','2026-03-18 02:46:06','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #126. Please go to My Reservations to sign.','Action Required'),(1042,'90','2026-03-18 02:47:31','','✅ <strong>Payment Confirmed</strong><br>Your payment #179 has been verified and marked as Paid.','Payment Update'),(1043,'40','2026-03-18 11:32:20','1','🧾 <strong>New Utility Bill</strong><br>A utility bill of ₱6.24 has been generated for your room.','Billing'),(1044,'40','2026-03-18 11:32:22','1','🧾 <strong>New Utility Bill</strong><br>A utility bill of ₱6.24 has been generated for your room.','Billing'),(1045,'40','2026-03-18 11:32:24','1','🧾 <strong>New Utility Bill</strong><br>A utility bill of ₱6.24 has been generated for your room.','Billing'),(1046,'91','2026-03-18 11:42:52','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1047,'91','2026-03-18 11:42:53','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1048,'91','2026-03-18 11:44:19','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1049,'91','2026-03-18 11:44:27','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #127. Please go to My Reservations to sign.','Action Required'),(1050,'91','2026-03-18 11:45:48','','✅ <strong>Payment Confirmed</strong><br>Your payment #183 has been verified and marked as Paid.','Payment Update'),(1051,'92','2026-03-18 11:56:08','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1052,'92','2026-03-18 11:56:10','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1053,'91','2026-03-18 11:59:02','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #127. Please go to My Reservations to sign.','Action Required'),(1054,'91','2026-03-18 12:11:29','','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #127 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1055,'40','2026-03-21 16:10:01','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱6.24 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1056,'92','2026-03-21 16:10:03','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1057,'92','2026-03-21 17:32:15','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1058,'40','2026-03-21 20:28:16','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱6.24 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1059,'92','2026-03-21 20:28:16','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1060,'40','2026-03-23 01:04:31','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱6.24 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1061,'92','2026-03-23 01:04:33','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1062,'40','2026-03-23 11:57:46','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱6.24 was due on Mar 18, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1063,'92','2026-03-23 11:57:48','1','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱480.00 has been applied to your account due to overdue payment.','Billing Alert'),(1064,'92','2026-03-23 11:57:50','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 21, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1065,'40','2026-03-23 19:55:46','1','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱0.31 has been applied to your account due to overdue payment.','Billing Alert'),(1066,'40','2026-03-23 19:55:48','1','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱0.31 has been applied to your account due to overdue payment.','Billing Alert'),(1067,'40','2026-03-23 19:55:50','1','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱0.31 has been applied to your account due to overdue payment.','Billing Alert'),(1068,'92','2026-03-23 19:55:52','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 21, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1069,'92','2026-03-25 17:44:36','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 21, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1070,'92','2026-03-25 21:45:09','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 21, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1071,'92','2026-03-26 13:29:00','1','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱850.00 has been applied to your account due to overdue payment.','Billing Alert'),(1072,'93','2026-03-26 16:01:57','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1073,'93','2026-03-26 16:01:58','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1074,'93','2026-03-26 16:24:00','1','✅ <strong>Profile Update Approved</strong><br>Your profile information has been updated.','System'),(1075,'93','2026-03-26 17:04:59','1','❌ <strong>Reservation Rejected</strong><br>Your booking #130 has been cancelled. Please contact support for details.','Booking Rejected'),(1076,'93','2026-03-26 17:06:37','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1077,'93','2026-03-26 20:02:02','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1078,'94','2026-03-26 22:13:42','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1079,'94','2026-03-26 22:13:45','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1080,'95','2026-03-26 23:31:20','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1081,'95','2026-03-26 23:31:21','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1082,'95','2026-03-26 23:32:11','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1083,'95','2026-03-26 23:32:18','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #133. Please go to My Reservations to sign.','Action Required'),(1084,'93','2026-03-27 00:02:58','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1085,'96','2026-03-27 00:27:08','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1086,'96','2026-03-27 00:27:08','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1087,'96','2026-03-27 00:34:49','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1088,'96','2026-03-27 00:34:54','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #134. Please go to My Reservations to sign.','Action Required'),(1089,'96','2026-03-27 00:59:22','','✅ <strong>Profile Update Approved</strong><br>Your profile information has been updated.','System'),(1090,'97','2026-03-27 01:01:05','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1091,'97','2026-03-27 01:01:06','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1092,'97','2026-03-27 01:01:26','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1093,'93','2026-03-27 11:05:55','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1094,'94','2026-03-27 11:05:55','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1095,'95','2026-03-27 11:05:57','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1096,'96','2026-03-27 11:05:57','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1097,'97','2026-03-27 11:05:59','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1098,'93','2026-03-27 18:47:58','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1099,'94','2026-03-27 18:48:00','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1100,'95','2026-03-27 18:48:02','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1101,'96','2026-03-27 18:48:04','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1102,'97','2026-03-27 18:48:06','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1103,'93','2026-03-28 00:18:58','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1104,'94','2026-03-28 00:19:00','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1105,'95','2026-03-28 00:19:02','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1106,'96','2026-03-28 00:19:04','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1107,'97','2026-03-28 00:19:06','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1108,'92','2026-03-28 00:22:05','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1109,'92','2026-03-28 00:22:07','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱1,200.00 was due on Mar 27, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1110,'92','2026-03-28 00:22:30','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1111,'92','2026-03-28 00:22:30','1','🏁 <strong>Stay Completed</strong><br>Your stay for reservation #136 has reached its scheduled end date and is now marked as Completed. Thank you for staying with us!','Contract Ended'),(1112,'92','2026-03-28 00:22:47','1','❌ <strong>Payment Cancelled</strong><br>Your payment #185 has been cancelled.','Payment Update'),(1113,'92','2026-03-28 00:22:56','1','❌ <strong>Payment Cancelled</strong><br>Your payment #184 has been cancelled.','Payment Update'),(1114,'92','2026-03-28 00:23:05','1','❌ <strong>Payment Cancelled</strong><br>Your payment #186 has been cancelled.','Payment Update'),(1115,'92','2026-03-28 00:23:12','1','❌ <strong>Payment Cancelled</strong><br>Your payment #190 has been cancelled.','Payment Update'),(1116,'92','2026-03-28 00:23:34','1','✅ <strong>Payment Confirmed</strong><br>Your payment #197 has been verified and marked as Paid.','Payment Update'),(1117,'93','2026-03-28 12:30:10','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1118,'94','2026-03-28 12:30:12','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1119,'95','2026-03-28 12:30:13','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1120,'96','2026-03-28 12:30:14','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1121,'97','2026-03-28 12:30:15','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1122,'93','2026-03-28 18:55:45','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1123,'94','2026-03-28 18:55:47','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1124,'95','2026-03-28 18:55:49','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1125,'96','2026-03-28 18:55:51','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1126,'97','2026-03-28 18:55:53','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1127,'99','2026-03-28 22:40:49','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1128,'99','2026-03-28 22:41:16','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1129,'99','2026-03-28 22:41:17','','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>4 Beds</strong> ends on <strong>2026-03-29</strong> (1 days left). Please contact admin to renew.','Expiration Alert'),(1130,'99','2026-03-28 22:41:21','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #137. Please go to My Reservations to sign.','Action Required'),(1131,'93','2026-03-28 23:21:36','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1132,'94','2026-03-28 23:21:36','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1133,'95','2026-03-28 23:21:38','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1134,'96','2026-03-28 23:21:39','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1135,'97','2026-03-28 23:21:40','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1136,'99','2026-03-29 00:14:51','','🏁 <strong>Stay Completed</strong><br>Your stay for reservation #137 has reached its scheduled end date and is now marked as Completed. Thank you for staying with us!','Contract Ended'),(1137,'93','2026-03-30 11:57:20','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,370.97 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1138,'94','2026-03-30 11:57:22','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,900.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1139,'95','2026-03-30 11:57:24','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱9,600.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1140,'96','2026-03-30 11:57:26','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1141,'97','2026-03-30 11:57:28','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱17,000.00 was due on Mar 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1142,'99','2026-03-30 11:57:30','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱700.00 was due on Mar 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1143,'93','2026-04-06 08:28:09','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱418.55 has been applied to your account due to overdue payment.','Billing Alert'),(1144,'93','2026-04-06 08:28:11','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱480.00 has been applied to your account due to overdue payment.','Billing Alert'),(1145,'94','2026-04-06 08:28:13','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱495.00 has been applied to your account due to overdue payment.','Billing Alert'),(1146,'95','2026-04-06 08:28:15','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱480.00 has been applied to your account due to overdue payment.','Billing Alert'),(1147,'96','2026-04-06 08:28:17','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱850.00 has been applied to your account due to overdue payment.','Billing Alert'),(1148,'97','2026-04-06 08:28:19','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱850.00 has been applied to your account due to overdue payment.','Billing Alert'),(1149,'99','2026-04-06 08:28:21','','⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱35.00 has been applied to your account due to overdue payment.','Billing Alert'),(1150,'97','2026-04-06 08:47:51','','✅ <strong>Payment Confirmed</strong><br>Your payment #196 has been verified and marked as Paid.','Payment Update'),(1151,'96','2026-04-06 08:49:40','','✅ <strong>Payment Confirmed</strong><br>Your payment #195 has been verified and marked as Paid.','Payment Update'),(1152,'96','2026-04-06 08:49:48','','✅ <strong>Payment Confirmed</strong><br>Your payment #203 has been verified and marked as Paid.','Payment Update'),(1153,'103','2026-04-06 09:19:47','1','✅ <strong>Payment Confirmed</strong><br>Your payment #206 has been verified and marked as Paid.','Payment Update'),(1154,'103','2026-04-06 09:22:43','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #138. Please go to My Reservations to sign.','Action Required'),(1155,'90','2026-04-08 10:44:35','','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>1 Bed</strong> ends on <strong>2026-04-15</strong> (7 days left). Please contact admin to renew.','Expiration Alert'),(1156,'103','2026-04-08 10:55:06','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1157,'103','2026-04-08 11:05:00','1','❌ <strong>Reservation Rejected</strong><br>Your booking #139 has been cancelled. Please contact support for details.','Booking Rejected'),(1158,'103','2026-04-08 11:10:55','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1159,'103','2026-04-08 11:48:34','1','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #138 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1160,'103','2026-04-08 11:48:41','1','❌ <strong>Reservation Rejected</strong><br>Your booking #140 has been cancelled. Please contact support for details.','Booking Rejected'),(1161,'103','2026-04-08 11:50:16','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1162,'103','2026-04-08 12:13:42','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1163,'103','2026-04-08 12:13:51','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #141. Please go to My Reservations to sign.','Action Required'),(1164,'103','2026-04-08 12:13:57','1','✅ <strong>Payment Confirmed</strong><br>Your payment #214 has been verified and marked as Paid.','Payment Update'),(1165,'103','2026-04-08 12:14:32','1','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Motorcycle Slot 1. A fee of ₱1,500.00 has been added to your account.','Parking'),(1166,'103','2026-04-08 12:18:56','1','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #15 has been marked as completed.','Parking'),(1167,'103','2026-04-08 12:20:29','1','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Motorcycle Slot 1. A fee of ₱1,500.00 has been added to your account.','Parking'),(1168,'103','2026-04-08 12:20:30','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱1,500.00 was due on Apr 08, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1169,'103','2026-04-08 12:22:05','1','❌ <strong>Payment Cancelled</strong><br>Your payment #216 has been cancelled.','Payment Update'),(1170,'103','2026-04-08 12:22:09','1','❌ <strong>Payment Cancelled</strong><br>Your payment #215 has been cancelled.','Payment Update'),(1171,'103','2026-04-08 12:26:30','1','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #15 has been marked as completed.','Parking'),(1172,'103','2026-04-08 12:29:53','1','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Car Slot 4. A fee of ₱600.00 has been added to your account.','Parking'),(1173,'103','2026-04-08 12:39:09','1','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #14 has been marked as completed.','Parking'),(1174,'103','2026-04-08 12:52:05','1','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Car Slot 4. A fee of ₱600.00 has been added to your account.','Parking'),(1175,'103','2026-04-08 12:53:41','1','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #14 has been marked as completed.','Parking'),(1176,'103','2026-04-08 13:00:07','1','✅ <strong>Payment Confirmed</strong><br>Your payment #217 has been verified and marked as Paid.','Payment Update'),(1177,'103','2026-04-08 13:00:12','1','❌ <strong>Payment Cancelled</strong><br>Your payment #218 has been cancelled.','Payment Update'),(1178,'103','2026-04-08 13:00:30','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1179,'103','2026-04-08 13:00:48','1','✅ <strong>Payment Confirmed</strong><br>Your payment #219 has been verified and marked as Paid.','Payment Update'),(1180,'103','2026-04-08 13:01:56','1','🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.','Extension Approved'),(1181,'103','2026-04-08 13:02:02','1','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #141 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1182,'103','2026-04-08 13:02:35','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1183,'103','2026-04-08 13:09:25','1','✅ <strong>Payment Confirmed</strong><br>Your payment #220 has been verified and marked as Paid.','Payment Update'),(1184,'103','2026-04-08 13:13:17','1','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>1 Bed</strong> ends on <strong>2026-04-09</strong> (1 days left). Please contact admin to renew.','Expiration Alert'),(1185,'103','2026-04-08 13:13:30','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #143. Please go to My Reservations to sign.','Action Required'),(1186,'103','2026-04-10 21:01:53','1','🏁 <strong>Stay Completed</strong><br>Your stay for reservation #143 has reached its scheduled end date and is now marked as Completed. Thank you for staying with us!','Contract Ended'),(1187,'90','2026-04-10 21:01:55','','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>1 Bed</strong> ends on <strong>2026-04-15</strong> (5 days left). Please contact admin to renew.','Expiration Alert'),(1188,'90','2026-04-11 07:12:39','','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>1 Bed</strong> ends on <strong>2026-04-15</strong> (4 days left). Please contact admin to renew.','Expiration Alert'),(1189,'103','2026-04-11 11:25:16','1','❌ <strong>Reservation Rejected</strong><br>Your booking #144 has been cancelled. Please contact support for details.','Booking Rejected'),(1190,'103','2026-04-11 11:25:46','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1191,'103','2026-04-11 11:26:21','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1192,'103','2026-04-11 11:26:26','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #145. Please go to My Reservations to sign.','Action Required'),(1193,'103','2026-04-11 11:32:33','1','💸 <strong>Deposit Refunded</strong><br>Your security deposit of ₱1,000.00 has been released and credited to your account balance.','Billing'),(1194,'103','2026-04-11 11:36:16','1','💸 <strong>Deposit Refunded</strong><br>Your security deposit of ₱1,200.00 has been released and credited to your account balance.','Billing'),(1195,'103','2026-04-11 11:38:31','1','✅ <strong>Payment Confirmed</strong><br>Your payment #223 has been verified and marked as Paid.','Payment Update'),(1196,'103','2026-04-11 11:42:35','1','💸 <strong>Deposit Refunded</strong><br>Your security deposit of ₱-1,000.00 has been released and credited to your account balance.','Billing'),(1197,'103','2026-04-11 12:02:15','1','💸 <strong>Deposit Refunded</strong><br>Your security deposit of ₱1,000.00 has been released via Cash.','Billing'),(1198,'103','2026-04-11 12:02:17','1','💸 <strong>Deposit Refunded</strong><br>Your security deposit of ₱1,000.00 has been released via Cash.','Billing'),(1199,'103','2026-04-11 12:02:48','1','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #145 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1200,'103','2026-04-11 12:03:09','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1201,'103','2026-04-11 12:03:25','1','✅ <strong>Payment Confirmed</strong><br>Your payment #227 has been verified and marked as Paid.','Payment Update'),(1202,'103','2026-04-11 12:03:29','1','✅ <strong>Payment Confirmed</strong><br>Your payment #226 has been verified and marked as Paid.','Payment Update'),(1203,'103','2026-04-11 12:03:35','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1204,'103','2026-04-11 12:03:40','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #146. Please go to My Reservations to sign.','Action Required'),(1205,'103','2026-04-11 12:04:01','1','💸 <strong>Deposit Refunded</strong><br>Your security deposit of ₱1,000.00 has been released to your account wallet.','Billing'),(1206,'103','2026-04-11 12:05:18','1','💸 <strong>Deposit Refunded</strong><br>Your security deposit of ₱-1,000.00 has been released to your account wallet.','Billing'),(1207,'103','2026-04-11 12:06:51','1','💸 <strong>Deposit Refunded</strong><br>Your security deposit of ₱1,000.00 has been released to your account wallet.','Billing'),(1208,'103','2026-04-11 12:09:29','1','✅ <strong>Payment Confirmed</strong><br>Your payment #230 has been verified and marked as Paid.','Payment Update'),(1209,'103','2026-04-11 12:09:36','1','✅ <strong>Payment Confirmed</strong><br>Your payment #224 has been verified and marked as Paid.','Payment Update'),(1210,'103','2026-04-11 12:09:42','1','💸 <strong>Deposit Refunded</strong><br>Your security deposit of ₱-1,000.00 has been released to your account wallet.','Billing'),(1211,'103','2026-04-11 12:09:57','1','✅ <strong>Payment Confirmed</strong><br>Your payment #231 has been verified and marked as Paid.','Payment Update'),(1212,'103','2026-04-11 12:10:14','1','💸 <strong>Deposit Refunded</strong><br>Your security deposit of ₱1,000.00 has been released to your account wallet.','Billing'),(1213,'103','2026-04-11 12:11:06','1','❌ <strong>Payment Cancelled</strong><br>Your payment #232 has been cancelled.','Payment Update'),(1214,'103','2026-04-11 12:11:12','1','✅ <strong>Payment Confirmed</strong><br>Your payment #224 has been verified and marked as Paid.','Payment Update'),(1215,'103','2026-04-11 12:11:37','1','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #146 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1216,'103','2026-04-11 12:15:22','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1217,'103','2026-04-11 12:16:00','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1218,'103','2026-04-11 12:16:05','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #147. Please go to My Reservations to sign.','Action Required'),(1219,'103','2026-04-11 12:16:42','1','💸 <strong>Deposit Refunded</strong><br>Your security deposit of ₱1,000.00 has been released via Cash.','Billing'),(1220,'103','2026-04-11 12:22:23','1','🔄 <strong>Contract Renewed</strong><br>Your stay has been extended by 1 months. Please check your billing.','Contract Renewed'),(1221,'103','2026-04-11 12:22:28','1','✅ <strong>Payment Confirmed</strong><br>Your payment #235 has been verified and marked as Paid.','Payment Update'),(1222,'103','2026-04-11 12:23:53','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1223,'103','2026-04-11 12:28:10','1','❌ <strong>Reservation Rejected</strong><br>Your booking #148 has been cancelled. Please contact support for details.','Booking Rejected'),(1224,'103','2026-04-11 12:28:22','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1225,'103','2026-04-11 12:28:33','1','✅ <strong>Payment Confirmed</strong><br>Your payment #236 has been verified and marked as Paid.','Payment Update'),(1226,'103','2026-04-11 12:28:44','1','🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.','Extension Approved'),(1227,'103','2026-04-11 12:38:23','1','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #147 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1228,'103','2026-04-11 12:38:50','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1229,'103','2026-04-11 12:39:21','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1230,'103','2026-04-11 12:39:27','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #150. Please go to My Reservations to sign.','Action Required'),(1231,'103','2026-04-11 12:40:10','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1232,'103','2026-04-11 12:40:23','1','✅ <strong>Payment Confirmed</strong><br>Your payment #239 has been verified and marked as Paid.','Payment Update'),(1233,'103','2026-04-11 12:40:30','1','🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.','Extension Approved'),(1234,'103','2026-04-11 12:40:51','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1235,'103','2026-04-11 12:40:58','1','❌ <strong>Payment Cancelled</strong><br>Your payment #240 has been cancelled.','Payment Update'),(1236,'103','2026-04-11 12:41:05','1','❌ <strong>Reservation Rejected</strong><br>Your booking #152 has been cancelled. Please contact support for details.','Booking Rejected'),(1237,'103','2026-04-11 12:51:28','1','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Motorcycle Slot 2. A fee of ₱600.00 has been added to your account.','Parking'),(1238,'103','2026-04-11 12:51:28','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱600.00 was due on Apr 11, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1239,'103','2026-04-11 12:53:53','1','✅ <strong>Payment Confirmed</strong><br>Your payment #241 has been verified and marked as Paid.','Payment Update'),(1240,'90','2026-04-11 13:12:39','','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>1 Bed</strong> ends on <strong>2026-04-15</strong> (4 days left). Please contact admin to renew.','Expiration Alert'),(1241,'103','2026-04-11 13:57:24','1','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #150 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1242,'103','2026-04-11 14:19:02','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1243,'103','2026-04-11 14:19:42','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1244,'103','2026-04-11 14:19:51','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #153. Please go to My Reservations to sign.','Action Required'),(1245,'105','2026-04-11 18:48:41','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #154. Please go to My Reservations to sign.','Action Required'),(1246,'105','2026-04-11 18:51:52','','Hello Stephen Squad,<br><br>You requested a password reset. Your verification code is: <h2 style=\'color:#2E7D32;\'>A2D117</h2><br>This code expires in 1 hour.','Password Reset'),(1247,'105','2026-04-11 18:53:04','','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #154 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1248,'106','2026-04-11 18:57:16','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #155. Please go to My Reservations to sign.','Action Required'),(1249,'106','2026-04-11 19:09:49','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>4 Beds</strong>.','System'),(1250,'90','2026-04-11 19:13:29','','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>1 Bed</strong> ends on <strong>2026-04-15</strong> (4 days left). Please contact admin to renew.','Expiration Alert'),(1251,'106','2026-04-11 19:18:30','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>6 Beds</strong>.','System'),(1252,'106','2026-04-11 19:25:10','1','🏠 <strong>Room Returned</strong><br>You have been returned to your previous room: <strong>4 Beds</strong>.','System'),(1253,'106','2026-04-11 19:29:18','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>6 Beds</strong>.','System'),(1254,'106','2026-04-11 19:29:39','1','🏠 <strong>Room Returned</strong><br>You have been returned to your previous room: <strong>4 Beds</strong>.','System'),(1255,'106','2026-04-11 19:32:05','1','🏠 <strong>Room Returned</strong><br>You have been returned to your previous room: <strong>4 Beds</strong>.','System'),(1256,'90','2026-04-13 09:31:59','','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>1 Bed</strong> ends on <strong>2026-04-15</strong> (2 days left). Please contact admin to renew.','Expiration Alert'),(1257,'103','2026-04-13 09:34:42','1','💸 <strong>Deposit Refunded</strong><br>Your security deposit of ₱1,000.00 has been released via Cash.','Billing'),(1258,'103','2026-04-13 09:35:09','1','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #153 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1259,'103','2026-04-13 09:37:14','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1260,'103','2026-04-13 09:37:35','1','❌ <strong>Reservation Rejected</strong><br>Your booking #156 has been cancelled. Please contact support for details.','Booking Rejected'),(1261,'103','2026-04-13 09:47:22','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1262,'103','2026-04-13 09:49:43','1','✅ <strong>Payment Confirmed</strong><br>Your payment #256 has been verified and marked as Paid.','Payment Update'),(1263,'103','2026-04-13 09:49:48','1','❌ <strong>Payment Cancelled</strong><br>Your payment #255 has been cancelled.','Payment Update'),(1264,'103','2026-04-13 10:31:04','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1265,'103','2026-04-13 10:31:10','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #157. Please go to My Reservations to sign.','Action Required'),(1266,'103','2026-04-13 10:31:45','1','✅ <strong>Payment Confirmed</strong><br>Your payment #255 has been verified and marked as Paid.','Payment Update'),(1267,'103','2026-04-13 10:31:59','1','💸 <strong>Deposit Refunded</strong><br>Your security deposit of ₱1,000.00 has been released via Cash.','Billing'),(1268,'103','2026-04-13 10:32:17','1','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #157 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1269,'103','2026-04-13 10:34:51','1','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #16 has been marked as completed.','Parking'),(1270,'103','2026-04-13 10:36:28','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1271,'103','2026-04-13 10:43:24','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1272,'103','2026-04-13 10:43:58','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #158. Please go to My Reservations to sign.','Action Required');
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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_reservations`
--

LOCK TABLES `parking_reservations` WRITE;
/*!40000 ALTER TABLE `parking_reservations` DISABLE KEYS */;
INSERT INTO `parking_reservations` VALUES (11,40,11,'2026-03-09','2026-03-09',600.00,'Monthly','Completed',NULL,NULL,'2026-03-09 12:49:30'),(24,103,15,'2026-04-08','2026-04-08',1500.00,'Monthly','Completed',NULL,NULL,'2026-04-08 04:14:32'),(25,103,15,'2026-04-08','2026-04-08',1500.00,'Monthly','Completed',NULL,NULL,'2026-04-08 04:20:29'),(26,103,14,'2026-04-08','2026-04-08',600.00,'Monthly','Completed',NULL,NULL,'2026-04-08 04:29:53'),(27,103,14,'2026-04-08','2026-04-08',600.00,'Monthly','Completed',NULL,NULL,'2026-04-08 04:52:05'),(28,103,16,'2026-04-11','2026-04-13',600.00,'Monthly','Completed','St3HD','Motobi Evo 200','2026-04-11 04:51:28');
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
INSERT INTO `parking_slots` VALUES (11,'Car Slot 1','Car','Available',600.00,50.00,0),(12,'Car Slot 2','Car','Available',600.00,50.00,0),(13,'Car Slot 3','Car','Available',600.00,50.00,0),(14,'Car Slot 4','Car','Available',600.00,50.00,0),(15,'Motorcycle Slot 1','Motorcycle','Available',600.00,50.00,0),(16,'Motorcycle Slot 2','Motorcycle','Available',600.00,50.00,0),(17,'Motorcycle Slot 3','Motorcycle','Available',600.00,50.00,0),(18,'Motorcycle Slot 4','Motorcycle','Available',600.00,50.00,0),(19,'Motorcycle Slot 5','Motorcycle','Available',600.00,50.00,0),(20,'Motorcycle Slot 6','Motorcycle','Available',600.00,50.00,0),(21,'Motorcycle Slot 7','Motorcycle','Available',600.00,50.00,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=259 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (55,51,28200.00,'Cash','Paid','2026-02-28 13:54:57',NULL,NULL,'Room Payment',0,0),(103,51,600.00,'Cash','Paid','2026-03-08 20:19:32',NULL,NULL,'Monthly Parking Fee (March 2026) for Car Slot 1 (Parking ID: 2)',1,0),(110,51,30.00,'','Paid','2026-03-08 20:19:38',NULL,NULL,'Late Penalty (5%) for Payment #103',0,0),(123,51,600.00,'Cash','Paid','2026-03-12 16:39:27',NULL,NULL,'Monthly Parking Fee (March 2026) for Car Slot 1 (Parking ID: 11)',0,0),(178,125,17000.00,'Cash','Paid','2026-03-17 18:21:33',NULL,NULL,'Room Payment',0,0),(179,126,17000.00,'Cash','Paid','2026-03-17 18:47:31',NULL,NULL,'Room Payment',0,0),(180,51,6.24,'Cash','Paid','2026-04-06 01:14:08',NULL,NULL,'Utility Bill (2026-03-18) - Split 1/1',1,0),(181,51,6.24,'Cash','Paid','2026-04-06 01:14:08',NULL,NULL,'Utility Bill (2026-03-18) - Split 1/1',1,0),(182,51,6.24,'Cash','Paid','2026-04-06 01:14:08',NULL,NULL,'Utility Bill (2026-03-18) - Split 1/1',1,0),(183,127,9900.00,'Cash','Paid','2026-03-18 03:45:48',NULL,NULL,'Room Payment',0,0),(184,128,9600.00,'Cash','Cancelled','2026-03-18 03:56:08',NULL,NULL,'Room Payment',1,0),(185,129,17000.00,'Cash','Cancelled','2026-03-21 09:32:15',NULL,NULL,'Room Payment',1,0),(186,128,480.00,'','Cancelled','2026-03-23 03:57:48',NULL,NULL,'Late Penalty (5%) for Payment #184',0,0),(187,51,0.31,'Cash','Paid','2026-04-06 01:14:08',NULL,NULL,'Late Penalty (5%) for Payment #180',0,0),(188,51,0.31,'Cash','Paid','2026-04-06 01:14:08',NULL,NULL,'Late Penalty (5%) for Payment #181',0,0),(189,51,0.31,'Cash','Paid','2026-04-06 01:14:08',NULL,NULL,'Late Penalty (5%) for Payment #182',0,0),(190,129,850.00,'','Cancelled','2026-03-26 05:29:00',NULL,NULL,'Late Penalty (5%) for Payment #185',0,0),(191,130,8370.97,'GCash','Paid','2026-04-13 03:01:54','PENDING_DRAGONPAY',NULL,'Room Payment',1,0),(192,131,9600.00,'Cash','Paid','2026-04-13 03:01:54',NULL,NULL,'Room Payment',1,0),(193,132,9900.00,'Cash','Paid','2026-04-13 03:02:12',NULL,NULL,'Room Payment',1,0),(194,133,9600.00,'Cash','Paid','2026-04-13 03:00:52',NULL,NULL,'Room Payment',1,0),(195,134,17000.00,'Cash','Paid','2026-04-06 00:49:40',NULL,NULL,'Room Payment',1,0),(196,135,17000.00,'Cash','Paid','2026-04-06 00:47:51',NULL,NULL,'Room Payment',1,0),(197,136,1200.00,'Cash','Paid','2026-03-27 16:23:34',NULL,NULL,'Room Payment',0,0),(198,137,700.00,'Cash','Paid','2026-04-11 03:17:12',NULL,NULL,'Initial Booking Payment',1,0),(199,130,418.55,'','Paid','2026-04-13 03:01:54',NULL,NULL,'Late Penalty (5%) for Payment #191',0,0),(200,131,480.00,'','Paid','2026-04-13 03:01:54',NULL,NULL,'Late Penalty (5%) for Payment #192',0,0),(201,132,495.00,'','Paid','2026-04-13 03:02:12',NULL,NULL,'Late Penalty (5%) for Payment #193',0,0),(202,133,480.00,'','Paid','2026-04-13 03:00:52',NULL,NULL,'Late Penalty (5%) for Payment #194',0,0),(203,134,850.00,'','Paid','2026-04-06 00:49:48',NULL,NULL,'Late Penalty (5%) for Payment #195',0,0),(204,135,850.00,'','Unpaid','2026-04-06 00:28:19',NULL,NULL,'Late Penalty (5%) for Payment #196',0,0),(205,137,35.00,'','Paid','2026-04-11 03:17:12',NULL,NULL,'Late Penalty (5%) for Payment #198',0,0),(206,138,9900.00,'Cash','Paid','2026-04-06 01:19:47',NULL,NULL,'Walk-in Booking Payment',0,0),(207,139,4500.00,'Cash','Cancelled','2026-04-08 02:55:06',NULL,NULL,'Initial Booking Payment (Voided - Reservation Cancelled)',0,0),(208,140,4500.00,'Cash','Cancelled','2026-04-08 03:10:55',NULL,NULL,'Initial Booking Payment (Voided - Reservation Cancelled)',0,0),(209,140,4500.00,'Cash','Cancelled','2026-06-05 16:00:00',NULL,NULL,'Month 2 Rent (Voided - Reservation Cancelled)',0,0),(210,140,4500.00,'Cash','Cancelled','2026-07-05 16:00:00',NULL,NULL,'Month 3 Rent (Voided - Reservation Cancelled)',0,0),(211,140,4500.00,'Cash','Cancelled','2026-08-05 16:00:00',NULL,NULL,'Month 4 Rent (Voided - Reservation Cancelled)',0,0),(212,140,4500.00,'Cash','Cancelled','2026-09-05 16:00:00',NULL,NULL,'Month 5 Rent (Voided - Reservation Cancelled)',0,0),(213,140,4500.00,'Cash','Cancelled','2026-10-05 16:00:00',NULL,NULL,'Month 6 Rent (Voided - Reservation Cancelled)',0,0),(214,141,17000.00,'Cash','Paid','2026-04-08 04:13:57',NULL,NULL,'Initial Booking Payment',0,0),(215,141,1500.00,'Cash','Cancelled','2026-04-08 04:14:32',NULL,NULL,'Monthly Parking Fee (April 2026) for Motorcycle Slot 1 (Parking ID: 24)',0,0),(216,141,1500.00,'Cash','Cancelled','2026-04-07 16:00:00',NULL,NULL,'Monthly Parking Fee (April 2026) for Motorcycle Slot 1 (Parking ID: 25)',0,0),(217,141,600.00,'Cash','Paid','2026-04-08 05:00:07',NULL,NULL,'Monthly Parking Fee (April 2026) for Car Slot 4 (Parking ID: 26)',0,0),(218,141,600.00,'Cash','Cancelled','2026-04-07 16:00:00',NULL,NULL,'Monthly Parking Fee (April 2026) for Car Slot 4 (Parking ID: 27)',0,0),(219,141,1200.00,'Cash','Paid','2026-04-08 05:00:48',NULL,NULL,'Initial Booking Payment (Refunded)',0,0),(220,143,1200.00,'Cash','Paid','2026-04-08 05:09:25',NULL,NULL,'Initial Booking Payment',0,0),(221,145,1000.00,'Cash','Paid','2026-04-11 03:26:16','',NULL,'Security Deposit [FULL] (Refunded)',0,0),(222,145,6600.00,'Cash','Paid','2026-04-11 03:26:16','',NULL,'First Month Rent [FULL]',0,0),(223,145,-1000.00,'Cash','Paid','2026-04-11 03:38:31','',NULL,'Security Deposit Refund Credit [FULL] (Refunded)',0,0),(225,145,1000.00,'','Paid','2026-04-11 03:42:35',NULL,NULL,'Security Deposit Refund Credit (Refunded via Cash) (Refunded via Cash)',0,0),(226,146,1000.00,'Cash','Paid','2026-04-11 04:03:29','',NULL,'Security Deposit [FULL] (Refunded via Wallet)',0,0),(227,146,6600.00,'Cash','Paid','2026-04-11 04:03:25','',NULL,'First Month Rent [FULL]',0,0),(228,146,-1000.00,'','Paid','2026-04-11 04:04:01',NULL,NULL,'Security Deposit Refund Credit (Refunded via Wallet)',0,0),(229,146,1000.00,'','Paid','2026-04-11 04:05:18',NULL,NULL,'Security Deposit Refund Credit (Refunded via Wallet)',0,0),(230,146,-1000.00,'','Paid','2026-04-11 04:09:29',NULL,NULL,'Security Deposit Refund Credit (Refunded via Wallet)',0,0),(231,146,1000.00,'','Paid','2026-04-11 04:09:57',NULL,NULL,'Security Deposit Refund Credit (Refunded via Wallet)',0,0),(233,147,1000.00,'Cash','Paid','2026-04-11 04:15:47','',NULL,'Security Deposit [FULL] (Refunded via Cash)',0,0),(234,147,6600.00,'Cash','Paid','2026-04-11 04:15:47','',NULL,'First Month Rent [FULL]',0,0),(235,147,6600.00,'Cash','Paid','2026-04-11 04:22:28',NULL,NULL,'Contract Renewal (1 months)',0,0),(236,147,6600.00,'Cash','Paid','2026-04-11 04:28:33','',NULL,'Extension Rent Payment [FULL]',0,0),(237,150,1000.00,'Cash','Paid','2026-04-11 04:39:15','',NULL,'Security Deposit [FULL]',0,0),(238,150,6900.00,'Cash','Paid','2026-04-11 04:39:15','',NULL,'First Month Rent [FULL]',0,0),(239,150,6900.00,'Cash','Paid','2026-04-11 04:40:23','',NULL,'Extension Rent Payment [FULL]',0,0),(240,152,6900.00,'Cash','Cancelled','2026-04-11 04:40:51',NULL,NULL,'Extension Rent Payment',0,0),(241,150,600.00,'Cash','Paid','2026-04-11 04:53:53','',NULL,'Monthly Parking Fee (April 2026) for Motorcycle Slot 2 (Parking ID: 28) [FULL]',0,0),(242,153,1000.00,'Cash','Paid','2026-04-11 06:19:34','',NULL,'Security Deposit [FULL] (Refunded via Cash)',0,0),(243,153,6900.00,'Cash','Paid','2026-04-11 06:19:34','',NULL,'First Month Rent [FULL]',0,0),(244,154,3000.00,'Cash','Paid','2026-04-11 10:48:25',NULL,NULL,'Security Deposit',0,0),(245,154,2800.00,'Cash','Paid','2026-04-11 10:48:25',NULL,NULL,'First Month Rent',0,0),(246,155,3000.00,'Cash','Paid','2026-04-11 10:56:42',NULL,NULL,'Security Deposit',0,0),(247,155,4600.00,'Cash','Paid','2026-04-11 10:56:42',NULL,NULL,'First Month Rent',0,0),(248,156,3000.00,'Cash','Cancelled','2026-04-13 01:37:14',NULL,NULL,'Security Deposit (Voided - Reservation Cancelled)',0,0),(249,156,4200.00,'Cash','Cancelled','2026-04-13 01:37:14',NULL,NULL,'First Month Rent (Voided - Reservation Cancelled)',0,0),(250,156,4200.00,'Cash','Cancelled','2026-05-12 16:00:00',NULL,NULL,'Month 2 Rent (Voided - Reservation Cancelled)',0,0),(251,156,4200.00,'Cash','Cancelled','2026-06-12 16:00:00',NULL,NULL,'Month 3 Rent (Voided - Reservation Cancelled)',0,0),(252,156,4200.00,'Cash','Cancelled','2026-07-12 16:00:00',NULL,NULL,'Month 4 Rent (Voided - Reservation Cancelled)',0,0),(253,156,4200.00,'Cash','Cancelled','2026-08-12 16:00:00',NULL,NULL,'Month 5 Rent (Voided - Reservation Cancelled)',0,0),(254,156,4200.00,'Cash','Cancelled','2026-09-12 16:00:00',NULL,NULL,'Month 6 Rent (Voided - Reservation Cancelled)',0,0),(255,157,1000.00,'Cash','Paid','2026-04-13 02:31:45','',NULL,'Security Deposit [FULL] (Refunded via Cash)',0,0),(256,157,6900.00,'Cash','Paid','2026-04-13 01:49:43',NULL,NULL,'First Month Rent',0,0),(257,158,1000.00,'Cash','Paid','2026-04-13 02:43:34',NULL,NULL,'Security Deposit',0,0),(258,158,6600.00,'Cash','Paid','2026-04-13 02:43:34',NULL,NULL,'First Month Rent',0,0);
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
  `security_deposit` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`reservation_id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=159 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (51,40,20,'','',6,28200.00,'Approved','2026-02-26 01:57:54','2026-02-26','2026-08-26',NULL,'Any','sig_51_1773770455.png',0,NULL,1,1,NULL,NULL,NULL,NULL,0.00),(125,90,21,'','',1,17000.00,'Completed','2026-03-17 18:18:38','2026-03-17','2026-04-15',NULL,'Any','sig_125_1773771658.png',0,NULL,1,1,NULL,NULL,NULL,NULL,0.00),(126,90,21,'','',1,17000.00,'Approved','2026-03-17 18:45:34','2026-03-17','2026-04-15',NULL,'Any','sig_126_1773773175.png',0,NULL,1,1,NULL,NULL,NULL,NULL,0.00),(127,91,41,'','',1,9900.00,'Completed','2026-03-18 03:42:52','2026-03-18','2026-04-16',NULL,'Lower Bunk','sig_127_1773806486.png',0,NULL,1,1,NULL,NULL,NULL,NULL,0.00),(128,92,24,'','',1,9600.00,'Cancelled','2026-03-18 03:56:08','2026-03-18','2026-04-16','Auto-expired due to non-payment','Lower Bunk',NULL,1,NULL,0,1,NULL,NULL,NULL,NULL,0.00),(129,92,45,'','',1,17000.00,'Cancelled','2026-03-21 09:32:15','2026-03-21','2026-04-19','Auto-expired due to non-payment','Any',NULL,1,NULL,0,1,NULL,NULL,NULL,NULL,0.00),(130,93,28,'','',6,8370.97,'Cancelled','2026-03-26 08:01:57','2026-03-26','2026-09-29',NULL,'Lower Bunk',NULL,0,NULL,0,1,NULL,NULL,NULL,NULL,0.00),(131,93,24,'','',1,9600.00,'Cancelled','2026-03-26 09:06:37','2026-03-26','2026-04-24','Auto-expired due to non-payment','Lower Bunk',NULL,0,NULL,0,1,NULL,NULL,NULL,NULL,0.00),(132,94,7,'','',1,9900.00,'Cancelled','2026-03-26 14:13:42','2026-03-26','2026-04-24','Auto-expired due to non-payment','Lower Bunk',NULL,0,NULL,0,1,NULL,NULL,NULL,NULL,0.00),(133,95,6,'','',1,9600.00,'Approved','2026-03-26 15:31:20','2026-03-26','2026-04-24',NULL,'Lower Bunk','sig_133_1774539151.png',0,NULL,1,1,NULL,NULL,NULL,NULL,0.00),(134,96,23,'','',1,17000.00,'Approved','2026-03-26 16:27:08','2026-03-26','2026-04-24',NULL,'Any','sig_134_1774542910.png',0,NULL,1,1,NULL,NULL,NULL,NULL,0.00),(135,97,22,'','',1,17000.00,'Approved','2026-03-26 17:01:05','2026-03-26','2026-04-24',NULL,'Any',NULL,0,NULL,0,1,'Student','KOLEHIYO NG SUBIC','mother name','09405355552',0.00),(136,92,45,'','',1,1200.00,'Completed','2026-03-27 16:22:05','2026-03-27','2026-03-28',NULL,'Any',NULL,0,NULL,0,1,'Student','KOLEHIYO NG SUBIC','mother name','09358548575',0.00),(137,99,7,'','',1,700.00,'Completed','2026-03-28 14:40:49','2026-03-28','2026-03-29',NULL,'Lower Bunk','sig_137_1774708891.png',0,NULL,1,1,'Student','KOLEHIYO NG SUBIC','mother name','09353456734',0.00),(138,103,32,'','',1,9900.00,'Completed','2026-04-06 01:17:16','2026-04-06','2026-05-06',NULL,'Lower Bunk','sig_138_1775438782.png',1,NULL,1,1,'Student','KNS','Sandrino Martin','09898776412',0.00),(139,103,7,'','',6,27000.00,'Cancelled','2026-04-08 02:55:06','2026-05-06','2026-10-30',NULL,'Lower Bunk','sig_138_1775438782.png',1,138,0,1,'Student','KNS','0','09898776412',0.00),(140,103,7,'','',6,27000.00,'Cancelled','2026-04-08 03:10:55','2026-05-06','2026-10-30',NULL,'Lower Bunk','sig_138_1775438782.png',1,138,0,1,'Student','KNS','0','09898776412',0.00),(141,103,21,'','',2,18200.00,'Completed','2026-04-08 03:50:16','2026-04-08','2026-05-08',NULL,'2 Persons','sig_141_1775621643.png',1,NULL,1,1,'Student','KNS','Sandrino Martin','09898776412',0.00),(143,103,45,'','',1,1200.00,'Completed','2026-04-08 05:02:35','2026-04-08','2026-04-09',NULL,'Solo','sig_143_1775625222.png',1,NULL,1,1,'Student','KNS','Sandrino Martin','09898776412',0.00),(144,103,6,'','',1,7600.00,'Cancelled','2026-04-11 03:23:11','2026-04-11','2026-05-10',NULL,'Lower Bunk',NULL,1,NULL,0,1,'Student','KNS','Sandrino Martin','09898776412',0.00),(145,103,6,'','',1,7600.00,'Completed','2026-04-11 03:25:46','2026-04-11','2026-05-10',NULL,'Lower Bunk','sig_145_1775877992.png',1,NULL,1,1,'Student','KNS','Sandrino Martin','09898776412',0.00),(146,103,6,'','',1,7600.00,'Completed','2026-04-11 04:03:09','2026-04-11','2026-05-10',NULL,'Lower Bunk','sig_146_1775880227.png',1,NULL,1,1,'Student','KNS','Sandrino Martin','09898776412',0.00),(147,103,6,'','',3,20800.00,'Completed','2026-04-11 04:15:22','2026-04-11','2026-07-09',NULL,'Lower Bunk','sig_147_1775880972.png',1,NULL,1,1,'Student','KNS','Sandrino Martin','09898776412',0.00),(148,103,6,'','',1,6600.00,'Cancelled','2026-04-11 04:23:53','2026-06-10','2026-07-09',NULL,'Any','sig_147_1775880972.png',1,147,0,1,'Student','KNS','Sandrino Martin','09898776412',0.00),(150,103,7,'','',2,14800.00,'Completed','2026-04-11 04:38:50','2026-04-11','2026-06-08',NULL,'Lower Bunk','sig_150_1775882372.png',1,NULL,1,1,'Student','KNS','Sandrino Martin','09898776412',0.00),(152,103,7,'','',1,6900.00,'Cancelled','2026-04-11 04:40:51','2026-06-08','2026-07-07',NULL,'Any','sig_150_1775882372.png',1,150,0,1,'Student','KNS','Sandrino Martin','09898776412',0.00),(153,103,7,'','',1,7900.00,'Completed','2026-04-11 06:19:02','2026-04-11','2026-05-10',NULL,'Lower Bunk','sig_153_1775888400.png',1,NULL,1,1,'Student','KNS','Sandrino Martin','09898776412',1000.00),(154,105,26,'','',6,5800.00,'Completed','2026-04-11 10:48:25','2026-04-11','2026-10-11',NULL,'Lower Bunk',NULL,0,NULL,1,1,'Student','Kolehiyo Ng Subic','Vic Sotto','09787543534',3000.00),(155,106,74,'','',6,7600.00,'Approved','2026-04-11 10:56:42','2026-04-11','2026-10-11',NULL,'Any','sig_155_1775905042.png',0,NULL,1,1,'Student','Kolehiyo Ng Subic','Vic Sotto','09997645124',3000.00),(156,103,6,'','',6,28200.00,'Cancelled','2026-04-13 01:37:14','2026-04-13','2026-09-29',NULL,'Lower Bunk',NULL,1,NULL,0,1,'Student','KNS','Sandrino Martin','09898776412',3000.00),(157,103,7,'','',1,7900.00,'Completed','2026-04-13 01:47:22','2026-04-13','2026-05-12',NULL,'Lower Bunk','sig_157_1776047476.png',1,NULL,1,1,'Student','KNS','Sandrino Martin','09898776412',1000.00),(158,103,6,'','',1,7600.00,'Approved','2026-04-13 02:36:28','2026-04-13','2026-05-12',NULL,'Lower Bunk','sig_158_1776048243.png',0,NULL,1,1,'Student','KNS','Sandrino Martin','09898776412',1000.00);
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `residents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `residents`
--

LOCK TABLES `residents` WRITE;
/*!40000 ALTER TABLE `residents` DISABLE KEYS */;
INSERT INTO `residents` VALUES (1,97,'Jomari','Lapeciros','',NULL,'Jomari@gmail.com','09850646646','Male','Student','KOLEHIYO NG SUBIC',NULL,'mother name','09405355552',NULL,'1774544465_school_FB_IMG_1737467323891.jpg','user',0,0,0,'2026-03-26 17:01:26'),(2,92,'bugoygirl','bugoy','',NULL,'testuser1@gmail.com','09435673450','Female','Student','KOLEHIYO NG SUBIC',NULL,'mother name','09358548575',NULL,'1773806168_school_614346928_33198446529802608_1565591098433792128_n.jpg','user',0,0,0,'2026-03-27 16:22:30'),(3,99,'dhenzel','tayson','',NULL,'fredhenzeltayson1@gmail.com','09242748742','Male','Student','KOLEHIYO NG SUBIC',NULL,'mother name','09353456734',NULL,'1774708849_school_88cf2736-019f-471d-8b01-ba282e144f93 (1).jfif','user',0,0,0,'2026-03-28 14:41:16'),(4,103,'Stephen','Squad','','','stephen@gmail.com','09837217312','Male','Student','KNS','San Isidro','Sandrino Martin','09898776412',NULL,'1775438236_sid_da3424d0-de1d-4043-98d2-85c9052f357b.jpg','user',1,0,0,'2026-04-08 04:13:42');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_transfers`
--

LOCK TABLES `room_transfers` WRITE;
/*!40000 ALTER TABLE `room_transfers` DISABLE KEYS */;
INSERT INTO `room_transfers` VALUES (1,155,74,6,'2026-04-11 19:18:30','Returned',1,NULL),(2,155,74,6,'2026-04-11 19:29:18','Returned',1,'2026-04-11 19:32:05');
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
) ENGINE=InnoDB AUTO_INCREMENT=954 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'hero_image','[\"1770471778_hero_edit.png\",\"1770447312_hero_edit.png\",\"1772369513_hero.png\",\"1770447047_hero.png\"]'),(125,'living_area_image','living_area_1770486291.jpg'),(126,'last_update','1776049343'),(290,'price_single','14000'),(291,'price_4bed_upper','6300'),(292,'price_4bed_lower','6900'),(293,'price_6bed_upper','5999'),(294,'price_6bed_lower','6600'),(303,'price_4bed_whole','26400'),(306,'price_6bed_whole','37797'),(315,'price_single_long','13000'),(319,'price_4bed_upper_long','4000'),(320,'price_4bed_lower_long','4500'),(321,'price_4bed_whole_long','17000'),(325,'price_6bed_upper_long','3500'),(326,'price_6bed_lower_long','4200'),(327,'price_6bed_whole_long','24000'),(548,'room_type_order','[\"Single\",\"4-Bed\",\"6-Bed\"]'),(681,'theme_primary','#34b875'),(682,'theme_dark','#1b5e20'),(683,'theme_accent','#ffb700'),(687,'migration_fix_dupe_rooms_v2','1'),(688,'migration_cleanup_v3','1'),(848,'login_bg','login_bg_1774085356.jpg'),(894,'migration_parking_rates_v1','1'),(929,'price_housekeeping_standard','400'),(939,'price_maintenance_standard','400');
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
  `suffix` varchar(10) DEFAULT NULL,
  `newsletter` tinyint(1) DEFAULT 1,
  `bio` text DEFAULT NULL,
  `social_link` varchar(255) DEFAULT NULL,
  `other_request_feature` tinyint(1) DEFAULT NULL,
  `change_password_feature` tinyint(1) DEFAULT NULL,
  `change_email_feature` tinyint(1) DEFAULT NULL,
  `delete_account_feature` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (40,'bartjavillonar@gmail.com','09304871699',NULL,'$2y$10$X9kKW5UpTXSviWVagyKhAuXEzk2SiwS4GxxgjIjikUt22qpKOwho2','','2026-02-26 01:57:54',0,0,'Male',NULL,NULL,'Employed','','',NULL,'','',NULL,0,1,'JAVILLONAR','BARTOLOME','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(90,'FRED@gmail.com','09435638478',NULL,'$2y$10$iUmw.165jLGP7fcS3nYyiumK8niQbNHzBljo33lb6WeoJnnlP6aNS','user','2026-03-17 18:17:13',0,0,'Male',NULL,NULL,NULL,NULL,'KOLEHIYO NG SUBIC','1773771518_school_614346928_33198446529802608_1565591098433792128_n.jpg',NULL,NULL,NULL,0,1,'TAYSON','FRED','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(91,'Angelene@gmail.com','09425673493',NULL,'$2y$10$2rYri8FwRKFYY3VV0I3k.OzdnruTmQi2k3YnlKGkdknn/uf3IM.Wa','user','2026-03-18 03:37:49',0,0,'Female',NULL,NULL,NULL,NULL,'KOLEHIYO NG SUBIC','1773805372_school_434612699_2697344013763217_6695140230318829305_n.jpg',NULL,NULL,NULL,0,0,'banate','Angelene','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(92,'testuser1@gmail.com','09435673450',NULL,'$2y$10$l6LziTTpI3wnbFLV.jb0EOHjmX6c1rzpcfssZibUPZ1MuaEIqweD.','user','2026-03-18 03:51:47',0,0,'Female',NULL,NULL,NULL,NULL,'KOLEHIYO NG SUBIC','1773806168_school_614346928_33198446529802608_1565591098433792128_n.jpg',NULL,NULL,NULL,0,0,'bugoy','bugoygirl','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(93,'keysha@gmail.com','09234623546',NULL,'$2y$10$.AneKyHxaVJ4F99TDjiqG.3hxZfH9V8v8waWwQrWUuEYnPo0yGmfS','user','2026-03-26 06:24:43',0,0,'Female',NULL,NULL,'Employed','bicol','',NULL,'puregold','09235432546',NULL,0,0,'sarto','keysha','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(94,'testuser2@gmail.com','09234287467',NULL,'$2y$10$wdh33NGQ3p4GvIQ.UQvsEOEefEWFBWLAuS8m1boX13wXU1VqXa78u','user','2026-03-26 14:12:35',0,0,'Male',NULL,NULL,NULL,NULL,'KOLEHIYO NG SUBIC','1774534422_school_Plants, vepoware, art, 2160x3840 wallpaper.jpg',NULL,NULL,NULL,0,0,'Viloria','Nicole','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(95,'testuser3@gmail.com','09345345678',NULL,'$2y$10$w9U6HbY8zTll4ah3rq4E6.hGhLYyLvESPy0wQov7r9WO0IPL004J2','user','2026-03-26 15:24:00',0,0,'Male',NULL,NULL,NULL,NULL,'KOLEHIYO NG SUBIC','1774539080_school_Plants, vepoware, art, 2160x3840 wallpaper.jpg',NULL,NULL,NULL,0,0,'santiago','Michael','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(96,'johnrasing@gmail.com','09573465734',NULL,'$2y$10$MZSIIUfE4Xf3EPy.uLlP4.xJsV2tBoukseu5MtnIkYBjm09Id1Fnu','user','2026-03-26 16:24:23',0,0,'Male',NULL,NULL,'Student','ddd','KOLEHIYO NG SUBIC','1774544334_school_266ecb72-025b-41ac-ab1d-dbb74101fd8a.jfif','mother name','09234567897',NULL,0,0,'Rasing','john ben','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(97,'Jomari@gmail.com','09850646646',NULL,'$2y$10$Co0u6Z2qxzCrEkMuS2U52OvbW5FTbI8TbIjhDU96xrMGwZxc0IpYu','user','2026-03-26 17:00:21',0,0,'Male',NULL,NULL,NULL,NULL,'KOLEHIYO NG SUBIC','1774544465_school_FB_IMG_1737467323891.jpg',NULL,NULL,NULL,0,1,'Lapeciros','Jomari','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(98,'bryanvalencia1@gmail.com','09345654685',NULL,'$2y$10$3Ohzdgbdw6CrUjr8Wz8GcunK12RpIvwBqS6Web/6THfMx2j3g4.Qe','user','2026-03-27 16:26:32',1,0,'Male',NULL,NULL,'Student',NULL,'kolehiyo ng subic','1774628791_sid_Plants, vepoware, art, 2160x3840 wallpaper.jpg','mother name','09645867546',NULL,1,0,'Ablao','bryan','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(99,'fredhenzeltayson1@gmail.com','09242748742',NULL,'$2y$10$7rwnz/oMga7qQZ9ZgniIt.cd4292cGMtYiMDP/Sp0nOlVq0U6xHdG','user','2026-03-28 14:40:05',0,0,'Male',NULL,NULL,NULL,NULL,'KOLEHIYO NG SUBIC','1774708849_school_88cf2736-019f-471d-8b01-ba282e144f93 (1).jfif',NULL,NULL,NULL,0,0,'tayson','dhenzel','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(100,'testuser4@gmail.com','09458643582',NULL,'$2y$10$Fx8Hdy0Yxsj1R99BebOdyugNgjae.N/wMGkWbKXIWaIdN19JqnPOK','user','2026-03-30 04:05:33',0,0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'test','ing','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(101,'begoza@gmail.com','09234234762',NULL,'$2y$10$q0wOzSzcbbiKJIRJJ2iSUOtrHs/XUJKf0xQSxraBkmWl.B3jmIJBK','user','2026-03-30 04:27:26',0,0,'Male',NULL,NULL,'Employed','sawmill','',NULL,'kns','09934594385',NULL,1,0,'Begosa','Stephen Zhane','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(102,'cocomartin123@gmail.com','09318236153',NULL,'$2y$10$87FyE4BlT8x1horGiFoLEuLm7FuQmehZbtCScjHrTF1D.Y7N1m56W','user','2026-03-30 04:51:48',1,0,'Male',NULL,NULL,'Student','San Isidro','KNS','1774846307_sid_99b79f7f-06d2-4fcf-ae61-c5911a0397f4.jpg','Sandrino Martin','09381237615',NULL,1,0,'MARTIN','COCO','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(103,'stephen@gmail.com','09837217312',NULL,'$2y$10$Ncf6cyzDuE4I6X6TREklhek3sfK0jSQ4EG4PyJXIfIAy8SBlCmE1O','user','2026-04-06 01:17:16',0,0,'Male',NULL,NULL,'Student','San Isidro','KNS','1775438236_sid_da3424d0-de1d-4043-98d2-85c9052f357b.jpg','Sandrino Martin','09898776412',NULL,1,1,'Squad','Stephen','','',1,NULL,NULL,NULL,NULL,NULL,NULL),(104,'tysonicrosini@gmail.com','09831237123',NULL,'$2y$10$32IuPZvG1UaGThPX2OWmqutVnf8ZJSkM.s.jNJmokdykwklMfgeiu','user','2026-04-08 03:56:51',1,0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'Squad','Stephen','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(105,'stephen123@gmail.com','09876632142',NULL,'$2y$10$4S/htOkwgoBDQ3zw0e/tme7DqNa0Ys/mOzRr4iKRLTNnZ2.tdyMue','user','2026-04-11 10:48:25',1,0,'Male','A2D117','2026-04-11 13:51:52','Student','San Isidro, San Isidro, Subic, Zambales, Region III (Central Luzon)','Kolehiyo Ng Subic',NULL,'Vic Sotto','09787543534',NULL,1,0,'Squad','Stephen','B','',1,NULL,NULL,NULL,NULL,NULL,NULL),(106,'stephen101@gmail.com','09887471241',NULL,'$2y$10$TxnZkn3sjbsUdL0KG6xVDe/qGXK0ah88miS08L5iutDWm1AwbJiQS','user','2026-04-11 10:56:42',0,0,'Male',NULL,NULL,'Student','San Isidro, San Isidro, Subic, Zambales, Region III (Central Luzon)','Kolehiyo Ng Subic',NULL,'Vic Sotto','09997645124',NULL,1,0,'Begosa','Stephen','B','',1,NULL,NULL,NULL,NULL,NULL,NULL),(107,'stephenpogi@gmail.com','09997312646',NULL,'$2y$10$dd7betn03AsyYtoCQ9AGSetT95S6HXASha6ZDdWX8Vvpqwy4YdbAy','user','2026-04-11 11:59:11',0,0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'Begosa','Stephen Zhane','Banaag','',1,NULL,NULL,NULL,NULL,NULL,NULL);
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
INSERT INTO `withdrawal_requests` VALUES (1,103,1100.00,'Jenny Angel Q.','09662285702','Pending','2026-04-11 04:08:49',NULL,NULL);
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

-- Dump completed on 2026-04-13 15:31:58
