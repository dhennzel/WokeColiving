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
) ENGINE=InnoDB AUTO_INCREMENT=299 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (89,38,'Account Created','Walk-in account created by Admin','2026-02-26 01:25:30'),(92,40,'Account Created','Walk-in account created by Admin','2026-02-26 01:57:54'),(93,40,'Walk-in Booking','Reservation #51 created by Admin','2026-02-26 01:57:54'),(108,40,'Payment Confirmed','Payment #55 marked as Paid by Admin.','2026-02-28 13:54:57'),(182,50,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-03-01 04:13:01'),(183,50,'Reservation Verifying','Reservation #71 moved to Verifying status.','2026-03-01 04:13:17'),(184,50,'Payment Confirmed','Payment #84 marked as Paid by Admin.','2026-03-01 04:13:23'),(185,50,'Lease Signed','Reservation #71','2026-03-01 04:13:32'),(186,50,'Reservation Approved','Reservation #71 approved.','2026-03-01 04:13:44'),(187,50,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-03-01 05:00:51'),(188,50,'Reservation Rejected','Reservation #72 has been cancelled.','2026-03-01 05:01:04'),(189,50,'Payment Confirmed','Payment #85 marked as Paid by Admin.','2026-03-01 05:01:12'),(190,50,'Contract Ended','Reservation #71 marked as Completed by admin.','2026-03-01 05:01:54'),(191,50,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-03-01 05:02:21'),(192,50,'Payment Submitted','Reservation #73 via Cash','2026-03-01 05:02:26'),(193,50,'Reservation Verifying','Reservation #73 moved to Verifying status.','2026-03-01 05:02:33'),(194,50,'Lease Signed','Reservation #73','2026-03-01 05:03:06'),(195,50,'Payment Confirmed','Payment #86 marked as Paid by Admin.','2026-03-01 05:03:13'),(196,50,'Reservation Approved','Reservation #73 approved.','2026-03-01 05:03:17'),(197,50,'Lease Signed','Reservation #73','2026-03-01 05:04:44'),(198,50,'Reservation Extended','Room: 6-Bed | Status: Pending','2026-03-01 05:05:05'),(199,50,'Payment Submitted','Reservation #74 via Cash','2026-03-01 05:05:09'),(200,50,'Reservation Verifying','Reservation #74 moved to Verifying status.','2026-03-01 05:05:14'),(201,50,'Reservation Extended','Contract #73 updated.','2026-03-01 05:05:17'),(202,50,'Payment Confirmed','Payment #87 marked as Paid by Admin.','2026-03-01 05:05:23'),(216,50,'Profile Updated','Admin updated user details.','2026-03-01 12:28:55'),(217,40,'Profile Updated','Admin updated user details.','2026-03-01 12:29:35'),(218,40,'Profile Updated','Admin updated user details.','2026-03-01 12:36:44'),(219,51,'Account Created','Walk-in account created by Admin','2026-03-01 12:38:56'),(220,51,'Walk-in Booking','Reservation #75 created by Admin','2026-03-01 12:38:56'),(221,51,'Profile Updated','Admin updated user details.','2026-03-01 12:39:58'),(222,51,'Lease Signed','Reservation #75','2026-03-01 12:53:18'),(223,51,'Payment Confirmed','Payment #88 marked as Paid by Admin.','2026-03-01 12:53:35'),(224,51,'Profile Updated','Admin updated user details.','2026-03-01 12:54:10'),(225,51,'Profile Updated','Admin updated user details.','2026-03-01 12:54:25'),(226,50,'Profile Updated','Admin updated user details.','2026-03-02 06:46:57'),(227,51,'Profile Update Approved','Admin approved profile changes.','2026-03-02 06:47:34'),(228,50,'Profile Update Approved','Admin approved profile changes.','2026-03-02 06:52:10'),(229,50,'Profile Updated','Admin updated user details.','2026-03-02 06:57:49'),(230,51,'Profile Updated','Admin updated user details.','2026-03-02 07:13:56'),(231,50,'Profile Updated','Admin updated user details.','2026-03-02 07:18:58'),(232,40,'Profile Updated','Admin updated user details.','2026-03-02 07:19:43'),(233,52,'Account Created','Walk-in account created by Admin','2026-03-02 07:27:14'),(234,52,'Walk-in Booking','Reservation #76 created by Admin','2026-03-02 07:27:14'),(235,52,'Payment Confirmed','Payment #89 marked as Paid by Admin.','2026-03-02 07:29:35'),(236,53,'Account Created','Walk-in account created by Admin','2026-03-02 07:37:20'),(237,53,'Walk-in Booking','Reservation #77 created by Admin','2026-03-02 07:37:20'),(238,53,'Payment Confirmed','Payment #90 marked as Paid by Admin.','2026-03-02 07:38:01'),(239,53,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-03-02 07:39:15'),(240,53,'Payment Submitted','Reservation #78 via Cash','2026-03-02 07:39:18'),(241,53,'Reservation Verifying','Reservation #78 moved to Verifying status.','2026-03-02 07:39:24'),(242,53,'Lease Signed','Reservation #78','2026-03-02 07:39:42'),(243,53,'Payment Confirmed','Payment #91 marked as Paid by Admin.','2026-03-02 07:39:48'),(244,53,'Reservation Extended','Contract #77 updated.','2026-03-02 07:39:53'),(248,55,'Account Created','Walk-in account created by Admin','2026-03-02 07:43:08'),(249,55,'Walk-in Booking','Reservation #80 created by Admin','2026-03-02 07:43:08'),(250,53,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-03-02 07:44:11'),(251,53,'Payment Submitted','Reservation #81 via Cash','2026-03-02 07:44:14'),(252,53,'Payment Confirmed','Payment #94 marked as Paid by Admin.','2026-03-02 07:44:20'),(253,53,'Reservation Verifying','Reservation #81 moved to Verifying status.','2026-03-02 07:44:25'),(254,53,'Lease Signed','Reservation #81','2026-03-02 07:44:43'),(255,53,'Reservation Extended','Contract #77 updated.','2026-03-02 07:44:47'),(256,53,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-03-02 07:49:10'),(257,53,'Reservation Verifying','Reservation #82 moved to Verifying status.','2026-03-02 07:49:16'),(258,53,'Payment Confirmed','Payment #95 marked as Paid by Admin.','2026-03-02 07:49:22'),(259,53,'Lease Signed','Reservation #82','2026-03-02 07:49:37'),(260,53,'Reservation Extended','Contract #77 updated.','2026-03-02 07:49:41'),(261,52,'Profile Updated','Admin updated user details.','2026-03-02 07:51:31'),(262,53,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-03-02 07:55:37'),(263,53,'Reservation Verifying','Reservation #83 moved to Verifying status.','2026-03-02 07:55:41'),(264,53,'Payment Confirmed','Payment #96 marked as Paid by Admin.','2026-03-02 07:55:46'),(265,53,'Lease Signed','Reservation #83','2026-03-02 07:56:00'),(266,53,'Reservation Extended','Contract #77 updated.','2026-03-02 07:56:06'),(267,53,'Signature Requested','Admin requested signature for Reservation #77','2026-03-02 07:56:29'),(268,53,'Signature Requested','Admin requested signature for Reservation #77','2026-03-02 07:56:39'),(269,53,'Signature Requested','Admin requested signature for Reservation #77','2026-03-02 07:58:55'),(270,53,'Lease Signed','Reservation #77','2026-03-02 07:59:05'),(271,52,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-03-02 08:00:27'),(272,52,'Payment Submitted','Reservation #84 via Cash','2026-03-02 08:00:30'),(273,52,'Reservation Verifying','Reservation #84 moved to Verifying status.','2026-03-02 08:00:36'),(274,52,'Payment Confirmed','Payment #97 marked as Paid by Admin.','2026-03-02 08:00:41'),(275,52,'Lease Signed','Reservation #84','2026-03-02 08:00:56'),(276,52,'Reservation Extended','Contract #76 updated.','2026-03-02 08:01:00'),(277,52,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-03-02 08:05:55'),(278,52,'Reservation Verifying','Reservation #85 moved to Verifying status.','2026-03-02 08:06:01'),(279,52,'Payment Confirmed','Payment #98 marked as Paid by Admin.','2026-03-02 08:06:07'),(280,52,'Lease Signed','Reservation #85','2026-03-02 08:06:20'),(281,52,'Reservation Extended','Contract #76 updated.','2026-03-02 08:06:27'),(282,52,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-03-02 08:08:18'),(283,52,'Payment Confirmed','Payment #99 marked as Paid by Admin.','2026-03-02 08:08:24'),(284,52,'Reservation Verifying','Reservation #86 moved to Verifying status.','2026-03-02 08:08:28'),(285,52,'Lease Signed','Reservation #86','2026-03-02 08:08:42'),(286,52,'Reservation Extended','Contract #76 updated.','2026-03-02 08:08:50'),(287,52,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-03-02 08:12:36'),(288,52,'Reservation Verifying','Reservation #87 moved to Verifying status.','2026-03-02 08:12:40'),(289,52,'Payment Confirmed','Payment #100 marked as Paid by Admin.','2026-03-02 08:12:46'),(290,52,'Signature Requested','Admin requested signature for Reservation #87 from receipt view.','2026-03-02 08:13:04'),(291,52,'Lease Signed','Reservation #87','2026-03-02 08:13:15'),(292,52,'Reservation Extended','Contract #76 updated.','2026-03-02 08:13:29'),(293,52,'Reservation Extended','Room: 4-Bed | Status: Pending','2026-03-02 08:15:56'),(294,52,'Reservation Verifying','Reservation #88 moved to Verifying status.','2026-03-02 08:16:03'),(295,52,'Payment Confirmed','Payment #101 marked as Paid by Admin.','2026-03-02 08:16:08'),(296,52,'Signature Requested','Admin requested signature for Reservation #88 from receipt view.','2026-03-02 08:16:16'),(297,52,'Lease Signed','Reservation #88','2026-03-02 08:16:26'),(298,52,'Reservation Extended','Contract #76 updated.','2026-03-02 08:16:46');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_password_history`
--

LOCK TABLES `admin_password_history` WRITE;
/*!40000 ALTER TABLE `admin_password_history` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `housekeeping_requests`
--

LOCK TABLES `housekeeping_requests` WRITE;
/*!40000 ALTER TABLE `housekeeping_requests` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=700 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (504,'40','2026-02-26 09:57:57','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(513,'40','2026-02-26 21:31:42','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(526,'40','2026-02-27 15:28:35','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(532,'40','2026-02-28 21:46:01','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱28,200.00 was due on Feb 26, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(536,'40','2026-02-28 21:54:57','','✅ <strong>Payment Confirmed</strong><br>Your payment #55 has been verified and marked as Paid.','Payment Update'),(613,'50','2026-03-01 12:12:59','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(614,'50','2026-03-01 12:13:00','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱560.00 was due on Mar 01, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(615,'50','2026-03-01 12:13:17','1','🔍 <strong>Reservation Verifying</strong><br>Your booking #71 is now being verified. Please ensure payment and lease signing are completed.','Booking Update'),(616,'50','2026-03-01 12:13:23','1','✅ <strong>Payment Confirmed</strong><br>Your payment #84 has been verified and marked as Paid.','Payment Update'),(617,'50','2026-03-01 12:13:44','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(618,'50','2026-03-01 12:13:45','1','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>4 Beds</strong> ends on <strong>2026-03-05</strong> (4 days left). Please contact admin to renew.','Expiration Alert'),(619,'50','2026-03-01 13:00:49','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(620,'50','2026-03-01 13:01:04','1','❌ <strong>Reservation Rejected</strong><br>Your booking #72 has been cancelled. Please contact support for details.','Booking Rejected'),(621,'50','2026-03-01 13:01:12','1','✅ <strong>Payment Confirmed</strong><br>Your payment #85 has been verified and marked as Paid.','Payment Update'),(622,'50','2026-03-01 13:01:54','1','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #71 has been marked as completed. Thank you for staying with us!','Contract Ended'),(623,'50','2026-03-01 13:02:19','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(624,'50','2026-03-01 13:02:33','1','🔍 <strong>Reservation Verifying</strong><br>Your booking #73 is now being verified. Please ensure payment and lease signing are completed.','Booking Update'),(625,'50','2026-03-01 13:03:13','1','✅ <strong>Payment Confirmed</strong><br>Your payment #86 has been verified and marked as Paid.','Payment Update'),(626,'50','2026-03-01 13:03:17','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(627,'50','2026-03-01 13:05:03','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(628,'50','2026-03-01 13:05:14','1','🔍 <strong>Reservation Verifying</strong><br>Your booking #74 is now being verified. Please ensure payment and lease signing are completed.','Booking Update'),(629,'50','2026-03-01 13:05:17','1','🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.','Extension Approved'),(630,'50','2026-03-01 13:05:23','1','✅ <strong>Payment Confirmed</strong><br>Your payment #87 has been verified and marked as Paid.','Payment Update'),(646,'51','2026-03-01 20:39:00','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱14,000.00 was due on Mar 01, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(647,'51','2026-03-01 20:53:35','1','✅ <strong>Payment Confirmed</strong><br>Your payment #88 has been verified and marked as Paid.','Payment Update'),(648,'51','2026-03-02 14:47:34','','✅ <strong>Profile Update Approved</strong><br>Your profile information has been updated.','System'),(649,'50','2026-03-02 14:52:10','1','✅ <strong>Profile Update Approved</strong><br>Your profile information has been updated.','System'),(650,'52','2026-03-02 15:27:16','1','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>4 Beds</strong> ends on <strong>2026-03-03</strong> (1 days left). Please contact admin to renew.','Expiration Alert'),(651,'52','2026-03-02 15:27:36','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱156.67 was due on Mar 02, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(652,'52','2026-03-02 15:29:35','1','✅ <strong>Payment Confirmed</strong><br>Your payment #89 has been verified and marked as Paid.','Payment Update'),(653,'53','2026-03-02 15:37:24','1','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>4 Beds</strong> ends on <strong>2026-03-05</strong> (3 days left). Please contact admin to renew.','Expiration Alert'),(654,'53','2026-03-02 15:37:26','1','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱420.00 was due on Mar 02, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(655,'53','2026-03-02 15:38:01','1','✅ <strong>Payment Confirmed</strong><br>Your payment #90 has been verified and marked as Paid.','Payment Update'),(656,'53','2026-03-02 15:39:13','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(657,'53','2026-03-02 15:39:24','','🔍 <strong>Reservation Verifying</strong><br>Your booking #78 is now being verified. Please ensure payment and lease signing are completed.','Booking Update'),(658,'53','2026-03-02 15:39:48','','✅ <strong>Payment Confirmed</strong><br>Your payment #91 has been verified and marked as Paid.','Payment Update'),(659,'53','2026-03-02 15:39:53','','🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.','Extension Approved'),(662,'55','2026-03-02 15:43:09','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱14,000.00 was due on Mar 02, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(663,'53','2026-03-02 15:44:09','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(664,'53','2026-03-02 15:44:20','','✅ <strong>Payment Confirmed</strong><br>Your payment #94 has been verified and marked as Paid.','Payment Update'),(665,'53','2026-03-02 15:44:25','','🔍 <strong>Reservation Verifying</strong><br>Your booking #81 is now being verified. Please ensure payment and lease signing are completed.','Booking Update'),(666,'53','2026-03-02 15:44:47','','🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.','Extension Approved'),(667,'53','2026-03-02 15:49:08','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(668,'53','2026-03-02 15:49:16','','🔍 <strong>Reservation Verifying</strong><br>Your booking #82 is now being verified. Please ensure payment and lease signing are completed.','Booking Update'),(669,'53','2026-03-02 15:49:22','','✅ <strong>Payment Confirmed</strong><br>Your payment #95 has been verified and marked as Paid.','Payment Update'),(670,'53','2026-03-02 15:49:41','','🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.','Extension Approved'),(671,'53','2026-03-02 15:55:35','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(672,'53','2026-03-02 15:55:41','','🔍 <strong>Reservation Verifying</strong><br>Your booking #83 is now being verified. Please ensure payment and lease signing are completed.','Booking Update'),(673,'53','2026-03-02 15:55:46','','✅ <strong>Payment Confirmed</strong><br>Your payment #96 has been verified and marked as Paid.','Payment Update'),(674,'53','2026-03-02 15:56:06','','🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.','Extension Approved'),(675,'53','2026-03-02 15:56:27','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #77. Please go to My Reservations to sign.','Action Required'),(676,'53','2026-03-02 15:56:37','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #77. Please go to My Reservations to sign.','Action Required'),(677,'53','2026-03-02 15:58:53','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #77. Please go to My Reservations to sign.','Action Required'),(678,'52','2026-03-02 16:00:25','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(679,'52','2026-03-02 16:00:36','1','🔍 <strong>Reservation Verifying</strong><br>Your booking #84 is now being verified. Please ensure payment and lease signing are completed.','Booking Update'),(680,'52','2026-03-02 16:00:41','1','✅ <strong>Payment Confirmed</strong><br>Your payment #97 has been verified and marked as Paid.','Payment Update'),(681,'52','2026-03-02 16:01:00','1','🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.','Extension Approved'),(682,'52','2026-03-02 16:05:53','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(683,'52','2026-03-02 16:06:01','','🔍 <strong>Reservation Verifying</strong><br>Your booking #85 is now being verified. Please ensure payment and lease signing are completed.','Booking Update'),(684,'52','2026-03-02 16:06:07','','✅ <strong>Payment Confirmed</strong><br>Your payment #98 has been verified and marked as Paid.','Payment Update'),(685,'52','2026-03-02 16:06:27','','🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.','Extension Approved'),(686,'52','2026-03-02 16:08:16','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(687,'52','2026-03-02 16:08:24','','✅ <strong>Payment Confirmed</strong><br>Your payment #99 has been verified and marked as Paid.','Payment Update'),(688,'52','2026-03-02 16:08:28','','🔍 <strong>Reservation Verifying</strong><br>Your booking #86 is now being verified. Please ensure payment and lease signing are completed.','Booking Update'),(689,'52','2026-03-02 16:08:50','','🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.','Extension Approved'),(690,'52','2026-03-02 16:12:34','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(691,'52','2026-03-02 16:12:40','','🔍 <strong>Reservation Verifying</strong><br>Your booking #87 is now being verified. Please ensure payment and lease signing are completed.','Booking Update'),(692,'52','2026-03-02 16:12:46','','✅ <strong>Payment Confirmed</strong><br>Your payment #100 has been verified and marked as Paid.','Payment Update'),(693,'52','2026-03-02 16:13:02','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #87. Please go to My Reservations to sign.','Action Required'),(694,'52','2026-03-02 16:13:29','','🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.','Extension Approved'),(695,'52','2026-03-02 16:15:54','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(696,'52','2026-03-02 16:16:03','','🔍 <strong>Reservation Verifying</strong><br>Your booking #88 is now being verified. Please ensure payment and lease signing are completed.','Booking Update'),(697,'52','2026-03-02 16:16:08','','✅ <strong>Payment Confirmed</strong><br>Your payment #101 has been verified and marked as Paid.','Payment Update'),(698,'52','2026-03-02 16:16:14','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #88. Please go to My Reservations to sign.','Action Required'),(699,'52','2026-03-02 16:16:46','','🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.','Extension Approved');
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
  `description` varchar(255) DEFAULT 'Room Payment',
  `is_penalized` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`payment_id`),
  KEY `reservation_id` (`reservation_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (55,51,28200.00,'Cash','Paid','2026-02-28 13:54:57',NULL,NULL,'Room Payment',0),(84,71,560.00,'Cash','Paid','2026-03-01 04:13:23',NULL,NULL,'Room Payment',0),(85,72,4700.00,'Cash','Paid','2026-03-01 05:01:12',NULL,NULL,'Room Payment',0),(86,73,4500.00,'Cash','Paid','2026-03-01 05:03:13','',NULL,'Room Payment',0),(87,73,3750.00,'Cash','Paid','2026-03-01 05:05:23','',NULL,'Room Payment',0),(88,75,14000.00,'Cash','Paid','2026-03-01 12:53:35',NULL,NULL,'Room Payment',0),(89,76,156.67,'Cash','Paid','2026-03-02 07:29:35',NULL,NULL,'Room Payment',0),(90,77,420.00,'Cash','Paid','2026-03-02 07:38:01',NULL,NULL,'Room Payment',0),(91,77,4700.00,'Cash','Paid','2026-03-02 07:39:48','',NULL,'Room Payment',0),(93,80,14000.00,'Cash','Unpaid','2026-03-02 07:43:08',NULL,NULL,'Room Payment',0),(94,77,156.67,'Cash','Paid','2026-03-02 07:44:20','',NULL,'Room Payment',0),(95,77,156.67,'Cash','Paid','2026-03-02 07:49:22',NULL,NULL,'Room Payment',0),(96,77,156.67,'Cash','Paid','2026-03-02 07:55:46',NULL,NULL,'Room Payment',0),(97,76,156.67,'Cash','Paid','2026-03-02 08:00:41','',NULL,'Room Payment',0),(98,76,156.67,'Cash','Paid','2026-03-02 08:06:07',NULL,NULL,'Room Payment',0),(99,76,156.67,'Cash','Paid','2026-03-02 08:08:24',NULL,NULL,'Room Payment',0),(100,76,156.67,'Cash','Paid','2026-03-02 08:12:46',NULL,NULL,'Room Payment',0),(101,76,156.67,'Cash','Paid','2026-03-02 08:16:08',NULL,NULL,'Room Payment',0);
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
  `is_archived` varchar(256) NOT NULL,
  `extended_from` int(11) DEFAULT NULL,
  `signature_required` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`reservation_id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (51,40,7,'','',6,28200.00,'Approved','2026-02-26 01:57:54','2026-02-26','2026-08-26',NULL,'Lower Bunk',NULL,'',NULL,0),(71,50,7,'','',1,560.00,'Completed','2026-03-01 04:12:59','2026-03-01','2026-03-05',NULL,'Upper Bunk','sig_71_1772338412.png','',NULL,0),(72,50,7,'','',1,4700.00,'Cancelled','2026-03-01 05:00:49','2026-03-05','2026-04-05',NULL,'Any',NULL,'1',71,0),(73,50,6,'','',2,8250.00,'Approved','2026-03-01 05:02:19','2026-03-01','2026-05-01',NULL,'Lower Bunk','sig_73_1772341484.png','',NULL,0),(75,51,8,'','',1,14000.00,'Approved','2026-03-01 12:38:56','2026-03-01','2026-04-01',NULL,'Any','sig_75_1772369598.png','',NULL,0),(76,52,7,'','',6,940.02,'Approved','2026-03-02 07:27:14','2026-03-02','2026-03-08',NULL,'Lower Bunk',NULL,'',NULL,0),(77,53,7,'','',5,5590.01,'Approved','2026-03-02 07:37:20','2026-03-02','2026-04-08',NULL,'Upper Bunk','sig_77_1772438345.png','',NULL,1),(80,55,8,'','',1,14000.00,'Approved','2026-03-02 07:43:08','2026-03-02','2026-04-02',NULL,'Any',NULL,'',NULL,0);
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
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (6,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,'301'),(7,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,'302'),(8,'1 Bed','Single',14000.00,6,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,3,'303'),(16,'203','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,2,NULL),(17,'204','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,2,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=267 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'hero_image','[\"1770471778_hero_edit.png\",\"1770447312_hero_edit.png\",\"1770447047_hero.png\",\"1772369513_hero.png\"]'),(125,'living_area_image','living_area_1770486291.jpg'),(126,'last_update','1772439365');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_update_requests`
--

LOCK TABLES `user_update_requests` WRITE;
/*!40000 ALTER TABLE `user_update_requests` DISABLE KEYS */;
INSERT INTO `user_update_requests` VALUES (1,51,'Male','Student','KNS','San Isidro, Subic, Zambales','Alvin Rasing','092651752762',NULL,'Approved','2026-03-02 06:45:55'),(2,50,'Male','Student','','San Isidro','Jim','0931872322',NULL,'Approved','2026-03-02 06:52:03');
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
  `role` enum('guest','admin') DEFAULT 'guest',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` varchar(256) NOT NULL,
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
  `is_walkin` varchar(256) NOT NULL,
  `night_mode` tinyint(1) DEFAULT 0,
  `last_name` varchar(256) NOT NULL,
  `first_name` varchar(256) NOT NULL,
  `middle_name` varchar(256) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (12,'nicle@gmail.com','09673101356',NULL,'$2y$10$KNndQy2NC0daPij1yMSbgeokMHXVHITVG1.1v4IGBZlpxad9OLsZy','guest','2026-02-05 12:23:10','',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',0,'','',''),(21,'alvino@gmail.com','097672634',NULL,'$2y$10$RYf9TDuYiF6.vkf3PHmHpOKM5AtOXTn/hGhmbx6ZFt2F/Tofc7Ima','guest','2026-02-07 17:18:08','',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',0,'','',''),(32,'testuser1@gmail.com','09673101356',NULL,'$2y$10$TXpUcsJcuoLs2Lhs4dIG.eVh15446oQC1OSOzv6bW6SF2Ds2zb/WK','guest','2026-02-23 14:50:11','',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',0,'','',''),(33,'bernasuncion11@gmail.com','09673101356',NULL,'$2y$10$zN11hFsbNiQLFcS9eX.6meFbz9NRjGxy1ZRPR8zBq1Vo1LVk98MV6','guest','2026-02-23 15:08:49','',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',0,'','',''),(35,'testuser17@gmail.com','09673101356',NULL,'$2y$10$zs0r27pl7mVaTK8DK.9i7elLYNMLPp00dJWIhISFmVYGmltzuMhNy','guest','2026-02-24 04:23:28','',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',0,'','',''),(36,'testuser18@gmail.com','09673101356',NULL,'$2y$10$y.gUdpOLGAr3pSnulxPyD.4.vUqBbMsIfqGJ8AdmonuDVwfXdSIVm','guest','2026-02-24 04:40:39','',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',0,'','',''),(37,'testuser101@gmail.com','09673101356',NULL,'$2y$10$rl9sLnd.AaN35QgySvxeduryju4SBn0EUnckps2zPoYQkeCaQ7A/O','guest','2026-02-26 01:17:22','',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',0,'','',''),(38,'Mamon@gmail.com','0928173123',NULL,'$2y$10$Qckb77kGLpHvJJ3wS.ppuOwqH7sye.78CIA1hGORX9piCZs1u3c5O','','2026-02-26 01:25:30','',0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',0,'','',''),(40,'bartjavillonar@gmail.com','09304871699',NULL,'$2y$10$X9kKW5UpTXSviWVagyKhAuXEzk2SiwS4GxxgjIjikUt22qpKOwho2','','2026-02-26 01:57:54','',0,'Male',NULL,NULL,'Employed','','',NULL,'','',NULL,'',0,'JAVILLONAR','BARTOLOME',''),(50,'tysonicrosini@gmail.com','09627344445',NULL,'$2y$10$YKuIeAQyvrlaqeQRNfek3.ZPnQx5Qn0VHzpQ60qWL1ae3G.ZN5gzK','guest','2026-03-01 04:11:53','',0,'Male',NULL,NULL,'Student','San Isidro','','1772341503_school_99b79f7f-06d2-4fcf-ae61-c5911a0397f4.jpg','Jim','0931872322','user_50_1772338462.jpg','',1,'TYSONI','CROSINI','M.'),(51,'stephen@gmail.com','09662285259',NULL,'$2y$10$FSi7SGMK6POe0ZyN0c6nD.jIrV8vtShwd/ylz49oByGIIT6uRYHey','','2026-03-01 12:38:56','',0,'Male',NULL,NULL,'Student','San Isidro, Subic, Zambales','KNS',NULL,'Alvin Rasing','092651752762',NULL,'1',1,'BEGOSA','STEPHEN ZHANE','B.'),(52,'nonoy@gmail.com','0936162565',NULL,'$2y$10$HNMksAELviPmQfTVn8zF9Oz.IopeFftOH6K8vsS37CFUNwMpKqW/2','','2026-03-02 07:27:14','',0,'Male',NULL,NULL,'Student','Quezon City, Manila','','1772439354_school_images.webp','Vic Sotto','093867162',NULL,'1',1,'Zuñiga','Nonoy',''),(53,'marcosison@gmail.com','09126351623',NULL,'$2y$10$622qblraCO8dTwnqTb8AEOYgcs9eVZ5gkionMfg8VkERBlcToiDaa','','2026-03-02 07:37:20','',0,'Male',NULL,NULL,'Employed','Subic, Zambales','VIVA MUSIC',NULL,'emeldapapin','0987788332',NULL,'1',1,'SISON','MARCO',''),(55,'cocomartin@gmail.com','097312632',NULL,'$2y$10$nM2vTccIPbPybmDJ3QD31OGI9kRR8r3wOeZEmLNOJw2nFMX1NJJj.','','2026-03-02 07:43:08','',0,'Male',NULL,NULL,NULL,NULL,NULL,NULL,'Sandrino Martin','096456345',NULL,'1',0,'MARTIN','COCO','');
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `waitlist`
--

LOCK TABLES `waitlist` WRITE;
/*!40000 ALTER TABLE `waitlist` DISABLE KEYS */;
INSERT INTO `waitlist` VALUES (2,50,'4-Bed','2026-03-01 04:12:36');
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

-- Dump completed on 2026-03-02 16:17:49
