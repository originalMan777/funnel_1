-- MySQL dump 10.13  Distrib 8.4.8, for Linux (x86_64)
--
-- Host: localhost    Database: funnel_1
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
-- Table structure for table `acquisition_campaigns`
--

DROP TABLE IF EXISTS `acquisition_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acquisition_campaigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `campaign_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'outbound',
  `industry` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `market_city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `market_state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `daily_touch_limit` int unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `settings` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acquisition_campaigns_slug_unique` (`slug`),
  KEY `acquisition_campaigns_created_by_foreign` (`created_by`),
  KEY `acquisition_campaigns_updated_by_foreign` (`updated_by`),
  KEY `acquisition_campaigns_campaign_type_index` (`campaign_type`),
  KEY `acquisition_campaigns_industry_index` (`industry`),
  KEY `acquisition_campaigns_market_city_index` (`market_city`),
  KEY `acquisition_campaigns_market_state_index` (`market_state`),
  KEY `acquisition_campaigns_status_index` (`status`),
  CONSTRAINT `acquisition_campaigns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_campaigns_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acquisition_campaigns`
--

LOCK TABLES `acquisition_campaigns` WRITE;
/*!40000 ALTER TABLE `acquisition_campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `acquisition_campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acquisition_companies`
--

DROP TABLE IF EXISTS `acquisition_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acquisition_companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `website_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `industry` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_industry` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `fit_score` smallint unsigned DEFAULT NULL,
  `data_source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acquisition_companies_created_by_foreign` (`created_by`),
  KEY `acquisition_companies_updated_by_foreign` (`updated_by`),
  KEY `acquisition_companies_name_index` (`name`),
  KEY `acquisition_companies_domain_index` (`domain`),
  KEY `acquisition_companies_industry_index` (`industry`),
  KEY `acquisition_companies_city_index` (`city`),
  KEY `acquisition_companies_state_index` (`state`),
  KEY `acquisition_companies_status_index` (`status`),
  CONSTRAINT `acquisition_companies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_companies_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acquisition_companies`
--

LOCK TABLES `acquisition_companies` WRITE;
/*!40000 ALTER TABLE `acquisition_companies` DISABLE KEYS */;
/*!40000 ALTER TABLE `acquisition_companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acquisition_contact_campaigns`
--

DROP TABLE IF EXISTS `acquisition_contact_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acquisition_contact_campaigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acquisition_contact_id` bigint unsigned NOT NULL,
  `acquisition_campaign_id` bigint unsigned NOT NULL,
  `acquisition_sequence_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `entered_at` timestamp NULL DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `exited_at` timestamp NULL DEFAULT NULL,
  `exit_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acq_contact_campaigns_contact_campaign_unique` (`acquisition_contact_id`,`acquisition_campaign_id`),
  KEY `acquisition_contact_campaigns_acquisition_campaign_id_foreign` (`acquisition_campaign_id`),
  KEY `acquisition_contact_campaigns_acquisition_sequence_id_foreign` (`acquisition_sequence_id`),
  KEY `acquisition_contact_campaigns_status_index` (`status`),
  KEY `acquisition_contact_campaigns_entered_at_index` (`entered_at`),
  CONSTRAINT `acquisition_contact_campaigns_acquisition_campaign_id_foreign` FOREIGN KEY (`acquisition_campaign_id`) REFERENCES `acquisition_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acquisition_contact_campaigns_acquisition_contact_id_foreign` FOREIGN KEY (`acquisition_contact_id`) REFERENCES `acquisition_contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acquisition_contact_campaigns_acquisition_sequence_id_foreign` FOREIGN KEY (`acquisition_sequence_id`) REFERENCES `acquisition_sequences` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acquisition_contact_campaigns`
--

LOCK TABLES `acquisition_contact_campaigns` WRITE;
/*!40000 ALTER TABLE `acquisition_contact_campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `acquisition_contact_campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acquisition_contacts`
--

