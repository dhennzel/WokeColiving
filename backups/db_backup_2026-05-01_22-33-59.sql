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
) ENGINE=InnoDB AUTO_INCREMENT=1252 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (89,38,'Account Created','Walk-in account created by Admin','2026-02-26 01:25:30','System','System'),(656,98,'Account Created','Walk-in account created by Super Admin','2026-03-27 16:26:32','Diane Tayson (Super Admin)','Super Admin'),(1058,127,'Reservation Submitted','Room: Single | Status: Pending','2026-04-27 16:25:55','Diane Tayson (Super Admin)','Super Admin'),(1059,128,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-04-27 16:27:39','Diane Tayson (Super Admin)','Super Admin'),(1060,129,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-04-27 16:32:19','Diane Tayson (Super Admin)','Super Admin'),(1061,130,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-04-27 16:35:53','Diane Tayson (Super Admin)','Super Admin'),(1062,131,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-04-27 16:40:09','Diane Tayson (Super Admin)','Super Admin'),(1064,133,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-27 16:48:21','Diane Tayson (Super Admin)','Super Admin'),(1066,135,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-27 16:53:21','Diane Tayson (Super Admin)','Super Admin'),(1069,138,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-27 16:59:35','Diane Tayson (Super Admin)','Super Admin'),(1070,139,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-27 17:01:47','Diane Tayson (Super Admin)','Super Admin'),(1071,140,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-27 17:03:41','Diane Tayson (Super Admin)','Super Admin'),(1072,141,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-27 17:05:41','Diane Tayson (Super Admin)','Super Admin'),(1073,142,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-28 03:22:36','Diane Tayson (Super Admin)','Super Admin'),(1074,143,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-28 03:26:26','Diane Tayson (Super Admin)','Super Admin'),(1076,145,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-28 03:31:33','Diane Tayson (Super Admin)','Super Admin'),(1077,146,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-28 04:25:59','Diane Tayson (Super Admin)','Super Admin'),(1078,147,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-28 04:29:57','Diane Tayson (Super Admin)','Super Admin'),(1080,149,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-28 04:41:16','Diane Tayson (Super Admin)','Super Admin'),(1081,150,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-28 04:42:42','Diane Tayson (Super Admin)','Super Admin'),(1082,142,'Payment Submitted','Reservation #206 via Cash for: First Month Rent, Security Deposit','2026-04-28 04:43:36','Diane Tayson (Super Admin)','Super Admin'),(1083,142,'Reservation Approved','Reservation #206 approved by Super Admin.','2026-04-28 04:44:47','Diane Tayson (Super Admin)','Super Admin'),(1084,142,'Signature Requested','Signature requested for Reservation #206 by Super Admin','2026-04-28 04:44:50','Diane Tayson (Super Admin)','Super Admin'),(1085,142,'Lease Signed','Reservation #206','2026-04-28 04:44:57','Diane Tayson (Super Admin)','Super Admin'),(1086,142,'Payment Submitted','Reservation #206 via Cash for: First Month Rent, Security Deposit','2026-04-28 04:48:08','Diane Tayson (Super Admin)','Super Admin'),(1087,142,'Payment Confirmed','Payment #457 marked as Paid by Super Admin.','2026-04-28 04:48:27','Diane Tayson (Super Admin)','Super Admin'),(1088,142,'Payment Confirmed','Payment #458 marked as Paid by Super Admin.','2026-04-28 04:48:29','Diane Tayson (Super Admin)','Super Admin'),(1089,128,'Payment Submitted','Reservation #192 via Cash for: First Month Rent, Security Deposit','2026-04-28 05:03:24','Diane Tayson (Super Admin)','Super Admin'),(1090,128,'Payment Confirmed','Payment #369 marked as Paid by Super Admin.','2026-04-28 05:03:31','Diane Tayson (Super Admin)','Super Admin'),(1091,128,'Payment Confirmed','Payment #370 marked as Paid by Super Admin.','2026-04-28 05:03:32','Diane Tayson (Super Admin)','Super Admin'),(1092,128,'Reservation Approved','Reservation #192 approved by Super Admin.','2026-04-28 05:03:50','Diane Tayson (Super Admin)','Super Admin'),(1093,128,'Signature Requested','Signature requested for Reservation #192 by Super Admin','2026-04-28 05:03:52','Diane Tayson (Super Admin)','Super Admin'),(1094,128,'Signature Requested','Signature requested for Reservation #192 by Super Admin','2026-04-28 05:03:55','Diane Tayson (Super Admin)','Super Admin'),(1095,128,'Lease Signed','Reservation #192','2026-04-28 05:04:03','Diane Tayson (Super Admin)','Super Admin'),(1098,151,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-28 05:08:30','Diane Tayson (Super Admin)','Super Admin'),(1099,151,'Payment Submitted','Reservation #215 via Cash for: Security Deposit, First Month Rent','2026-04-28 05:08:34','Diane Tayson (Super Admin)','Super Admin'),(1100,151,'Reservation Approved','Reservation #215 approved by Super Admin.','2026-04-28 05:09:04','Diane Tayson (Super Admin)','Super Admin'),(1101,151,'Signature Requested','Signature requested for Reservation #215 by Super Admin','2026-04-28 05:09:07','Diane Tayson (Super Admin)','Super Admin'),(1102,151,'Signature Requested','Signature requested for Reservation #215 by Super Admin','2026-04-28 05:09:11','Diane Tayson (Super Admin)','Super Admin'),(1103,151,'Lease Signed','Reservation #215','2026-04-28 05:09:16','Diane Tayson (Super Admin)','Super Admin'),(1104,151,'Payment Submitted','Reservation #215 via Cash for: First Month Rent, Security Deposit','2026-04-28 05:09:19','Diane Tayson (Super Admin)','Super Admin'),(1105,151,'Payment Confirmed','Payment #520 marked as Paid by Super Admin.','2026-04-28 05:09:28','Diane Tayson (Super Admin)','Super Admin'),(1106,151,'Payment Confirmed','Payment #521 marked as Paid by Super Admin.','2026-04-28 05:09:31','Diane Tayson (Super Admin)','Super Admin'),(1107,138,'Payment Submitted','Reservation #202 via Cash for: First Month Rent, Security Deposit','2026-04-28 05:15:54','Diane Tayson (Super Admin)','Super Admin'),(1108,138,'Payment Confirmed','Payment #439 marked as Paid by Super Admin.','2026-04-28 05:16:03','Diane Tayson (Super Admin)','Super Admin'),(1109,138,'Payment Confirmed','Payment #440 marked as Paid by Super Admin.','2026-04-28 05:16:05','Diane Tayson (Super Admin)','Super Admin'),(1110,138,'Reservation Approved','Reservation #202 approved by Super Admin.','2026-04-28 05:16:16','Diane Tayson (Super Admin)','Super Admin'),(1111,138,'Signature Requested','Signature requested for Reservation #202 by Super Admin','2026-04-28 05:19:04','Diane Tayson (Super Admin)','Super Admin'),(1112,138,'Lease Signed','Reservation #202','2026-04-28 05:19:12','Diane Tayson (Super Admin)','Super Admin'),(1113,138,'Lease Signed','Reservation #202','2026-04-28 05:19:20','Diane Tayson (Super Admin)','Super Admin'),(1114,130,'Payment Submitted','Reservation #194 via Cash for: First Month Rent, Security Deposit','2026-04-28 05:26:49','Diane Tayson (Super Admin)','Super Admin'),(1115,130,'Payment Confirmed','Payment #383 marked as Paid by Super Admin.','2026-04-28 05:27:02','Diane Tayson (Super Admin)','Super Admin'),(1116,130,'Payment Confirmed','Payment #384 marked as Paid by Super Admin.','2026-04-28 05:27:04','Diane Tayson (Super Admin)','Super Admin'),(1117,130,'Reservation Approved','Reservation #194 approved by Super Admin.','2026-04-28 05:27:13','Diane Tayson (Super Admin)','Super Admin'),(1118,130,'Signature Requested','Signature requested for Reservation #194 by Super Admin','2026-04-28 05:27:15','Diane Tayson (Super Admin)','Super Admin'),(1119,130,'Signature Requested','Signature requested for Reservation #194 by Super Admin','2026-04-28 05:27:17','Diane Tayson (Super Admin)','Super Admin'),(1120,130,'Lease Signed','Reservation #194','2026-04-28 05:27:23','Diane Tayson (Super Admin)','Super Admin'),(1121,133,'Payment Submitted','Reservation #197 via Cash for: First Month Rent, Security Deposit','2026-04-28 05:28:40','Diane Tayson (Super Admin)','Super Admin'),(1122,133,'Payment Confirmed','Payment #404 marked as Paid by Super Admin.','2026-04-28 05:29:21','Diane Tayson (Super Admin)','Super Admin'),(1123,133,'Payment Confirmed','Payment #405 marked as Paid by Super Admin.','2026-04-28 05:29:24','Diane Tayson (Super Admin)','Super Admin'),(1124,133,'Reservation Approved','Reservation #197 approved by Super Admin.','2026-04-28 05:29:43','Diane Tayson (Super Admin)','Super Admin'),(1125,133,'Signature Requested','Signature requested for Reservation #197 by Super Admin','2026-04-28 05:29:45','Diane Tayson (Super Admin)','Super Admin'),(1126,133,'Signature Requested','Signature requested for Reservation #197 by Super Admin','2026-04-28 05:29:47','Diane Tayson (Super Admin)','Super Admin'),(1127,133,'Lease Signed','Reservation #197','2026-04-28 05:29:54','Diane Tayson (Super Admin)','Super Admin'),(1128,129,'Payment Submitted','Reservation #193 via Cash for: First Month Rent, Security Deposit','2026-04-28 05:37:28','Diane Tayson (Super Admin)','Super Admin'),(1129,129,'Payment Confirmed','Payment #376 marked as Paid by Super Admin.','2026-04-28 06:13:16','Diane Tayson (Super Admin)','Super Admin'),(1130,129,'Payment Confirmed','Payment #377 marked as Paid by Super Admin.','2026-04-28 06:13:18','Diane Tayson (Super Admin)','Super Admin'),(1131,129,'Reservation Approved','Reservation #193 approved by Super Admin.','2026-04-28 06:13:33','Diane Tayson (Super Admin)','Super Admin'),(1132,129,'Signature Requested','Signature requested for Reservation #193 by Super Admin','2026-04-28 06:13:36','Diane Tayson (Super Admin)','Super Admin'),(1133,129,'Lease Signed','Reservation #193','2026-04-28 06:13:44','Diane Tayson (Super Admin)','Super Admin'),(1134,127,'Payment Submitted','Reservation #191 via Cash for: First Month Rent, Security Deposit','2026-04-28 06:18:47','Diane Tayson (Super Admin)','Super Admin'),(1135,127,'Payment Confirmed','Payment #362 marked as Paid by Super Admin.','2026-04-28 06:18:53','Diane Tayson (Super Admin)','Super Admin'),(1136,127,'Payment Confirmed','Payment #363 marked as Paid by Super Admin.','2026-04-28 06:18:55','Diane Tayson (Super Admin)','Super Admin'),(1137,145,'Payment Submitted','Reservation #209 via Cash for: First Month Rent, Security Deposit','2026-04-28 06:20:01','Diane Tayson (Super Admin)','Super Admin'),(1138,145,'Payment Confirmed','Payment #478 marked as Paid by Super Admin.','2026-04-28 06:20:25','Diane Tayson (Super Admin)','Super Admin'),(1139,145,'Payment Confirmed','Payment #479 marked as Paid by Super Admin.','2026-04-28 06:20:27','Diane Tayson (Super Admin)','Super Admin'),(1140,145,'Reservation Approved','Reservation #209 approved by Super Admin.','2026-04-28 06:23:57','Diane Tayson (Super Admin)','Super Admin'),(1141,145,'Lease Signed','Reservation #209','2026-04-28 06:26:01','Diane Tayson (Super Admin)','Super Admin'),(1142,150,'Payment Submitted','Reservation #214 via Cash for: First Month Rent, Security Deposit','2026-04-28 06:33:32','Diane Tayson (Super Admin)','Super Admin'),(1143,150,'Payment Confirmed','Payment #513 marked as Paid by Super Admin.','2026-04-28 06:33:42','Diane Tayson (Super Admin)','Super Admin'),(1144,150,'Payment Confirmed','Payment #514 marked as Paid by Super Admin.','2026-04-28 06:33:43','Diane Tayson (Super Admin)','Super Admin'),(1145,150,'Reservation Approved','Reservation #214 approved by Super Admin.','2026-04-28 06:34:19','Diane Tayson (Super Admin)','Super Admin'),(1146,150,'Signature Requested','Signature requested for Reservation #214 by Super Admin','2026-04-28 06:34:22','Diane Tayson (Super Admin)','Super Admin'),(1147,150,'Lease Signed','Reservation #214','2026-04-28 06:34:34','Diane Tayson (Super Admin)','Super Admin'),(1148,150,'Signature Requested','Signature requested for Reservation #214 by Super Admin','2026-04-28 06:34:40','Diane Tayson (Super Admin)','Super Admin'),(1149,139,'Payment Submitted','Reservation #203 via Cash for: First Month Rent, Security Deposit','2026-04-28 06:35:27','Diane Tayson (Super Admin)','Super Admin'),(1150,139,'Payment Confirmed','Payment #441 marked as Paid by Super Admin.','2026-04-28 06:35:34','Diane Tayson (Super Admin)','Super Admin'),(1151,139,'Payment Confirmed','Payment #442 marked as Paid by Super Admin.','2026-04-28 06:35:36','Diane Tayson (Super Admin)','Super Admin'),(1152,139,'Reservation Approved','Reservation #203 approved by Super Admin.','2026-04-28 06:35:53','Diane Tayson (Super Admin)','Super Admin'),(1153,139,'Signature Requested','Signature requested for Reservation #203 by Super Admin','2026-04-28 06:35:58','Diane Tayson (Super Admin)','Super Admin'),(1154,139,'Lease Signed','Reservation #203','2026-04-28 06:36:15','Diane Tayson (Super Admin)','Super Admin'),(1155,146,'Payment Submitted','Reservation #210 via Cash for: First Month Rent, Security Deposit','2026-04-28 06:38:40','Diane Tayson (Super Admin)','Super Admin'),(1156,146,'Payment Confirmed','Payment #485 marked as Paid by Super Admin.','2026-04-28 06:38:52','Diane Tayson (Super Admin)','Super Admin'),(1157,146,'Payment Confirmed','Payment #486 marked as Paid by Super Admin.','2026-04-28 06:38:54','Diane Tayson (Super Admin)','Super Admin'),(1158,146,'Reservation Approved','Reservation #210 approved by Super Admin.','2026-04-28 06:40:36','Diane Tayson (Super Admin)','Super Admin'),(1159,146,'Signature Requested','Signature requested for Reservation #210 by Super Admin','2026-04-28 06:40:39','Diane Tayson (Super Admin)','Super Admin'),(1160,147,'Payment Submitted','Reservation #211 via Cash for: First Month Rent, Security Deposit','2026-04-28 07:16:40','Diane Tayson (Super Admin)','Super Admin'),(1161,147,'Payment Submitted','Reservation #211 via Cash for: First Month Rent, Security Deposit','2026-04-28 07:16:44','Diane Tayson (Super Admin)','Super Admin'),(1162,147,'Payment Confirmed','Payment #492 marked as Paid by Super Admin.','2026-04-28 07:17:13','Diane Tayson (Super Admin)','Super Admin'),(1163,147,'Payment Confirmed','Payment #493 marked as Paid by Super Admin.','2026-04-28 07:17:15','Diane Tayson (Super Admin)','Super Admin'),(1164,147,'Reservation Approved','Reservation #211 approved by Super Admin.','2026-04-28 07:18:59','Diane Tayson (Super Admin)','Super Admin'),(1165,147,'Signature Requested','Signature requested for Reservation #211 by Super Admin','2026-04-28 07:19:03','Diane Tayson (Super Admin)','Super Admin'),(1166,147,'Lease Signed','Reservation #211','2026-04-28 07:19:08','Diane Tayson (Super Admin)','Super Admin'),(1167,131,'Payment Submitted','Reservation #195 via Cash for: First Month Rent, Security Deposit','2026-04-28 07:19:46','Diane Tayson (Super Admin)','Super Admin'),(1168,131,'Payment Confirmed','Payment #390 marked as Paid by Super Admin.','2026-04-28 07:19:59','Diane Tayson (Super Admin)','Super Admin'),(1169,131,'Payment Confirmed','Payment #391 marked as Paid by Super Admin.','2026-04-28 07:20:01','Diane Tayson (Super Admin)','Super Admin'),(1170,131,'Reservation Approved','Reservation #195 approved by Super Admin.','2026-04-28 07:20:18','Diane Tayson (Super Admin)','Super Admin'),(1171,131,'Signature Requested','Signature requested for Reservation #195 by Super Admin','2026-04-28 07:20:19','Diane Tayson (Super Admin)','Super Admin'),(1172,131,'Signature Requested','Signature requested for Reservation #195 by Super Admin','2026-04-28 07:20:55','Diane Tayson (Super Admin)','Super Admin'),(1173,131,'Signature Requested','Signature requested for Reservation #195 by Super Admin','2026-04-28 07:20:58','Diane Tayson (Super Admin)','Super Admin'),(1174,131,'Lease Signed','Reservation #195','2026-04-28 07:21:03','Diane Tayson (Super Admin)','Super Admin'),(1175,143,'Payment Submitted','Reservation #207 via Cash for: First Month Rent, Security Deposit','2026-04-28 07:49:03','Diane Tayson (Super Admin)','Super Admin'),(1176,143,'Payment Confirmed','Payment #464 marked as Paid by Super Admin.','2026-04-28 07:49:14','Diane Tayson (Super Admin)','Super Admin'),(1177,143,'Payment Confirmed','Payment #465 marked as Paid by Super Admin.','2026-04-28 07:49:16','Diane Tayson (Super Admin)','Super Admin'),(1178,143,'Reservation Approved','Reservation #207 approved by Super Admin.','2026-04-28 07:49:31','Diane Tayson (Super Admin)','Super Admin'),(1179,143,'Signature Requested','Signature requested for Reservation #207 by Super Admin','2026-04-28 07:49:33','Diane Tayson (Super Admin)','Super Admin'),(1180,143,'Lease Signed','Reservation #207','2026-04-28 07:49:39','Diane Tayson (Super Admin)','Super Admin'),(1182,127,'Reservation Approved','Reservation #191 approved by Super Admin.','2026-04-28 07:50:12','Diane Tayson (Super Admin)','Super Admin'),(1183,141,'Payment Submitted','Reservation #205 via Cash for: First Month Rent, Security Deposit','2026-04-28 08:05:03','Diane Tayson (Super Admin)','Super Admin'),(1184,141,'Payment Confirmed','Payment #455 marked as Paid by Super Admin.','2026-04-28 08:05:12','Diane Tayson (Super Admin)','Super Admin'),(1185,141,'Payment Confirmed','Payment #456 marked as Paid by Super Admin.','2026-04-28 08:05:14','Diane Tayson (Super Admin)','Super Admin'),(1186,141,'Reservation Approved','Reservation #205 approved by Super Admin.','2026-04-28 08:05:57','Diane Tayson (Super Admin)','Super Admin'),(1187,141,'Signature Requested','Signature requested for Reservation #205 by Super Admin','2026-04-28 08:06:01','Diane Tayson (Super Admin)','Super Admin'),(1188,141,'Lease Signed','Reservation #205','2026-04-28 08:06:07','Diane Tayson (Super Admin)','Super Admin'),(1189,135,'Payment Submitted','Reservation #199 via Cash for: First Month Rent, Security Deposit','2026-04-28 08:06:47','Diane Tayson (Super Admin)','Super Admin'),(1190,135,'Payment Confirmed','Payment #418 marked as Paid by Super Admin.','2026-04-28 08:06:55','Diane Tayson (Super Admin)','Super Admin'),(1191,135,'Payment Confirmed','Payment #419 marked as Paid by Super Admin.','2026-04-28 08:06:56','Diane Tayson (Super Admin)','Super Admin'),(1192,135,'Reservation Approved','Reservation #199 approved by Super Admin.','2026-04-28 08:07:12','Diane Tayson (Super Admin)','Super Admin'),(1193,135,'Signature Requested','Signature requested for Reservation #199 by Super Admin','2026-04-28 08:07:15','Diane Tayson (Super Admin)','Super Admin'),(1194,135,'Lease Signed','Reservation #199','2026-04-28 08:07:20','Diane Tayson (Super Admin)','Super Admin'),(1195,149,'Payment Submitted','Reservation #213 via Cash for: First Month Rent, Security Deposit','2026-04-28 08:08:11','Diane Tayson (Super Admin)','Super Admin'),(1196,149,'Reservation Approved','Reservation #213 approved by Super Admin.','2026-04-28 08:08:51','Diane Tayson (Super Admin)','Super Admin'),(1197,149,'Signature Requested','Signature requested for Reservation #213 by Super Admin','2026-04-28 08:08:54','Diane Tayson (Super Admin)','Super Admin'),(1198,149,'Lease Signed','Reservation #213','2026-04-28 08:09:05','Diane Tayson (Super Admin)','Super Admin'),(1199,149,'Payment Confirmed','Payment #506 marked as Paid by Super Admin.','2026-04-28 08:09:14','Diane Tayson (Super Admin)','Super Admin'),(1200,149,'Payment Confirmed','Payment #507 marked as Paid by Super Admin.','2026-04-28 08:09:16','Diane Tayson (Super Admin)','Super Admin'),(1201,140,'Payment Submitted','Reservation #204 via Cash for: First Month Rent, Security Deposit','2026-04-28 08:09:45','Diane Tayson (Super Admin)','Super Admin'),(1202,140,'Payment Confirmed','Payment #448 marked as Paid by Super Admin.','2026-04-28 08:09:53','Diane Tayson (Super Admin)','Super Admin'),(1203,140,'Payment Confirmed','Payment #449 marked as Paid by Super Admin.','2026-04-28 08:09:55','Diane Tayson (Super Admin)','Super Admin'),(1204,140,'Reservation Approved','Reservation #204 approved by Super Admin.','2026-04-28 08:10:10','Diane Tayson (Super Admin)','Super Admin'),(1205,140,'Signature Requested','Signature requested for Reservation #204 by Super Admin','2026-04-28 08:10:13','Diane Tayson (Super Admin)','Super Admin'),(1206,140,'Signature Requested','Signature requested for Reservation #204 by Super Admin','2026-04-28 08:10:15','Diane Tayson (Super Admin)','Super Admin'),(1207,140,'Lease Signed','Reservation #204','2026-04-28 08:10:19','Diane Tayson (Super Admin)','Super Admin'),(1211,152,'Account Created','Walk-in account created by Super Admin','2026-04-28 11:55:30','Diane Tayson (Super Admin)','Super Admin'),(1212,152,'Walk-in Booking','Reservation #216 created by Super Admin','2026-04-28 11:55:31','Diane Tayson (Super Admin)','Super Admin'),(1213,152,'Signature Requested','Signature requested for Reservation #216 by Super Admin from receipt view.','2026-04-28 11:55:34','Diane Tayson (Super Admin)','Super Admin'),(1214,153,'Restored from Companion','User was previously a companion of Tayson, Fredhenzel.','2026-04-28 11:57:07','Diane Tayson (Super Admin)','Super Admin'),(1215,154,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-29 02:36:24','User','User'),(1216,154,'Payment Submitted','Reservation #217 via GCash for: Daily Stay Payment','2026-04-29 02:37:19','User','User'),(1217,154,'Payment Confirmed','Payment #529 marked as Paid by Super Admin.','2026-04-29 02:38:51','Diane Tayson (Super Admin)','Super Admin'),(1218,154,'Reservation Approved','Reservation #217 approved by Super Admin.','2026-04-29 02:39:04','Diane Tayson (Super Admin)','Super Admin'),(1219,154,'Signature Requested','Signature requested for Reservation #217 by Super Admin','2026-04-29 02:39:17','Diane Tayson (Super Admin)','Super Admin'),(1220,154,'Lease Signed','Reservation #217','2026-04-29 02:39:45','Diane Tayson (Super Admin)','Super Admin'),(1221,154,'Room Re-assigned','Moved to 6 Beds (Upper Bunk) by Super Admin','2026-04-29 02:56:03','Diane Tayson (Super Admin)','Super Admin'),(1222,154,'Room Return Requested','User requested to return to their old room (Transfer ID: 6)','2026-04-29 03:01:32','Diane Tayson (Super Admin)','Super Admin'),(1223,154,'Room Returned','Returned to 4 Beds by Super Admin','2026-04-29 03:03:59','Diane Tayson (Super Admin)','Super Admin'),(1224,155,'Reservation Submitted','Room: Single | Status: Pending','2026-04-29 05:19:24','User','User'),(1225,155,'Payment Submitted','Reservation #218 via Cash for: First Month Rent, Security Deposit','2026-04-29 05:19:50','User','User'),(1226,155,'Payment Confirmed','Payment #530 marked as Paid by Super Admin.','2026-04-29 05:21:26','Diane Tayson (Super Admin)','Super Admin'),(1227,155,'Payment Confirmed','Payment #531 marked as Paid by Super Admin.','2026-04-29 05:21:29','Diane Tayson (Super Admin)','Super Admin'),(1228,155,'Reservation Approved','Reservation #218 approved by Super Admin.','2026-04-29 05:21:47','Diane Tayson (Super Admin)','Super Admin'),(1229,155,'Signature Requested','Signature requested for Reservation #218 by Super Admin','2026-04-29 05:21:59','Diane Tayson (Super Admin)','Super Admin'),(1230,155,'Lease Signed','Reservation #218','2026-04-29 05:22:10','Diane Tayson (Super Admin)','Super Admin'),(1231,155,'Parking Assigned','Assigned to Motorcycle Slot 1 by Super Admin','2026-04-29 05:24:06','Diane Tayson (Super Admin)','Super Admin'),(1232,155,'Payment Submitted','Reservation #218 via Cash for: Monthly Parking Fee (April 2026) for Motorcycle Slot 1','2026-04-29 05:24:29','Diane Tayson (Super Admin)','Super Admin'),(1233,155,'Payment Confirmed','Payment #537 marked as Paid by Super Admin.','2026-04-29 05:24:46','Diane Tayson (Super Admin)','Super Admin'),(1234,138,'Contract Ended','Reservation #202 marked as Completed by Super Admin.','2026-04-29 05:25:24','Diane Tayson (Super Admin)','Super Admin'),(1235,155,'Parking Ended','Parking reservation #37 ended by Super Admin','2026-04-29 05:26:19','Diane Tayson (Super Admin)','Super Admin'),(1236,155,'Contract Ended','Reservation #218 marked as Completed by Super Admin.','2026-04-29 05:26:39','Diane Tayson (Super Admin)','Super Admin'),(1237,155,'Reservation Submitted','Room: 6-Bed | Status: Pending','2026-04-29 05:48:06','Diane Tayson (Super Admin)','Super Admin'),(1238,155,'Payment Submitted','Reservation #219 via GCash for: First Month Rent, Security Deposit','2026-04-29 05:48:42','Diane Tayson (Super Admin)','Super Admin'),(1239,155,'Payment Confirmed','Payment #538 marked as Paid by Super Admin.','2026-04-29 05:49:04','Diane Tayson (Super Admin)','Super Admin'),(1240,155,'Payment Confirmed','Payment #539 marked as Paid by Super Admin.','2026-04-29 05:49:06','Diane Tayson (Super Admin)','Super Admin'),(1241,155,'Reservation Approved','Reservation #219 approved by Super Admin.','2026-04-29 05:52:51','Diane Tayson (Super Admin)','Super Admin'),(1242,138,'Reservation Submitted','Room: 4-Bed | Status: Pending','2026-04-29 05:54:21','Diane Tayson (Super Admin)','Super Admin'),(1243,128,'Room Re-assigned','Moved to 6 Beds (Lower Bunk) by Super Admin','2026-04-29 06:04:25','Diane Tayson (Super Admin)','Super Admin'),(1244,128,'Room Returned','Returned to 6 Beds by Super Admin','2026-04-29 07:04:18','Diane Tayson (Super Admin)','Super Admin'),(1245,138,'Payment Submitted','Reservation #220 via Cash for: First Month Rent, Security Deposit','2026-04-29 07:06:13','Diane Tayson (Super Admin)','Super Admin'),(1246,138,'Reservation Approved','Reservation #220 approved by Super Admin.','2026-04-29 07:15:20','Diane Tayson (Super Admin)','Super Admin'),(1247,138,'Signature Requested','Signature requested for Reservation #220 by Super Admin','2026-04-29 07:15:30','Diane Tayson (Super Admin)','Super Admin'),(1248,138,'Lease Signed','Reservation #220','2026-04-29 07:15:39','Diane Tayson (Super Admin)','Super Admin'),(1249,128,'Room Re-assigned','Moved to 6 Beds (Lower Bunk) by Super Admin','2026-04-30 12:50:49','Diane Tayson (Super Admin)','Super Admin'),(1250,128,'Room Returned','Returned to 6 Beds by Super Admin','2026-04-30 12:58:36','Diane Tayson (Super Admin)','Super Admin'),(1251,128,'Room Re-assigned','Moved to 6 Beds (Lower Bunk) by Super Admin','2026-04-30 12:58:47','Diane Tayson (Super Admin)','Super Admin');
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
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_requests`
--

LOCK TABLES `maintenance_requests` WRITE;
/*!40000 ALTER TABLE `maintenance_requests` DISABLE KEYS */;
INSERT INTO `maintenance_requests` VALUES (8,NULL,36,'Routine Preventive Maintenance','Scheduled','2026-04-29','2026-04-29 05:56:37',400.00),(9,NULL,75,'Routine Preventive Maintenance','Scheduled','2026-04-30','2026-04-30 12:49:52',0.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=1624 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1447,'127','2026-04-28 00:25:55','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1448,'128','2026-04-28 00:27:39','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1449,'129','2026-04-28 00:32:19','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1450,'130','2026-04-28 00:35:53','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1451,'131','2026-04-28 00:40:09','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1453,'133','2026-04-28 00:48:21','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1455,'135','2026-04-28 00:53:21','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1458,'138','2026-04-28 00:59:35','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1459,'139','2026-04-28 01:01:47','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1460,'140','2026-04-28 01:03:41','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1461,'141','2026-04-28 01:05:41','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1462,'127','2026-04-28 11:19:00','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱8,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1463,'128','2026-04-28 11:19:00','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1464,'129','2026-04-28 11:19:00','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1465,'130','2026-04-28 11:19:00','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1466,'130','2026-04-28 11:19:00','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1467,'131','2026-04-28 11:19:00','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1468,'131','2026-04-28 11:19:00','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1470,'133','2026-04-28 11:19:00','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1472,'135','2026-04-28 11:19:00','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1473,'135','2026-04-28 11:19:00','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1478,'138','2026-04-28 11:19:01','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱1,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1479,'139','2026-04-28 11:19:01','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1480,'140','2026-04-28 11:19:01','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1481,'140','2026-04-28 11:19:01','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1482,'141','2026-04-28 11:19:01','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱1,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1483,'142','2026-04-28 11:22:36','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1484,'143','2026-04-28 11:26:26','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1486,'145','2026-04-28 11:31:33','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1487,'146','2026-04-28 12:25:59','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1488,'147','2026-04-28 12:29:56','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1490,'149','2026-04-28 12:41:16','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1491,'150','2026-04-28 12:42:42','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1492,'142','2026-04-28 12:44:47','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1493,'142','2026-04-28 12:44:50','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #206. Please go to My Reservations to sign.','Action Required'),(1494,'142','2026-04-28 12:48:27','','✅ <strong>Payment Confirmed</strong><br>Your payment #457 has been verified and marked as Paid.','Payment Update'),(1495,'142','2026-04-28 12:48:29','','✅ <strong>Payment Confirmed</strong><br>Your payment #458 has been verified and marked as Paid.','Payment Update'),(1496,'128','2026-04-28 13:03:31','','✅ <strong>Payment Confirmed</strong><br>Your payment #369 has been verified and marked as Paid.','Payment Update'),(1497,'128','2026-04-28 13:03:32','','✅ <strong>Payment Confirmed</strong><br>Your payment #370 has been verified and marked as Paid.','Payment Update'),(1498,'128','2026-04-28 13:03:50','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1499,'128','2026-04-28 13:03:52','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #192. Please go to My Reservations to sign.','Action Required'),(1500,'128','2026-04-28 13:03:55','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #192. Please go to My Reservations to sign.','Action Required'),(1504,'151','2026-04-28 13:08:30','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1505,'151','2026-04-28 13:09:04','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1506,'151','2026-04-28 13:09:07','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #215. Please go to My Reservations to sign.','Action Required'),(1507,'151','2026-04-28 13:09:11','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #215. Please go to My Reservations to sign.','Action Required'),(1508,'151','2026-04-28 13:09:28','','✅ <strong>Payment Confirmed</strong><br>Your payment #520 has been verified and marked as Paid.','Payment Update'),(1509,'151','2026-04-28 13:09:31','','✅ <strong>Payment Confirmed</strong><br>Your payment #521 has been verified and marked as Paid.','Payment Update'),(1510,'138','2026-04-28 13:16:03','','✅ <strong>Payment Confirmed</strong><br>Your payment #439 has been verified and marked as Paid.','Payment Update'),(1511,'138','2026-04-28 13:16:05','','✅ <strong>Payment Confirmed</strong><br>Your payment #440 has been verified and marked as Paid.','Payment Update'),(1512,'138','2026-04-28 13:16:16','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1513,'138','2026-04-28 13:19:04','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #202. Please go to My Reservations to sign.','Action Required'),(1514,'130','2026-04-28 13:27:02','','✅ <strong>Payment Confirmed</strong><br>Your payment #383 has been verified and marked as Paid.','Payment Update'),(1515,'130','2026-04-28 13:27:04','','✅ <strong>Payment Confirmed</strong><br>Your payment #384 has been verified and marked as Paid.','Payment Update'),(1516,'130','2026-04-28 13:27:13','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1517,'130','2026-04-28 13:27:15','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #194. Please go to My Reservations to sign.','Action Required'),(1518,'130','2026-04-28 13:27:17','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #194. Please go to My Reservations to sign.','Action Required'),(1519,'133','2026-04-28 13:29:21','','✅ <strong>Payment Confirmed</strong><br>Your payment #404 has been verified and marked as Paid.','Payment Update'),(1520,'133','2026-04-28 13:29:24','','✅ <strong>Payment Confirmed</strong><br>Your payment #405 has been verified and marked as Paid.','Payment Update'),(1521,'133','2026-04-28 13:29:43','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1522,'133','2026-04-28 13:29:45','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #197. Please go to My Reservations to sign.','Action Required'),(1523,'133','2026-04-28 13:29:47','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #197. Please go to My Reservations to sign.','Action Required'),(1524,'129','2026-04-28 14:13:16','','✅ <strong>Payment Confirmed</strong><br>Your payment #376 has been verified and marked as Paid.','Payment Update'),(1525,'129','2026-04-28 14:13:18','','✅ <strong>Payment Confirmed</strong><br>Your payment #377 has been verified and marked as Paid.','Payment Update'),(1526,'129','2026-04-28 14:13:33','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1527,'129','2026-04-28 14:13:36','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #193. Please go to My Reservations to sign.','Action Required'),(1528,'127','2026-04-28 14:18:53','','✅ <strong>Payment Confirmed</strong><br>Your payment #362 has been verified and marked as Paid.','Payment Update'),(1529,'127','2026-04-28 14:18:55','','✅ <strong>Payment Confirmed</strong><br>Your payment #363 has been verified and marked as Paid.','Payment Update'),(1530,'145','2026-04-28 14:20:25','','✅ <strong>Payment Confirmed</strong><br>Your payment #478 has been verified and marked as Paid.','Payment Update'),(1531,'145','2026-04-28 14:20:27','','✅ <strong>Payment Confirmed</strong><br>Your payment #479 has been verified and marked as Paid.','Payment Update'),(1532,'145','2026-04-28 14:23:57','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1533,'150','2026-04-28 14:33:42','','✅ <strong>Payment Confirmed</strong><br>Your payment #513 has been verified and marked as Paid.','Payment Update'),(1534,'150','2026-04-28 14:33:43','','✅ <strong>Payment Confirmed</strong><br>Your payment #514 has been verified and marked as Paid.','Payment Update'),(1535,'150','2026-04-28 14:34:19','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1536,'150','2026-04-28 14:34:22','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #214. Please go to My Reservations to sign.','Action Required'),(1537,'150','2026-04-28 14:34:40','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #214. Please go to My Reservations to sign.','Action Required'),(1538,'139','2026-04-28 14:35:34','','✅ <strong>Payment Confirmed</strong><br>Your payment #441 has been verified and marked as Paid.','Payment Update'),(1539,'139','2026-04-28 14:35:36','','✅ <strong>Payment Confirmed</strong><br>Your payment #442 has been verified and marked as Paid.','Payment Update'),(1540,'139','2026-04-28 14:35:53','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1541,'139','2026-04-28 14:35:58','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #203. Please go to My Reservations to sign.','Action Required'),(1542,'146','2026-04-28 14:38:52','','✅ <strong>Payment Confirmed</strong><br>Your payment #485 has been verified and marked as Paid.','Payment Update'),(1543,'146','2026-04-28 14:38:54','','✅ <strong>Payment Confirmed</strong><br>Your payment #486 has been verified and marked as Paid.','Payment Update'),(1544,'146','2026-04-28 14:40:36','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1545,'146','2026-04-28 14:40:39','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #210. Please go to My Reservations to sign.','Action Required'),(1546,'147','2026-04-28 15:17:13','','✅ <strong>Payment Confirmed</strong><br>Your payment #492 has been verified and marked as Paid.','Payment Update'),(1547,'147','2026-04-28 15:17:15','','✅ <strong>Payment Confirmed</strong><br>Your payment #493 has been verified and marked as Paid.','Payment Update'),(1548,'147','2026-04-28 15:18:59','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1549,'131','2026-04-28 15:19:02','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1550,'135','2026-04-28 15:19:02','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1553,'140','2026-04-28 15:19:02','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱3,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1554,'141','2026-04-28 15:19:02','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱1,000.00 was due on Apr 28, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1555,'147','2026-04-28 15:19:03','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #211. Please go to My Reservations to sign.','Action Required'),(1556,'131','2026-04-28 15:19:59','','✅ <strong>Payment Confirmed</strong><br>Your payment #390 has been verified and marked as Paid.','Payment Update'),(1557,'131','2026-04-28 15:20:01','','✅ <strong>Payment Confirmed</strong><br>Your payment #391 has been verified and marked as Paid.','Payment Update'),(1558,'131','2026-04-28 15:20:18','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1559,'131','2026-04-28 15:20:19','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #195. Please go to My Reservations to sign.','Action Required'),(1560,'131','2026-04-28 15:20:55','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #195. Please go to My Reservations to sign.','Action Required'),(1561,'131','2026-04-28 15:20:58','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #195. Please go to My Reservations to sign.','Action Required'),(1562,'143','2026-04-28 15:49:14','','✅ <strong>Payment Confirmed</strong><br>Your payment #464 has been verified and marked as Paid.','Payment Update'),(1563,'143','2026-04-28 15:49:16','','✅ <strong>Payment Confirmed</strong><br>Your payment #465 has been verified and marked as Paid.','Payment Update'),(1564,'143','2026-04-28 15:49:31','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1565,'143','2026-04-28 15:49:33','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #207. Please go to My Reservations to sign.','Action Required'),(1567,'127','2026-04-28 15:50:12','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1568,'141','2026-04-28 16:05:12','','✅ <strong>Payment Confirmed</strong><br>Your payment #455 has been verified and marked as Paid.','Payment Update'),(1569,'141','2026-04-28 16:05:14','','✅ <strong>Payment Confirmed</strong><br>Your payment #456 has been verified and marked as Paid.','Payment Update'),(1570,'141','2026-04-28 16:05:57','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1571,'141','2026-04-28 16:06:01','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #205. Please go to My Reservations to sign.','Action Required'),(1572,'135','2026-04-28 16:06:55','','✅ <strong>Payment Confirmed</strong><br>Your payment #418 has been verified and marked as Paid.','Payment Update'),(1573,'135','2026-04-28 16:06:56','','✅ <strong>Payment Confirmed</strong><br>Your payment #419 has been verified and marked as Paid.','Payment Update'),(1574,'135','2026-04-28 16:07:12','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1575,'135','2026-04-28 16:07:15','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #199. Please go to My Reservations to sign.','Action Required'),(1576,'149','2026-04-28 16:08:51','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1577,'149','2026-04-28 16:08:54','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #213. Please go to My Reservations to sign.','Action Required'),(1578,'149','2026-04-28 16:09:14','','✅ <strong>Payment Confirmed</strong><br>Your payment #506 has been verified and marked as Paid.','Payment Update'),(1579,'149','2026-04-28 16:09:16','','✅ <strong>Payment Confirmed</strong><br>Your payment #507 has been verified and marked as Paid.','Payment Update'),(1580,'140','2026-04-28 16:09:53','','✅ <strong>Payment Confirmed</strong><br>Your payment #448 has been verified and marked as Paid.','Payment Update'),(1581,'140','2026-04-28 16:09:55','','✅ <strong>Payment Confirmed</strong><br>Your payment #449 has been verified and marked as Paid.','Payment Update'),(1582,'140','2026-04-28 16:10:10','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1583,'140','2026-04-28 16:10:13','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #204. Please go to My Reservations to sign.','Action Required'),(1584,'140','2026-04-28 16:10:15','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #204. Please go to My Reservations to sign.','Action Required'),(1591,'152','2026-04-28 19:55:34','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #216. Please go to My Reservations to sign.','Action Required'),(1592,'152','2026-04-28 20:14:54','','Hello Fredhenzel Tayson,<br><br>You requested a password reset. Your verification code is: <h2 style=\'color:#2E7D32;\'>595D77</h2><br>This code expires in 1 hour.','Password Reset'),(1593,'154','2026-04-29 10:36:24','1','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1594,'154','2026-04-29 10:38:51','1','✅ <strong>Payment Confirmed</strong><br>Your payment #529 has been verified and marked as Paid.','Payment Update'),(1595,'154','2026-04-29 10:39:04','1','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1596,'154','2026-04-29 10:39:17','1','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #217. Please go to My Reservations to sign.','Action Required'),(1597,'154','2026-04-29 10:56:03','1','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>6 Beds</strong>.','System'),(1598,'154','2026-04-29 11:03:59','','🏠 <strong>Room Returned</strong><br>You have been returned to your previous room: <strong>4 Beds</strong>.','System'),(1599,'155','2026-04-29 13:19:24','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1600,'155','2026-04-29 13:21:26','','✅ <strong>Payment Confirmed</strong><br>Your payment #530 has been verified and marked as Paid.','Payment Update'),(1601,'155','2026-04-29 13:21:29','','✅ <strong>Payment Confirmed</strong><br>Your payment #531 has been verified and marked as Paid.','Payment Update'),(1602,'155','2026-04-29 13:21:47','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1603,'155','2026-04-29 13:21:59','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #218. Please go to My Reservations to sign.','Action Required'),(1604,'155','2026-04-29 13:24:06','','🅿️ <strong>Parking Assigned</strong><br>You have been assigned to Motorcycle Slot 1. A fee of ₱900.00 has been added to your account.','Parking'),(1605,'155','2026-04-29 13:24:06','','⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱900.00 was due on Apr 29, 2026. Please pay immediately to avoid penalties.','Payment Warning'),(1606,'155','2026-04-29 13:24:46','','✅ <strong>Payment Confirmed</strong><br>Your payment #537 has been verified and marked as Paid.','Payment Update'),(1607,'138','2026-04-29 13:25:24','','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #202 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1608,'155','2026-04-29 13:26:19','','🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #15 has been marked as completed.','Parking'),(1609,'155','2026-04-29 13:26:39','','🏁 <strong>Contract Completed</strong><br>Your stay for reservation #218 has been marked as completed. Thank you for staying with us!','Contract Ended'),(1610,'155','2026-04-29 13:48:06','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1611,'155','2026-04-29 13:49:04','','✅ <strong>Payment Confirmed</strong><br>Your payment #538 has been verified and marked as Paid.','Payment Update'),(1612,'155','2026-04-29 13:49:06','','✅ <strong>Payment Confirmed</strong><br>Your payment #539 has been verified and marked as Paid.','Payment Update'),(1613,'155','2026-04-29 13:52:51','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1614,'138','2026-04-29 13:54:21','','✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.','Booking Status'),(1615,'128','2026-04-29 14:04:25','','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>6 Beds</strong>.','System'),(1616,'128','2026-04-29 15:04:18','','🏠 <strong>Room Returned</strong><br>You have been returned to your previous room: <strong>6 Beds</strong>.','System'),(1617,'138','2026-04-29 15:15:20','','🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.','Booking Approved'),(1618,'138','2026-04-29 15:15:30','','✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #220. Please go to My Reservations to sign.','Action Required'),(1619,'128','2026-04-30 20:50:49','','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>6 Beds</strong>.','System'),(1620,'128','2026-04-30 20:58:36','','🏠 <strong>Room Returned</strong><br>You have been returned to your previous room: <strong>6 Beds</strong>.','System'),(1621,'128','2026-04-30 20:58:47','','🏠 <strong>Room Changed</strong><br>You have been moved to <strong>6 Beds</strong>.','System'),(1622,'154','2026-05-01 15:35:12','','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>4 Beds</strong> ends on <strong>2026-05-08</strong> (7 days left). Please contact admin to renew.','Expiration Alert'),(1623,'154','2026-05-01 22:33:17','','⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>4 Beds</strong> ends on <strong>2026-05-08</strong> (7 days left). Please contact admin to renew.','Expiration Alert');
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
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_reservations`
--

LOCK TABLES `parking_reservations` WRITE;
/*!40000 ALTER TABLE `parking_reservations` DISABLE KEYS */;
INSERT INTO `parking_reservations` VALUES (37,155,15,'2026-04-29','2026-04-29',900.00,'Monthly','Completed','6767','toyota','2026-04-29 05:24:06');
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
INSERT INTO `parking_slots` VALUES (11,'Car Slot 1','Car','Available',6000.00,200.00,0),(12,'Car Slot 2','Car','Available',6000.00,200.00,0),(13,'Car Slot 3','Car','Available',6000.00,200.00,0),(14,'Car Slot 4','Car','Available',6000.00,200.00,0),(15,'Motorcycle Slot 1','Motorcycle','Available',900.00,50.00,0),(16,'Motorcycle Slot 2','Motorcycle','Available',900.00,50.00,0),(17,'Motorcycle Slot 3','Motorcycle','Available',900.00,50.00,0),(18,'Motorcycle Slot 4','Motorcycle','Available',900.00,50.00,0),(19,'Motorcycle Slot 5','Motorcycle','Available',900.00,50.00,0),(20,'Motorcycle Slot 6','Motorcycle','Available',900.00,50.00,0),(21,'Motorcycle Slot 7','Motorcycle','Available',900.00,50.00,0);
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
  `parking_reservation_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=552 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (362,191,NULL,8000.00,'Cash','Paid','2026-04-28 06:18:53','Pay at Property','Cash','Security Deposit',0,0),(363,191,NULL,13000.00,'Cash','Paid','2026-04-28 06:18:55','Pay at Property','Cash','First Month Rent',0,0),(364,191,NULL,13000.00,'','Unpaid','2026-05-26 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(365,191,NULL,13000.00,'','Unpaid','2026-06-26 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(366,191,NULL,13000.00,'','Unpaid','2026-07-26 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(367,191,NULL,13000.00,'','Unpaid','2026-08-26 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(368,191,NULL,13000.00,'','Unpaid','2026-09-26 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(369,192,NULL,3000.00,'Cash','Paid','2026-04-28 05:03:31','Pay at Property','Cash','Security Deposit',0,0),(370,192,NULL,4200.00,'Cash','Paid','2026-04-28 05:03:32','Pay at Property','Cash','First Month Rent',0,0),(371,192,NULL,4200.00,'','Unpaid','2026-05-26 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(372,192,NULL,4200.00,'','Unpaid','2026-06-26 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(373,192,NULL,4200.00,'','Unpaid','2026-07-26 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(374,192,NULL,4200.00,'','Unpaid','2026-08-26 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(375,192,NULL,4200.00,'','Unpaid','2026-09-26 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(376,193,NULL,3000.00,'Cash','Paid','2026-04-28 06:13:16','Pay at Property','Cash','Security Deposit',0,0),(377,193,NULL,4200.00,'Cash','Paid','2026-04-28 06:13:18','Pay at Property','Cash','First Month Rent',0,0),(378,193,NULL,4200.00,'','Unpaid','2026-05-26 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(379,193,NULL,4200.00,'','Unpaid','2026-06-26 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(380,193,NULL,4200.00,'','Unpaid','2026-07-26 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(381,193,NULL,4200.00,'','Unpaid','2026-08-26 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(382,193,NULL,4200.00,'','Unpaid','2026-09-26 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(383,194,NULL,3000.00,'Cash','Paid','2026-04-28 05:27:02','Pay at Property','Cash','Security Deposit',0,0),(384,194,NULL,3500.00,'Cash','Paid','2026-04-28 05:27:04','Pay at Property','Cash','First Month Rent',0,0),(385,194,NULL,3500.00,'','Unpaid','2026-05-26 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(386,194,NULL,3500.00,'','Unpaid','2026-06-26 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(387,194,NULL,3500.00,'','Unpaid','2026-07-26 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(388,194,NULL,3500.00,'','Unpaid','2026-08-26 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(389,194,NULL,3500.00,'','Unpaid','2026-09-26 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(390,195,NULL,3000.00,'Cash','Paid','2026-04-28 07:19:59','Pay at Property','Cash','Security Deposit',0,0),(391,195,NULL,3500.00,'Cash','Paid','2026-04-28 07:20:01','Pay at Property','Cash','First Month Rent',0,0),(392,195,NULL,3500.00,'','Unpaid','2026-05-26 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(393,195,NULL,3500.00,'','Unpaid','2026-06-26 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(394,195,NULL,3500.00,'','Unpaid','2026-07-26 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(395,195,NULL,3500.00,'','Unpaid','2026-08-26 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(396,195,NULL,3500.00,'','Unpaid','2026-09-26 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(404,197,NULL,3000.00,'Cash','Paid','2026-04-28 05:29:21','Pay at Property','Cash','Security Deposit',0,0),(405,197,NULL,4000.00,'Cash','Paid','2026-04-28 05:29:24','Pay at Property','Cash','First Month Rent',0,0),(406,197,NULL,4000.00,'','Unpaid','2026-05-26 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(407,197,NULL,4000.00,'','Unpaid','2026-06-26 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(408,197,NULL,4000.00,'','Unpaid','2026-07-26 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(409,197,NULL,4000.00,'','Unpaid','2026-08-26 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(410,197,NULL,4000.00,'','Unpaid','2026-09-26 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(418,199,NULL,3000.00,'Cash','Paid','2026-04-28 08:06:55','Pay at Property','Cash','Security Deposit',0,0),(419,199,NULL,4500.00,'Cash','Paid','2026-04-28 08:06:56','Pay at Property','Cash','First Month Rent',0,0),(420,199,NULL,4500.00,'','Unpaid','2026-05-26 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(421,199,NULL,4500.00,'','Unpaid','2026-06-26 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(422,199,NULL,4500.00,'','Unpaid','2026-07-26 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(423,199,NULL,4500.00,'','Unpaid','2026-08-26 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(424,199,NULL,4500.00,'','Unpaid','2026-09-26 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(439,202,NULL,1000.00,'Cash','Paid','2026-04-28 05:16:03','Pay at Property','Cash','Security Deposit [FULL]',0,0),(440,202,NULL,6300.00,'Cash','Paid','2026-04-28 05:16:05','Pay at Property','Cash','First Month Rent [FULL]',0,0),(441,203,NULL,3000.00,'Cash','Paid','2026-04-28 06:35:34','Pay at Property','Cash','Security Deposit',0,0),(442,203,NULL,4500.00,'Cash','Paid','2026-04-28 06:35:36','Pay at Property','Cash','First Month Rent',0,0),(443,203,NULL,4500.00,'','Unpaid','2026-05-26 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(444,203,NULL,4500.00,'','Unpaid','2026-06-26 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(445,203,NULL,4500.00,'','Unpaid','2026-07-26 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(446,203,NULL,4500.00,'','Unpaid','2026-08-26 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(447,203,NULL,4500.00,'','Unpaid','2026-09-26 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(448,204,NULL,3000.00,'Cash','Paid','2026-04-28 08:09:53','Pay at Property','Cash','Security Deposit',0,0),(449,204,NULL,4500.00,'Cash','Paid','2026-04-28 08:09:55','Pay at Property','Cash','First Month Rent',0,0),(450,204,NULL,4500.00,'','Unpaid','2026-05-26 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(451,204,NULL,4500.00,'','Unpaid','2026-06-26 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(452,204,NULL,4500.00,'','Unpaid','2026-07-26 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(453,204,NULL,4500.00,'','Unpaid','2026-08-26 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(454,204,NULL,4500.00,'','Unpaid','2026-09-26 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(455,205,NULL,1000.00,'Cash','Paid','2026-04-28 08:05:12','Pay at Property','Cash','Security Deposit [FULL]',0,0),(456,205,NULL,6300.00,'Cash','Paid','2026-04-28 08:05:14','Pay at Property','Cash','First Month Rent [FULL]',0,0),(457,206,NULL,3000.00,'Cash','Paid','2026-04-28 04:48:27','Pay at Property','Cash','Security Deposit',0,0),(458,206,NULL,4000.00,'Cash','Paid','2026-04-28 04:48:29','Pay at Property','Cash','First Month Rent',0,0),(459,206,NULL,4000.00,'','Unpaid','2026-05-27 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(460,206,NULL,4000.00,'','Unpaid','2026-06-27 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(461,206,NULL,4000.00,'','Unpaid','2026-07-27 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(462,206,NULL,4000.00,'','Unpaid','2026-08-27 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(463,206,NULL,4000.00,'','Unpaid','2026-09-27 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(464,207,NULL,3000.00,'Cash','Paid','2026-04-28 07:49:14','Pay at Property','Cash','Security Deposit',0,0),(465,207,NULL,4000.00,'Cash','Paid','2026-04-28 07:49:16','Pay at Property','Cash','First Month Rent',0,0),(466,207,NULL,4000.00,'','Unpaid','2026-05-27 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(467,207,NULL,4000.00,'','Unpaid','2026-06-27 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(468,207,NULL,4000.00,'','Unpaid','2026-07-27 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(469,207,NULL,4000.00,'','Unpaid','2026-08-27 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(470,207,NULL,4000.00,'','Unpaid','2026-09-27 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(478,209,NULL,3000.00,'Cash','Paid','2026-04-28 06:20:25','Pay at Property','Cash','Security Deposit',0,0),(479,209,NULL,4500.00,'Cash','Paid','2026-04-28 06:20:27','Pay at Property','Cash','First Month Rent',0,0),(480,209,NULL,4500.00,'','Unpaid','2026-05-27 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(481,209,NULL,4500.00,'','Unpaid','2026-06-27 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(482,209,NULL,4500.00,'','Unpaid','2026-07-27 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(483,209,NULL,4500.00,'','Unpaid','2026-08-27 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(484,209,NULL,4500.00,'','Unpaid','2026-09-27 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(485,210,NULL,3000.00,'Cash','Paid','2026-04-28 06:38:52','Pay at Property','Cash','Security Deposit',0,0),(486,210,NULL,4000.00,'Cash','Paid','2026-04-28 06:38:54','Pay at Property','Cash','First Month Rent',0,0),(487,210,NULL,4000.00,'','Unpaid','2026-05-27 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(488,210,NULL,4000.00,'','Unpaid','2026-06-27 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(489,210,NULL,4000.00,'','Unpaid','2026-07-27 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(490,210,NULL,4000.00,'','Unpaid','2026-08-27 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(491,210,NULL,4000.00,'','Unpaid','2026-09-27 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(492,211,NULL,3000.00,'Cash','Paid','2026-04-28 07:17:13','Pay at Property','Cash','Security Deposit',0,0),(493,211,NULL,4500.00,'Cash','Paid','2026-04-28 07:17:15','Pay at Property','Cash','First Month Rent',0,0),(494,211,NULL,4500.00,'','Unpaid','2026-05-27 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(495,211,NULL,4500.00,'','Unpaid','2026-06-27 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(496,211,NULL,4500.00,'','Unpaid','2026-07-27 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(497,211,NULL,4500.00,'','Unpaid','2026-08-27 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(498,211,NULL,4500.00,'','Unpaid','2026-09-27 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(506,213,NULL,3000.00,'Cash','Paid','2026-04-28 08:09:14','Pay at Property','Cash','Security Deposit',0,0),(507,213,NULL,4000.00,'Cash','Paid','2026-04-28 08:09:16','Pay at Property','Cash','First Month Rent',0,0),(508,213,NULL,4000.00,'','Unpaid','2026-05-27 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(509,213,NULL,4000.00,'','Unpaid','2026-06-27 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(510,213,NULL,4000.00,'','Unpaid','2026-07-27 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(511,213,NULL,4000.00,'','Unpaid','2026-08-27 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(512,213,NULL,4000.00,'','Unpaid','2026-09-27 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(513,214,NULL,3000.00,'Cash','Paid','2026-04-28 06:33:42','Pay at Property','Cash','Security Deposit',0,0),(514,214,NULL,4000.00,'Cash','Paid','2026-04-28 06:33:43','Pay at Property','Cash','First Month Rent',0,0),(515,214,NULL,4000.00,'','Unpaid','2026-05-27 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(516,214,NULL,4000.00,'','Unpaid','2026-06-27 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(517,214,NULL,4000.00,'','Unpaid','2026-07-27 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(518,214,NULL,4000.00,'','Unpaid','2026-08-27 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(519,214,NULL,4000.00,'','Unpaid','2026-09-27 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(520,215,NULL,3000.00,'Cash','Paid','2026-04-28 05:09:27','Pay at Property','Cash','Security Deposit',0,0),(521,215,NULL,4000.00,'Cash','Paid','2026-04-28 05:09:31','Pay at Property','Cash','First Month Rent',0,0),(522,215,NULL,4000.00,'','Unpaid','2026-05-27 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(523,215,NULL,4000.00,'','Unpaid','2026-06-27 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(524,215,NULL,4000.00,'','Unpaid','2026-07-27 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(525,215,NULL,4000.00,'','Unpaid','2026-08-27 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(526,215,NULL,4000.00,'','Unpaid','2026-09-27 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(527,216,NULL,3000.00,'Cash','Paid','2026-04-28 11:55:30',NULL,NULL,'Security Deposit',0,0),(528,216,NULL,18700.00,'Cash','Paid','2026-04-28 11:55:30',NULL,NULL,'First Month Rent',0,0),(529,217,NULL,22500.00,'GCash','Paid','2026-04-29 02:38:51','64637432772','1777430239_gcash_671549365_2397717717406408_7555397030948241540_n.jpg','Daily Stay Payment [FULL]',0,0),(530,218,NULL,8000.00,'Cash','Paid','2026-04-29 05:21:26','Pay at Property','Cash','Security Deposit',0,0),(531,218,NULL,13000.00,'Cash','Paid','2026-04-29 05:21:29','Pay at Property','Cash','First Month Rent',0,0),(532,218,NULL,13000.00,'','Cancelled','2026-05-28 16:00:00',NULL,NULL,'Month 2 Rent (Voided - Reservation Completed)',0,0),(533,218,NULL,13000.00,'','Cancelled','2026-06-28 16:00:00',NULL,NULL,'Month 3 Rent (Voided - Reservation Completed)',0,0),(534,218,NULL,13000.00,'','Cancelled','2026-07-28 16:00:00',NULL,NULL,'Month 4 Rent (Voided - Reservation Completed)',0,0),(535,218,NULL,13000.00,'','Cancelled','2026-08-28 16:00:00',NULL,NULL,'Month 5 Rent (Voided - Reservation Completed)',0,0),(536,218,NULL,13000.00,'','Cancelled','2026-09-28 16:00:00',NULL,NULL,'Month 6 Rent (Voided - Reservation Completed)',0,0),(537,218,37,900.00,'Cash','Paid','2026-04-29 05:24:46','Pay at Property','Cash','Monthly Parking Fee (April 2026) for Motorcycle Slot 1 (Parking ID: 37)',0,0),(538,219,NULL,3000.00,'GCash','Paid','2026-04-29 05:49:04','09673103189','1777441722_gcash_images.jfif','Security Deposit',0,0),(539,219,NULL,4200.00,'GCash','Paid','2026-04-29 05:49:06','09673103189','1777441722_gcash_images.jfif','First Month Rent',0,0),(540,219,NULL,4200.00,'','Unpaid','2027-01-31 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(541,219,NULL,4200.00,'','Unpaid','2027-02-28 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(542,219,NULL,4200.00,'','Unpaid','2027-03-31 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(543,219,NULL,4200.00,'','Unpaid','2027-04-30 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(544,219,NULL,4200.00,'','Unpaid','2027-05-31 16:00:00',NULL,NULL,'Month 6 Rent',0,0),(545,220,NULL,3000.00,'Cash','Paid','2026-04-29 07:06:23','Pay at Property','Cash','Security Deposit',0,0),(546,220,NULL,4500.00,'Cash','Paid','2026-04-29 07:06:23','Pay at Property','Cash','First Month Rent',0,0),(547,220,NULL,4500.00,'','Unpaid','2026-05-28 16:00:00',NULL,NULL,'Month 2 Rent',0,0),(548,220,NULL,4500.00,'','Unpaid','2026-06-28 16:00:00',NULL,NULL,'Month 3 Rent',0,0),(549,220,NULL,4500.00,'','Unpaid','2026-07-28 16:00:00',NULL,NULL,'Month 4 Rent',0,0),(550,220,NULL,4500.00,'','Unpaid','2026-08-28 16:00:00',NULL,NULL,'Month 5 Rent',0,0),(551,220,NULL,4500.00,'','Unpaid','2026-09-28 16:00:00',NULL,NULL,'Month 6 Rent',0,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=221 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (191,127,45,'','',6,86000.00,'Approved','2026-04-27 16:25:55','2026-04-27','2026-10-30',NULL,'Solo',NULL,0,NULL,0,1,'Employed','','Company Name','09234589239',8000.00,NULL),(192,128,36,'','',6,28200.00,'Approved','2026-04-27 16:27:39','2026-04-27','2026-10-30',NULL,'Lower Bunk','sig_192_1777352643.png',0,NULL,1,1,'Employed','','Company Name','09358923985',3000.00,NULL),(193,129,6,'','',6,28200.00,'Approved','2026-04-27 16:32:19','2026-04-27','2026-10-30',NULL,'Lower Bunk','sig_193_1777356824.png',0,NULL,1,1,'Employed','','Company Name','09304523098',3000.00,NULL),(194,130,6,'','',6,24000.00,'Approved','2026-04-27 16:35:53','2026-04-27','2026-10-30',NULL,'Upper Bunk','sig_194_1777354043.png',0,NULL,1,1,'Employed','','Company Name','09345673542',3000.00,NULL),(195,131,6,'','',6,24000.00,'Approved','2026-04-27 16:40:09','2026-04-27','2026-10-30',NULL,'Upper Bunk','sig_195_1777360863.png',0,NULL,1,1,'Employed','','Company Name','09213451287',3000.00,NULL),(197,133,59,'','',6,27000.00,'Approved','2026-04-27 16:48:21','2026-04-27','2026-10-30',NULL,'Upper Bunk','sig_197_1777354194.png',0,NULL,1,1,'Employed','','Company Name','09473464573',3000.00,NULL),(199,135,32,'','',6,30000.00,'Approved','2026-04-27 16:53:21','2026-04-27','2026-10-30',NULL,'Lower Bunk','sig_199_1777363640.png',0,NULL,1,1,'Employed','','Company Name','09234658732',3000.00,NULL),(202,138,59,'','',1,7300.00,'Completed','2026-04-27 16:59:35','2026-04-27','2026-05-26',NULL,'Upper Bunk','sig_202_1777353560.png',1,NULL,1,1,'Employed','','Company Name','09234562838',1000.00,NULL),(203,139,74,'','',6,30000.00,'Approved','2026-04-27 17:01:47','2026-04-27','2026-10-30',NULL,'Lower Bunk','sig_203_1777358175.png',0,NULL,1,1,'Employed','','Company Name','09324589374',3000.00,NULL),(204,140,74,'','',6,30000.00,'Approved','2026-04-27 17:03:41','2026-04-27','2026-10-30',NULL,'Lower Bunk','sig_204_1777363819.png',0,NULL,1,1,'Employed','','Company Name','09235612309',3000.00,NULL),(205,141,74,'','',1,7300.00,'Approved','2026-04-27 17:05:41','2026-04-27','2026-05-26',NULL,'Upper Bunk','sig_205_1777363567.png',0,NULL,1,1,'Employed','','Company Name','09246298675',1000.00,NULL),(206,142,74,'','',6,27000.00,'Approved','2026-04-28 03:22:36','2026-04-28','2026-10-30',NULL,'Upper Bunk','sig_206_1777351497.png',0,NULL,1,1,'Employed','','Company Name','09214517578',3000.00,NULL),(207,143,34,'','',6,27000.00,'Approved','2026-04-28 03:26:26','2026-04-28','2026-10-30',NULL,'Upper Bunk','sig_207_1777362579.png',0,NULL,1,1,'Employed','','Company Name','09234562178',3000.00,NULL),(209,145,34,'','',6,30000.00,'Approved','2026-04-28 03:31:33','2026-04-28','2026-10-30',NULL,'Lower Bunk','sig_209_1777357561.png',0,NULL,0,1,'Employed','','Company Name','09230423840',3000.00,NULL),(210,146,34,'','',6,27000.00,'Approved','2026-04-28 04:25:59','2026-04-28','2026-10-30',NULL,'Upper Bunk',NULL,0,NULL,1,1,'Employed','','Company Name','09456718651',3000.00,NULL),(211,147,35,'','',6,30000.00,'Approved','2026-04-28 04:29:56','2026-04-28','2026-10-30',NULL,'Lower Bunk','sig_211_1777360748.png',0,NULL,1,1,'Employed','','Company Name','09235623834',3000.00,NULL),(213,149,35,'','',6,27000.00,'Approved','2026-04-28 04:41:16','2026-04-28','2026-10-30',NULL,'Upper Bunk','sig_213_1777363745.png',0,NULL,1,1,'Employed','','Company Name','09935729835',3000.00,NULL),(214,150,35,'','',6,27000.00,'Approved','2026-04-28 04:42:42','2026-04-28','2026-10-30',NULL,'Upper Bunk','sig_214_1777358074.png',0,NULL,1,1,'Employed','','Company Name','09234523534',3000.00,NULL),(215,151,59,'','',6,27000.00,'Approved','2026-04-28 05:08:29','2026-04-28','2026-10-30',NULL,'Upper Bunk','sig_215_1777352956.png',0,NULL,1,1,'Employed','','Company Name','09234567289',3000.00,NULL),(216,152,31,'','',6,21700.00,'Approved','2026-04-28 11:55:30','2026-04-28','2026-10-28',NULL,'Whole Room',NULL,0,NULL,1,1,'Student','kolehiyo ng subic','mother name','09234568723',3000.00,'[{\"name\":\"tayson, jaimeeh\",\"first_name\":\"jaimeeh\",\"last_name\":\"tayson\",\"middle_name\":\"\",\"gender\":\"Female\",\"email\":\"jaimeeh@gmail.com\",\"phone\":\"09345687329\",\"id_image\":\"1777377330_comp_admin_0_images.jfif\",\"restored\":true,\"restored_user_id\":153}]'),(217,154,49,'','',1,22500.00,'Approved','2026-04-29 02:36:24','2026-04-29','2026-05-08',NULL,'Any','sig_217_1777430385.png',0,NULL,1,1,'Student','villaflor high school','ciciel','09646477247',0.00,'[{\"name\":\"ablao, bryan s\",\"first_name\":\"bryan\",\"last_name\":\"ablao\",\"middle_name\":\"s\",\"gender\":\"Male\",\"email\":\"\",\"phone\":\"09728264626\",\"id_image\":\"1777430184_comp_0_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg\"}]'),(218,155,20,'','',6,86000.00,'Completed','2026-04-29 05:19:24','2026-04-29','2026-10-30',NULL,'Solo','sig_218_1777440130.png',1,NULL,1,1,'Student','KOLEHIYO NG SUBIC','mother name','09346893463',8000.00,NULL),(219,155,24,'','',6,28200.00,'Approved','2026-04-29 05:48:06','2027-01-01','2027-06-29',NULL,'Lower Bunk',NULL,0,NULL,0,1,'Student','KOLEHIYO NG SUBIC','mother name','09346893463',3000.00,NULL),(220,138,34,'','',6,30000.00,'Approved','2026-04-29 05:54:21','2026-04-29','2026-10-30',NULL,'Lower Bunk','sig_220_1777446939.png',0,NULL,1,1,'Employed','','Company Name','09234562838',3000.00,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `residents`
--

LOCK TABLES `residents` WRITE;
/*!40000 ALTER TABLE `residents` DISABLE KEYS */;
INSERT INTO `residents` VALUES (20,NULL,'Marwino','Santiago','',NULL,'','09986521412','Male',NULL,NULL,NULL,NULL,NULL,NULL,'1776958198_comp_0_received_1998664277751362.webp','companion',0,0,0,'2026-04-23 15:30:31',1,181),(21,NULL,'Layla','Delima','',NULL,'','09975214123','Female',NULL,NULL,NULL,NULL,NULL,NULL,'1776958198_comp_1_Client_Profile.jpg','companion',0,0,0,'2026-04-23 15:30:31',1,181),(25,NULL,'Fred','Tyson','m',NULL,'tysoni@gmail.com','09974634634','Male',NULL,NULL,NULL,NULL,NULL,NULL,'1777119752_comp_0_download.jpg','companion',0,0,0,'2026-04-25 12:23:13',1,186),(31,142,'Rafael Dave','Angeles','','','RAFAELDAVE@gmail.com','09256782367','Male','Employed','','Cupang, City of Muntinlupa, Fourth District, National Capital Region (NCR)','Company Name','09214517578',NULL,'1777346556_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 04:44:47',0,NULL),(32,128,'Jairus Anastacio','Avecilla','','','JAIRUS@gmail.com','09234578326','Male','Employed','','Barangay 104, City of Manila, First District, National Capital Region (NCR)','Company Name','09358923985',NULL,'1777307259_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 05:03:50',0,NULL),(33,151,'Cyrill','Bataller','','','CYRILL@gmail.com','09234561693','Male','Employed','','Barangay 108, City of Manila, First District, National Capital Region (NCR)','Company Name','09234567289',NULL,'1777352909_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 05:09:04',0,NULL),(34,138,'James','Calderon','','','JAMES@gmail.com','09267290656','Male','Employed','','Barangay 109, City of Manila, First District, National Capital Region (NCR)','Company Name','09234562838',NULL,'1777309175_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 05:16:16',0,NULL),(35,130,'Jam Cloyd','Caraig','V','','JAM@gmail.com','09384597203','Male','Employed','','Hulong Duhat, City of Malabon, Third District, National Capital Region (NCR)','Company Name','09345673542',NULL,'1777307753_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 05:27:13',0,NULL),(36,133,'Dj Matthew','Castro','','','MATTHEW@gmail.com','09234578163','Male','Employed','','San Antonio, City of Pasig, Second District, National Capital Region (NCR)','Company Name','09473464573',NULL,'1777308501_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 05:29:43',0,NULL),(37,129,'Raymond','Chua','','','RAYMONDa@gmail.com','09236983453','Male','Employed','','Buli, City of Muntinlupa, Fourth District, National Capital Region (NCR)','Company Name','09304523098',NULL,'1777307539_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 06:13:33',0,NULL),(38,145,'Jevan','Erin','A','','JEVAN@gmail.com','09356293452','Male','Employed','','Rosario, City of Pasig, Second District, National Capital Region (NCR)','Company Name','09230423840',NULL,'1777347093_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 06:23:57',0,NULL),(39,150,'Lance','Flores','S','','LANCE@gmail.com','09145364532','Male','Employed','','Concepcion Uno, City of Marikina, Second District, National Capital Region (NCR)','Company Name','09234523534',NULL,'1777351362_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 06:34:19',0,NULL),(40,139,'Bobin','Hasan','','','BOBIN@gmail.com','09234587628','Male','Employed','','Barangay 106, City of Manila, First District, National Capital Region (NCR)','Company Name','09324589374',NULL,'1777309307_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 06:35:53',0,NULL),(41,146,'Reimer','Kasala','C','','REIMER@gmail.com','09234572397','Male','Employed','','Daniel Maing, Kalawit, Zamboanga Del Norte, Region IX (Zamboanga Peninsula)','Company Name','09456718651',NULL,'1777350359_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 06:40:36',0,NULL),(42,147,'Johndel','Lanot','','','JOHNDEL@gmail.com','09325692364','Male','Employed','','Marikina Heights, City of Marikina, Second District, National Capital Region (NCR)','Company Name','09235623834',NULL,'1777350596_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 07:18:59',0,NULL),(43,131,'Krian','Lerry','L','','KRIAN@gmail.com','09543874657','Male','Employed','','Lingunan, City of Valenzuela, Third District, National Capital Region (NCR)','Company Name','09213451287',NULL,'1777308009_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 07:20:18',0,NULL),(44,143,'Cesar','Malvar','','','CESAR@gmail.com','09230451623','Male','Employed','','Barangay 108, City of Manila, First District, National Capital Region (NCR)','Company Name','09234562178',NULL,'1777346786_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 07:49:31',0,NULL),(45,127,'Janvin','Deveza','','','JANVIN@gmail.com','09983572836','Male','Employed','','Putatan, City of Muntinlupa, Fourth District, National Capital Region (NCR)','Company Name','09234589239',NULL,'1777307155_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 07:50:12',0,NULL),(46,141,'Jaireh','Villapando','','','JAIREH@gmail.com','09234578929','Male','Employed','','Forbes Park, City of Makati, Fourth District, National Capital Region (NCR)','Company Name','09246298675',NULL,'1777309541_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 08:05:57',0,NULL),(47,135,'Nelson','Vergara','','','NELSON@gmail.com','09848743348','Male','Employed','','New Alabang Village, City of Muntinlupa, Fourth District, National Capital Region (NCR)','Company Name','09234658732',NULL,'1777308801_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 08:07:12',0,NULL),(48,149,'Vince Aldrin','Pardito','','','VINCEALDRIN@gmail.com','09234578239','Male','Employed','','Bel-Air, City of Makati, Fourth District, National Capital Region (NCR)','Company Name','09935729835',NULL,'1777351276_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 08:08:51',0,NULL),(49,140,'Carl Joshua','Monreal','','','CARLJOSHUA@gmail.com','09345678872','Male','Employed','','Cembo, City of Makati, Fourth District, National Capital Region (NCR)','Company Name','09235612309',NULL,'1777309421_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','user',0,0,0,'2026-04-28 08:10:10',0,NULL),(50,152,'Fredhenzel','Tayson','','','fredhenzeltayson1@gmail.com','09345698373','Male','Student','kolehiyo ng subic','Laoag, San Marcelino, Zambales, Region III (Central Luzon)','mother name','09234568723',NULL,NULL,'user',1,0,0,'2026-04-28 11:55:30',0,NULL),(51,NULL,'jaimeeh','tayson','',NULL,'jaimeeh@gmail.com','09345687329','Female',NULL,NULL,NULL,NULL,NULL,NULL,'1777377330_comp_admin_0_images.jfif','companion',0,0,0,'2026-04-28 11:55:30',1,216),(52,154,'Izee','Solomon','','','izeesolomon@gmail.com','09463424626','Female','Student','villaflor high school','San Jose, Licab, Nueva Ecija, Region III (Central Luzon)','ciciel','09646477247',NULL,'1777430184_id_images.jfif','user',0,0,0,'2026-04-29 02:39:04',0,NULL),(53,NULL,'bryan','ablao','s',NULL,'','09728264626','Male',NULL,NULL,NULL,NULL,NULL,NULL,'1777430184_comp_0_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','companion',0,0,0,'2026-04-29 02:39:04',1,217),(54,155,'Keysha','Sarto','','','keysha@gmail.com','09258238453','Female','Student','KOLEHIYO NG SUBIC','Laoag, San Marcelino, Zambales, Region III (Central Luzon)','mother name','09346893463',NULL,'1777439964_id_images.jfif','user',0,0,0,'2026-04-29 05:21:47',0,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_transfers`
--

LOCK TABLES `room_transfers` WRITE;
/*!40000 ALTER TABLE `room_transfers` DISABLE KEYS */;
INSERT INTO `room_transfers` VALUES (1,155,74,6,'2026-04-11 19:18:30','Returned',1,NULL),(2,155,74,6,'2026-04-11 19:29:18','Returned',1,'2026-04-11 19:32:05'),(3,173,32,45,'2026-04-23 11:15:56','Returned',0,'2026-04-23 11:17:06'),(4,185,7,32,'2026-04-25 21:10:46','Returned',0,'2026-04-25 21:11:21'),(5,184,6,26,'2026-04-25 21:29:04','Returned',0,'2026-04-25 21:29:13'),(6,217,49,58,'2026-04-29 10:56:03','Returned',1,'2026-04-29 11:03:59'),(7,192,6,36,'2026-04-29 14:04:25','Returned',0,'2026-04-29 15:04:18'),(8,192,6,36,'2026-04-30 20:50:49','Returned',0,'2026-04-30 20:58:36'),(9,192,6,36,'2026-04-30 20:58:47','Moved',0,NULL);
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
INSERT INTO `rooms` VALUES (6,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,2,'202',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,1,0,'Male'),(7,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,'303',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,19,0,'Male'),(20,'1 Bed','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,4,'401',0.00,0.00,0.00,13000.00,700.00,700.00,2,0,'Male'),(21,'1 Bed','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,5,'501',0.00,0.00,0.00,13000.00,700.00,700.00,3,0,'Male'),(22,'1 Bed','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,6,'601',0.00,0.00,0.00,13000.00,700.00,700.00,4,0,'Male'),(23,'1 Bed','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,7,'701',0.00,0.00,0.00,13000.00,700.00,700.00,5,0,'Male'),(24,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,4,'402',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,2,0,'Female'),(25,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,5,'502',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,3,0,'Female'),(26,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,6,'602',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,4,0,'Male'),(27,'702','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,7,'702',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,5,0,''),(28,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,'403',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,20,0,'Female'),(29,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,'503',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,14,0,'Male'),(30,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,'603',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,9,0,'Female'),(31,'703','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,'703',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,4,0,''),(32,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,'204',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,2,0,'Male'),(34,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,'206',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,21,0,'Male'),(35,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,'207',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,22,0,'Male'),(36,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,2,'208',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,6,0,'Male'),(37,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,2,'209',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,7,0,'Female'),(38,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,'308',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,9,0,'Female'),(39,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,'309',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,10,0,'Male'),(40,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,'304',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,23,0,'Male'),(41,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,'305',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,24,0,'Female'),(42,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,'306',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,25,0,'Female'),(43,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,3,'307',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,26,0,'Female'),(44,'205','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,'205',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,20,1,''),(45,'1 Bed','Single',14000.00,1,0,NULL,'434612699_2697344013763217_6695140230318829305_n.jpg','Available','Available',0.00,0.00,0,2,'201',0.00,0.00,0.00,13000.00,700.00,700.00,1,0,'Any'),(46,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,'404',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,27,0,'Male'),(47,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,'405',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,28,0,'Male'),(48,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,'406',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,29,0,'Female'),(49,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,4,'407',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,30,0,'Female'),(50,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,4,'408',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,11,0,'Male'),(51,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,4,'409',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,12,0,'Female'),(52,'302','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,'302',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,8,1,''),(53,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,5,'508',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,13,0,'Male'),(54,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,5,'509',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,14,0,'Female'),(55,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,6,'608',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,15,0,'Male'),(56,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,6,'609',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,16,0,'Female'),(57,'708','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,7,'708',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,17,0,''),(58,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,7,'709',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,18,0,''),(59,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,'203',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,1,0,'Male'),(60,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,'504',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,15,0,'Female'),(61,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,'505',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,16,0,'Male'),(62,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,'506',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,17,0,'Male'),(63,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,5,'507',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,18,0,'Female'),(64,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,'604',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,10,0,'Female'),(65,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,'605',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,11,0,'Female'),(66,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,'606',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,12,0,'Female'),(67,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,6,'607',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,13,0,'Male'),(68,'704','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,'704',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,5,0,''),(69,'705','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,'705',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,6,0,''),(70,'706','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,'706',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,7,0,''),(71,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,7,'707',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,8,0,''),(74,'4 Beds','4-Bed',6900.00,4,0,NULL,'553086532_1458289505383792_3468955167122582667_n.jpg','Available','Available',6300.00,6900.00,0,2,'205',26400.00,4000.00,4500.00,17000.00,700.00,2500.00,3,0,'Male'),(75,'6 Beds','6-Bed',6600.00,6,0,NULL,'462649285_910103944161119_916589224660087614_n.jpg','Available','Available',5999.00,6600.00,0,3,'302',37797.00,3500.00,4200.00,24000.00,600.00,3500.00,0,0,'Female');
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
) ENGINE=InnoDB AUTO_INCREMENT=1483 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'hero_image','[\"1770471778_hero_edit.png\",\"1770447312_hero_edit.png\",\"1772369513_hero.png\",\"1770447047_hero.png\",\"1777044935_hero.png\",\"1777044976_hero.png\",\"1777044992_hero.png\"]'),(125,'living_area_image','living_area_1770486291.jpg'),(126,'last_update','1777553927'),(290,'price_single','14000'),(291,'price_4bed_upper','6300'),(292,'price_4bed_lower','6900'),(293,'price_6bed_upper','5999'),(294,'price_6bed_lower','6600'),(303,'price_4bed_whole','26400'),(306,'price_6bed_whole','37797'),(315,'price_single_long','13000'),(319,'price_4bed_upper_long','4000'),(320,'price_4bed_lower_long','4500'),(321,'price_4bed_whole_long','17000'),(325,'price_6bed_upper_long','3500'),(326,'price_6bed_lower_long','4200'),(327,'price_6bed_whole_long','24000'),(548,'room_type_order','[\"Single\",\"4-Bed\",\"6-Bed\"]'),(687,'migration_fix_dupe_rooms_v2','1'),(688,'migration_cleanup_v3','1'),(848,'login_bg','login_bg_1774085356.jpg'),(894,'migration_parking_rates_v1','1'),(929,'price_housekeeping_standard','400'),(939,'price_maintenance_standard','400'),(1071,'house_rules','house_rules_1776910379.pdf'),(1082,'gcash_qr','gcash_qr_1776912371.jpg'),(1133,'clearance_file','clearance_form_1776919302.pdf'),(1142,'price_parking_car_monthly','6000'),(1143,'price_parking_car_daily','200'),(1144,'price_parking_motor_monthly','900'),(1145,'price_parking_motor_daily','50'),(1183,'migration_companions_v2','1'),(1188,'clearance_template','<div style=\"text-align: center; margin-bottom: 20px;\">\r\n<h2>Tenant Clearance Form</h2>\r\n<p>Woke Coliving INC. | 205 Kanlaon St. Mandaluyong, Philippines</p>\r\n</div>\r\n<table style=\"width: 100%; border-collapse: collapse; margin-bottom: 20px;\" border=\"1\">\r\n<tbody>\r\n<tr>\r\n<td style=\"padding: 8px;\"><strong>Name:</strong> {TENANT_NAME}</td>\r\n<td style=\"padding: 8px;\"><strong>Room:</strong> {ROOM}</td>\r\n</tr>\r\n<tr>\r\n<td style=\"padding: 8px;\"><strong>Stay Period:</strong> {START_DATE} to {END_DATE}</td>\r\n<td style=\"padding: 8px;\"><strong>Clearance Date:</strong> {CLEARANCE_DATE}</td>\r\n</tr>\r\n</tbody>\r\n</table>\r\n<p>This is to certify that <strong>{TENANT_NAME}</strong> has successfully completed their stay and is hereby cleared of all property and room accountabilities as of {CLEARANCE_DATE}.</p>\r\n<p>The security deposit will be refunded minus any deductions for property damages, lost items, or unpaid utility bills as detailed below.</p>\r\n<table style=\"width: 100%; border-collapse: collapse; margin-top: 20px;\" border=\"1\">\r\n<tbody>\r\n<tr>\r\n<td style=\"padding: 8px;\"><strong>Security Deposit Amount</strong></td>\r\n<td style=\"padding: 8px; text-align: right;\">Php {DEPOSIT_AMOUNT}</td>\r\n</tr>\r\n<tr>\r\n<td style=\"padding: 8px;\"><strong>Less: Deductions</strong><br><small>{DEDUCTION_REMARKS}</small></td>\r\n<td style=\"padding: 8px; text-align: right; color: red;\">- Php {DEDUCTION_AMOUNT}</td>\r\n</tr>\r\n<tr style=\"background-color: #f8f9fa;\">\r\n<td style=\"padding: 8px;\"><strong>Net Refundable Amount</strong></td>\r\n<td style=\"padding: 8px; text-align: right; font-weight: bold;\">Php {NET_REFUND}</td>\r\n</tr>\r\n</tbody>\r\n</table>\r\n<p><br><br></p>\r\n<table style=\"width: 100%; border: none; margin-top: 40px;\">\r\n<tbody>\r\n<tr>\r\n<td style=\"text-align: center; width: 45%; border-top: 1px solid #000; padding-top: 5px;\">{TENANT_NAME}<br><small>Tenant Signature</small></td>\r\n<td style=\"width: 10%;\">&nbsp;</td>\r\n<td style=\"text-align: center; width: 45%; border-top: 1px solid #000; padding-top: 5px;\">WOKE COLIVING ADMIN<br><small>Authorized Representative</small></td>\r\n</tr>\r\n</tbody>\r\n</table>'),(1239,'maintenance_mode','0'),(1241,'maintenance_end_time','1777263610'),(1259,'smtp_host','banaagbanaag03@gmail.com'),(1260,'smtp_port','3'),(1261,'smtp_username','banaagbanaag03@gmail.com'),(1262,'smtp_password','wokecoliving'),(1263,'smtp_from_email','stephenzhanebegosa@gmail.com'),(1264,'smtp_from_name','wokecoliving'),(1287,'price_single_daily','700'),(1288,'price_4bed_daily_bed','700'),(1289,'price_4bed_daily_room','2500'),(1290,'price_6bed_daily_bed','600'),(1291,'price_6bed_daily_room','3500');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=156 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (127,'JANVIN@gmail.com','09983572836',NULL,'$2y$10$8Uo5ZhU1WA8e4T9HJnjrN.0km9YwhSbctHe3jeNPalDNbqbHgfkqu','user','2026-04-27 16:24:40',0,0,'Male',NULL,NULL,'Employed','Putatan, City of Muntinlupa, Fourth District, National Capital Region (NCR)',NULL,'1777307155_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09234589239',NULL,0,0,'Deveza','Janvin','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(128,'JAIRUS@gmail.com','09234578326',NULL,'$2y$10$Y6bE6Kf2/Q5tPscbSaa5l.2runc9jil6VW2W4aqZlVP8BYYWATfbS','user','2026-04-27 16:26:40',0,0,'Male',NULL,NULL,'Employed','Barangay 104, City of Manila, First District, National Capital Region (NCR)',NULL,'1777307259_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09358923985',NULL,0,0,'Avecilla','Jairus Anastacio','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(129,'RAYMONDa@gmail.com','09236983453',NULL,'$2y$10$NYIayCaP.ILRIWMlG9haDeBJQSC6Zz9KKz0PtrP6ymohUbiH7899.','user','2026-04-27 16:28:24',0,0,'Male',NULL,NULL,'Employed','Buli, City of Muntinlupa, Fourth District, National Capital Region (NCR)',NULL,'1777307539_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09304523098',NULL,0,0,'Chua','Raymond','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(130,'JAM@gmail.com','09384597203',NULL,'$2y$10$z8cso2olSTWZVvnpzxxwHOErcdrDrPm6fTHcQyKWaa2JRKaiY9qjK','user','2026-04-27 16:34:09',0,0,'Male',NULL,NULL,'Employed','Hulong Duhat, City of Malabon, Third District, National Capital Region (NCR)',NULL,'1777307753_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09345673542',NULL,0,0,'Caraig','Jam Cloyd','V','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(131,'KRIAN@gmail.com','09543874657',NULL,'$2y$10$9wId81nAPKJlDK8A95mx2OMrja2MYHFgRAc3pofcY32mIfkt13x2G','user','2026-04-27 16:37:33',0,0,'Male',NULL,NULL,'Employed','Lingunan, City of Valenzuela, Third District, National Capital Region (NCR)',NULL,'1777308009_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09213451287',NULL,0,0,'Lerry','Krian','L','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(133,'MATTHEW@gmail.com','09234578163',NULL,'$2y$10$5/p29KZJ4ZvmV5Kx5ZSMtuZYyGD4RriW5xLmyfLRKLVoGxIEarClK','user','2026-04-27 16:46:23',0,0,'Male',NULL,NULL,'Employed','San Antonio, City of Pasig, Second District, National Capital Region (NCR)',NULL,'1777308501_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09473464573',NULL,0,0,'Castro','Dj Matthew','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(135,'NELSON@gmail.com','09848743348',NULL,'$2y$10$.4QxgGLCWQvyAuxO0o0kvuYNFm1CEI./oH4s/hBytKLMktVk1CDoK','user','2026-04-27 16:52:12',0,0,'Male',NULL,NULL,'Employed','New Alabang Village, City of Muntinlupa, Fourth District, National Capital Region (NCR)',NULL,'1777308801_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09234658732',NULL,0,0,'Vergara','Nelson','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(138,'JAMES@gmail.com','09267290656',NULL,'$2y$10$bgWwDd5cIZNhFuLIZYjd4Oxjrdcs7qZrOZVUOaSCQjNfv0a2S8kqS','user','2026-04-27 16:58:39',0,0,'Male',NULL,NULL,'Employed','Barangay 109, City of Manila, First District, National Capital Region (NCR)',NULL,'1777309175_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09234562838',NULL,0,0,'Calderon','James','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(139,'BOBIN@gmail.com','09234587628',NULL,'$2y$10$tI.gOtPrdQCL9BUTavsfduLNAxPzf99mnsZeNPvOmiO6yehmmYhmm','user','2026-04-27 17:00:50',0,0,'Male',NULL,NULL,'Employed','Barangay 106, City of Manila, First District, National Capital Region (NCR)',NULL,'1777309307_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09324589374',NULL,0,0,'Hasan','Bobin','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(140,'CARLJOSHUA@gmail.com','09345678872',NULL,'$2y$10$tJTmBf.r6BUoaK4FwNc4Su6yltgOzC0z8WPFUoa/F2rGghr3J6jnS','user','2026-04-27 17:02:54',0,0,'Male',NULL,NULL,'Employed','Cembo, City of Makati, Fourth District, National Capital Region (NCR)',NULL,'1777309421_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09235612309',NULL,0,0,'Monreal','Carl Joshua','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(141,'JAIREH@gmail.com','09234578929',NULL,'$2y$10$5NiwSPRgOk0hJe4PnTRbLe.nm1zxViX8HnSVXaZONrgjcyYmLWp/W','user','2026-04-27 17:04:53',0,0,'Male',NULL,NULL,'Employed','Forbes Park, City of Makati, Fourth District, National Capital Region (NCR)',NULL,'1777309541_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09246298675',NULL,0,0,'Villapando','Jaireh','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(142,'RAFAELDAVE@gmail.com','09256782367',NULL,'$2y$10$P8iJl0r9CDy2rF9ggzraN.xrf/riLb33fx0ix78RDcmAX4knCTQNm','user','2026-04-28 03:21:07',0,0,'Male',NULL,NULL,'Employed','Cupang, City of Muntinlupa, Fourth District, National Capital Region (NCR)',NULL,'1777346556_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09214517578',NULL,0,0,'Angeles','Rafael Dave','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(143,'CESAR@gmail.com','09230451623',NULL,'$2y$10$wTU6u2xQDL0fBkBJ4J1Au.OAEOg3KOJ1MXVf7cSo02H2W8uvA8LvO','user','2026-04-28 03:24:29',0,0,'Male',NULL,NULL,'Employed','Barangay 108, City of Manila, First District, National Capital Region (NCR)',NULL,'1777346786_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09234562178',NULL,0,0,'Malvar','Cesar','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(145,'JEVAN@gmail.com','09356293452',NULL,'$2y$10$dpdSeTs3may8zQg5aGEnj.kPnRcEd/c6UdWaaYUO8bjLzMdI54bx2','user','2026-04-28 03:29:36',0,0,'Male',NULL,NULL,'Employed','Rosario, City of Pasig, Second District, National Capital Region (NCR)',NULL,'1777347093_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09230423840',NULL,0,0,'Erin','Jevan','A','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(146,'REIMER@gmail.com','09234572397',NULL,'$2y$10$8rn0uQjAWdHbk/EpBS2ei.iyE1lUBHoCfWz1C8M3oQtbHYAeeS/iu','user','2026-04-28 03:32:39',0,0,'Male',NULL,NULL,'Employed','Daniel Maing, Kalawit, Zamboanga Del Norte, Region IX (Zamboanga Peninsula)',NULL,'1777350359_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09456718651',NULL,0,0,'Kasala','Reimer','C','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(147,'JOHNDEL@gmail.com','09325692364',NULL,'$2y$10$IqfTTnd.eEz9cudofE4Jh./m.iLgybJ3716Wze1PWjxNxlHt6K.ia','user','2026-04-28 04:29:00',0,0,'Male',NULL,NULL,'Employed','Marikina Heights, City of Marikina, Second District, National Capital Region (NCR)',NULL,'1777350596_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09235623834',NULL,0,0,'Lanot','Johndel','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(149,'VINCEALDRIN@gmail.com','09234578239',NULL,'$2y$10$fjxH/P7pXJuRdRbCILWIhukbY5LUA7SrIJxrO7H7iKkzjiPqoF/86','user','2026-04-28 04:33:51',0,0,'Male',NULL,NULL,'Employed','Bel-Air, City of Makati, Fourth District, National Capital Region (NCR)',NULL,'1777351276_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09935729835',NULL,0,0,'Pardito','Vince Aldrin','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(150,'LANCE@gmail.com','09145364532',NULL,'$2y$10$wUKmyPCptX1KKZh4bVUhx.pFUY.jheRxB/1yttiIj1oRLDILcLkbe','user','2026-04-28 04:42:18',0,0,'Male',NULL,NULL,'Employed','Concepcion Uno, City of Marikina, Second District, National Capital Region (NCR)',NULL,'1777351362_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09234523534',NULL,0,0,'Flores','Lance','S','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(151,'CYRILL@gmail.com','09234561693',NULL,'$2y$10$XgJ1ImLiUmw8ynChYhQurePbzay/jbLKoxCwlXgwdnJUzr8HHSlAm','user','2026-04-28 05:07:43',0,0,'Male',NULL,NULL,'Employed','Barangay 108, City of Manila, First District, National Capital Region (NCR)',NULL,'1777352909_id_company_id_card_for_employee_information_include_date_of_joining_and_validity_details_Slide01.jpg','Company Name','09234567289',NULL,0,0,'Bataller','Cyrill','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'Company ID'),(152,'fredhenzeltayson1@gmail.com','09345698373',NULL,'$2y$10$/3rDumjhPXjWddmpAaF3gO1sxMyspfnyVqrkI.4EkbcihcIgNnbka','user','2026-04-28 11:55:30',0,0,'Male','595D77','2026-04-28 15:14:54','Student','Laoag, San Marcelino, Zambales, Region III (Central Luzon)','kolehiyo ng subic',NULL,'mother name','09234568723',NULL,1,0,'Tayson','Fredhenzel','','',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(153,'jaimeeh@gmail.com','09345687329',NULL,'$2y$10$p6hp/yiottDSGJCnt2YY8uUR72oRQ9j.uXe.nitl.1VeCwHdNnAMa','user','2026-04-28 11:57:07',0,0,'Female',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'tayson','jaimeeh','',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(154,'izeesolomon@gmail.com','09463424626',NULL,'$2y$10$RgU5nO.NkIKKAk5wohtaY.jiVRbaLaD9BDWCq76jBccUWsvyM3ami','user','2026-04-29 02:32:17',0,0,'Female',NULL,NULL,'Student','San Jose, Licab, Nueva Ecija, Region III (Central Luzon)','villaflor high school','1777430184_id_images.jfif','ciciel','09646477247',NULL,0,0,'Solomon','Izee','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'School ID'),(155,'keysha@gmail.com','09258238453',NULL,'$2y$10$rlDhGjahETemEV4PVDEGzuhvKLXLLs9EvNLOltNQL8Ll.DrlD6NFm','user','2026-04-29 05:16:36',0,0,'Female',NULL,NULL,'Student','Laoag, San Marcelino, Zambales, Region III (Central Luzon)','KOLEHIYO NG SUBIC','1777439964_id_images.jfif','mother name','09346893463',NULL,0,0,'Sarto','Keysha','','',1,NULL,NULL,NULL,NULL,NULL,NULL,'School ID');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utility_bills`
--

LOCK TABLES `utility_bills` WRITE;
/*!40000 ALTER TABLE `utility_bills` DISABLE KEYS */;
INSERT INTO `utility_bills` VALUES (2,20,NULL,'2026-03-18',1.05,1.57,12.00,1.12,0.40,35.00,6.24,'2026-03-18 03:32:20',1),(3,20,NULL,'2026-03-18',1.05,1.57,12.00,1.12,0.40,35.00,6.24,'2026-03-18 03:32:22',1),(4,20,NULL,'2026-03-18',1.05,1.57,12.00,1.12,0.40,35.00,6.24,'2026-03-18 03:32:24',1),(5,59,NULL,'2026-04-23',1022.00,1054.00,9.00,300.00,310.00,20.00,488.00,'2026-04-23 03:41:17',1),(6,59,NULL,'2026-04-23',1022.00,1054.00,9.00,300.00,310.00,20.00,488.00,'2026-04-23 03:41:37',1),(7,40,NULL,'2026-04-23',100.00,150.00,12.00,100.00,105.00,35.00,775.00,'2026-04-23 13:12:56',1),(8,40,NULL,'2026-04-23',100.00,150.00,12.00,100.00,105.00,35.00,775.00,'2026-04-23 13:13:35',1),(9,6,NULL,'2026-04-25',100.00,150.00,12.00,50.00,75.00,35.00,1475.00,'2026-04-25 13:26:40',1),(10,6,NULL,'2026-04-25',100.00,150.00,12.00,50.00,75.00,35.00,1475.00,'2026-04-25 13:27:20',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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

-- Dump completed on 2026-05-01 22:33:59
