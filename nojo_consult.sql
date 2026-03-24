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
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'marvel','marvel','2026-03-18 23:22:31','2026-03-18 23:22:31'),(2,'Tech','tech','2026-03-18 23:27:22','2026-03-18 23:27:22'),(3,'food','food','2026-03-22 02:04:02','2026-03-22 02:04:02'),(4,'trees','trees','2026-03-22 03:04:52','2026-03-22 03:04:52'),(5,'dogs','dogs','2026-03-22 03:04:57','2026-03-22 03:04:57'),(6,'clothes','clothes','2026-03-22 03:05:02','2026-03-22 03:05:02');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
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

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_08_14_170933_add_two_factor_columns_to_users_table',1),(5,'2026_03_06_000000_add_is_admin_to_users_table',2),(6,'2026_03_06_000100_create_posts_table',2),(7,'2026_03_06_000200_create_tags_table',2),(8,'2026_03_06_000300_create_post_tag_table',2),(9,'2026_03_06_000600_create_categories_table',2),(10,'2026_03_06_000700_add_category_id_to_posts_table',2),(11,'2026_03_06_000800_add_sources_to_posts_table',2),(12,'2026_03_06_001000_add_featured_image_path_to_posts_table',2),(13,'2026_03_21_034146_create_popups_table',3),(14,'2026_03_21_070000_create_popup_leads_table',4),(15,'2026_03_21_080000_add_admin_controls_to_popups_table',5),(16,'2026_03_21_090000_seed_default_consultation_popups',6),(17,'2026_03_22_000000_create_lead_boxes_table',7),(18,'2026_03_22_000100_create_lead_slots_table',7),(19,'2026_03_22_000200_create_lead_assignments_table',7),(20,'2026_03_22_000300_create_leads_table',7);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `popup_leads`
--

DROP TABLE IF EXISTS `popup_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `popup_leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `popup_id` bigint unsigned NOT NULL,
  `page_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lead_type` enum('general','buyer','seller') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `popup_leads_popup_id_foreign` (`popup_id`),
  KEY `popup_leads_page_key_index` (`page_key`),
  KEY `popup_leads_lead_type_index` (`lead_type`),
  KEY `popup_leads_email_index` (`email`),
  CONSTRAINT `popup_leads_popup_id_foreign` FOREIGN KEY (`popup_id`) REFERENCES `popups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `popup_leads`
--

LOCK TABLES `popup_leads` WRITE;
/*!40000 ALTER TABLE `popup_leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `popup_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `popups`
--

DROP TABLE IF EXISTS `popups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `popups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('general','buyer','seller','consultation','resource') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `priority` int unsigned NOT NULL DEFAULT '100',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `eyebrow` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `headline` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `cta_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Get Started',
  `success_message` text COLLATE utf8mb4_unicode_ci,
  `layout` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'centered',
  `trigger_type` enum('time','scroll','exit','click') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'time',
  `trigger_delay` int unsigned DEFAULT NULL,
  `trigger_scroll` int unsigned DEFAULT NULL,
  `target_pages` json DEFAULT NULL,
  `device` enum('all','desktop','mobile') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `frequency` enum('once_session','once_day','always') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'once_session',
  `audience` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'guests',
  `suppress_if_lead_captured` tinyint(1) NOT NULL DEFAULT '1',
  `suppression_scope` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all_lead_popups',
  `form_fields` json DEFAULT NULL,
  `lead_type` enum('general','buyer','seller') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `post_submit_action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'message',
  `post_submit_redirect_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `popups_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `popups`
--

LOCK TABLES `popups` WRITE;
/*!40000 ALTER TABLE `popups` DISABLE KEYS */;
INSERT INTO `popups` VALUES (1,'Popup 1','popup-1','consultation','primary',1,1,'Free Consultation','Start with a free consultation checklist','Tell us where you are in the process and we will help you take the next right step.','Get the checklist','Thanks. Your information was received and your next step is on the way.','centered','time',2,50,'[\"home\"]','all','once_day','everyone',1,'all_lead_popups','[\"name\", \"email\", \"phone\"]','general','message',NULL,'2026-03-21 07:21:23','2026-03-21 07:22:31'),(2,'Popup 2','popup-2','consultation','fallback',2,1,'Still Looking?','Need help deciding your next move?','Leave your information and we will follow up with guidance based on your situation.','Request help','Thanks. We received your details and will follow up shortly.','centered','time',3,NULL,'[\"consultation\", \"buyers\", \"sellers\"]','all','once_day','guests',1,'all_lead_popups','[\"name\", \"email\", \"phone\", \"message\"]','general','message',NULL,'2026-03-21 07:21:23','2026-03-21 07:21:23');
/*!40000 ALTER TABLE `popups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_tag`
--

DROP TABLE IF EXISTS `post_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_tag` (
  `post_id` bigint unsigned NOT NULL,
  `tag_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`),
  KEY `post_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `post_tag_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_tag`
--