DROP TABLE IF EXISTS `acquisition_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acquisition_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acquisition_company_id` bigint unsigned DEFAULT NULL,
  `acquisition_person_id` bigint unsigned DEFAULT NULL,
  `owner_user_id` bigint unsigned DEFAULT NULL,
  `contact_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inbound',
  `state` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `source_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `normalized_email_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `normalized_phone_key` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primary_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primary_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_name_snapshot` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website_url_snapshot` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_snapshot` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `next_action_at` timestamp NULL DEFAULT NULL,
  `is_suppressed` tinyint(1) NOT NULL DEFAULT '0',
  `suppressed_at` timestamp NULL DEFAULT NULL,
  `suppression_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qualified_at` timestamp NULL DEFAULT NULL,
  `converted_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acq_contacts_normalized_email_unique` (`normalized_email_key`),
  UNIQUE KEY `acq_contacts_normalized_phone_unique` (`normalized_phone_key`),
  KEY `acquisition_contacts_acquisition_company_id_foreign` (`acquisition_company_id`),
  KEY `acquisition_contacts_acquisition_person_id_foreign` (`acquisition_person_id`),
  KEY `acquisition_contacts_owner_user_id_foreign` (`owner_user_id`),
  KEY `acquisition_contacts_created_by_foreign` (`created_by`),
  KEY `acquisition_contacts_updated_by_foreign` (`updated_by`),
  KEY `acquisition_contacts_contact_type_index` (`contact_type`),
  KEY `acquisition_contacts_state_index` (`state`),
  KEY `acquisition_contacts_source_type_index` (`source_type`),
  KEY `acquisition_contacts_primary_email_index` (`primary_email`),
  KEY `acquisition_contacts_primary_phone_index` (`primary_phone`),
  KEY `acquisition_contacts_display_name_index` (`display_name`),
  KEY `acquisition_contacts_last_activity_at_index` (`last_activity_at`),
  KEY `acquisition_contacts_next_action_at_index` (`next_action_at`),
  KEY `acquisition_contacts_is_suppressed_index` (`is_suppressed`),
  CONSTRAINT `acquisition_contacts_acquisition_company_id_foreign` FOREIGN KEY (`acquisition_company_id`) REFERENCES `acquisition_companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_contacts_acquisition_person_id_foreign` FOREIGN KEY (`acquisition_person_id`) REFERENCES `acquisition_people` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_contacts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_contacts_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_contacts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acquisition_contacts`
--

LOCK TABLES `acquisition_contacts` WRITE;
/*!40000 ALTER TABLE `acquisition_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `acquisition_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acquisition_events`
--

DROP TABLE IF EXISTS `acquisition_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acquisition_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acquisition_contact_id` bigint unsigned NOT NULL,
  `acquisition_company_id` bigint unsigned DEFAULT NULL,
  `acquisition_person_id` bigint unsigned DEFAULT NULL,
  `acquisition_campaign_id` bigint unsigned DEFAULT NULL,
  `acquisition_touch_id` bigint unsigned DEFAULT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `related_table` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_id` bigint unsigned DEFAULT NULL,
  `summary` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` json DEFAULT NULL,
  `occurred_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acquisition_events_acquisition_contact_id_foreign` (`acquisition_contact_id`),
  KEY `acquisition_events_acquisition_company_id_foreign` (`acquisition_company_id`),
  KEY `acquisition_events_acquisition_person_id_foreign` (`acquisition_person_id`),
  KEY `acquisition_events_acquisition_campaign_id_foreign` (`acquisition_campaign_id`),
  KEY `acquisition_events_acquisition_touch_id_foreign` (`acquisition_touch_id`),
  KEY `acquisition_events_actor_user_id_foreign` (`actor_user_id`),
  KEY `acquisition_events_event_type_index` (`event_type`),
  KEY `acquisition_events_channel_index` (`channel`),
  KEY `acquisition_events_related_table_index` (`related_table`),
  KEY `acquisition_events_related_id_index` (`related_id`),
  KEY `acquisition_events_occurred_at_index` (`occurred_at`),
  CONSTRAINT `acquisition_events_acquisition_campaign_id_foreign` FOREIGN KEY (`acquisition_campaign_id`) REFERENCES `acquisition_campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_events_acquisition_company_id_foreign` FOREIGN KEY (`acquisition_company_id`) REFERENCES `acquisition_companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_events_acquisition_contact_id_foreign` FOREIGN KEY (`acquisition_contact_id`) REFERENCES `acquisition_contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acquisition_events_acquisition_person_id_foreign` FOREIGN KEY (`acquisition_person_id`) REFERENCES `acquisition_people` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_events_acquisition_touch_id_foreign` FOREIGN KEY (`acquisition_touch_id`) REFERENCES `acquisition_touches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_events_actor_user_id_foreign` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acquisition_events`
--

