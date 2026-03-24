-- MySQL dump 10.13  Distrib 8.4.8, for Linux (x86_64)
--
-- Host: localhost    Database: nojo_consult
-- ------------------------------------------------------
-- Server version	8.4.8

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `lead_boxes`
--

DROP TABLE IF EXISTS `lead_boxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_boxes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `internal_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_text` text COLLATE utf8mb4_unicode_ci,
  `button_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` json DEFAULT NULL,
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_boxes_type_status_index` (`type`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_boxes`
--

LOCK TABLES `lead_boxes` WRITE;
/*!40000 ALTER TABLE `lead_boxes` DISABLE KEYS */;
INSERT INTO `lead_boxes` VALUES (1,'resource','active','consult resource','consult',NULL,'Get the resource','book-open','{\"visual_preset\": \"default\"}','[]','2026-03-22 18:09:08','2026-03-22 18:09:42'),(2,'service','active','gcgf','TESTER 111','chcchgchgchcgc','Request a call',NULL,'{\"cta_line\": \"Quick question? Let\'s get you answers.\", \"value_points\": [{\"line\": \"Clear guidance\", \"icon_key\": \"shield-check\"}, {\"line\": \"Fast response\", \"icon_key\": \"message-square\"}, {\"line\": \"Practical next steps\", \"icon_key\": \"sparkles\"}], \"reassurance_text\": \"No pressure. No spam.\"}','[]','2026-03-22 18:53:28','2026-03-22 18:54:26');
/*!40000 ALTER TABLE `lead_boxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_slots`
--

DROP TABLE IF EXISTS `lead_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_slots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_slots_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_slots`
--

LOCK TABLES `lead_slots` WRITE;
/*!40000 ALTER TABLE `lead_slots` DISABLE KEYS */;
INSERT INTO `lead_slots` VALUES (1,'home_intro',1,'2026-03-22 17:59:33','2026-03-22 17:59:33'),(2,'home_mid',1,'2026-03-22 18:53:42','2026-03-22 18:53:42'),(3,'home_bottom',1,'2026-03-22 20:51:47','2026-03-22 20:51:47');
/*!40000 ALTER TABLE `lead_slots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_assignments`
--

DROP TABLE IF EXISTS `lead_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_slot_id` bigint unsigned NOT NULL,
  `lead_box_id` bigint unsigned NOT NULL,
  `override_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `override_short_text` text COLLATE utf8mb4_unicode_ci,
  `override_button_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_assignments_lead_slot_id_unique` (`lead_slot_id`),
  KEY `lead_assignments_lead_box_id_foreign` (`lead_box_id`),
  CONSTRAINT `lead_assignments_lead_box_id_foreign` FOREIGN KEY (`lead_box_id`) REFERENCES `lead_boxes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_assignments_lead_slot_id_foreign` FOREIGN KEY (`lead_slot_id`) REFERENCES `lead_slots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_assignments`
--

LOCK TABLES `lead_assignments` WRITE;
/*!40000 ALTER TABLE `lead_assignments` DISABLE KEYS */;
INSERT INTO `lead_assignments` VALUES (1,1,1,NULL,NULL,NULL,'2026-03-22 18:10:30','2026-03-22 18:10:30'),(2,2,2,NULL,NULL,NULL,'2026-03-22 18:54:37','2026-03-22 18:54:37');
/*!40000 ALTER TABLE `lead_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_box_id` bigint unsigned NOT NULL,
  `lead_slot_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `page_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leads_lead_box_id_foreign` (`lead_box_id`),
  KEY `leads_lead_slot_key_page_key_index` (`lead_slot_key`,`page_key`),
  KEY `leads_email_index` (`email`),
  CONSTRAINT `leads_lead_box_id_foreign` FOREIGN KEY (`lead_box_id`) REFERENCES `lead_boxes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-23  5:38:42