LOCK TABLES `post_tag` WRITE;
/*!40000 ALTER TABLE `post_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `post_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sources` longtext COLLATE utf8mb4_unicode_ci,
  `featured_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `canonical_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_description` text COLLATE utf8mb4_unicode_ci,
  `og_image_path` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `noindex` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `posts_slug_unique` (`slug`),
  KEY `posts_status_published_at_index` (`status`,`published_at`),
  KEY `posts_created_by_index` (`created_by`),
  KEY `posts_updated_by_index` (`updated_by`),
  KEY `posts_category_id_foreign` (`category_id`),
  CONSTRAINT `posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `posts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `posts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (1,'spiderman helps old man','spidey1','hgfhghhgghfh','<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed mauris neque, rutrum eget consectetur eu, vulputate eu lorem. Nunc condimentum tempor est quis feugiat. Aenean placerat sit amet neque eu interdum. Praesent enim turpis, tristique non tincidunt eget, condimentum quis sapien. Nunc molestie orci a ornare mattis. In commodo placerat laoreet. Praesent vehicula sollicitudin nibh non elementum. Suspendisse tincidunt faucibus facilisis. Aenean facilisis pellentesque quam eget vulputate. Vivamus sapien leo, sagittis sed consequat non, suscipit id massa. Sed feugiat interdum turpis, id ultricies diam tristique facilisis. Nullam velit urna, viverra quis sem non, porttitor imperdiet sapien.</p><p>Sed varius elementum feugiat. Cras sagittis aliquam imperdiet. In sit amet elit eget enim commodo dapibus. Suspendisse ullamcorper ornare tellus, sit amet malesuada lorem scelerisque vitae. Nam pulvinar, quam vel ornare lacinia, purus nulla lacinia justo, ac luctus dolor nisl at ex. Vivamus lobortis massa justo, ut rhoncus odio consequat eu. Integer ut turpis sed nulla venenatis mollis. In cursus purus et magna interdum, eget tempus erat fermentum. Maecenas sollicitudin viverra purus dapibus malesuada. Phasellus ultrices risus ut nibh rutrum mollis. Cras in vulputate lacus. Vivamus ac turpis non risus blandit pharetra. Mauris a pulvinar tortor. In malesuada urna in bibendum aliquet. Pellentesque ut ipsum justo. Curabitur sollicitudin nibh et nulla mattis convallis.</p><p>Duis non ipsum ligula. Nulla turpis metus, porta ut odio in, condimentum hendrerit massa. Proin dapibus ac ante ac condimentum. Aenean suscipit urna quam, eu vulputate quam tempus nec. Vestibulum elit turpis, sodales eget bibendum vel, pharetra et mi. Ut auctor rutrum augue non convallis. Fusce nec justo vitae nulla blandit volutpat at at risus. Duis vestibulum imperdiet suscipit. Duis facilisis neque id nibh commodo maximus. Cras mattis, nunc ut luctus tempor, tortor metus finibus risus, sagittis suscipit tortor massa eget turpis. Nullam et congue elit. Aenean lorem justo, lacinia rutrum nisl ac, interdum venenatis turpis. Nullam maximus placerat turpis, sit amet vestibulum sem consectetur eget. Phasellus iaculis, purus nec volutpat blandit, nulla odio consequat ligula, eget maximus erat tortor et lacus. Maecenas et magna nec libero tempor lobortis. Pellentesque ac tellus dui.</p><p>Aliquam aliquam finibus nunc, sit amet suscipit tellus tristique sit amet. Suspendisse posuere erat vitae viverra sagittis. Quisque semper euismod consequat. Nullam rutrum porttitor lacus feugiat consequat. Curabitur a est eu dolor mollis sodales. Integer scelerisque porttitor leo. Interdum et malesuada fames ac ante ipsum primis in faucibus. Integer nibh eros, egestas et magna nec, aliquam finibus felis. In vehicula ante elit, id vestibulum lorem tincidunt sit amet. Phasellus ante elit, dapibus ut massa eu, porta euismod lorem.</p><p>Nam arcu felis, semper a libero elementum, ultricies semper turpis. Vivamus sed nisl leo. Praesent facilisis dictum leo, non suscipit leo porttitor quis. Duis quis convallis urna. Vestibulum sagittis ex a ullamcorper viverra. Aliquam egestas sapien sem, vitae mattis justo fringilla eget. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Vivamus non elit non lectus posuere elementum. Vivamus dictum et erat at rhoncus. Morbi viverra libero vitae ipsum dictum, et facilisis justo porttitor. Duis et ultricies quam.</p>',NULL,'/images/blog/sp.webp','published','2026-03-18 23:22:58',NULL,NULL,NULL,NULL,NULL,'/images/blog/sp.webp',0,1,1,'2026-03-18 23:22:31','2026-03-18 23:22:58',1),(2,'Black man typing something','black-man-typing-something',NULL,'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed mauris neque, rutrum eget consectetur eu, vulputate eu lorem. Nunc condimentum tempor est quis feugiat. Aenean placerat sit amet neque eu interdum. Praesent enim turpis, tristique non tincidunt eget, condimentum quis sapien. Nunc molestie orci a ornare mattis. In commodo placerat laoreet. Praesent vehicula sollicitudin nibh non elementum. Suspendisse tincidunt faucibus facilisis. Aenean facilisis pellentesque quam eget vulputate. Vivamus sapien leo, sagittis sed consequat non, suscipit id massa. Sed feugiat interdum turpis, id ultricies diam tristique facilisis. Nullam velit urna, viverra quis sem non, porttitor imperdiet sapien.</p><p>Sed varius elementum feugiat. Cras sagittis aliquam imperdiet. In sit amet elit eget enim commodo dapibus. Suspendisse ullamcorper ornare tellus, sit amet malesuada lorem scelerisque vitae. Nam pulvinar, quam vel ornare lacinia, purus nulla lacinia justo, ac luctus dolor nisl at ex. Vivamus lobortis massa justo, ut rhoncus odio consequat eu. Integer ut turpis sed nulla venenatis mollis. In cursus purus et magna interdum, eget tempus erat fermentum. Maecenas sollicitudin viverra purus dapibus malesuada. Phasellus ultrices risus ut nibh rutrum mollis. Cras in vulputate lacus. Vivamus ac turpis non risus blandit pharetra. Mauris a pulvinar tortor. In malesuada urna in bibendum aliquet. Pellentesque ut ipsum justo. Curabitur sollicitudin nibh et nulla mattis convallis.</p><p>Duis non ipsum ligula. Nulla turpis metus, porta ut odio in, condimentum hendrerit massa. Proin dapibus ac ante ac condimentum. Aenean suscipit urna quam, eu vulputate quam tempus nec. Vestibulum elit turpis, sodales eget bibendum vel, pharetra et mi. Ut auctor rutrum augue non convallis. Fusce nec justo vitae nulla blandit volutpat at at risus. Duis vestibulum imperdiet suscipit. Duis facilisis neque id nibh commodo maximus. Cras mattis, nunc ut luctus tempor, tortor metus finibus risus, sagittis suscipit tortor massa eget turpis. Nullam et congue elit. Aenean lorem justo, lacinia rutrum nisl ac, interdum venenatis turpis. Nullam maximus placerat turpis, sit amet vestibulum sem consectetur eget. Phasellus iaculis, purus nec volutpat blandit, nulla odio consequat ligula, eget maximus erat tortor et lacus. Maecenas et magna nec libero tempor lobortis. Pellentesque ac tellus dui.</p><p>Aliquam aliquam finibus nunc, sit amet suscipit tellus tristique sit amet. Suspendisse posuere erat vitae viverra sagittis. Quisque semper euismod consequat. Nullam rutrum porttitor lacus feugiat consequat. Curabitur a est eu dolor mollis sodales. Integer scelerisque porttitor leo. Interdum et malesuada fames ac ante ipsum primis in faucibus. Integer nibh eros, egestas et magna nec, aliquam finibus felis. In vehicula ante elit, id vestibulum lorem tincidunt sit amet. Phasellus ante elit, dapibus ut massa eu, porta euismod lorem.</p><p>Nam arcu felis, semper a libero elementum, ultricies semper turpis. Vivamus sed nisl leo. Praesent facilisis dictum leo, non suscipit leo porttitor quis. Duis quis convallis urna. Vestibulum sagittis ex a ullamcorper viverra. Aliquam egestas sapien sem, vitae mattis justo fringilla eget. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Vivamus non elit non lectus posuere elementum. Vivamus dictum et erat at rhoncus. Morbi viverra libero vitae ipsum dictum, et facilisis justo porttitor. Duis et ultricies quam.</p>',NULL,'/images/blog/black-man-typing-something.webp','published','2026-03-21 13:52:04',NULL,NULL,NULL,NULL,NULL,'/images/blog/blog-image-024.jpg',0,1,1,'2026-03-18 23:27:22','2026-03-22 22:23:22',2);
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('70pDxXcFUYwXVYLH8o9lRWcYuWBJuIKRBSkDQb5V',1,'192.168.65.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:148.0) Gecko/20100101 Firefox/148.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTGFGOGxXdnNJVnYwdjhLeVducDVudDZ6a1d1M3RwZkU5OFZKVGRRUSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJuZXciO2E6MDp7fXM6Mzoib2xkIjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDM6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA4OC9hZG1pbi9jb250ZW50LWZvcm11bGEiO3M6NToicm91dGUiO3M6Mjc6ImFkbWluLmNvbnRlbnQtZm9ybXVsYS5pbmRleCI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==',1774240786);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES (1,'pit bull','pit-bull','2026-03-22 02:27:26','2026-03-22 02:27:26');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'jc','a@b.com',NULL,'$2y$12$441yzYkt2018htsIpp6ej.7zMEpmelTnv0JnDGK64L2RZ55jb1Ewq',1,NULL,NULL,NULL,NULL,'2026-03-16 22:28:00','2026-03-17 01:47:17');
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

-- Dump completed on 2026-03-23  5:35:38