LOCK TABLES `acquisition_events` WRITE;
/*!40000 ALTER TABLE `acquisition_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `acquisition_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acquisition_paths`
--

DROP TABLE IF EXISTS `acquisition_paths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acquisition_paths` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acquisition_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_context` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acquisition_paths_path_key_unique` (`path_key`),
  KEY `acquisition_paths_acquisition_id_foreign` (`acquisition_id`),
  KEY `acquisition_paths_service_id_foreign` (`service_id`),
  KEY `acquisition_paths_entry_type_index` (`entry_type`),
  KEY `acquisition_paths_source_context_index` (`source_context`),
  KEY `acquisition_paths_is_active_index` (`is_active`),
  CONSTRAINT `acquisition_paths_acquisition_id_foreign` FOREIGN KEY (`acquisition_id`) REFERENCES `acquisitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acquisition_paths_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acquisition_paths`
--

LOCK TABLES `acquisition_paths` WRITE;
/*!40000 ALTER TABLE `acquisition_paths` DISABLE KEYS */;
/*!40000 ALTER TABLE `acquisition_paths` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acquisition_people`
--

DROP TABLE IF EXISTS `acquisition_people`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acquisition_people` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acquisition_company_id` bigint unsigned DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary_contact` tinyint(1) NOT NULL DEFAULT '0',
  `linkedin_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acquisition_people_acquisition_company_id_foreign` (`acquisition_company_id`),
  KEY `acquisition_people_created_by_foreign` (`created_by`),
  KEY `acquisition_people_updated_by_foreign` (`updated_by`),
  KEY `acquisition_people_full_name_index` (`full_name`),
  KEY `acquisition_people_email_index` (`email`),
  KEY `acquisition_people_phone_index` (`phone`),
  CONSTRAINT `acquisition_people_acquisition_company_id_foreign` FOREIGN KEY (`acquisition_company_id`) REFERENCES `acquisition_companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_people_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_people_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acquisition_people`
--

LOCK TABLES `acquisition_people` WRITE;
/*!40000 ALTER TABLE `acquisition_people` DISABLE KEYS */;
/*!40000 ALTER TABLE `acquisition_people` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acquisition_sequence_steps`
--

DROP TABLE IF EXISTS `acquisition_sequence_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acquisition_sequence_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acquisition_sequence_id` bigint unsigned NOT NULL,
  `step_order` int unsigned NOT NULL,
  `step_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delay_amount` int unsigned NOT NULL DEFAULT '0',
  `delay_unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'days',
  `requires_approval` tinyint(1) NOT NULL DEFAULT '1',
  `template_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template_subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template_body` longtext COLLATE utf8mb4_unicode_ci,
  `settings` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acq_sequence_steps_sequence_order_unique` (`acquisition_sequence_id`,`step_order`),
  KEY `acquisition_sequence_steps_step_order_index` (`step_order`),
  KEY `acquisition_sequence_steps_step_type_index` (`step_type`),
  KEY `acquisition_sequence_steps_is_active_index` (`is_active`),
  CONSTRAINT `acquisition_sequence_steps_acquisition_sequence_id_foreign` FOREIGN KEY (`acquisition_sequence_id`) REFERENCES `acquisition_sequences` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acquisition_sequence_steps`
--

LOCK TABLES `acquisition_sequence_steps` WRITE;
/*!40000 ALTER TABLE `acquisition_sequence_steps` DISABLE KEYS */;
/*!40000 ALTER TABLE `acquisition_sequence_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acquisition_sequences`
--

DROP TABLE IF EXISTS `acquisition_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acquisition_sequences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acquisition_campaign_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'outbound',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `description` text COLLATE utf8mb4_unicode_ci,
  `settings` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acquisition_sequences_acquisition_campaign_id_foreign` (`acquisition_campaign_id`),
  KEY `acquisition_sequences_created_by_foreign` (`created_by`),
  KEY `acquisition_sequences_updated_by_foreign` (`updated_by`),
  KEY `acquisition_sequences_sequence_type_index` (`sequence_type`),
  KEY `acquisition_sequences_status_index` (`status`),
  CONSTRAINT `acquisition_sequences_acquisition_campaign_id_foreign` FOREIGN KEY (`acquisition_campaign_id`) REFERENCES `acquisition_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acquisition_sequences_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_sequences_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acquisition_sequences`
--

LOCK TABLES `acquisition_sequences` WRITE;
/*!40000 ALTER TABLE `acquisition_sequences` DISABLE KEYS */;
/*!40000 ALTER TABLE `acquisition_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acquisition_sources`
--

DROP TABLE IF EXISTS `acquisition_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acquisition_sources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acquisition_contact_id` bigint unsigned NOT NULL,
  `source_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_table` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_record_id` bigint unsigned DEFAULT NULL,
  `page_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_medium` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_campaign` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referrer_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acquisition_sources_acquisition_contact_id_foreign` (`acquisition_contact_id`),
  KEY `acquisition_sources_source_type_index` (`source_type`),
  KEY `acquisition_sources_source_table_index` (`source_table`),
  KEY `acquisition_sources_source_record_id_index` (`source_record_id`),
  KEY `acquisition_sources_page_key_index` (`page_key`),
  CONSTRAINT `acquisition_sources_acquisition_contact_id_foreign` FOREIGN KEY (`acquisition_contact_id`) REFERENCES `acquisition_contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acquisition_sources`
--

LOCK TABLES `acquisition_sources` WRITE;
/*!40000 ALTER TABLE `acquisition_sources` DISABLE KEYS */;
/*!40000 ALTER TABLE `acquisition_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acquisition_touches`
--

DROP TABLE IF EXISTS `acquisition_touches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acquisition_touches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acquisition_contact_id` bigint unsigned NOT NULL,
  `acquisition_campaign_id` bigint unsigned DEFAULT NULL,
  `acquisition_sequence_id` bigint unsigned DEFAULT NULL,
  `acquisition_sequence_step_id` bigint unsigned DEFAULT NULL,
  `owner_user_id` bigint unsigned DEFAULT NULL,
  `touch_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `scheduled_for` timestamp NOT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci,
  `recipient_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `response_detected_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acquisition_touches_acquisition_contact_id_foreign` (`acquisition_contact_id`),
  KEY `acquisition_touches_acquisition_campaign_id_foreign` (`acquisition_campaign_id`),
  KEY `acquisition_touches_acquisition_sequence_id_foreign` (`acquisition_sequence_id`),
  KEY `acquisition_touches_acquisition_sequence_step_id_foreign` (`acquisition_sequence_step_id`),
  KEY `acquisition_touches_owner_user_id_foreign` (`owner_user_id`),
  KEY `acquisition_touches_touch_type_index` (`touch_type`),
  KEY `acquisition_touches_status_index` (`status`),
  KEY `acquisition_touches_scheduled_for_index` (`scheduled_for`),
  KEY `acquisition_touches_recipient_email_index` (`recipient_email`),
  KEY `acquisition_touches_recipient_phone_index` (`recipient_phone`),
  KEY `acquisition_touches_provider_message_id_index` (`provider_message_id`),
  CONSTRAINT `acquisition_touches_acquisition_campaign_id_foreign` FOREIGN KEY (`acquisition_campaign_id`) REFERENCES `acquisition_campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_touches_acquisition_contact_id_foreign` FOREIGN KEY (`acquisition_contact_id`) REFERENCES `acquisition_contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acquisition_touches_acquisition_sequence_id_foreign` FOREIGN KEY (`acquisition_sequence_id`) REFERENCES `acquisition_sequences` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_touches_acquisition_sequence_step_id_foreign` FOREIGN KEY (`acquisition_sequence_step_id`) REFERENCES `acquisition_sequence_steps` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acquisition_touches_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acquisition_touches`
--

LOCK TABLES `acquisition_touches` WRITE;
/*!40000 ALTER TABLE `acquisition_touches` DISABLE KEYS */;
/*!40000 ALTER TABLE `acquisition_touches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acquisitions`
--

DROP TABLE IF EXISTS `acquisitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acquisitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acquisitions_slug_unique` (`slug`),
  KEY `acquisitions_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acquisitions`
--

LOCK TABLES `acquisitions` WRITE;
/*!40000 ALTER TABLE `acquisitions` DISABLE KEYS */;
/*!40000 ALTER TABLE `acquisitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_notifications`
--

DROP TABLE IF EXISTS `admin_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `priority` int unsigned NOT NULL DEFAULT '100',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `link_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` bigint unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_notifications_is_read_created_at_index` (`is_read`,`created_at`),
  KEY `admin_notifications_type_key_index` (`type_key`),
  KEY `admin_notifications_status_index` (`status`),
  KEY `admin_notifications_priority_index` (`priority`),
  KEY `admin_notifications_is_read_index` (`is_read`),
  KEY `admin_notifications_source_type_index` (`source_type`),
  KEY `admin_notifications_source_id_index` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_notifications`
--

LOCK TABLES `admin_notifications` WRITE;
/*!40000 ALTER TABLE `admin_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_index_sections`
--

DROP TABLE IF EXISTS `blog_index_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_index_sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `section_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `source_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'latest',
  `category_id` bigint unsigned DEFAULT NULL,
  `title_override` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_index_sections_section_key_unique` (`section_key`),
  KEY `blog_index_sections_category_id_foreign` (`category_id`),
  CONSTRAINT `blog_index_sections_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_index_sections`
--

LOCK TABLES `blog_index_sections` WRITE;
/*!40000 ALTER TABLE `blog_index_sections` DISABLE KEYS */;
INSERT INTO `blog_index_sections` VALUES (1,'wide_section',1,'latest',NULL,NULL,'2026-04-17 03:12:51','2026-04-17 03:12:51'),(2,'cluster_left',1,'latest',NULL,NULL,'2026-04-17 03:12:51','2026-04-17 03:12:51'),(3,'cluster_right',1,'latest',NULL,NULL,'2026-04-17 03:12:51','2026-04-17 03:12:51');
/*!40000 ALTER TABLE `blog_index_sections` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communication_deliveries`
--

DROP TABLE IF EXISTS `communication_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `communication_deliveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `communication_event_id` bigint unsigned NOT NULL,
  `communication_template_id` bigint unsigned DEFAULT NULL,
  `communication_template_version_id` bigint unsigned DEFAULT NULL,
  `is_test` tinyint(1) NOT NULL DEFAULT '0',
  `action_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `payload` json DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `communication_deliveries_communication_event_id_foreign` (`communication_event_id`),
  KEY `communication_deliveries_action_key_index` (`action_key`),
  KEY `communication_deliveries_channel_index` (`channel`),
  KEY `communication_deliveries_provider_index` (`provider`),
  KEY `communication_deliveries_recipient_email_index` (`recipient_email`),
  KEY `communication_deliveries_status_index` (`status`),
  KEY `communication_deliveries_provider_message_id_index` (`provider_message_id`),
  KEY `communication_deliveries_template_index` (`communication_template_id`),
  KEY `communication_deliveries_template_version_index` (`communication_template_version_id`),
  KEY `communication_deliveries_is_test_index` (`is_test`),
  CONSTRAINT `cd_template_fk` FOREIGN KEY (`communication_template_id`) REFERENCES `communication_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cd_template_version_fk` FOREIGN KEY (`communication_template_version_id`) REFERENCES `communication_template_versions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `communication_deliveries_communication_event_id_foreign` FOREIGN KEY (`communication_event_id`) REFERENCES `communication_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communication_deliveries`
--

LOCK TABLES `communication_deliveries` WRITE;
/*!40000 ALTER TABLE `communication_deliveries` DISABLE KEYS */;
/*!40000 ALTER TABLE `communication_deliveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communication_events`
--

DROP TABLE IF EXISTS `communication_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `communication_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `acquisition_contact_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payload` json DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `communication_events_acquisition_contact_id_foreign` (`acquisition_contact_id`),
  KEY `communication_events_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `communication_events_event_key_index` (`event_key`),
  KEY `communication_events_status_index` (`status`),
  CONSTRAINT `communication_events_acquisition_contact_id_foreign` FOREIGN KEY (`acquisition_contact_id`) REFERENCES `acquisition_contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communication_events`
--

LOCK TABLES `communication_events` WRITE;
/*!40000 ALTER TABLE `communication_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `communication_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communication_settings`
--

DROP TABLE IF EXISTS `communication_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `communication_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `communication_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communication_settings`
--

LOCK TABLES `communication_settings` WRITE;
/*!40000 ALTER TABLE `communication_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `communication_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communication_template_bindings`
--

DROP TABLE IF EXISTS `communication_template_bindings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `communication_template_bindings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `action_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `communication_template_id` bigint unsigned NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `priority` int unsigned NOT NULL DEFAULT '100',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `communication_template_bindings_event_channel_action_unique` (`event_key`,`channel`,`action_key`),
  KEY `communication_template_bindings_event_channel_enabled_index` (`event_key`,`channel`,`is_enabled`),
  KEY `communication_template_bindings_template_index` (`communication_template_id`),
  CONSTRAINT `ctb_template_fk` FOREIGN KEY (`communication_template_id`) REFERENCES `communication_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communication_template_bindings`
--

LOCK TABLES `communication_template_bindings` WRITE;
/*!40000 ALTER TABLE `communication_template_bindings` DISABLE KEYS */;
/*!40000 ALTER TABLE `communication_template_bindings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communication_template_versions`
--

DROP TABLE IF EXISTS `communication_template_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `communication_template_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `communication_template_id` bigint unsigned NOT NULL,
  `version_number` int unsigned NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preview_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `headline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `html_body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_body` longtext COLLATE utf8mb4_unicode_ci,
  `variables_schema` json DEFAULT NULL,
  `sample_payload` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `communication_template_versions_template_version_unique` (`communication_template_id`,`version_number`),
  KEY `communication_template_versions_created_by_foreign` (`created_by`),
  KEY `communication_template_versions_template_published_index` (`communication_template_id`,`is_published`),
  CONSTRAINT `communication_template_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ctv_template_fk` FOREIGN KEY (`communication_template_id`) REFERENCES `communication_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communication_template_versions`
--

LOCK TABLES `communication_template_versions` WRITE;
/*!40000 ALTER TABLE `communication_template_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `communication_template_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communication_templates`
--

DROP TABLE IF EXISTS `communication_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `communication_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'transactional',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `description` text COLLATE utf8mb4_unicode_ci,
  `from_name_override` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_email_override` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reply_to_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `current_version_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `communication_templates_key_unique` (`key`),
  KEY `communication_templates_created_by_foreign` (`created_by`),
  KEY `communication_templates_updated_by_foreign` (`updated_by`),
  KEY `communication_templates_channel_category_index` (`channel`,`category`),
  KEY `communication_templates_channel_index` (`channel`),
  KEY `communication_templates_category_index` (`category`),
  KEY `communication_templates_status_index` (`status`),
  KEY `ct_current_version_fk` (`current_version_id`),
  CONSTRAINT `communication_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `communication_templates_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ct_current_version_fk` FOREIGN KEY (`current_version_id`) REFERENCES `communication_template_versions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communication_templates`
--

LOCK TABLES `communication_templates` WRITE;
/*!40000 ALTER TABLE `communication_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `communication_templates` ENABLE KEYS */;
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
  `acquisition_id` bigint unsigned DEFAULT NULL,
  `service_id` bigint unsigned DEFAULT NULL,
  `acquisition_path_id` bigint unsigned DEFAULT NULL,
  `acquisition_path_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `override_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `override_short_text` text COLLATE utf8mb4_unicode_ci,
  `override_button_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_assignments_lead_slot_id_unique` (`lead_slot_id`),
  KEY `lead_assignments_lead_box_id_foreign` (`lead_box_id`),
  KEY `lead_assignments_acquisition_id_foreign` (`acquisition_id`),
  KEY `lead_assignments_service_id_foreign` (`service_id`),
  KEY `lead_assignments_acquisition_path_id_foreign` (`acquisition_path_id`),
  CONSTRAINT `lead_assignments_acquisition_id_foreign` FOREIGN KEY (`acquisition_id`) REFERENCES `acquisitions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lead_assignments_acquisition_path_id_foreign` FOREIGN KEY (`acquisition_path_id`) REFERENCES `acquisition_paths` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lead_assignments_lead_box_id_foreign` FOREIGN KEY (`lead_box_id`) REFERENCES `lead_boxes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_assignments_lead_slot_id_foreign` FOREIGN KEY (`lead_slot_id`) REFERENCES `lead_slots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_assignments_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_assignments`
--

LOCK TABLES `lead_assignments` WRITE;
/*!40000 ALTER TABLE `lead_assignments` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_boxes`
--

LOCK TABLES `lead_boxes` WRITE;
/*!40000 ALTER TABLE `lead_boxes` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_slots`
--

LOCK TABLES `lead_slots` WRITE;
/*!40000 ALTER TABLE `lead_slots` DISABLE KEYS */;
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
  `acquisition_contact_id` bigint unsigned DEFAULT NULL,
  `acquisition_id` bigint unsigned DEFAULT NULL,
  `service_id` bigint unsigned DEFAULT NULL,
  `acquisition_path_id` bigint unsigned DEFAULT NULL,
  `acquisition_path_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lead_box_id` bigint unsigned DEFAULT NULL,
  `lead_slot_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_slot_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_popup_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `page_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_page_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lead_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leads_lead_box_id_foreign` (`lead_box_id`),
  KEY `leads_lead_slot_key_page_key_index` (`lead_slot_key`,`page_key`),
  KEY `leads_email_index` (`email`),
  KEY `leads_acquisition_contact_id_foreign` (`acquisition_contact_id`),
  KEY `leads_acquisition_id_foreign` (`acquisition_id`),
  KEY `leads_service_id_foreign` (`service_id`),
  KEY `leads_acquisition_path_id_foreign` (`acquisition_path_id`),
  CONSTRAINT `leads_acquisition_contact_id_foreign` FOREIGN KEY (`acquisition_contact_id`) REFERENCES `acquisition_contacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_acquisition_id_foreign` FOREIGN KEY (`acquisition_id`) REFERENCES `acquisitions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_acquisition_path_id_foreign` FOREIGN KEY (`acquisition_path_id`) REFERENCES `acquisition_paths` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_lead_box_id_foreign` FOREIGN KEY (`lead_box_id`) REFERENCES `lead_boxes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leads_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
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
-- Table structure for table `marketing_contact_syncs`
--

DROP TABLE IF EXISTS `marketing_contact_syncs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketing_contact_syncs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acquisition_contact_id` bigint unsigned NOT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `audience_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_contact_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_sync_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_error_message` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `marketing_sync_contact_provider_audience_unique` (`acquisition_contact_id`,`provider`,`audience_key`),
  KEY `marketing_contact_syncs_provider_index` (`provider`),
  KEY `marketing_contact_syncs_audience_key_index` (`audience_key`),
  KEY `marketing_contact_syncs_email_index` (`email`),
  KEY `marketing_contact_syncs_external_contact_id_index` (`external_contact_id`),
  KEY `marketing_contact_syncs_last_sync_status_index` (`last_sync_status`),
  CONSTRAINT `marketing_contact_syncs_acquisition_contact_id_foreign` FOREIGN KEY (`acquisition_contact_id`) REFERENCES `acquisition_contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketing_contact_syncs`
--

LOCK TABLES `marketing_contact_syncs` WRITE;
/*!40000 ALTER TABLE `marketing_contact_syncs` DISABLE KEYS */;
/*!40000 ALTER TABLE `marketing_contact_syncs` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_08_14_170933_add_two_factor_columns_to_users_table',1),(5,'2026_03_06_000000_add_is_admin_to_users_table',1),(6,'2026_03_06_000100_create_posts_table',1),(7,'2026_03_06_000200_create_tags_table',1),(8,'2026_03_06_000300_create_post_tag_table',1),(9,'2026_03_06_000600_create_categories_table',1),(10,'2026_03_06_000700_add_category_id_to_posts_table',1),(11,'2026_03_06_000800_add_sources_to_posts_table',1),(12,'2026_03_06_001000_add_featured_image_path_to_posts_table',1),(13,'2026_03_21_034146_create_popups_table',1),(14,'2026_03_21_070000_create_popup_leads_table',1),(15,'2026_03_21_080000_add_admin_controls_to_popups_table',1),(16,'2026_03_21_090000_seed_default_consultation_popups',1),(17,'2026_03_22_000000_create_lead_boxes_table',1),(18,'2026_03_22_000100_create_lead_slots_table',1),(19,'2026_03_22_000200_create_lead_assignments_table',1),(20,'2026_03_22_000300_create_leads_table',1),(21,'2026_03_25_000001_add_is_featured_to_posts_table',1),(22,'2026_03_25_000002_create_blog_index_sections_table',1),(23,'2026_03_29_000001_create_security_audit_logs_table',1),(24,'2026_03_29_022137_make_leads_lead_box_and_slot_nullable',1),(25,'2026_03_30_004016_add_deleted_at_to_posts_table',1),(26,'2026_03_30_005027_add_archived_at_to_posts_table',1),(27,'2026_04_11_100000_create_acquisition_companies_table',1),(28,'2026_04_11_100100_create_acquisition_people_table',1),(29,'2026_04_11_100200_create_acquisition_contacts_table',1),(30,'2026_04_11_100300_create_acquisition_sources_table',1),(31,'2026_04_11_100400_create_acquisition_campaigns_table',1),(32,'2026_04_11_100500_create_acquisition_sequences_table',1),(33,'2026_04_11_100600_create_acquisition_sequence_steps_table',1),(34,'2026_04_11_100700_create_acquisition_contact_campaigns_table',1),(35,'2026_04_11_100800_create_acquisition_touches_table',1),(36,'2026_04_11_100900_create_acquisition_events_table',1),(37,'2026_04_11_101000_add_acquisition_contact_id_to_leads_table',1),(38,'2026_04_11_101100_add_acquisition_contact_id_to_popup_leads_table',1),(39,'2026_04_14_120000_add_identity_keys_to_acquisition_contacts_table',1),(40,'2026_04_14_150000_create_acquisitions_table',1),(41,'2026_04_14_150100_create_services_table',1),(42,'2026_04_14_150200_create_acquisition_paths_table',1),(43,'2026_04_14_150300_add_acquisition_context_to_leads_table',1),(44,'2026_04_14_150400_add_acquisition_context_to_lead_assignments_table',1),(45,'2026_04_15_000000_create_communication_events_table',1),(46,'2026_04_15_000100_create_communication_deliveries_table',1),(47,'2026_04_15_000200_create_marketing_contact_syncs_table',1),(48,'2026_04_15_000300_create_communication_settings_table',1),(49,'2026_04_15_000400_create_communication_templates_table',1),(50,'2026_04_15_000500_create_communication_template_versions_table',1),(51,'2026_04_15_000600_add_current_version_id_to_communication_templates_table',1),(52,'2026_04_15_000700_create_communication_template_bindings_table',1),(53,'2026_04_15_000800_add_template_traceability_to_communication_deliveries_table',1),(54,'2026_04_16_000900_create_admin_notifications_table',1);
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
  `acquisition_contact_id` bigint unsigned DEFAULT NULL,
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
  KEY `popup_leads_acquisition_contact_id_foreign` (`acquisition_contact_id`),
  CONSTRAINT `popup_leads_acquisition_contact_id_foreign` FOREIGN KEY (`acquisition_contact_id`) REFERENCES `acquisition_contacts` (`id`) ON DELETE SET NULL,
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
INSERT INTO `popups` VALUES (1,'Popup 1','popup-1','consultation','primary',1,1,'Free Consultation','Start with a free consultation checklist','Tell us where you are in the process and we will help you take the next right step.','Get the checklist','Thanks. Your information was received and your next step is on the way.','centered','time',2,NULL,'[\"home\"]','all','once_day','guests',1,'all_lead_popups','[\"name\", \"email\", \"phone\"]','general','message',NULL,'2026-04-17 03:12:51','2026-04-17 03:12:51'),(2,'Popup 2','popup-2','consultation','fallback',2,1,'Still Looking?','Need help deciding your next move?','Leave your information and we will follow up with guidance based on your situation.','Request help','Thanks. We received your details and will follow up shortly.','centered','time',3,NULL,'[\"consultation\", \"buyers\", \"sellers\"]','all','once_day','guests',1,'all_lead_popups','[\"name\", \"email\", \"phone\", \"message\"]','general','message',NULL,'2026-04-17 03:12:51','2026-04-17 03:12:51');
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
  `featured_image_path` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `featured_image_alt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `featured_image_caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
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
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `posts_slug_unique` (`slug`),
  KEY `posts_status_published_at_index` (`status`,`published_at`),
  KEY `posts_created_by_index` (`created_by`),
  KEY `posts_updated_by_index` (`updated_by`),
  KEY `posts_category_id_foreign` (`category_id`),
  CONSTRAINT `posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `posts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `posts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_audit_logs`
--

DROP TABLE IF EXISTS `security_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `event` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `context` json DEFAULT NULL,
  `occurred_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `security_audit_logs_event_index` (`event`),
  KEY `security_audit_logs_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `security_audit_logs_user_id_index` (`user_id`),
  KEY `security_audit_logs_occurred_at_index` (`occurred_at`),
  CONSTRAINT `security_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_audit_logs`
--

LOCK TABLES `security_audit_logs` WRITE;
/*!40000 ALTER TABLE `security_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acquisition_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `services_acquisition_id_slug_unique` (`acquisition_id`,`slug`),
  KEY `services_is_active_index` (`is_active`),
  CONSTRAINT `services_acquisition_id_foreign` FOREIGN KEY (`acquisition_id`) REFERENCES `acquisitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'jc','a@b.com',NULL,'$2y$12$Saj4hVVOc7GUlxAfu27NguR.qM8FJqj4RAFYmWZpCqWq0XoTx.bES',0,NULL,NULL,NULL,NULL,'2026-04-17 03:16:28','2026-04-17 03:16:28'),(2,'Jameel Admin','your-real-admin@email.com','2026-04-17 03:57:04','$2y$12$ojk4TmSDhLGJp9rH2dmheO2I4sRQlHptaIoGkQDcqZAEo1VJoxI4u',1,NULL,NULL,NULL,'DUpdvZSUVZ','2026-04-17 03:57:04','2026-04-17 03:57:04');
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

-- Dump completed on 2026-04-17  3:58:21
