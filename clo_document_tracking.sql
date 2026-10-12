-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 12, 2026 at 12:37 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clo_document_tracking`
--

-- --------------------------------------------------------

--
-- Table structure for table `active_user_sessions`
--

CREATE TABLE `active_user_sessions` (
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `session_token_hash` char(64) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `active_user_sessions`
--

INSERT INTO `active_user_sessions` (`session_id`, `user_id`, `session_token_hash`, `updated_at`) VALUES
(48, 1, 'ba0cdc755e0fe60a6766e4829ddf780f0e80f67de27674964a8de2f698231d9b', '2026-10-09 16:31:12'),
(51, 2, 'eac42d4c6dae5253d8aefd6f2385178bfeb2e76b2be6b162980e3e74982603be', '2026-10-09 14:03:43'),
(57, 1, '7b6327936b0399ed10f8acc49a1ed455a2eac65a91c193e922c2e87441db08e2', '2026-10-09 16:31:33'),
(58, 20, 'bea803264f77d890dc6519959fa8f58bd98a27cba7f957f4070306497f7134d0', '2026-10-09 16:20:07'),
(59, 20, '0f14fd455f6f5d6b3380e334251a53d65349cfdadd03bad8061acc6d5dc9b3d0', '2026-10-09 16:31:34');

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `attachment_id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `uploaded_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size_bytes` bigint(20) UNSIGNED NOT NULL,
  `checksum_sha256` char(64) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `uploaded_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attachments`
--

INSERT INTO `attachments` (`attachment_id`, `document_id`, `uploaded_by_user_id`, `file_name`, `stored_name`, `file_path`, `mime_type`, `file_size_bytes`, `checksum_sha256`, `is_public`, `uploaded_at`) VALUES
(30, 39, 20, 'PROGRAM.pdf', 'PROGRAM.pdf', 'uploads/PROGRAM.pdf', 'application/pdf', 4249999, '43ea6cad8b4b4d42784383d36dc5a17904644f32a16a58bee1cd965eec0a9789', 0, '2026-09-25 12:32:13'),
(31, 40, 1, 'PROGRAM.pdf', 'PROGRAM-1.pdf', 'uploads/PROGRAM-1.pdf', 'application/pdf', 4249999, '43ea6cad8b4b4d42784383d36dc5a17904644f32a16a58bee1cd965eec0a9789', 0, '2026-09-25 13:08:58');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `audit_log_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `entity_type` varchar(80) NOT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(40) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`audit_log_id`, `user_id`, `entity_type`, `entity_id`, `action`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 4, 'documents', 1, 'CREATE', NULL, '{\"tracking_no\": \"CLO-2026-000001\", \"current_status_id\": 1}', '192.168.1.25', 'Mozilla/5.0', '2026-08-10 09:31:00'),
(2, 2, 'documents', 1, 'UPDATE_STATUS', '{\"current_status_id\": 1}', '{\"current_status_id\": 2}', '192.168.1.11', 'Mozilla/5.0', '2026-08-10 10:00:05'),
(3, 2, 'documents', 3, 'APPROVE', '{\"current_status_id\": 2}', '{\"current_status_id\": 4}', '192.168.1.11', 'Mozilla/5.0', '2026-08-10 14:10:21'),
(4, 1, 'users', 5, 'UPDATE_ROLE', '{\"role_id\": 3}', '{\"role_id\": 3}', '192.168.1.10', 'Mozilla/5.0', '2026-08-12 08:00:00'),
(5, 3, 'attachments', 3, 'VIEW', NULL, '{\"attachment_id\": 3}', '192.168.1.19', 'Mozilla/5.0', '2026-08-14 09:05:42'),
(6, 5, 'DOCUMENT', 16, 'ROUTED', '{\"current_department_id\":3,\"current_assignee_user_id\":null}', '{\"current_department_id\":1,\"current_assignee_user_id\":1,\"route_remarks\":\"dadasfaasd\",\"due_at\":\"2026-09-03 07:54:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 07:54:22'),
(7, 1, 'DOCUMENT', 16, 'ROUTED', '{\"current_department_id\":1,\"current_assignee_user_id\":null}', '{\"current_department_id\":3,\"current_assignee_user_id\":5,\"route_remarks\":null,\"due_at\":\"2026-09-03 08:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 08:00:46'),
(8, 1, 'DOCUMENT', 12, 'UPDATED', '{\"title\":\"\",\"description\":\"\",\"document_type_id\":0,\"current_department_id\":0,\"current_assignee_user_id\":null,\"current_status_id\":0,\"priority_level\":\"\",\"confidentiality_level\":\"\",\"external_reference_no\":\"\",\"date_due\":\"\"}', '{\"title\":\"gfsfsfsgd\",\"description\":\"agdf\",\"document_type_id\":1,\"current_department_id\":2,\"current_assignee_user_id\":null,\"current_status_id\":4,\"priority_level\":\"URGENT\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-08-18 00:00:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 12:36:40'),
(9, 1, 'DOCUMENT', 16, 'ROUTED', '{\"current_department_id\":\"City Human Resource Management Office\",\"current_assignee_user_id\":null}', '{\"current_department_id\":\"City Budget Office\",\"current_assignee_user_id\":null,\"route_remarks\":null,\"due_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 13:23:36'),
(10, 1, 'DOCUMENT', 16, 'ROUTED', '{\"current_department_id\":\"City Budget Office\",\"current_assignee_user_id\":null}', '{\"current_department_id\":\"Bids and Awards Committee\",\"current_assignee_user_id\":null,\"route_remarks\":null,\"due_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 13:25:21'),
(11, 1, 'DOCUMENT', 16, 'ROUTED', '{\"current_department_id\":\"Bids and Awards Committee\",\"current_assignee_user_id\":null}', '{\"current_department_id\":\"City Budget Office\",\"current_assignee_user_id\":null,\"route_remarks\":null,\"due_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 13:25:56'),
(12, 1, 'DOCUMENT', 16, 'ROUTED', '{\"current_department_id\":\"City Budget Office\",\"current_assignee_user_id\":null}', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"route_remarks\":\"dadadww\",\"due_at\":\"2026-09-03 13:32:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 13:32:35'),
(13, 1, 'DOCUMENT', 15, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":null}', '{\"route_id\":22,\"current_department_id\":\"City Human Resource Management Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"route_remarks\":\"reqrwq\",\"due_at\":\"2026-09-04 09:16:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.136.1 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36', '2026-09-04 09:16:17'),
(14, 5, 'DOCUMENT', 15, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Evelyn Torres\",\"remarks\":\"thankyou\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 09:20:36'),
(15, 5, 'DOCUMENT', 15, 'ROUTED', '{\"current_department_id\":\"City Human Resource Management Office\",\"current_assignee_user_id\":null}', '{\"route_id\":23,\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Maria Santos\",\"route_remarks\":\"wqeqweqwrq\",\"due_at\":\"2026-09-04 09:22:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 09:22:35'),
(16, 1, 'DOCUMENT', 15, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":23,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Maria Santos\",\"remarks\":\"thankyou\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.136.1 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36', '2026-09-04 09:22:57'),
(17, 2, 'DOCUMENT', 16, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":null}', '{\"route_id\":24,\"current_department_id\":\"City Human Resource Management Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"route_remarks\":\"fsdfsgfssdgsdf\",\"due_at\":\"2026-09-04 11:28:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 11:27:58'),
(18, 5, 'DOCUMENT', 16, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":24,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Evelyn Torres\",\"remarks\":null}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 11:29:40'),
(19, 5, 'DOCUMENT', 17, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000017\",\"title\":\"sfdfsgsdf\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Under Review\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 12:08:22'),
(20, 5, 'DOCUMENT', 17, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":null}', '{\"route_id\":25,\"current_department_id\":\"City Human Resource Management Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"route_remarks\":\"twerefdfgdfgfsdgf\",\"due_at\":\"2026-09-30 12:09:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 12:09:58'),
(21, 5, 'DOCUMENT', 17, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":25,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Evelyn Torres\",\"remarks\":null}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 12:10:15'),
(22, 5, 'DOCUMENT', 17, 'ROUTED', '{\"current_department_id\":\"City Human Resource Management Office\",\"current_assignee_user_id\":null}', '{\"route_id\":26,\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"route_remarks\":\"dasdf\",\"due_at\":\"2026-09-30 12:10:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 12:10:45'),
(23, 2, 'DOCUMENT', 17, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":26,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Antonio Reyes\",\"remarks\":\"sfsdf\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 12:11:40'),
(24, 5, 'DOCUMENT', 12, 'UPDATED', '{\"title\":\"gfsfsfsgd\",\"description\":\"agdf\",\"document_type_id\":\"Memorandum\",\"current_department_id\":\"City Mayor Office\",\"current_assignee_user_id\":null,\"current_status_id\":\"Approved\",\"priority_level\":\"URGENT\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-08-18\"}', '{\"title\":\"gfsfsfsgd\",\"description\":\"agdf\",\"document_type_id\":\"Memorandum\",\"current_department_id\":\"City Mayor Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Approved\",\"priority_level\":\"URGENT\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-08-18 00:00:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 12:47:54'),
(25, 5, 'DOCUMENT', 17, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":null}', '{\"route_id\":27,\"current_department_id\":\"City Human Resource Management Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"route_remarks\":null,\"due_at\":\"2026-09-04 12:49:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 12:48:57'),
(26, 5, 'DOCUMENT', 17, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":27,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Evelyn Torres\",\"remarks\":null}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 12:49:10'),
(27, 5, 'DOCUMENT', 17, 'UPDATED', '{\"title\":\"sfdfsgsdf\",\"description\":\"fdgdfgsfsdaf\",\"document_type_id\":\"Resolution Draft\",\"current_department_id\":\"City Human Resource Management Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Under Review\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"sfdfsgsdf\",\"description\":\"fdgdfgsfsdaf\",\"document_type_id\":\"Resolution Draft\",\"current_department_id\":\"City Human Resource Management Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30 00:00:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 12:49:42'),
(28, 1, 'DOCUMENT', 17, 'ROUTED', '{\"current_department_id\":\"City Human Resource Management Office\",\"current_assignee_user_id\":null}', '{\"route_id\":28,\"current_department_id\":\"City Human Resource Management Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"route_remarks\":null,\"due_at\":\"2026-09-04 13:32:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 13:32:42'),
(29, 5, 'DOCUMENT', 17, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":28,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Evelyn Torres\",\"remarks\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 13:33:19'),
(30, 5, 'DOCUMENT', 17, 'ROUTED', '{\"current_department_id\":\"City Human Resource Management Office\",\"current_assignee_user_id\":null}', '{\"route_id\":29,\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"route_remarks\":\"dasda\",\"due_at\":\"2026-09-04 13:41:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 13:41:56'),
(31, 2, 'DOCUMENT', 17, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":29,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Antonio Reyes\",\"remarks\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 13:42:48'),
(32, 1, 'PASSWORD_RESET', 1, 'ADMIN_RESET_REQUESTED_MANUAL', NULL, '{\"attempted_login\":\"admin@city.gov.ph\",\"employee_no\":\"CLO-0001\",\"question\":null,\"success\":false,\"routing\":\"manual_admin_process\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 10:03:21'),
(33, 1, 'PASSWORD_RESET', 1, 'ADMIN_RESET_REQUESTED_MANUAL', NULL, '{\"attempted_login\":\"admin@city.gov.ph\",\"employee_no\":\"CLO-0001\",\"question\":null,\"success\":false,\"routing\":\"manual_admin_process\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 10:11:32'),
(34, 5, 'PASSWORD_RESET', 5, 'RESET_ANSWER_VERIFIED', NULL, '{\"attempted_login\":\"torres@city.gov.ph\",\"employee_no\":\"HRMO-0005\",\"question\":\"color\",\"success\":true}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 12:33:29'),
(35, 5, 'PASSWORD_RESET', 5, 'RESET_ANSWER_FAILED', NULL, '{\"attempted_login\":\"torres@city.gov.ph\",\"employee_no\":\"HRMO-0005\",\"question\":\"color\",\"success\":false}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 12:33:55'),
(36, 5, 'PASSWORD_RESET', 5, 'RESET_ANSWER_FAILED', NULL, '{\"attempted_login\":\"torres@city.gov.ph\",\"employee_no\":\"HRMO-0005\",\"question\":\"color\",\"success\":false}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 12:34:00'),
(37, 5, 'PASSWORD_RESET', 5, 'RESET_ANSWER_FAILED', NULL, '{\"attempted_login\":\"torres@city.gov.ph\",\"employee_no\":\"HRMO-0005\",\"question\":\"color\",\"success\":false}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 12:34:04'),
(38, 5, 'PASSWORD_RESET', 5, 'RESET_ANSWER_FAILED', NULL, '{\"attempted_login\":\"torres@city.gov.ph\",\"employee_no\":\"HRMO-0005\",\"question\":\"color\",\"success\":false}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 12:34:10'),
(39, 5, 'PASSWORD_RESET', 5, 'RESET_ANSWER_FAILED', NULL, '{\"attempted_login\":\"torres@city.gov.ph\",\"employee_no\":\"HRMO-0005\",\"question\":\"color\",\"success\":false}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 12:34:19'),
(40, 5, 'PASSWORD_RESET', 5, 'RESET_REQUEST_LOCKED', NULL, '{\"attempted_login\":\"torres@city.gov.ph\",\"employee_no\":\"HRMO-0005\",\"question\":null,\"success\":false}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 12:34:37'),
(183, 1, 'DOCUMENT', 17, 'ARCHIVED', '{\"current_status_id\":1}', '{\"current_status_id\":6}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-11 03:35:15'),
(184, 1, 'DOCUMENT', 17, 'RESTORED', '{\"current_status_id\":6}', '{\"current_status_id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-11 03:35:30'),
(185, 1, 'DOCUMENT', 17, 'ARCHIVED', '{\"current_status_id\":1}', '{\"current_status_id\":6}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-11 03:35:40'),
(186, 1, 'PASSWORD_RESET', 1, 'ADMIN_RESET_REQUESTED_MANUAL', NULL, '{\"attempted_login\":\"admin@city.gov.ph\",\"employee_no\":\"CLO-0001\",\"question\":null,\"success\":false,\"routing\":\"manual_admin_process\"}', '192.168.1.172', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-18 09:26:35'),
(187, 1, 'DOCUMENT', 17, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":null}', '{\"route_id\":45,\"current_department_id\":\"Bids and Awards Committee\",\"current_assignee_user_id\":\"Liza Dela Cruz\",\"route_remarks\":null,\"due_at\":\"2026-09-18 11:35:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-18 11:35:37'),
(188, 1, 'DOCUMENT', 17, 'UPDATED', '{\"title\":\"sfdfsgsdf\",\"description\":\"fdgdfgsfsdaf\",\"document_type_id\":\"Resolution Draft\",\"current_department_id\":\"Bids and Awards Committee\",\"current_assignee_user_id\":\"Liza Dela Cruz\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"sfdfsgsdf\",\"description\":\"fdgdfgsfsdaf\",\"document_type_id\":\"Resolution Draft\",\"current_department_id\":\"Bids and Awards Committee\",\"current_assignee_user_id\":\"Liza Dela Cruz\",\"current_status_id\":\"Received\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30 00:00:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-18 14:09:39'),
(189, 3, 'DOCUMENT', 17, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":45,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Liza Dela Cruz\",\"remarks\":null}', '192.168.1.43', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36', '2026-09-18 15:21:34'),
(190, 1, 'DOCUMENT', 17, 'UPDATED', '{\"title\":\"sfdfsgsdf\",\"description\":\"fdgdfgsfsdaf\",\"document_type_id\":\"Resolution Draft\",\"current_department_id\":\"Bids and Awards Committee\",\"current_assignee_user_id\":\"Liza Dela Cruz\",\"current_status_id\":\"Received\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"sfdfsgsdf\",\"description\":\"fdgdfgsfsdaf\",\"document_type_id\":\"Resolution Draft\",\"current_department_id\":\"Bids and Awards Committee\",\"current_assignee_user_id\":\"Liza Dela Cruz\",\"current_status_id\":\"Approved\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30 00:00:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-18 15:32:15'),
(191, 1, 'DOCUMENT', 17, 'UPDATED', '{\"title\":\"sfdfsgsdf\",\"description\":\"fdgdfgsfsdaf\",\"document_type_id\":\"Resolution Draft\",\"current_department_id\":\"Bids and Awards Committee\",\"current_assignee_user_id\":\"Liza Dela Cruz\",\"current_status_id\":\"Approved\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"sfdfsgsdf\",\"description\":\"fdgdfgsfsdaf\",\"document_type_id\":\"Resolution Draft\",\"current_department_id\":\"Bids and Awards Committee\",\"current_assignee_user_id\":\"Liza Dela Cruz\",\"current_status_id\":\"Approved\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-18 00:00:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-18 15:33:02'),
(192, 1, 'DOCUMENT', 17, 'ROUTED', '{\"current_department_id\":\"Bids and Awards Committee\",\"current_assignee_user_id\":null}', '{\"route_id\":46,\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Kyle Busalla\",\"route_remarks\":null,\"due_at\":\"2026-09-21 09:39:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 09:39:44'),
(193, 1, 'DOCUMENT', 17, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":46,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Super Admin\",\"remarks\":null}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 09:39:56'),
(194, 6, 'DOCUMENT', 17, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":null}', '{\"route_id\":47,\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Super Admin\",\"route_remarks\":null,\"due_at\":\"2026-09-21 13:04:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 13:03:54'),
(195, 1, 'DOCUMENT', 17, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":47,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Super Admin\",\"remarks\":null}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 13:04:23'),
(196, 1, 'DOCUMENT', 35, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"title\":\"wqeqwe\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 09:01:23'),
(197, 1, 'DOCUMENT', 35, 'UPDATED', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10\"}', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10 00:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 09:01:55'),
(198, 1, 'DOCUMENT', 35, 'UPDATED', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10\"}', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10 00:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 09:02:54'),
(199, 1, 'DOCUMENT', 35, 'UPDATED', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10\"}', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10 00:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 09:10:00'),
(200, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:45:13\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:45:13'),
(201, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:45:25\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:45:25'),
(202, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:49:03\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:49:03'),
(203, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:49:09\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:49:09'),
(204, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:50:09\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:50:09'),
(205, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:50:29\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:50:29'),
(206, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:50:59\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:50:59'),
(207, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:51:04\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:51:04'),
(208, 1, 'DOCUMENT_TRACKING_SLIP', 17, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:51:04\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:51:04'),
(209, 1, 'DOCUMENT_TRACKING_SLIP', 15, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:51:04\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:51:04'),
(210, 1, 'DOCUMENT_TRACKING_SLIP', 16, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:51:04\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:51:04'),
(211, 1, 'DOCUMENT_TRACKING_SLIP', 14, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:51:04\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:51:04'),
(212, 1, 'DOCUMENT_TRACKING_SLIP', 13, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:51:04\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:51:04'),
(213, 1, 'DOCUMENT_TRACKING_SLIP', 4, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:51:04\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:51:04'),
(214, 1, 'DOCUMENT_TRACKING_SLIP', 12, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:51:04\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:51:04'),
(215, 1, 'DOCUMENT_TRACKING_SLIP', 3, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:51:04\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:51:04'),
(216, 1, 'DOCUMENT_TRACKING_SLIP', 2, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:51:04\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:51:04'),
(217, 1, 'DOCUMENT_TRACKING_SLIP', 1, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:51:04\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:51:04'),
(218, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:52:31\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:52:31'),
(219, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:52:38\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:52:38'),
(220, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:52:52\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:52:52'),
(221, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:53:43\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:53:43'),
(222, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:54:24\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:54:24'),
(223, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:55:38\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:55:38'),
(224, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:55:54\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:55:54'),
(225, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:56:03\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:56:03'),
(226, 1, 'DOCUMENT_TRACKING_SLIP', 17, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:56:42\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:56:42'),
(227, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:57:10\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:57:10'),
(228, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 12:59:32\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 12:59:32'),
(229, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 13:00:47\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 13:00:47'),
(230, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 13:03:28\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 13:03:28'),
(231, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 13:03:40\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 13:03:40'),
(232, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 13:04:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 13:04:00'),
(233, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 13:04:29\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 13:04:29'),
(234, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 13:05:50\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 13:05:50'),
(235, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-24 15:23:04\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 15:23:04'),
(236, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:38:32\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:38:32'),
(237, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:43:48\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:43:48'),
(238, 1, 'DOCUMENT_TRACKING_SLIP', 16, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:43:58\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:43:58'),
(239, 1, 'DOCUMENT_TRACKING_SLIP', 17, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:44:09\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:44:09'),
(240, 1, 'DOCUMENT_TRACKING_SLIP', 17, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:44:13\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:44:13'),
(241, 1, 'DOCUMENT_TRACKING_SLIP', 17, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:45:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:45:06'),
(242, 1, 'DOCUMENT_TRACKING_SLIP', 16, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:45:18\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:45:18'),
(243, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:48:24\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:48:24'),
(244, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:49:26\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:49:26'),
(245, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:50:49\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:50:49'),
(246, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:51:12\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:51:12'),
(247, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:51:22\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:51:22'),
(248, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:51:27\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:51:27'),
(249, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:51:34\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:51:34'),
(250, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:51:43\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:51:43'),
(251, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:52:33\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:52:33'),
(252, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:52:55\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:52:55'),
(253, 1, 'DOCUMENT_TRACKING_SLIP', 17, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:53:10\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:53:10'),
(254, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:53:44\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:53:44'),
(255, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:54:22\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:54:22'),
(256, 1, 'DOCUMENT', 35, 'UPDATED', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10\"}', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10 00:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:58:54'),
(257, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:58:56\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:58:56'),
(258, 1, 'DOCUMENT', 35, 'UPDATED', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10\"}', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10 00:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:59:08'),
(259, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 08:59:10\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 08:59:10'),
(260, 1, 'DOCUMENT', 35, 'UPDATED', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10\"}', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10 00:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:04:18'),
(261, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:04:19\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:04:19'),
(262, 1, 'DOCUMENT', 35, 'UPDATED', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10\"}', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Kyle Busalla\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10 00:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:05:17'),
(263, 1, 'DOCUMENT', 35, 'UPDATED', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Kyle Busalla\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10\"}', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10 00:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:05:26');
INSERT INTO `audit_logs` (`audit_log_id`, `user_id`, `entity_type`, `entity_id`, `action`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(264, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:05:55\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:05:55'),
(265, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:06:22\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:06:22'),
(266, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:09:30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:09:30'),
(267, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:10:50\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:10:50'),
(268, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:12:42\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:12:42'),
(269, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:15:27\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:15:27'),
(270, 1, 'DOCUMENT', 35, 'UPDATED', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10\"}', '{\"title\":\"wqeqwe\",\"description\":\"eqrtfgsf\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-10 00:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:19:15'),
(271, 1, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:19:18\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:19:18'),
(272, 1, 'DOCUMENT', 36, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"title\":\"AUTHORITY TO TRAVEL\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\"}', '192.168.1.29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:34:09'),
(273, 1, 'DOCUMENT_TRACKING_SLIP', 36, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:34:16\"}', '192.168.1.29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:34:16'),
(274, 1, 'DOCUMENT_TRACKING_SLIP', 36, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:42:02\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:42:02'),
(275, 1, 'DOCUMENT', 36, 'UPDATED', '{\"title\":\"AUTHORITY TO TRAVEL\",\"description\":\"request authority to travel abroad\",\"document_type_id\":\"Legal Opinion Request\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-25\"}', '{\"title\":\"AUTHORITY TO TRAVEL\",\"description\":\"request authority to travel abroad\",\"document_type_id\":\"Legal Opinion Request\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-25 00:00:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:43:34'),
(276, 1, 'DOCUMENT_TRACKING_SLIP', 36, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:43:45\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:43:45'),
(277, 1, 'DOCUMENT_TRACKING_SLIP', 36, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:44:14\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:44:14'),
(278, 1, 'DOCUMENT_TRACKING_SLIP', 36, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:44:19\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:44:19'),
(279, 1, 'DOCUMENT_TRACKING_SLIP', 36, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:44:45\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:44:45'),
(280, 1, 'DOCUMENT_TRACKING_SLIP', 36, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:47:26\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:47:26'),
(281, 1, 'DOCUMENT', 36, 'UPDATED', '{\"title\":\"AUTHORITY TO TRAVEL\",\"description\":\"request authority to travel abroad\",\"document_type_id\":\"Legal Opinion Request\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-25\"}', '{\"title\":\"AUTHORITY TO TRAVEL\",\"description\":\"request authority to travel abroad\",\"document_type_id\":\"Legal Opinion Request\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-25 00:00:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:57:49'),
(282, 1, 'DOCUMENT_TRACKING_SLIP', 36, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 09:57:56\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 09:57:56'),
(283, 1, 'DOCUMENT_TRACKING_SLIP', 36, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 10:03:59\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 10:03:59'),
(284, 1, 'DOCUMENT_TRACKING_SLIP', 36, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 10:29:33\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 10:29:33'),
(285, 1, 'DOCUMENT', 36, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":null}', '{\"route_id\":48,\"current_department_id\":\"Bids and Awards Committee\",\"current_assignee_user_id\":\"Liza Dela Cruz\",\"route_remarks\":null,\"due_at\":\"2026-09-25 10:33:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 10:33:49'),
(286, 1, 'DOCUMENT_TRACKING_SLIP', 36, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 10:34:03\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 10:34:03'),
(287, 1, 'DOCUMENT_TRACKING_SLIP', 36, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 10:38:37\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 10:38:37'),
(288, 1, 'DOCUMENT_TRACKING_SLIP', 36, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 10:41:12\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 10:41:12'),
(289, 1, 'DOCUMENT_TRACKING_SLIP', 36, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 10:41:17\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 10:41:17'),
(290, 1, 'DOCUMENT', 37, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000037\",\"title\":\"asdasd\",\"current_assignee_user_id\":null,\"current_status_id\":\"Received\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:20:13'),
(291, 1, 'DOCUMENT_TRACKING_SLIP', 37, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:20:25\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:20:25'),
(292, 1, 'DOCUMENT', 37, 'UPDATED', '{\"title\":\"asdasd\",\"description\":\"dsdasda\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":null,\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"asdasd\",\"description\":\"dsdasda\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30 00:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:20:57'),
(293, 1, 'DOCUMENT_TRACKING_SLIP', 37, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:21:03\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:21:03'),
(294, 1, 'DOCUMENT_TRACKING_SLIP', 37, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:26:29\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:26:29'),
(295, 1, 'DOCUMENT_TRACKING_SLIP', 37, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:27:48\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:27:48'),
(296, 1, 'DOCUMENT_TRACKING_SLIP', 37, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:30:42\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:30:42'),
(297, 1, 'DOCUMENT', 37, 'UPDATED', '{\"title\":\"asdasd\",\"description\":\"dsdasda\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"asdasd\",\"description\":\"dsdasda\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30 00:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:31:01'),
(298, 1, 'DOCUMENT_TRACKING_SLIP', 37, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:31:03\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:31:03'),
(299, 1, 'DOCUMENT_TRACKING_SLIP', 37, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:37:17\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:37:17'),
(300, 1, 'DOCUMENT', 37, 'UPDATED', '{\"title\":\"asdasd\",\"description\":\"dsdasda\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"asdasd\",\"description\":\"dsdasda\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Kyle Busalla\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30 00:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:37:32'),
(301, 1, 'DOCUMENT_TRACKING_SLIP', 37, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:37:34\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:37:34'),
(302, 1, 'DOCUMENT', 37, 'UPDATED', '{\"title\":\"asdasd\",\"description\":\"dsdasda\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Kyle Busalla\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"asdasd\",\"description\":\"dsdasda\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Kyle Busalla\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30 00:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:37:53'),
(303, 1, 'DOCUMENT_TRACKING_SLIP', 37, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:44:56\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:44:56'),
(304, 1, 'DOCUMENT', 38, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000038\",\"title\":\"weqweqw\",\"current_assignee_user_id\":null,\"current_status_id\":\"Received\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:55:34'),
(305, 1, 'DOCUMENT_TRACKING_SLIP', 38, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:55:48\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:55:48'),
(306, 1, 'DOCUMENT', 38, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":null}', '{\"route_id\":49,\"current_department_id\":\"City Budget Office\",\"current_assignee_user_id\":null,\"route_remarks\":null,\"due_at\":\"2026-09-25 11:56:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:56:40'),
(307, 1, 'DOCUMENT', 38, 'ROUTED', '{\"current_department_id\":\"City Budget Office\",\"current_assignee_user_id\":null}', '{\"route_id\":50,\"current_department_id\":\"City Budget Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"route_remarks\":null,\"due_at\":\"2026-09-25 11:57:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:56:59'),
(308, 1, 'DOCUMENT_TRACKING_SLIP', 37, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:57:21\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:57:21'),
(309, 1, 'DOCUMENT_TRACKING_SLIP', 37, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:57:23\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:57:23'),
(310, 1, 'DOCUMENT_TRACKING_SLIP', 38, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:58:16\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:58:16'),
(311, 1, 'DOCUMENT_TRACKING_SLIP', 38, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:58:34\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:58:34'),
(312, 1, 'DOCUMENT_TRACKING_SLIP', 38, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 11:58:57\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 11:58:57'),
(313, 1, 'DOCUMENT_TRACKING_SLIP', 38, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 12:08:37\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 12:08:37'),
(314, 1, 'DOCUMENT_TRACKING_SLIP', 38, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 12:08:58\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 12:08:58'),
(315, 1, 'DOCUMENT_TRACKING_SLIP', 38, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-25 12:29:55\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 12:29:55'),
(316, 1, 'DOCUMENT', 39, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000039\",\"title\":\"wqeqeqw\",\"current_assignee_user_id\":\"New Clo\",\"current_status_id\":\"Received\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 12:31:22'),
(317, 2, 'DOCUMENT', 38, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":49,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Antonio Reyes\",\"remarks\":null}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 12:33:31'),
(318, 2, 'DOCUMENT', 38, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":50,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Antonio Reyes\",\"remarks\":null}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 12:33:33'),
(319, 2, 'DOCUMENT', 39, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"CONFIDENTIAL\",\"requested_by_user_id\":2}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 12:35:42'),
(320, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 12:50:11'),
(321, 1, 'DOCUMENT', 40, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"title\":\"eqweqw\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:08:58'),
(322, 20, 'DOCUMENT', 40, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"CONFIDENTIAL\",\"requested_by_user_id\":20}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:10:05'),
(323, 2, 'DOCUMENT', 40, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":20,\"granted_by_user_id\":2}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:10:25'),
(324, 20, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30 00:00:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:26:55'),
(325, 20, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30 00:00:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:26:57'),
(326, 20, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30 00:00:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:27:19'),
(327, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-09-25 13:27:22\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:27:22'),
(328, 2, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30 00:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 15:41:19'),
(329, 2, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30 00:00:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 16:20:32'),
(330, 2, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":2,\"generated_at\":\"2026-09-29 14:02:28\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-29 14:02:28'),
(331, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-30 13:49:26\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 13:49:26'),
(332, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-30 13:53:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 13:53:53'),
(333, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-30 14:42:27\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 14:42:27'),
(334, 1, 'DOCUMENT_TRACKING_SLIP', 15, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\",\"generated_by_user_id\":1,\"generated_at\":\"2026-09-30 14:42:48\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 14:42:48'),
(335, 2, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30 00:00:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:22:34'),
(336, 2, 'DOCUMENT', 39, 'ACCESS_REQUEST_CANCELLED', NULL, '{\"request_id\":319,\"cancelled_by_user_id\":2}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:22:49'),
(337, 20, 'DOCUMENT', 40, 'ACCESS_REQUEST_CANCELLED', NULL, '{\"request_id\":322,\"cancelled_by_user_id\":20}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:24:01'),
(338, 20, 'DOCUMENT', 39, 'CONFIDENTIALITY_UPDATED', '{\"confidentiality_level\":\"CONFIDENTIAL\"}', '{\"confidentiality_level\":\"CONFIDENTIAL\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:24:24'),
(339, 5, 'DOCUMENT', 40, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"CONFIDENTIAL\",\"requested_by_user_id\":5}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:26:57'),
(340, 5, 'DOCUMENT', 39, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"CONFIDENTIAL\",\"requested_by_user_id\":5}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:27:05'),
(341, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":5,\"granted_by_user_id\":20}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:27:26'),
(342, 5, 'DOCUMENT', 39, 'ACCESS_REQUEST_CANCELLED', NULL, '{\"request_id\":340,\"cancelled_by_user_id\":5}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:28:02'),
(344, 2, 'DOCUMENT', 40, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":5,\"granted_by_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:51:41'),
(345, 2, 'DOCUMENT', 40, 'ACCESS_REVOKED', NULL, '{\"request_id\":339,\"revoked_user_id\":5}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:52:35'),
(346, 5, 'DOCUMENT', 40, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"CONFIDENTIAL\",\"requested_by_user_id\":5}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:52:53'),
(347, 2, 'DOCUMENT', 40, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":5,\"granted_by_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:53:01'),
(348, 5, 'DOCUMENT', 40, 'ACCESS_REQUEST_CANCELLED', NULL, '{\"request_id\":346,\"cancelled_by_user_id\":5}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:53:09'),
(350, 2, 'DOCUMENT', 39, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"CONFIDENTIAL\",\"requested_by_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 10:07:03'),
(351, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 10:13:18'),
(352, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":350,\"revoked_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 10:13:23'),
(353, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-01 12:48:36\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 12:48:36'),
(354, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-01 12:58:03\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 12:58:03'),
(355, 1, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30 00:00:00\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 12:58:26'),
(356, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-01 12:58:29\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 12:58:29'),
(357, 20, 'DOCUMENT', 15, 'UPDATED', '{\"title\":\"sdasd\",\"description\":\"asdasd\",\"document_type_id\":\"Contract Review\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Received\",\"priority_level\":\"HIGH\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"\"}', '{\"title\":\"sdasd\",\"description\":\"asdasd\",\"document_type_id\":\"Contract Review\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Received\",\"priority_level\":\"HIGH\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-31 14:41:00\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 14:41:12'),
(358, 20, 'DOCUMENT', 14, 'UPDATED', '{\"title\":\"wqeqwe\",\"description\":\"\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Budget Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"\",\"confidentiality_level\":\"\",\"external_reference_no\":\"\",\"date_due\":\"\"}', '{\"title\":\"wqeqwe\",\"description\":\"\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Budget Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-10-31 14:41:00\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 14:41:26'),
(359, 20, 'DOCUMENT', 42, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000042\",\"title\":\"COPILOT-TEST-MUP6B12U\",\"current_assignee_user_id\":\"New Clo\",\"current_status_id\":\"Received\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 14:49:11'),
(360, 20, 'DOCUMENT', 42, 'ASSIGNED', NULL, '{\"assigned_to_user_id\":20}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 14:49:11'),
(361, 20, 'DOCUMENT', 42, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":null}', '{\"route_id\":51,\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"New Clo\",\"route_remarks\":\"COPILOT-TEST workflow route\",\"due_at\":\"2026-10-15 12:00:00\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 14:49:44'),
(362, 20, 'DOCUMENT', 42, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":51,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"New Clo\",\"remarks\":\"COPILOT-TEST route receipt\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 14:50:06'),
(363, 20, 'DOCUMENT', 42, 'ASSIGNMENT_ACCEPTED', '{\"accepted_at\":null}', '{\"accepted_by_user_id\":20}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 14:50:11'),
(364, 20, 'DOCUMENT', 42, 'UPDATED', '{\"title\":\"COPILOT-TEST-MUP6B12U\",\"description\":\"Temporary automated workflow test; remove after verification.\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"New Clo\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-11-01\"}', '{\"title\":\"COPILOT-TEST-MUP6B12U\",\"description\":\"Temporary automated workflow test; edit/status verified.\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"New Clo\",\"current_status_id\":\"Approved\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-11-01 00:00:00\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 14:51:01'),
(365, 1, 'DOCUMENT', 43, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000043\",\"title\":\"COPILOT-TEST-ADMIN-RESTORE-MUP6M7QT\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Received\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 14:57:29'),
(366, 1, 'DOCUMENT', 43, 'ASSIGNED', NULL, '{\"assigned_to_user_id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 14:57:29'),
(367, 1, 'DOCUMENT', 44, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000044\",\"title\":\"COPILOT-TEST-DATEFIX-MUP7K6C8\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Received\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 15:23:53'),
(368, 1, 'DOCUMENT', 44, 'ASSIGNED', NULL, '{\"assigned_to_user_id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 15:23:53'),
(369, 1, 'DOCUMENT', 45, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000045\",\"title\":\"COPILOT-TEST-EDITDATE-MUP7MV3L\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Received\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 15:25:59'),
(370, 1, 'DOCUMENT', 45, 'ASSIGNED', NULL, '{\"assigned_to_user_id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 15:25:59'),
(371, 1, 'DOCUMENT', 45, 'UPDATED', '{\"title\":\"COPILOT-TEST-EDITDATE-MUP7MV3L\",\"description\":\"Temporary due date edit-path regression test.\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-11-04\"}', '{\"title\":\"COPILOT-TEST-EDITDATE-MUP7MV3L\",\"description\":\"Temporary due date edit-path regression test.\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-11-05\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 15:26:03'),
(372, 1, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 15:26:58'),
(373, 1, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 15:27:07'),
(374, 1, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 15:27:17'),
(375, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-01 15:37:44\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 15:37:44'),
(376, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-01 15:37:44\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 15:37:44'),
(377, 20, 'DOCUMENT', 46, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000046\",\"title\":\"COPILOT-TEST-ATTACH-20261001\",\"current_assignee_user_id\":\"New Clo\",\"current_status_id\":\"Received\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 15:43:52');
INSERT INTO `audit_logs` (`audit_log_id`, `user_id`, `entity_type`, `entity_id`, `action`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(378, 20, 'DOCUMENT', 46, 'ASSIGNED', NULL, '{\"assigned_to_user_id\":20}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 15:43:52'),
(379, 20, 'DOCUMENT', 47, 'IMPORTED', NULL, '{\"tracking_no\":\"CLO-2026-000047\",\"source\":\"Excel import\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 15:46:24'),
(380, 20, 'DOCUMENT', 48, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000048\",\"title\":\"COPILOT-TEST-ATTACH-VIEW-20261001\",\"current_assignee_user_id\":\"New Clo\",\"current_status_id\":\"Received\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 15:50:42'),
(381, 20, 'DOCUMENT', 48, 'ASSIGNED', NULL, '{\"assigned_to_user_id\":20}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 15:50:42'),
(382, 1, 'DOCUMENT', 49, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000049\",\"title\":\"CLO QA Attachment Download 2026-10-01\",\"current_assignee_user_id\":null,\"current_status_id\":\"Received\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 16:13:01'),
(383, 1, 'DOCUMENT', 50, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000050\",\"title\":\"CLO QA Access Request 2026-10-01\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Received\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 16:21:44'),
(384, 1, 'DOCUMENT', 50, 'ASSIGNED', NULL, '{\"assigned_to_user_id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 16:21:44'),
(385, NULL, 'DOCUMENT', 50, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"HIGHLY_CONFIDENTIAL\",\"requested_by_user_id\":24}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 16:22:48'),
(386, 1, 'DOCUMENT', 50, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":24,\"granted_by_user_id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 16:23:10'),
(387, 1, 'DOCUMENT', 50, 'ACCESS_REVOKED', NULL, '{\"request_id\":385,\"revoked_user_id\":24}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 16:23:22'),
(388, 1, 'DOCUMENT', 51, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000051\",\"title\":\"CLO QA Reject Request 2026-10-01\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Received\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 16:29:12'),
(389, 1, 'DOCUMENT', 51, 'ASSIGNED', NULL, '{\"assigned_to_user_id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 16:29:12'),
(390, NULL, 'DOCUMENT', 51, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"HIGHLY_CONFIDENTIAL\",\"requested_by_user_id\":25}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 16:29:23'),
(391, 1, 'DOCUMENT', 51, 'ACCESS_REQUEST_REJECTED', NULL, '{\"request_id\":390,\"rejected_by_user_id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 16:29:35'),
(392, NULL, 'DOCUMENT', 51, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"HIGHLY_CONFIDENTIAL\",\"requested_by_user_id\":25}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 16:29:45'),
(393, NULL, 'DOCUMENT', 51, 'ACCESS_REQUEST_CANCELLED', NULL, '{\"request_id\":392,\"cancelled_by_user_id\":25}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.139.1 Chrome/150.0.7871.250 Electron/43.6.0 Safari/537.36', '2026-10-01 16:30:05'),
(394, 2, 'DOCUMENT', 39, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"CONFIDENTIAL\",\"requested_by_user_id\":2}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 08:29:59'),
(395, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 08:30:29'),
(396, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":394,\"revoked_user_id\":2}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 08:31:37'),
(397, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-02 08:39:30\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 08:39:30'),
(398, 1, 'DOCUMENT', 52, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000052\",\"title\":\"CLO QA Full Flow 2026-10-02\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Received\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:13:10'),
(399, 1, 'DOCUMENT', 52, 'ASSIGNED', NULL, '{\"assigned_to_user_id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:13:10'),
(400, 1, 'DOCUMENT', 52, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":null}', '{\"route_id\":52,\"current_department_id\":\"City Mayor Office\",\"current_assignee_user_id\":\"QA Flow Recipient\",\"route_remarks\":\"QA route: receive and verify document movement.\",\"due_at\":\"2026-10-05 15:00:00\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:16:05'),
(401, NULL, 'DOCUMENT', 52, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":52,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"QA Flow Recipient\",\"remarks\":\"QA received and verified the routed document.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:16:26'),
(402, NULL, 'DOCUMENT', 52, 'ASSIGNMENT_ACCEPTED', '{\"accepted_at\":null}', '{\"accepted_by_user_id\":26}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:16:38'),
(403, NULL, 'DOCUMENT', 52, 'ROUTED', '{\"current_department_id\":\"City Mayor Office\",\"current_assignee_user_id\":null}', '{\"route_id\":53,\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Super Admin\",\"route_remarks\":\"QA return to CLO after Records Office processing.\",\"due_at\":\"2026-10-07 15:00:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:17:00'),
(404, 1, 'DOCUMENT', 52, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":53,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Super Admin\",\"remarks\":\"QA returned document received by CLO.\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:17:19'),
(405, 1, 'DOCUMENT', 52, 'ASSIGNMENT_ACCEPTED', '{\"accepted_at\":null}', '{\"accepted_by_user_id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:17:34'),
(406, 1, 'DOCUMENT', 52, 'UPDATED', '{\"title\":\"CLO QA Full Flow 2026-10-02\",\"description\":\"Temporary end-to-end workflow verification record. Remove after testing.\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Received\",\"priority_level\":\"HIGH\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"\"}', '{\"title\":\"CLO QA Full Flow 2026-10-02\",\"description\":\"Temporary end-to-end workflow verification record. Remove after testing.\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Under Review\",\"priority_level\":\"HIGH\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":null}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:18:43'),
(407, 1, 'DOCUMENT', 52, 'UPDATED', '{\"title\":\"CLO QA Full Flow 2026-10-02\",\"description\":\"Temporary end-to-end workflow verification record. Remove after testing.\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Under Review\",\"priority_level\":\"HIGH\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"\"}', '{\"title\":\"CLO QA Full Flow 2026-10-02\",\"description\":\"Temporary end-to-end workflow verification record. Remove after testing.\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Approved\",\"priority_level\":\"HIGH\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":null}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:18:50'),
(408, 1, 'DOCUMENT', 53, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000053\",\"title\":\"QA Slip Edit 2026-10-02\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Received\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:35:27'),
(409, 1, 'DOCUMENT', 53, 'ASSIGNED', NULL, '{\"assigned_to_user_id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:35:27'),
(410, 1, 'DOCUMENT_TRACKING_SLIP', 53, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000053\",\"document_title\":\"QA Slip Edit 2026-10-02\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-02 10:36:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 10:36:00'),
(411, 1, 'DOCUMENT_TRACKING_SLIP', 53, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000053\",\"document_title\":\"QA Slip Edit 2026-10-02\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-02 10:36:31\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 10:36:31'),
(412, 1, 'DOCUMENT_TRACKING_SLIP', 53, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000053\",\"document_title\":\"QA Slip Edit 2026-10-02\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-02 10:36:39\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 10:36:39'),
(413, 1, 'DOCUMENT_TRACKING_SLIP', 53, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000053\",\"document_title\":\"QA Slip Edit 2026-10-02\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-02 10:36:42\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 10:36:42'),
(414, 1, 'DOCUMENT_TRACKING_SLIP', 53, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000053\",\"document_title\":\"QA Slip Edit 2026-10-02\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-02 10:36:45\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 10:36:45'),
(415, 1, 'DOCUMENT_TRACKING_SLIP', 53, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000053\",\"document_title\":\"QA Slip Edit 2026-10-02\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-02 10:37:13\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:37:13'),
(416, 1, 'DOCUMENT_TRACKING_SLIP', 53, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000053\",\"document_title\":\"QA Slip Edit 2026-10-02\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-02 10:39:33\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:39:33'),
(417, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-02 10:43:52\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:43:52'),
(418, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-02 10:45:37\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:45:37'),
(419, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-02 10:46:42\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:46:42'),
(420, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-02 10:46:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 10:46:53'),
(421, 1, 'DOCUMENT', 54, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000054\",\"title\":\"QA LAN UAT 2026-10-02\",\"current_assignee_user_id\":\"Super Admin\",\"current_status_id\":\"Received\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:49:44'),
(422, 1, 'DOCUMENT', 54, 'ASSIGNED', NULL, '{\"assigned_to_user_id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:49:44'),
(423, 1, 'DOCUMENT', 54, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":null}', '{\"route_id\":54,\"current_department_id\":\"City Mayor Office\",\"current_assignee_user_id\":\"QA LAN Recipient\",\"route_remarks\":\"LAN UAT route to Records Officer.\",\"due_at\":\"2026-10-07 15:00:00\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:49:51'),
(424, NULL, 'DOCUMENT', 54, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":54,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"QA LAN Recipient\",\"remarks\":\"LAN UAT: recipient received and reviewed the test route.\"}', '192.168.1.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:50:09'),
(425, NULL, 'DOCUMENT', 54, 'ASSIGNMENT_ACCEPTED', '{\"accepted_at\":null}', '{\"accepted_by_user_id\":27}', '192.168.1.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:50:15'),
(426, NULL, 'DOCUMENT', 54, 'ROUTED', '{\"current_department_id\":\"City Mayor Office\",\"current_assignee_user_id\":null}', '{\"route_id\":55,\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Super Admin\",\"route_remarks\":\"LAN UAT notification check for pending route.\",\"due_at\":\"2026-10-07 16:00:00\"}', '192.168.1.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:51:14'),
(427, 1, 'DOCUMENT', 54, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":55,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Super Admin\",\"remarks\":\"Admin received LAN UAT return route.\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 10:51:27'),
(428, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-02 11:08:41\"}', '192.168.1.115', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36', '2026-10-02 11:08:41'),
(429, 1, 'DOCUMENT', 2, 'UPDATED', '{\"title\":\"Request for Legal Opinion on Overtime Policy\",\"description\":\"HRMO requests legal guidance on overtime policy implementation.\",\"document_type_id\":\"Legal Opinion Request\",\"current_department_id\":\"City Mayor Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Routed\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"HRMO-2026-078\",\"date_due\":\"2026-08-18\"}', '{\"title\":\"Request for Legal Opinion on Overtime Policy\",\"description\":\"HRMO requests legal guidance on overtime policy implementation.\",\"document_type_id\":\"Legal Opinion Request\",\"current_department_id\":\"City Mayor Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Approved\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-08-18\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 13:06:03'),
(430, 1, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Approved\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 13:06:23'),
(431, 1, 'DOCUMENT', 13, 'UPDATED', '{\"title\":\"MEMO\",\"description\":\"asfagfdgs\",\"document_type_id\":\"Memorandum\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Received\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"\",\"external_reference_no\":\"\",\"date_due\":\"2026-08-19\"}', '{\"title\":\"MEMO\",\"description\":\"asfagfdgs\",\"document_type_id\":\"Memorandum\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"current_status_id\":\"Approved\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-08-19\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 13:06:51'),
(432, 1, 'DOCUMENT', 39, 'UPDATED', '{\"title\":\"wqeqeqw\",\"description\":\"ewqeqweqw\",\"document_type_id\":\"Legal Opinion Request\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"New Clo\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"wqeqeqw\",\"description\":\"ewqeqweqw\",\"document_type_id\":\"Legal Opinion Request\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"New Clo\",\"current_status_id\":\"Approved\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 13:07:09'),
(433, 1, 'DOCUMENT', 1, 'UPDATED', '{\"title\":\"Review of Janitorial Services Contract\",\"description\":\"Legal review requested for 2027 janitorial contract.\",\"document_type_id\":\"Contract Review\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Under Review\",\"priority_level\":\"HIGH\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"BAC-2026-114\",\"date_due\":\"2026-08-20\"}', '{\"title\":\"Review of Janitorial Services Contract\",\"description\":\"Legal review requested for 2027 janitorial contract.\",\"document_type_id\":\"Contract Review\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Approved\",\"priority_level\":\"HIGH\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-08-20\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 13:07:20'),
(434, 1, 'DOCUMENT', 4, 'UPDATED', '{\"title\":\"Notice to Contractor for Delay\",\"description\":\"Legal notice preparation for delayed infrastructure project.\",\"document_type_id\":\"Notice / Compliance Letter\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Kyle Busalla\",\"current_status_id\":\"Received\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"\",\"external_reference_no\":\"BUDGET-2026-040\",\"date_due\":\"2026-08-21\"}', '{\"title\":\"Notice to Contractor for Delay\",\"description\":\"Legal notice preparation for delayed infrastructure project.\",\"document_type_id\":\"Notice / Compliance Letter\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Kyle Busalla\",\"current_status_id\":\"Approved\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-08-21\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 13:07:25'),
(435, 1, 'DOCUMENT', 36, 'UPDATED', '{\"title\":\"AUTHORITY TO TRAVEL\",\"description\":\"request authority to travel abroad\",\"document_type_id\":\"Legal Opinion Request\",\"current_department_id\":\"Bids and Awards Committee\",\"current_assignee_user_id\":\"Liza Dela Cruz\",\"current_status_id\":\"Received\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-25\"}', '{\"title\":\"AUTHORITY TO TRAVEL\",\"description\":\"request authority to travel abroad\",\"document_type_id\":\"Legal Opinion Request\",\"current_department_id\":\"Bids and Awards Committee\",\"current_assignee_user_id\":\"Liza Dela Cruz\",\"current_status_id\":\"Approved\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-25\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 13:07:30'),
(436, 1, 'DOCUMENT', 38, 'UPDATED', '{\"title\":\"weqweqw\",\"description\":\"eqweqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Budget Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"weqweqw\",\"description\":\"eqweqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Budget Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Approved\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 13:07:35'),
(437, 1, 'DOCUMENT', 37, 'UPDATED', '{\"title\":\"asdasd\",\"description\":\"dsdasda\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Kyle Busalla\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"asdasd\",\"description\":\"dsdasda\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Kyle Busalla\",\"current_status_id\":\"Approved\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 13:08:00'),
(438, 1, 'DOCUMENT', 37, 'UPDATED', '{\"title\":\"asdasd\",\"description\":\"dsdasda\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Kyle Busalla\",\"current_status_id\":\"Approved\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"asdasd\",\"description\":\"dsdasda\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Kyle Busalla\",\"current_status_id\":\"Approved\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 13:08:01'),
(439, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-02 13:15:47\"}', '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 13:15:47'),
(440, 20, 'DOCUMENT', 39, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"New Clo\"}', '{\"route_id\":56,\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"New Clo\",\"external_recipient_name\":null,\"route_remarks\":null,\"due_at\":\"2026-10-09 13:48:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 13:48:45'),
(441, 2, 'DOCUMENT', 39, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":56,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Antonio Reyes\",\"remarks\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 13:49:50'),
(442, 2, 'DOCUMENT', 40, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\"}', '{\"route_id\":57,\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"external_recipient_name\":null,\"route_remarks\":\"Route test to Liza Dela Cruz.\",\"due_at\":\"2026-10-03 07:22:00\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-02 15:22:45'),
(443, 3, 'DOCUMENT', 40, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":57,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Liza Dela Cruz\",\"remarks\":null}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-05 10:05:40'),
(446, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-05 12:53:43\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-05 12:53:43'),
(448, 1, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":1,\"generated_at\":\"2026-10-05 12:57:14\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-05 12:57:14'),
(449, 1, 'DOCUMENT', 17, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Super Admin\"}', '{\"route_id\":58,\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Super Admin\",\"external_recipient_name\":null,\"route_remarks\":null,\"due_at\":\"2026-10-30 13:39:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-05 13:39:58'),
(450, 20, 'DOCUMENT', 17, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":58,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"New Clo\",\"remarks\":null}', '192.168.1.189', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36', '2026-10-05 13:40:50'),
(453, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-06 10:19:16\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 10:19:16'),
(455, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-06 10:21:23\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 10:21:23'),
(457, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-06 10:24:06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 10:24:06'),
(459, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-06 10:24:13\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 10:24:13'),
(461, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-06 10:24:35\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 10:24:35'),
(463, 20, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-06 10:45:55\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 10:45:55'),
(465, 20, 'DOCUMENT_TRACKING_SLIP', 35, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-06 10:47:38\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 10:47:38'),
(471, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-06 12:58:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 12:58:53'),
(473, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-06 12:59:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 12:59:15'),
(474, 20, 'DOCUMENT', 55, 'CREATED', NULL, '{\"tracking_no\":\"CLO-2026-000055\",\"title\":\"rfhfh\",\"current_assignee_user_id\":null,\"current_status_id\":\"Received\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 13:33:05'),
(475, 20, 'DOCUMENT', 40, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"CONFIDENTIAL\",\"requested_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.140.0 Chrome/150.0.7871.250 Electron/43.7.3 Safari/537.36', '2026-10-06 13:42:46'),
(476, 2, 'DOCUMENT', 40, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":20,\"granted_by_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 13:43:12'),
(477, 2, 'DOCUMENT', 40, 'ACCESS_REVOKED', NULL, '{\"request_id\":475,\"revoked_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 13:43:15'),
(478, 2, 'DOCUMENT', 40, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\"}', '{\"route_id\":59,\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"external_recipient_name\":null,\"route_remarks\":null,\"due_at\":\"2026-10-06 15:18:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 15:18:52'),
(479, 5, 'DOCUMENT', 40, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":59,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Evelyn Torres\",\"remarks\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 15:19:17'),
(480, 5, 'DOCUMENT', 35, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\"}', '{\"route_id\":60,\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"external_recipient_name\":null,\"route_remarks\":null,\"due_at\":\"2026-10-09 15:40:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 15:40:11'),
(481, 5, 'DOCUMENT', 35, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":60,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Evelyn Torres\",\"remarks\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 15:40:38'),
(482, 5, 'DOCUMENT', 35, 'ROUTED', '{\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\"}', '{\"route_id\":61,\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Evelyn Torres\",\"external_recipient_name\":null,\"route_remarks\":null,\"due_at\":\"2026-10-09 15:40:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 15:41:02'),
(483, 2, 'DOCUMENT', 35, 'RECEIVED', '{\"route_status\":\"PENDING\",\"received_by_user_id\":null}', '{\"route_id\":61,\"route_status\":\"RECEIVED\",\"received_by_user_id\":\"Antonio Reyes\",\"remarks\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 15:41:43'),
(484, 1, 'AUTH_SESSION', 1, 'DEVICE_LOGIN_REQUEST', NULL, '{\"request_id\":1,\"message\":\"A login attempt from another device requires approval.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 16:17:17'),
(485, 1, 'AUTH_SESSION', 2, 'DEVICE_LOGIN_REQUEST', NULL, '{\"request_id\":2,\"message\":\"A login attempt from another device requires approval.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 16:20:02'),
(486, 1, 'AUTH_SESSION', 3, 'DEVICE_LOGIN_REQUEST', NULL, '{\"request_id\":3,\"message\":\"A login attempt from another device requires approval.\"}', '192.168.1.189', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 16:20:24'),
(487, 1, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 08:16:26'),
(488, 1, 'DOCUMENT', 3, 'UPDATED', '{\"title\":\"Draft Resolution on Traffic Ordinance\",\"description\":\"City council draft resolution for legal vetting.\",\"document_type_id\":\"Resolution Draft\",\"current_department_id\":\"City Mayor Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Approved\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"\",\"external_reference_no\":\"CMO-2026-221\",\"date_due\":\"2026-08-21\"}', '{\"title\":\"Draft Resolution on Traffic Ordinance\",\"description\":\"City council draft resolution for legal vetting.\",\"document_type_id\":\"Resolution Draft\",\"current_department_id\":\"City Mayor Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Approved\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"CMO-2026-221\",\"date_due\":\"2026-08-21\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 08:16:55'),
(489, 20, 'DOCUMENT', 3, 'UPDATED', '{\"title\":\"Draft Resolution on Traffic Ordinance\",\"description\":\"City council draft resolution for legal vetting.\",\"document_type_id\":\"Resolution Draft\",\"current_department_id\":\"City Mayor Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Approved\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"CMO-2026-221\",\"date_due\":\"2026-08-21\"}', '{\"title\":\"Draft Resolution on Traffic Ordinance\",\"description\":\"City council draft resolution for legal vetting.\",\"document_type_id\":\"Resolution Draft\",\"current_department_id\":\"City Mayor Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Approved\",\"priority_level\":\"LOW\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"CMO-2026-221\",\"date_due\":\"2026-08-21\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 08:17:43'),
(490, 20, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 08:23:47'),
(491, 20, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 08:24:04'),
(492, 20, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"CLO-2026-222\",\"date_due\":\"2026-09-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 08:30:50'),
(510, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 08:42:54\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 08:42:54'),
(512, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 08:43:07\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 08:43:07'),
(513, 20, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"CLO-2026-222\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"CLO-2026-222\",\"date_due\":\"2026-09-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 08:54:34'),
(514, 20, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"CLO-2026-222\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"CLO-2026-222\",\"date_due\":\"2026-09-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 08:54:47'),
(515, 20, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"CLO-2026-222\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"CLO-2026-223\",\"date_due\":\"2026-09-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 08:54:54');
INSERT INTO `audit_logs` (`audit_log_id`, `user_id`, `entity_type`, `entity_id`, `action`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(516, 20, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"CLO-2026-223\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"CLO-2026-222\",\"date_due\":\"2026-09-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 08:55:06'),
(518, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 08:55:13\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 08:55:13'),
(520, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 08:55:46\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 08:55:46'),
(522, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:17:35\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:17:35'),
(524, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:18:51\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:18:51'),
(529, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:20:09\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:20:09'),
(531, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:22:11\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:22:11'),
(533, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:22:45\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:22:45'),
(535, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:24:35\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:24:35'),
(537, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:24:47\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:24:47'),
(539, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:30:40\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:30:40'),
(541, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:33:23\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:33:23'),
(543, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:33:44\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:33:44'),
(545, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:33:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:33:53'),
(547, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:34:14\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:34:14'),
(549, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:34:35\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:34:35'),
(551, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:34:54\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:34:54'),
(553, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:35:09\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:35:09'),
(555, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:35:54\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:35:54'),
(557, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:36:04\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:36:04'),
(559, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:36:27\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:36:27'),
(561, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:36:46\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:36:46'),
(563, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:37:04\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:37:04'),
(565, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:37:24\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:37:24'),
(567, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:38:03\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:38:03'),
(569, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:38:14\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:38:14'),
(571, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:38:49\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:38:49'),
(573, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:38:59\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:38:59'),
(575, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:40:41\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:40:41'),
(577, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:41:08\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:41:08'),
(579, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:41:37\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:41:37'),
(581, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:42:41\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:42:41'),
(583, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:43:28\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:43:28'),
(585, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:43:56\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:43:56'),
(587, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:44:15\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:44:15'),
(589, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:44:34\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:44:34'),
(591, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:44:54\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:44:54'),
(593, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:45:07\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:45:07'),
(595, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:46:40\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:46:40'),
(597, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:47:28\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:47:28'),
(599, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:47:36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:47:36'),
(601, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:47:50\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:47:50'),
(603, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:50:01\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:50:01'),
(605, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:50:24\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:50:24'),
(607, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:50:36\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:50:36'),
(609, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:50:48\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:50:48'),
(611, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:52:42\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:52:42'),
(613, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 09:53:12\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 09:53:12'),
(615, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 10:01:49\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:01:49'),
(616, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 10:10:12\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:10:12'),
(618, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 10:13:07\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:13:07'),
(620, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 10:14:10\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:14:10'),
(621, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 10:17:21\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:17:21'),
(622, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:18:16'),
(623, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 10:18:16\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:18:16'),
(624, 2, 'DOCUMENT', 39, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"CONFIDENTIAL\",\"requested_by_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:24:25'),
(625, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:24:57'),
(626, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":394,\"revoked_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:25:23'),
(627, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:25:26'),
(628, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":394,\"revoked_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:25:27'),
(629, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:25:28'),
(630, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":394,\"revoked_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:25:29'),
(631, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:25:30'),
(632, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":394,\"revoked_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:25:31'),
(633, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:25:33'),
(634, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":394,\"revoked_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:25:33'),
(635, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:25:34'),
(636, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":394,\"revoked_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:25:35'),
(637, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:25:35'),
(638, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":624,\"revoked_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:25:37'),
(639, 2, 'DOCUMENT', 39, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"CONFIDENTIAL\",\"requested_by_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:25:58'),
(640, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:26:12'),
(641, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":394,\"revoked_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:28:21'),
(642, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:28:23'),
(643, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":394,\"revoked_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:28:24'),
(644, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:28:25'),
(645, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":394,\"revoked_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:28:26'),
(646, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:28:27'),
(647, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":639,\"revoked_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:29:13'),
(648, 2, 'DOCUMENT', 39, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"CONFIDENTIAL\",\"requested_by_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:29:26'),
(649, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:29:37'),
(650, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":648,\"revoked_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:33:08'),
(651, 2, 'DOCUMENT', 39, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"CONFIDENTIAL\",\"requested_by_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:33:35'),
(652, 2, 'DOCUMENT', 39, 'ACCESS_REQUEST_CANCELLED', NULL, '{\"request_id\":651,\"cancelled_by_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 10:33:39'),
(653, 2, 'DOCUMENT', 39, 'ACCESS_REQUEST', NULL, '{\"confidentiality_level\":\"CONFIDENTIAL\",\"requested_by_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 11:16:05'),
(654, 20, 'DOCUMENT', 39, 'ACCESS_GRANTED', NULL, '{\"granted_user_id\":2,\"granted_by_user_id\":20}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 11:16:49'),
(655, 20, 'DOCUMENT', 39, 'ACCESS_REVOKED', NULL, '{\"request_id\":653,\"revoked_user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 11:18:41'),
(656, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:09:26'),
(657, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 13:09:26\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:09:26'),
(658, 20, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"CLO-2026-222\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"CLO-2026-222\",\"date_due\":\"2026-09-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:09:38'),
(659, 20, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"INTERNAL\",\"external_reference_no\":\"CLO-2026-222\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"HIGHLY_CONFIDENTIAL\",\"external_reference_no\":\"CLO-2026-222\",\"date_due\":\"2026-09-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:09:53'),
(660, 20, 'DOCUMENT', 40, 'UPDATED', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"HIGHLY_CONFIDENTIAL\",\"external_reference_no\":\"CLO-2026-222\",\"date_due\":\"2026-09-30\"}', '{\"title\":\"eqweqw\",\"description\":\"weqweqw\",\"document_type_id\":\"Case File\",\"current_department_id\":\"City Legal Office\",\"current_assignee_user_id\":\"Antonio Reyes\",\"current_status_id\":\"Received\",\"priority_level\":\"MEDIUM\",\"confidentiality_level\":\"CONFIDENTIAL\",\"external_reference_no\":\"CLO-2026-222\",\"date_due\":\"2026-09-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:10:01'),
(661, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(662, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(663, 20, 'DOCUMENT', 38, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(664, 20, 'DOCUMENT', 37, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(665, 20, 'DOCUMENT', 36, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(666, 20, 'DOCUMENT', 35, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(667, 20, 'DOCUMENT', 17, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(668, 20, 'DOCUMENT', 16, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(669, 20, 'DOCUMENT', 15, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(670, 20, 'DOCUMENT', 14, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(671, 20, 'DOCUMENT', 13, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(672, 20, 'DOCUMENT', 12, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(673, 20, 'DOCUMENT', 4, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(674, 20, 'DOCUMENT', 3, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(675, 20, 'DOCUMENT', 2, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(676, 20, 'DOCUMENT', 1, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:11'),
(677, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:53'),
(678, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 13:58:53\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:58:53'),
(679, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(680, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(681, 20, 'DOCUMENT', 38, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(682, 20, 'DOCUMENT', 37, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(683, 20, 'DOCUMENT', 36, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(684, 20, 'DOCUMENT', 35, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(685, 20, 'DOCUMENT', 17, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(686, 20, 'DOCUMENT', 16, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(687, 20, 'DOCUMENT', 15, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(688, 20, 'DOCUMENT', 14, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(689, 20, 'DOCUMENT', 13, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(690, 20, 'DOCUMENT', 12, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(691, 20, 'DOCUMENT', 4, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(692, 20, 'DOCUMENT', 3, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(693, 20, 'DOCUMENT', 2, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(694, 20, 'DOCUMENT', 1, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:07'),
(695, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(696, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(697, 20, 'DOCUMENT', 38, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(698, 20, 'DOCUMENT', 37, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(699, 20, 'DOCUMENT', 36, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(700, 20, 'DOCUMENT', 35, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(701, 20, 'DOCUMENT', 17, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(702, 20, 'DOCUMENT', 16, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(703, 20, 'DOCUMENT', 15, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(704, 20, 'DOCUMENT', 14, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(705, 20, 'DOCUMENT', 13, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(706, 20, 'DOCUMENT', 12, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(707, 20, 'DOCUMENT', 4, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(708, 20, 'DOCUMENT', 3, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(709, 20, 'DOCUMENT', 2, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(710, 20, 'DOCUMENT', 1, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 13:59:16'),
(711, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:00:17'),
(712, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:00:26'),
(713, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 14:00:26\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:00:26'),
(714, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:00:40'),
(715, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:48'),
(716, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:48'),
(717, 20, 'DOCUMENT', 38, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:48'),
(718, 20, 'DOCUMENT', 37, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:48'),
(719, 20, 'DOCUMENT', 36, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:48'),
(720, 20, 'DOCUMENT', 35, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:48'),
(721, 20, 'DOCUMENT', 17, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:48'),
(722, 20, 'DOCUMENT', 16, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:48'),
(723, 20, 'DOCUMENT', 15, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:48');
INSERT INTO `audit_logs` (`audit_log_id`, `user_id`, `entity_type`, `entity_id`, `action`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(724, 20, 'DOCUMENT', 14, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:48'),
(725, 20, 'DOCUMENT', 13, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:48'),
(726, 20, 'DOCUMENT', 12, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:48'),
(727, 20, 'DOCUMENT', 4, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:48'),
(728, 20, 'DOCUMENT', 3, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:48'),
(729, 20, 'DOCUMENT', 2, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:49'),
(730, 20, 'DOCUMENT', 1, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:01:49'),
(731, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(732, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(733, 20, 'DOCUMENT', 38, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(734, 20, 'DOCUMENT', 37, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(735, 20, 'DOCUMENT', 36, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(736, 20, 'DOCUMENT', 35, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(737, 20, 'DOCUMENT', 17, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(738, 20, 'DOCUMENT', 16, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(739, 20, 'DOCUMENT', 15, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(740, 20, 'DOCUMENT', 14, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(741, 20, 'DOCUMENT', 13, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(742, 20, 'DOCUMENT', 12, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(743, 20, 'DOCUMENT', 4, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(744, 20, 'DOCUMENT', 3, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(745, 20, 'DOCUMENT', 2, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(746, 20, 'DOCUMENT', 1, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:02:28'),
(747, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(748, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(749, 20, 'DOCUMENT', 38, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(750, 20, 'DOCUMENT', 37, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(751, 20, 'DOCUMENT', 36, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(752, 20, 'DOCUMENT', 35, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(753, 20, 'DOCUMENT', 17, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(754, 20, 'DOCUMENT', 16, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(755, 20, 'DOCUMENT', 15, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(756, 20, 'DOCUMENT', 14, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(757, 20, 'DOCUMENT', 13, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(758, 20, 'DOCUMENT', 12, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(759, 20, 'DOCUMENT', 4, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(760, 20, 'DOCUMENT', 3, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(761, 20, 'DOCUMENT', 2, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(762, 20, 'DOCUMENT', 1, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:06'),
(763, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:22'),
(764, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:22'),
(765, 20, 'DOCUMENT', 38, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:22'),
(766, 20, 'DOCUMENT', 37, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:22'),
(767, 20, 'DOCUMENT', 36, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:22'),
(768, 20, 'DOCUMENT', 35, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:22'),
(769, 20, 'DOCUMENT', 17, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:23'),
(770, 20, 'DOCUMENT', 16, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:23'),
(771, 20, 'DOCUMENT', 15, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:23'),
(772, 20, 'DOCUMENT', 14, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:23'),
(773, 20, 'DOCUMENT', 13, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:23'),
(774, 20, 'DOCUMENT', 12, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:23'),
(775, 20, 'DOCUMENT', 4, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:23'),
(776, 20, 'DOCUMENT', 3, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:23'),
(777, 20, 'DOCUMENT', 2, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:23'),
(778, 20, 'DOCUMENT', 1, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:23'),
(779, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(780, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(781, 20, 'DOCUMENT', 38, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(782, 20, 'DOCUMENT', 37, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(783, 20, 'DOCUMENT', 36, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(784, 20, 'DOCUMENT', 35, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(785, 20, 'DOCUMENT', 17, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(786, 20, 'DOCUMENT', 16, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(787, 20, 'DOCUMENT', 15, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(788, 20, 'DOCUMENT', 14, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(789, 20, 'DOCUMENT', 13, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(790, 20, 'DOCUMENT', 12, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(791, 20, 'DOCUMENT', 4, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(792, 20, 'DOCUMENT', 3, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(793, 20, 'DOCUMENT', 2, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(794, 20, 'DOCUMENT', 1, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:03:31'),
(795, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:04:20'),
(796, 20, 'DOCUMENT_TRACKING_SLIP', 39, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 14:04:20\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:04:20'),
(797, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(798, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(799, 20, 'DOCUMENT', 38, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(800, 20, 'DOCUMENT', 37, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(801, 20, 'DOCUMENT', 36, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(802, 20, 'DOCUMENT', 35, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(803, 20, 'DOCUMENT', 17, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(804, 20, 'DOCUMENT', 16, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(805, 20, 'DOCUMENT', 15, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(806, 20, 'DOCUMENT', 14, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(807, 20, 'DOCUMENT', 13, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(808, 20, 'DOCUMENT', 12, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(809, 20, 'DOCUMENT', 4, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(810, 20, 'DOCUMENT', 3, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(811, 20, 'DOCUMENT', 2, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(812, 20, 'DOCUMENT', 1, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:05:22'),
(813, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(814, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(815, 20, 'DOCUMENT', 38, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(816, 20, 'DOCUMENT', 37, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(817, 20, 'DOCUMENT', 36, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(818, 20, 'DOCUMENT', 35, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(819, 20, 'DOCUMENT', 17, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(820, 20, 'DOCUMENT', 16, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(821, 20, 'DOCUMENT', 15, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(822, 20, 'DOCUMENT', 14, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(823, 20, 'DOCUMENT', 13, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(824, 20, 'DOCUMENT', 12, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(825, 20, 'DOCUMENT', 4, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(826, 20, 'DOCUMENT', 3, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(827, 20, 'DOCUMENT', 2, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(828, 20, 'DOCUMENT', 1, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:04'),
(829, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(830, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(831, 20, 'DOCUMENT', 38, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(832, 20, 'DOCUMENT', 37, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(833, 20, 'DOCUMENT', 36, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(834, 20, 'DOCUMENT', 35, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(835, 20, 'DOCUMENT', 17, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(836, 20, 'DOCUMENT', 16, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(837, 20, 'DOCUMENT', 15, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(838, 20, 'DOCUMENT', 14, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(839, 20, 'DOCUMENT', 13, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(840, 20, 'DOCUMENT', 12, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(841, 20, 'DOCUMENT', 4, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(842, 20, 'DOCUMENT', 3, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(843, 20, 'DOCUMENT', 2, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(844, 20, 'DOCUMENT', 1, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:06:58'),
(845, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:08'),
(846, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 14:07:08\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:08'),
(847, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(848, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(849, 20, 'DOCUMENT', 38, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(850, 20, 'DOCUMENT', 37, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(851, 20, 'DOCUMENT', 36, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(852, 20, 'DOCUMENT', 35, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(853, 20, 'DOCUMENT', 17, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(854, 20, 'DOCUMENT', 16, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(855, 20, 'DOCUMENT', 15, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(856, 20, 'DOCUMENT', 14, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(857, 20, 'DOCUMENT', 13, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(858, 20, 'DOCUMENT', 12, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(859, 20, 'DOCUMENT', 4, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(860, 20, 'DOCUMENT', 3, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(861, 20, 'DOCUMENT', 2, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(862, 20, 'DOCUMENT', 1, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:07:34'),
(863, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(864, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(865, 20, 'DOCUMENT', 38, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(866, 20, 'DOCUMENT', 37, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(867, 20, 'DOCUMENT', 36, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(868, 20, 'DOCUMENT', 35, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(869, 20, 'DOCUMENT', 17, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(870, 20, 'DOCUMENT', 16, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(871, 20, 'DOCUMENT', 15, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(872, 20, 'DOCUMENT', 14, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(873, 20, 'DOCUMENT', 13, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(874, 20, 'DOCUMENT', 12, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(875, 20, 'DOCUMENT', 4, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(876, 20, 'DOCUMENT', 3, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(877, 20, 'DOCUMENT', 2, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(878, 20, 'DOCUMENT', 1, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:35'),
(879, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:39'),
(880, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 14:08:39\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:08:39'),
(881, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:10:20'),
(882, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 14:10:20\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:10:20'),
(883, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:10:35'),
(884, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 14:10:35\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:10:35'),
(885, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:25'),
(886, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 14:11:25\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:25');
INSERT INTO `audit_logs` (`audit_log_id`, `user_id`, `entity_type`, `entity_id`, `action`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(887, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(888, 20, 'DOCUMENT', 39, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000039\",\"document_title\":\"wqeqeqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(889, 20, 'DOCUMENT', 38, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000038\",\"document_title\":\"weqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(890, 20, 'DOCUMENT', 37, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000037\",\"document_title\":\"asdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(891, 20, 'DOCUMENT', 36, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000036\",\"document_title\":\"AUTHORITY TO TRAVEL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(892, 20, 'DOCUMENT', 35, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000035\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(893, 20, 'DOCUMENT', 17, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000017\",\"document_title\":\"sfdfsgsdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(894, 20, 'DOCUMENT', 16, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000016\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(895, 20, 'DOCUMENT', 15, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000015\",\"document_title\":\"sdasd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(896, 20, 'DOCUMENT', 14, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000014\",\"document_title\":\"wqeqwe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(897, 20, 'DOCUMENT', 13, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000013\",\"document_title\":\"MEMO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(898, 20, 'DOCUMENT', 12, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000012\",\"document_title\":\"gfsfsfsgd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(899, 20, 'DOCUMENT', 4, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000004\",\"document_title\":\"Notice to Contractor for Delay\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(900, 20, 'DOCUMENT', 3, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000003\",\"document_title\":\"Draft Resolution on Traffic Ordinance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(901, 20, 'DOCUMENT', 2, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000002\",\"document_title\":\"Request for Legal Opinion on Overtime Policy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(902, 20, 'DOCUMENT', 1, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000001\",\"document_title\":\"Review of Janitorial Services Contract\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 14:11:29'),
(903, 20, 'DOCUMENT', 40, 'PRINT_INITIATED', NULL, '{\"source\":\"TRACKING_SLIP\",\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 15:29:38'),
(904, 20, 'DOCUMENT_TRACKING_SLIP', 40, 'GENERATE', NULL, '{\"tracking_no\":\"CLO-2026-000040\",\"document_title\":\"eqweqw\",\"generated_by_user_id\":20,\"generated_at\":\"2026-10-07 15:29:38\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 15:29:38');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(10) UNSIGNED NOT NULL,
  `department_code` varchar(20) NOT NULL,
  `department_name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_code`, `department_name`, `description`, `is_active`, `created_at`) VALUES
(1, 'CLO', 'City Legal Office', 'Main legal office', 1, '2026-08-14 00:49:43'),
(2, 'CMO', 'City Mayor Office', 'Mayor and executive office', 1, '2026-08-14 00:49:43'),
(3, 'CHRMO', 'City Human Resource Management Office', 'Personnel and HR concerns', 1, '2026-08-14 00:49:43'),
(4, 'CBO', 'City Budget Office', 'Budget and fiscal approvals', 1, '2026-08-14 00:49:43'),
(5, 'BAC', 'Bids and Awards Committee', 'Procurement and bidding matters', 1, '2026-08-14 00:49:43'),
(19, 'OTHERS', 'Others', 'Personal documents and sources without a registered department.', 0, '2026-09-25 03:19:02');

-- --------------------------------------------------------

--
-- Table structure for table `device_login_requests`
--

CREATE TABLE `device_login_requests` (
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `code_hash` char(64) NOT NULL,
  `code_value` char(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `device_login_requests`
--

INSERT INTO `device_login_requests` (`request_id`, `user_id`, `code_hash`, `code_value`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 1, '9deaab69ef4b0282c4f8e0cbfecf85d8185ed5a827291cbb71180a60851e36c0', '281600', '2026-10-06 16:27:17', '2026-10-06 16:20:02', '2026-10-06 16:17:17'),
(2, 1, '3449814db72c149f2d58fc60288780a7274517efbf37ea529484d2f6fb38c37a', '518837', '2026-10-06 16:30:02', NULL, '2026-10-06 16:20:02'),
(3, 1, '242b05fda51d151c1a446135f05d2429eb9a83207a4bc7f53f2dc0acc5656f76', '947573', '2026-10-06 16:30:24', NULL, '2026-10-06 16:20:24');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `tracking_no` varchar(40) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `document_type_id` int(10) UNSIGNED NOT NULL,
  `current_status_id` int(10) UNSIGNED NOT NULL,
  `origin_department_id` int(10) UNSIGNED NOT NULL,
  `origin_department_other` varchar(255) DEFAULT NULL,
  `current_department_id` int(10) UNSIGNED NOT NULL,
  `created_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `current_assignee_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `priority_level` enum('LOW','MEDIUM','HIGH','URGENT') NOT NULL DEFAULT 'MEDIUM',
  `confidentiality_level` enum('PUBLIC','INTERNAL','CONFIDENTIAL','HIGHLY_CONFIDENTIAL') NOT NULL DEFAULT 'INTERNAL',
  `external_reference_no` varchar(100) DEFAULT NULL,
  `date_received` date NOT NULL,
  `date_due` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  `archived_by_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`document_id`, `tracking_no`, `title`, `description`, `document_type_id`, `current_status_id`, `origin_department_id`, `origin_department_other`, `current_department_id`, `created_by_user_id`, `current_assignee_user_id`, `priority_level`, `confidentiality_level`, `external_reference_no`, `date_received`, `date_due`, `created_at`, `updated_at`, `archived_at`, `archived_by_user_id`) VALUES
(1, 'CLO-2026-000001', 'Review of Janitorial Services Contract', 'Legal review requested for 2027 janitorial contract.', 2, 4, 5, NULL, 1, 4, 2, 'HIGH', 'CONFIDENTIAL', '', '2026-08-10', '2026-08-20', '2026-08-14 00:49:43', '2026-10-02 05:07:20', NULL, NULL),
(2, 'CLO-2026-000002', 'Request for Legal Opinion on Overtime Policy', 'HRMO requests legal guidance on overtime policy implementation.', 4, 4, 3, NULL, 2, 5, 2, 'MEDIUM', 'INTERNAL', '', '2026-08-11', '2026-08-18', '2026-08-14 00:49:43', '2026-10-02 05:06:03', NULL, NULL),
(3, 'CLO-2026-000003', 'Draft Resolution on Traffic Ordinance', 'City council draft resolution for legal vetting.', 3, 4, 2, NULL, 2, 4, 2, 'LOW', 'INTERNAL', 'CLO-2026-221', '2026-08-09', '2026-08-21', '2026-08-14 00:49:43', '2026-10-07 00:23:14', NULL, NULL),
(4, 'CLO-2026-000004', 'Notice to Contractor for Delay', 'Legal notice preparation for delayed infrastructure project.', 6, 4, 4, NULL, 1, 4, 6, 'LOW', 'INTERNAL', '', '2026-08-14', '2026-08-21', '2026-08-14 00:49:43', '2026-10-02 05:07:25', NULL, NULL),
(12, 'CLO-2026-000012', 'gfsfsfsgd', 'agdf', 1, 4, 4, NULL, 2, 5, 5, 'URGENT', 'INTERNAL', '', '2026-08-18', '2026-08-18', '2026-08-18 07:28:22', '2026-09-04 04:47:54', NULL, NULL),
(13, 'CLO-2026-000013', 'MEMO', 'asfagfdgs', 1, 4, 3, NULL, 1, 1, 5, 'LOW', 'INTERNAL', '', '2026-08-19', '2026-08-19', '2026-08-19 02:45:45', '2026-10-02 05:06:50', NULL, NULL),
(14, 'CLO-2026-000014', 'wqeqwe', '', 5, 1, 5, NULL, 4, 1, 5, 'MEDIUM', 'INTERNAL', '', '2026-08-19', '2026-10-31', '2026-08-19 06:44:38', '2026-10-01 06:41:26', NULL, NULL),
(15, 'CLO-2026-000015', 'sdasd', 'asdasd', 2, 1, 5, NULL, 1, 1, 1, 'HIGH', 'INTERNAL', '', '2026-08-19', '2026-10-31', '2026-08-19 06:46:03', '2026-10-01 06:41:12', NULL, NULL),
(16, 'CLO-2026-000016', 'eqweqw', 'qwewqe', 5, 4, 4, NULL, 3, 1, 5, 'MEDIUM', 'INTERNAL', '', '2026-08-19', '2026-08-19', '2026-08-19 06:48:52', '2026-09-04 03:27:58', NULL, NULL),
(17, 'CLO-2026-000017', 'sfdfsgsdf', 'fdgdfgsfsdaf', 3, 4, 1, NULL, 1, 5, 1, 'LOW', 'INTERNAL', '', '2026-09-04', '2026-09-18', '2026-09-04 04:08:22', '2026-10-05 05:39:58', NULL, NULL),
(35, 'CLO-2026-000035', 'wqeqwe', 'eqrtfgsf', 5, 1, 5, NULL, 1, 1, 5, 'MEDIUM', 'INTERNAL', '', '2026-09-24', '2026-10-10', '2026-09-24 01:01:23', '2026-10-06 07:41:43', NULL, NULL),
(36, 'CLO-2026-000036', 'AUTHORITY TO TRAVEL', 'request authority to travel abroad', 4, 4, 4, NULL, 5, 1, 3, 'LOW', 'INTERNAL', '', '2026-09-25', '2026-09-25', '2026-09-25 01:34:09', '2026-10-02 05:07:30', NULL, NULL),
(37, 'CLO-2026-000037', 'asdasd', 'dsdasda', 5, 4, 1, '', 1, 1, 6, 'MEDIUM', 'CONFIDENTIAL', '', '2026-09-25', '2026-09-30', '2026-09-25 03:20:13', '2026-10-02 05:08:01', NULL, NULL),
(38, 'CLO-2026-000038', 'weqweqw', 'eqweqweqw', 5, 4, 19, 'Kyle Busalla', 4, 1, 2, 'MEDIUM', 'INTERNAL', '', '2026-09-25', '2026-09-30', '2026-09-25 03:55:34', '2026-10-02 05:07:35', NULL, NULL),
(39, 'CLO-2026-000039', 'wqeqeqw', 'ewqeqweqw', 4, 4, 1, '', 1, 1, 20, 'MEDIUM', 'CONFIDENTIAL', '', '2026-09-25', '2026-09-30', '2026-09-25 04:31:22', '2026-10-02 05:48:45', NULL, NULL),
(40, 'CLO-2026-000040', 'eqweqw', 'weqweqw', 5, 1, 1, '', 1, 1, 2, 'MEDIUM', 'CONFIDENTIAL', 'CLO-2026-222', '2026-09-25', '2026-09-30', '2026-09-25 05:08:58', '2026-10-07 05:10:01', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `document_access_grants`
--

CREATE TABLE `document_access_grants` (
  `grant_id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `granted_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `granted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_assignment_receipts`
--

CREATE TABLE `document_assignment_receipts` (
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `accepted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_assignment_receipts`
--

INSERT INTO `document_assignment_receipts` (`document_id`, `user_id`, `assigned_by_user_id`, `assigned_at`, `accepted_at`) VALUES
(1, 2, 4, '2026-08-14 08:49:43', '2026-08-14 08:49:43'),
(2, 2, 5, '2026-08-14 08:49:43', '2026-08-14 08:49:43'),
(3, 2, 4, '2026-08-14 08:49:43', '2026-08-14 08:49:43'),
(4, 6, 4, '2026-08-14 08:49:43', '2026-08-14 08:49:43'),
(12, 5, 5, '2026-08-18 15:28:22', '2026-08-18 15:28:22'),
(13, 5, 1, '2026-08-19 10:45:45', '2026-08-19 10:45:45'),
(14, 5, 1, '2026-08-19 14:44:38', '2026-08-19 14:44:38'),
(15, 1, 1, '2026-08-19 14:46:03', '2026-08-19 14:46:03'),
(16, 5, 1, '2026-08-19 14:48:52', '2026-08-19 14:48:52'),
(17, 1, 5, '2026-09-04 12:08:22', '2026-09-04 12:08:22'),
(35, 5, 1, '2026-09-24 09:01:23', '2026-09-24 09:01:23'),
(36, 3, 1, '2026-09-25 09:34:09', '2026-09-25 09:34:09'),
(37, 6, 1, '2026-09-25 11:20:13', '2026-09-25 11:20:13'),
(38, 2, 1, '2026-09-25 11:55:34', '2026-09-25 11:55:34'),
(39, 20, 1, '2026-09-25 12:31:22', '2026-09-25 12:31:22'),
(40, 2, 1, '2026-09-25 13:08:58', '2026-09-25 13:08:58');

-- --------------------------------------------------------

--
-- Table structure for table `document_history`
--

CREATE TABLE `document_history` (
  `document_history_id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `document_status_id` int(10) UNSIGNED NOT NULL,
  `changed_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `change_note` varchar(500) DEFAULT NULL,
  `changed_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_history`
--

INSERT INTO `document_history` (`document_history_id`, `document_id`, `document_status_id`, `changed_by_user_id`, `change_note`, `changed_at`) VALUES
(1, 1, 1, 4, 'Document received from BAC.', '2026-08-10 09:30:00'),
(2, 1, 2, 2, 'Assigned for contract legal review.', '2026-08-10 10:00:00'),
(3, 2, 1, 5, 'Submitted to CLO records desk.', '2026-08-11 10:00:00'),
(4, 2, 2, 2, 'Under legal opinion drafting.', '2026-08-11 13:15:00'),
(5, 2, 3, 2, 'Routed approved opinion to CMO.', '2026-08-13 15:40:00'),
(6, 3, 1, 4, 'Document received from CMO.', '2026-08-09 09:10:00'),
(7, 3, 2, 3, 'Reviewed by legal staff.', '2026-08-09 11:35:00'),
(8, 3, 4, 2, 'Approved by City Attorney.', '2026-08-10 14:10:00'),
(9, 4, 1, 4, 'New budget-related notice request logged.', '2026-08-14 08:25:00'),
(85, 17, 6, 1, 'Document archived.', '2026-09-11 03:35:15'),
(86, 17, 1, 1, 'Document restored.', '2026-09-11 03:35:30'),
(87, 17, 6, 1, 'Document archived.', '2026-09-11 03:35:40');

-- --------------------------------------------------------

--
-- Table structure for table `document_routes`
--

CREATE TABLE `document_routes` (
  `document_route_id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `from_department_id` int(10) UNSIGNED DEFAULT NULL,
  `to_department_id` int(10) UNSIGNED NOT NULL,
  `routed_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `routed_to_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `external_recipient_name` varchar(150) DEFAULT NULL,
  `route_status` enum('PENDING','RECEIVED','RETURNED','COMPLETED') NOT NULL DEFAULT 'PENDING',
  `remarks` varchar(500) DEFAULT NULL,
  `routed_at` datetime NOT NULL,
  `received_at` datetime DEFAULT NULL,
  `due_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_routes`
--

INSERT INTO `document_routes` (`document_route_id`, `document_id`, `from_department_id`, `to_department_id`, `routed_by_user_id`, `routed_to_user_id`, `external_recipient_name`, `route_status`, `remarks`, `routed_at`, `received_at`, `due_at`) VALUES
(1, 1, 5, 1, 4, 2, NULL, 'RECEIVED', 'Forwarded to CLO for legal review.', '2026-08-10 09:15:00', '2026-08-10 09:30:00', '2026-08-20 17:00:00'),
(2, 2, 3, 1, 5, 2, NULL, 'COMPLETED', 'Opinion draft completed and returned to CMO.', '2026-08-11 10:00:00', '2026-08-11 10:20:00', '2026-08-18 17:00:00'),
(3, 2, 1, 2, 2, 4, NULL, 'RECEIVED', 'Routing signed legal opinion to CMO.', '2026-08-13 15:40:00', '2026-08-13 16:05:00', '2026-08-18 17:00:00'),
(4, 3, 2, 1, 4, 2, NULL, 'COMPLETED', 'Urgent ordinance draft for attorney approval.', '2026-08-09 08:50:00', '2026-08-09 09:10:00', '2026-08-15 12:00:00'),
(9, 4, 1, 1, 1, 1, NULL, '', '', '2026-08-18 11:08:44', NULL, NULL),
(11, 4, 1, 1, 1, 6, NULL, '', '', '2026-08-18 11:10:05', NULL, NULL),
(12, 12, 1, 2, 5, NULL, NULL, '', '', '2026-08-18 15:29:42', NULL, NULL),
(13, 16, 1, 5, 1, NULL, NULL, '', '', '2026-08-19 14:59:48', NULL, NULL),
(14, 16, 5, 1, 1, NULL, NULL, '', '', '2026-08-19 14:59:55', NULL, NULL),
(15, 16, 1, 3, 1, 5, NULL, '', '', '2026-09-02 17:03:26', NULL, '2026-09-04 17:03:00'),
(16, 16, 3, 1, 5, 1, NULL, '', 'dadasfaasd', '2026-09-03 07:54:22', NULL, '2026-09-03 07:54:00'),
(17, 16, 1, 3, 1, 5, NULL, '', '', '2026-09-03 08:00:46', NULL, '2026-09-03 08:00:00'),
(18, 16, 3, 4, 1, NULL, NULL, '', '', '2026-09-03 13:23:36', NULL, NULL),
(19, 16, 4, 5, 1, NULL, NULL, '', '', '2026-09-03 13:25:21', NULL, NULL),
(20, 16, 5, 4, 1, NULL, NULL, '', '', '2026-09-03 13:25:56', NULL, NULL),
(21, 16, 4, 1, 1, 2, NULL, '', 'dadadww', '2026-09-03 13:32:35', NULL, '2026-09-03 13:32:00'),
(22, 15, 1, 3, 1, 5, NULL, 'RECEIVED', 'thankyou', '2026-09-04 09:16:17', '2026-09-04 09:20:36', '2026-09-04 09:16:00'),
(23, 15, 3, 1, 5, 1, NULL, 'RECEIVED', 'thankyou', '2026-09-04 09:22:35', '2026-09-04 09:22:57', '2026-09-04 09:22:00'),
(24, 16, 1, 3, 2, 5, NULL, 'RECEIVED', 'fsdfsgfssdgsdf', '2026-09-04 11:27:58', '2026-09-04 11:29:40', '2026-09-04 11:28:00'),
(25, 17, 1, 3, 5, 5, NULL, 'RECEIVED', 'twerefdfgdfgfsdgf', '2026-09-04 12:09:58', '2026-09-04 12:10:15', '2026-09-30 12:09:00'),
(26, 17, 3, 1, 5, 2, NULL, 'RECEIVED', 'sfsdf', '2026-09-04 12:10:45', '2026-09-04 12:11:40', '2026-09-30 12:10:00'),
(27, 17, 1, 3, 5, 5, NULL, 'RECEIVED', '', '2026-09-04 12:48:57', '2026-09-04 12:49:10', '2026-09-04 12:49:00'),
(28, 17, 3, 3, 1, 5, NULL, 'RECEIVED', '', '2026-09-04 13:32:41', '2026-09-04 13:33:19', '2026-09-04 13:32:00'),
(29, 17, 3, 1, 5, 2, NULL, 'RECEIVED', 'dasda', '2026-09-04 13:41:56', '2026-09-04 13:42:48', '2026-09-04 13:41:00'),
(45, 17, 1, 5, 1, 3, NULL, 'RECEIVED', '', '2026-09-18 11:35:37', '2026-09-18 15:21:34', '2026-09-18 11:35:00'),
(46, 17, 5, 1, 1, 6, NULL, 'RECEIVED', '', '2026-09-21 09:39:44', '2026-09-21 09:39:56', '2026-09-21 09:39:00'),
(47, 17, 1, 1, 6, 1, NULL, 'RECEIVED', '', '2026-09-21 13:03:54', '2026-09-21 13:04:23', '2026-09-21 13:04:00'),
(48, 36, 1, 5, 1, 3, NULL, 'PENDING', '', '2026-09-25 10:33:49', NULL, '2026-09-25 10:33:00'),
(49, 38, 1, 4, 1, NULL, NULL, 'RECEIVED', '', '2026-09-25 11:56:40', '2026-09-25 12:33:30', '2026-09-25 11:56:00'),
(50, 38, 4, 4, 1, 2, NULL, 'RECEIVED', '', '2026-09-25 11:56:59', '2026-09-25 12:33:33', '2026-09-25 11:57:00'),
(56, 39, 1, 1, 20, 2, NULL, 'RECEIVED', '', '2026-10-02 13:48:45', '2026-10-02 13:49:50', '2026-10-09 13:48:00'),
(57, 40, 1, 1, 2, 3, NULL, 'RECEIVED', 'Route test to Liza Dela Cruz.', '2026-10-02 15:22:45', '2026-10-05 10:05:40', '2026-10-03 07:22:00'),
(58, 17, 1, 1, 1, 20, NULL, 'RECEIVED', '', '2026-10-05 13:39:58', '2026-10-05 13:40:50', '2026-10-30 13:39:00'),
(59, 40, 1, 1, 2, 5, NULL, 'RECEIVED', '', '2026-10-06 15:18:52', '2026-10-06 15:19:17', '2026-10-06 15:18:00'),
(60, 35, 1, 1, 5, 5, NULL, 'RECEIVED', '', '2026-10-06 15:40:11', '2026-10-06 15:40:38', '2026-10-09 15:40:00'),
(61, 35, 1, 1, 5, 2, NULL, 'RECEIVED', '', '2026-10-06 15:41:02', '2026-10-06 15:41:43', '2026-10-09 15:40:00');

-- --------------------------------------------------------

--
-- Table structure for table `document_slip_fields`
--

CREATE TABLE `document_slip_fields` (
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `action_desired` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`action_desired`)),
  `tasks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tasks`)),
  `outputs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`outputs`)),
  `evaluation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evaluation`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_status`
--

CREATE TABLE `document_status` (
  `document_status_id` int(10) UNSIGNED NOT NULL,
  `status_code` varchar(20) NOT NULL,
  `status_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_final` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_status`
--

INSERT INTO `document_status` (`document_status_id`, `status_code`, `status_name`, `description`, `is_final`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'RECEIVED', 'Received', 'Document has been received by receiving office', 0, 1, 1, '2026-08-14 00:49:43'),
(2, 'UNDER_REVIEW', 'Under Review', 'Document is currently being reviewed', 0, 2, 1, '2026-08-14 00:49:43'),
(3, 'ROUTED', 'Routed', 'Document has been routed to another office', 0, 3, 1, '2026-08-14 00:49:43'),
(4, 'APPROVED', 'Approved', 'Document has been approved', 1, 4, 1, '2026-08-14 00:49:43'),
(5, 'REJECTED', 'Rejected', 'Document has been rejected', 1, 5, 1, '2026-08-14 00:49:43'),
(6, 'ARCHIVED', 'Archived', 'Document is completed and archived', 1, 6, 1, '2026-08-14 00:49:43');

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `document_type_id` int(10) UNSIGNED NOT NULL,
  `type_code` varchar(20) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `retention_days` int(10) UNSIGNED DEFAULT NULL,
  `requires_acknowledgment` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`document_type_id`, `type_code`, `type_name`, `description`, `retention_days`, `requires_acknowledgment`, `is_active`, `created_at`) VALUES
(1, 'MEMO', 'Memorandum', 'Internal office memorandum', 1825, 1, 1, '2026-08-14 00:49:43'),
(2, 'CONTRACT', 'Contract Review', 'Contracts requiring legal review', 3650, 1, 1, '2026-08-14 00:49:43'),
(3, 'RESO', 'Resolution Draft', 'City council resolution draft', 3650, 1, 1, '2026-08-14 00:49:43'),
(4, 'LEGAL_OP', 'Legal Opinion Request', 'Request for legal opinion', 3650, 1, 1, '2026-08-14 00:49:43'),
(5, 'CASE', 'Case File', 'Litigation or administrative case file', 5475, 1, 1, '2026-08-14 00:49:43'),
(6, 'NOTICE', 'Notice / Compliance Letter', 'Outgoing legal notice', 1825, 1, 1, '2026-08-14 00:49:43');

-- --------------------------------------------------------

--
-- Table structure for table `login_device_requests`
--

CREATE TABLE `login_device_requests` (
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `request_token_hash` char(64) NOT NULL,
  `remember_me` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('PENDING','APPROVED','DENIED','EXPIRED') NOT NULL DEFAULT 'PENDING',
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `decided_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_device_requests`
--

INSERT INTO `login_device_requests` (`request_id`, `user_id`, `request_token_hash`, `remember_me`, `status`, `requested_at`, `expires_at`, `decided_at`) VALUES
(3, 20, 'b451b929e4ba6b641ee286151e7e1a21c7e4974d01859d0a2e19aa57b4923100', 0, 'APPROVED', '2026-10-06 16:44:32', '2026-10-06 16:46:32', '2026-10-06 16:46:15'),
(4, 20, '49b97fce2595eea49ba47040ec9782c215e7b5a6b48507b2dab692e13891046a', 0, 'APPROVED', '2026-10-06 16:46:21', '2026-10-06 16:48:21', '2026-10-06 16:46:26'),
(5, 20, '3bdf7b3bb33786cc45ee88775cb843a52e3e87bc671c1b4d5fd6f518c1e4c71d', 0, 'APPROVED', '2026-10-06 16:47:30', '2026-10-06 16:49:30', '2026-10-06 16:47:42'),
(6, 20, 'a7cffea0d74ffc5f64911b35a2240013394361ac78cea60b6c098fb14cbe8009', 0, 'APPROVED', '2026-10-06 16:47:45', '2026-10-06 16:49:45', '2026-10-06 16:48:01'),
(7, 20, 'fe2fa9bb4bb41407bc3aa32f1c3447b57efb1b16b76cc29c98434939ebcd306f', 0, 'APPROVED', '2026-10-06 16:47:47', '2026-10-06 16:49:47', '2026-10-06 16:47:59'),
(8, 20, 'a3bf7d8fa6d71b1fd4d4191776325228a131ef4a6b7811150be9b94f0d70a93e', 0, 'APPROVED', '2026-10-06 16:47:50', '2026-10-06 16:49:50', '2026-10-06 16:47:54'),
(9, 1, '90fff58ee8ebd7a43358aa063ab59772a47f8b2a6f39cc305942b8227dff7cb6', 0, 'EXPIRED', '2026-10-06 16:48:13', '2026-10-06 16:50:13', '2026-10-06 16:51:00'),
(10, 1, 'df15cdc6c27cb60cb4b5d66162ddcdd22091231841dbc23d94969040914a9651', 0, 'APPROVED', '2026-10-06 16:55:06', '2026-10-06 17:05:06', '2026-10-06 16:55:11'),
(11, 1, 'e381808f865df0f9fb87dab8eb73a0cd3a0e54fd76063dd8ee50191862fecbab', 0, 'APPROVED', '2026-10-06 16:57:01', '2026-10-06 17:07:01', '2026-10-06 16:57:06'),
(12, 1, '79ea4bbc995c2dc0f88e825fed34f13f53bd11fd661484fca90a2b8beb9752bb', 0, 'APPROVED', '2026-10-06 16:58:12', '2026-10-06 17:08:12', '2026-10-06 16:58:29'),
(13, 1, '77a8e41036d010c5090f1bbc91a2a620b4790e127e8850734fab146ae4fa97f8', 0, 'DENIED', '2026-10-06 16:59:54', '2026-10-06 17:09:54', '2026-10-06 17:00:07'),
(14, 1, 'ddf0b6327bf429fa7590d4496638bdf7581704fb304ff883f26c1bf6a5e9e182', 0, 'APPROVED', '2026-10-06 17:00:14', '2026-10-06 17:10:14', '2026-10-06 17:00:17'),
(15, 1, '15810b24358a3508bc862c87a5b7ee5fe7d7e15f937c0b9c610077058b362128', 0, 'DENIED', '2026-10-06 17:00:47', '2026-10-06 17:10:47', '2026-10-06 17:00:51'),
(16, 20, '9af9f486d99b0a97cbe2479da279d0ac3eb9ebfaebf39708864fc397387c219e', 0, 'APPROVED', '2026-10-07 11:16:26', '2026-10-07 11:26:26', '2026-10-07 11:16:46'),
(17, 20, '5988bbf47bc9453094b4b878fb29d29dd5ad7dd4da151bd392dddff65c7b1110', 0, 'APPROVED', '2026-10-07 14:56:50', '2026-10-07 15:06:50', '2026-10-07 14:57:06'),
(18, 1, '2b0953d235313b049b59c1110cae737c30d47c1808a6ea80c715988aae626ab9', 0, 'APPROVED', '2026-10-08 10:23:50', '2026-10-08 10:33:50', '2026-10-08 10:23:53'),
(19, 1, '5f89343a1c6c9a44ecdc61d67b016737dba8b0331613034de1e0a044643cd4a9', 0, 'APPROVED', '2026-10-09 12:23:32', '2026-10-09 12:33:32', '2026-10-09 12:23:37'),
(20, 1, '20a9f17548fc4d59ee9e34f656d015db91da807f143540a27a57c8595b29dd54', 0, 'APPROVED', '2026-10-09 12:26:25', '2026-10-09 12:36:25', '2026-10-09 12:26:31'),
(21, 1, 'eb9ceac5444fc36e30f10652023a3f2f94a253660487cbbf4fa0d66a141e7eb2', 0, 'APPROVED', '2026-10-09 12:27:11', '2026-10-09 12:37:11', '2026-10-09 12:27:16'),
(22, 1, '7c1198ad4d003d0e05c75373d3c3163600b117efc3a656b55f45b59d54e71524', 0, 'APPROVED', '2026-10-09 12:27:55', '2026-10-09 12:37:55', '2026-10-09 12:27:59'),
(23, 1, 'ecb06819f40aca899408028efe792a3ea6874772f6cdcb6fe48f604f210c32ba', 0, 'APPROVED', '2026-10-09 12:31:35', '2026-10-09 12:41:35', '2026-10-09 12:31:43'),
(24, 1, 'b7c994e8bfed3d6b98d1e99b1a4688d0ab6539ea48db176f1c6b15881687f5b2', 0, 'APPROVED', '2026-10-09 14:48:25', '2026-10-09 14:58:25', '2026-10-09 14:48:29'),
(25, 20, '00073cc65985afbaf5160842213e3ae9895fe1297e6ea66913f83570abb83bb8', 0, 'APPROVED', '2026-10-09 15:15:39', '2026-10-09 15:25:39', '2026-10-09 15:15:45'),
(26, 20, '3cfc4ae886aa758e999acc3f3da5ef439942e36910bbd4cf8104392251a210b6', 0, 'APPROVED', '2026-10-09 15:18:45', '2026-10-09 15:28:45', '2026-10-09 15:19:00'),
(27, 20, '23d0ac8fd6cf35e32c056cc20bbcb2a30d5265016942e9c6a8dd9f0a35d954d3', 0, 'APPROVED', '2026-10-09 15:20:28', '2026-10-09 15:30:28', '2026-10-09 15:20:37'),
(28, 20, '1a3cdfa3960756e2640997f1c1bc04b04cac675fc2d91e8785de8f081f7feadf', 0, 'APPROVED', '2026-10-09 15:35:02', '2026-10-09 15:45:02', '2026-10-09 15:35:18'),
(29, 1, '88ca99cdc39f5f4b7156ce714d9e629a3adb384dad78fa56a130c752f9008fe4', 0, 'APPROVED', '2026-10-09 16:01:04', '2026-10-09 16:11:04', '2026-10-09 16:01:42'),
(30, 20, 'c4d88b3430ed9502de2417d1f3c9ef505f945f2461df6dfb683b73020dff52cd', 0, 'APPROVED', '2026-10-09 16:10:12', '2026-10-09 16:20:12', '2026-10-09 16:10:19');

-- --------------------------------------------------------

--
-- Table structure for table `notification_dismissals`
--

CREATE TABLE `notification_dismissals` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `notification_key` varchar(120) NOT NULL,
  `dismissed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_reads`
--

CREATE TABLE `notification_reads` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `audit_log_id` bigint(20) UNSIGNED NOT NULL,
  `read_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_reads`
--

INSERT INTO `notification_reads` (`user_id`, `audit_log_id`, `read_at`) VALUES
(1, 6, '2026-09-03 08:00:02'),
(1, 7, '2026-09-03 08:00:48'),
(1, 8, '2026-09-03 12:42:19'),
(1, 9, '2026-09-03 13:28:58'),
(1, 10, '2026-09-03 13:28:58'),
(1, 11, '2026-09-03 13:28:58'),
(1, 12, '2026-09-03 13:33:51'),
(1, 15, '2026-09-04 09:22:41'),
(1, 29, '2026-09-04 13:43:55'),
(1, 189, '2026-09-18 15:21:51'),
(1, 193, '2026-09-21 09:39:57'),
(1, 194, '2026-09-21 13:04:18'),
(1, 317, '2026-09-25 12:35:07'),
(1, 318, '2026-09-25 12:35:07'),
(1, 339, '2026-10-01 09:38:09'),
(1, 389, '2026-10-01 16:30:05'),
(1, 392, '2026-10-01 16:30:05'),
(1, 409, '2026-10-02 10:38:05'),
(1, 422, '2026-10-02 10:51:19'),
(1, 424, '2026-10-02 10:51:19'),
(1, 426, '2026-10-02 10:51:19'),
(1, 450, '2026-10-05 13:41:09'),
(2, 6, '2026-09-03 14:58:48'),
(2, 7, '2026-09-03 14:58:48'),
(2, 9, '2026-09-03 14:58:48'),
(2, 10, '2026-09-03 14:58:48'),
(2, 11, '2026-09-03 14:58:48'),
(2, 12, '2026-09-03 14:58:48'),
(2, 18, '2026-09-04 11:29:53'),
(2, 22, '2026-09-04 12:11:08'),
(2, 30, '2026-09-04 13:42:44'),
(2, 307, '2026-09-25 12:33:17'),
(2, 339, '2026-10-01 09:51:34'),
(2, 440, '2026-10-06 15:41:21'),
(2, 443, '2026-10-05 12:49:04'),
(2, 479, '2026-10-06 15:41:23'),
(2, 482, '2026-10-06 15:41:25'),
(3, 187, '2026-09-18 15:21:25'),
(5, 6, '2026-09-03 08:01:06'),
(5, 7, '2026-09-03 08:01:06'),
(5, 8, '2026-09-03 14:23:55'),
(5, 9, '2026-09-03 14:23:55'),
(5, 10, '2026-09-03 14:23:55'),
(5, 11, '2026-09-03 14:23:55'),
(5, 12, '2026-09-03 14:23:55'),
(5, 13, '2026-09-04 09:16:42'),
(5, 16, '2026-09-04 09:23:01'),
(5, 17, '2026-09-04 11:29:12'),
(5, 20, '2026-09-04 12:10:11'),
(5, 21, '2026-09-04 12:10:17'),
(5, 23, '2026-09-04 12:12:47'),
(5, 25, '2026-09-04 12:49:04'),
(5, 26, '2026-09-04 12:50:11'),
(5, 28, '2026-09-04 13:33:08'),
(5, 31, '2026-09-04 14:01:00'),
(5, 478, '2026-10-06 15:38:01'),
(5, 480, '2026-10-06 15:41:08'),
(5, 481, '2026-10-06 15:41:09'),
(6, 192, '2026-09-21 13:03:23'),
(6, 195, '2026-09-21 13:04:36'),
(20, 340, '2026-10-01 09:27:24'),
(20, 350, '2026-10-01 10:07:27'),
(20, 362, '2026-10-01 14:50:32'),
(20, 394, '2026-10-02 08:30:17'),
(20, 441, '2026-10-02 15:37:05'),
(20, 449, '2026-10-06 16:44:24'),
(20, 624, '2026-10-07 10:24:54'),
(20, 653, '2026-10-07 11:16:47');

-- --------------------------------------------------------

--
-- Table structure for table `page_access`
--

CREATE TABLE `page_access` (
  `page_name` varchar(120) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `page_access`
--

INSERT INTO `page_access` (`page_name`, `role_name`, `is_allowed`) VALUES
('admin.php', 'ADMIN', 1),
('all_documents.php', 'ADMIN', 1),
('all_documents.php', 'CITY_ATTORNEY', 1),
('all_documents.php', 'DOCUMENT_VIEWER', 1),
('all_documents.php', 'INCOMING', 1),
('all_documents.php', 'LEGAL_STAFF', 1),
('all_documents.php', 'OUTGOING', 1),
('all_documents.php', 'RECORDS_OFFICER', 1),
('archive_documents.php', 'ADMIN', 1),
('archive_documents.php', 'CITY_ATTORNEY', 1),
('archive_documents.php', 'INCOMING', 1),
('archive_documents.php', 'LEGAL_STAFF', 1),
('archive_documents.php', 'OUTGOING', 1),
('archive_documents.php', 'RECORDS_OFFICER', 1),
('audit_trail.php', 'ADMIN', 1),
('dashboard.php', 'ADMIN', 1),
('dashboard.php', 'CITY_ATTORNEY', 1),
('dashboard.php', 'DOCUMENT_VIEWER', 1),
('dashboard.php', 'INCOMING', 1),
('dashboard.php', 'LEGAL_STAFF', 1),
('dashboard.php', 'OUTGOING', 1),
('dashboard.php', 'RECORDS_OFFICER', 1),
('department_management.php', 'ADMIN', 1),
('department_management.php', 'INCOMING', 1),
('document_management.php', 'ADMIN', 1),
('document_management.php', 'CITY_ATTORNEY', 1),
('document_management.php', 'INCOMING', 1),
('document_management.php', 'LEGAL_STAFF', 1),
('document_management.php', 'OUTGOING', 1),
('document_management.php', 'RECORDS_OFFICER', 1),
('document_routing.php', 'ADMIN', 1),
('document_routing.php', 'CITY_ATTORNEY', 1),
('document_routing.php', 'INCOMING', 1),
('document_routing.php', 'LEGAL_STAFF', 1),
('document_routing.php', 'OUTGOING', 1),
('document_routing.php', 'RECORDS_OFFICER', 1),
('document_tracking.php', 'ADMIN', 1),
('document_tracking.php', 'CITY_ATTORNEY', 1),
('document_tracking.php', 'INCOMING', 1),
('document_tracking.php', 'LEGAL_STAFF', 1),
('document_tracking.php', 'OUTGOING', 1),
('document_tracking.php', 'RECORDS_OFFICER', 1),
('document_type_management.php', 'ADMIN', 1),
('document_type_management.php', 'INCOMING', 1),
('pending_access_requests.php', 'ADMIN', 1),
('pending_access_requests.php', 'CITY_ATTORNEY', 1),
('pending_access_requests.php', 'DOCUMENT_VIEWER', 1),
('pending_access_requests.php', 'INCOMING', 1),
('pending_access_requests.php', 'LEGAL_STAFF', 1),
('pending_access_requests.php', 'OUTGOING', 1),
('pending_access_requests.php', 'RECORDS_OFFICER', 1),
('pending_documents.php', 'ADMIN', 1),
('pending_documents.php', 'CITY_ATTORNEY', 1),
('pending_documents.php', 'INCOMING', 1),
('pending_documents.php', 'LEGAL_STAFF', 1),
('pending_documents.php', 'OUTGOING', 1),
('pending_documents.php', 'RECORDS_OFFICER', 1),
('reports.php', 'ADMIN', 1),
('reports.php', 'CITY_ATTORNEY', 1),
('reports.php', 'INCOMING', 1),
('reports.php', 'LEGAL_STAFF', 1),
('reports.php', 'OUTGOING', 1),
('reports.php', 'RECORDS_OFFICER', 1),
('system_settings.php', 'ADMIN', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `selector` char(24) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`reset_id`, `user_id`, `selector`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 1, '1422dd19153ed5deda246898', '546dd0bb9f3b3a2d2c5e402f58e7177625e337b080c2263878a3e1f882fe40bc', '2026-08-17 11:30:22', '2026-08-17 11:00:54', '2026-08-17 11:00:22'),
(2, 5, '25dc9ed2ab1d7aa3904c7dbc', 'b878d8946e1383b02b35e5f394f4b432544c87bef53e565e5a0c6e5cdf0b58a8', '2026-08-17 11:32:29', '2026-08-17 11:02:51', '2026-08-17 11:02:29'),
(3, 1, 'f739e3208ff41f7e006e3062', '080d4de316bf91f17f581b381d7d4c4f15b45f88c7839ec7dc2608312445412d', '2026-08-17 13:51:40', '2026-08-17 13:21:51', '2026-08-17 13:21:40'),
(4, 5, '7469da92046b10a2cf51e3a9', '71a48d142818b4a6f7ccd87b9b98f8ea8a609d4f1fba56d71d3a4f2eeddb97d5', '2026-08-17 15:07:23', '2026-08-17 14:37:35', '2026-08-17 14:37:23'),
(5, 5, 'cecb0fd13e25c95a416d3377', 'a5952d566e6313a1aac54cd5cf81836727480997daa23fdc8671e5c8baa3514c', '2026-09-07 13:03:29', NULL, '2026-09-07 12:33:29');

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `token_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `selector` char(24) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `last_used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(10) UNSIGNED NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`, `is_active`, `created_at`) VALUES
(1, 'ADMIN', 'System administrator with full access', 1, '2026-08-14 00:49:43'),
(2, 'CITY_ATTORNEY', 'City Attorney / approving authority', 1, '2026-08-14 00:49:43'),
(3, 'LEGAL_STAFF', 'Legal staff for processing and drafting', 1, '2026-08-14 00:49:43'),
(4, 'RECORDS_OFFICER', 'Records officer for routing and archiving', 1, '2026-08-14 00:49:43'),
(5, 'DOCUMENT_VIEWER', 'View-only role for tracking and viewing documents', 1, '2026-08-17 07:15:39'),
(6, 'INCOMING', 'Incoming personnel for receiving, editing, and assigning documents', 1, '2026-09-25 07:39:42'),
(7, 'OUTGOING', 'Outgoing personnel authorized to send documents to other departments', 1, '2026-10-05 03:33:28');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` bigint(20) UNSIGNED NOT NULL,
  `office_name` varchar(255) NOT NULL DEFAULT 'City Legal Office',
  `logo_path` varchar(500) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `system_title` varchar(255) NOT NULL DEFAULT 'Document Tracking',
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `office_name`, `logo_path`, `address`, `system_title`, `updated_by`, `updated_at`) VALUES
(1, 'City Legal Office', 'uploads/settings/logo_bb020e658aa05228a99abf97fe41b456.png', 'Panabo City Hall', 'Document Tracking', 1, '2026-08-20 15:30:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employee_no` varchar(30) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `theme_preference` enum('light','dark') NOT NULL DEFAULT 'light',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `employee_no`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `profile_picture`, `role_id`, `department_id`, `theme_preference`, `is_active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'CLO-0001', 'admin.clo', 'admin@city.gov.ph', '$2y$10$76/QdzVkD/onYdq4ggpmDONdMlPj4RbjZsBqKB29NNtSVXKzF8Qve', 'Super', 'Admin', NULL, 1, 1, 'dark', 1, '2026-10-09 16:01:44', '2026-08-14 00:49:43', '2026-10-09 08:10:55'),
(2, 'CLO-0002', 'attorney.reyes', 'reyes@city.gov.ph', '$2y$10$Gcg9zqwJoR2rHlEpzNnEs.DAUJLc6MPQVLU4tl9.jgToj2ERJTBcC', 'Antonio', 'Reyes', NULL, 4, 1, 'dark', 1, '2026-10-09 14:03:00', '2026-08-14 00:49:43', '2026-10-09 06:03:14'),
(3, 'CLO-0003', 'staff.delacruz', 'delacruz@city.gov.ph', '$2y$10$POgJpW9tILpmNht4bHRiFOxvHfEC4A4NUYzRG3qF8dbiQbIBlumv.', 'Liza', 'Dela Cruz', NULL, 3, 1, 'light', 1, '2026-10-05 11:58:51', '2026-08-14 00:49:43', '2026-10-05 03:58:51'),
(4, 'CMO-0004', 'records.garcia', 'garcia@city.gov.ph', '$2y$10$z6i1kQ9vWn2D7cP8mVb8xO5WkUQ6pE8yH4xN1qA9sY7L2tR3bJ5Wu', 'Paolo', 'Garcia', NULL, 4, 1, 'light', 1, '2026-08-14 07:50:00', '2026-08-14 00:49:43', '2026-10-02 05:28:11'),
(5, 'HRMO-0005', 'hr.torres', 'torres@city.gov.ph', '$2y$10$vFiQUwPx2/Z3AtA5pP2ijOQY./t89A88dEEVvuDNgTIynKNsCc2TK', 'Evelyn', 'Torres', NULL, 7, 1, 'light', 1, '2026-10-06 15:22:56', '2026-08-14 00:49:43', '2026-10-06 07:22:56'),
(6, 'CLO-3952', 'busalla.kyle', 'busalla.kyle@city.gov.ph', '$2y$10$KYqyeZtAxeTIuy/Q4PfgsOGmf2w5IzKbN53Xh7AHzxkOGfb0AybbW', 'Kyle', 'Busalla', NULL, 6, 1, 'light', 1, '2026-09-25 11:38:50', '2026-08-14 01:03:03', '2026-10-05 04:57:07'),
(20, 'CLO-4444', 'new.clo', 'newclo@city.gov.ph', '$2y$10$fMUyfhRNRt2MYPjzjULLoOQc4WE4BuZCSfF2dJmnUlMfHuATbh3qW', 'New', 'Clo', NULL, 6, 1, 'dark', 1, '2026-10-09 16:10:20', '2026-09-25 04:29:37', '2026-10-09 08:10:20');

-- --------------------------------------------------------

--
-- Table structure for table `user_messages`
--

CREATE TABLE `user_messages` (
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `recipient_id` bigint(20) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_message_typing`
--

CREATE TABLE `user_message_typing` (
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `recipient_id` bigint(20) UNSIGNED NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`sender_id`,`recipient_id`),
  KEY `idx_user_message_typing_recipient` (`recipient_id`,`updated_at`),
  CONSTRAINT `fk_user_message_typing_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_user_message_typing_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `user_message_presence`
--

CREATE TABLE `user_message_presence` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `last_seen_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  KEY `idx_user_message_presence_last_seen` (`last_seen_at`),
  CONSTRAINT `fk_user_message_presence_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_messages`
--

INSERT INTO `user_messages` (`message_id`, `sender_id`, `recipient_id`, `message`, `created_at`, `read_at`) VALUES
(1, 1, 2, 'Testing message from UI', '2026-10-09 13:32:48', '2026-10-09 14:03:17'),
(2, 1, 20, 'aha man imong ge review', '2026-10-09 13:34:15', '2026-10-09 13:39:21'),
(3, 20, 1, 'dadas', '2026-10-09 14:40:58', '2026-10-09 15:24:07'),
(4, 1, 20, 'dasdasda', '2026-10-09 14:41:28', '2026-10-09 15:20:47'),
(5, 1, 20, 'dasd', '2026-10-09 14:43:30', '2026-10-09 15:20:47'),
(6, 1, 20, 'dadsad', '2026-10-09 14:45:40', '2026-10-09 14:48:49'),
(7, 1, 20, 'ddsadsad', '2026-10-09 14:50:17', '2026-10-09 14:50:32'),
(8, 20, 1, 'dadsadsa', '2026-10-09 14:50:34', '2026-10-09 15:24:07'),
(9, 1, 20, 'sdsadas', '2026-10-09 14:51:50', '2026-10-09 15:20:47'),
(10, 1, 20, 'dada', '2026-10-09 14:54:37', '2026-10-09 14:54:47'),
(11, 1, 20, 'dadsd', '2026-10-09 14:58:37', '2026-10-09 15:20:47'),
(12, 20, 1, 'ddsadsada', '2026-10-09 14:58:50', '2026-10-09 14:58:59'),
(13, 20, 1, 'dsdsdas', '2026-10-09 15:00:25', '2026-10-09 15:00:38'),
(14, 1, 20, 'dadad', '2026-10-09 15:00:41', '2026-10-09 15:02:19'),
(15, 20, 1, 'dsadsad', '2026-10-09 15:06:18', '2026-10-09 15:06:28'),
(16, 1, 20, 'dasds', '2026-10-09 15:06:32', '2026-10-09 15:06:35'),
(17, 1, 20, 'dsdad', '2026-10-09 15:08:26', '2026-10-09 15:20:47'),
(18, 20, 1, 'dasdsa', '2026-10-09 15:08:37', '2026-10-09 15:08:47'),
(19, 20, 1, 'dasdsad', '2026-10-09 15:23:06', '2026-10-09 15:23:13'),
(20, 1, 20, 'dadasds', '2026-10-09 15:23:20', '2026-10-09 15:23:29'),
(21, 20, 1, 'sddsdsadsdsa', '2026-10-09 15:34:12', '2026-10-09 15:34:30'),
(22, 1, 20, 'sfsdfsdfsdfgsd', '2026-10-09 15:34:44', '2026-10-09 15:34:49'),
(23, 20, 1, 'dsdas', '2026-10-09 15:37:56', '2026-10-09 15:38:02'),
(24, 1, 20, 'dasdas', '2026-10-09 15:56:14', '2026-10-09 15:56:28'),
(25, 1, 20, 'dsaddadsadsa', '2026-10-09 15:56:38', '2026-10-09 15:56:50'),
(26, 20, 1, 'dasdsad', '2026-10-09 15:56:47', '2026-10-09 15:58:00'),
(27, 20, 1, 'dsadadadad', '2026-10-09 15:58:25', '2026-10-09 15:58:37'),
(28, 1, 20, 'eqweqweq', '2026-10-09 16:04:31', '2026-10-09 16:06:25'),
(29, 20, 1, 'Hahshhd', '2026-10-09 16:06:31', '2026-10-09 16:10:44'),
(30, 1, 20, 'ddadadaddadad', '2026-10-09 16:11:43', '2026-10-09 16:11:58'),
(31, 1, 20, 'dsadsadad', '2026-10-09 16:11:55', '2026-10-09 16:11:58'),
(32, 20, 1, 'dadada', '2026-10-09 16:12:16', '2026-10-09 16:12:24'),
(33, 1, 20, 'daadad', '2026-10-09 16:14:22', '2026-10-09 16:14:33'),
(34, 1, 20, 'eqeqeq', '2026-10-09 16:16:11', '2026-10-09 16:17:47'),
(35, 20, 1, 'yes', '2026-10-09 16:16:22', '2026-10-09 16:17:24'),
(36, 1, 20, 'dsada', '2026-10-09 16:17:27', '2026-10-09 16:17:47'),
(37, 1, 20, 'dsada', '2026-10-09 16:17:28', '2026-10-09 16:17:47'),
(38, 1, 20, 'hey', '2026-10-09 16:20:13', '2026-10-09 16:24:38'),
(39, 20, 1, 'yes', '2026-10-09 16:20:23', '2026-10-09 16:22:00'),
(40, 1, 20, 'dada', '2026-10-09 16:24:35', '2026-10-09 16:24:38'),
(41, 20, 1, 'dada', '2026-10-09 16:29:07', '2026-10-09 16:29:15'),
(42, 1, 20, 'ddadsad', '2026-10-09 16:29:19', '2026-10-09 16:30:39'),
(43, 1, 20, 'dada', '2026-10-09 16:30:32', '2026-10-09 16:30:39'),
(44, 20, 1, 'dasdsad', '2026-10-09 16:30:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_security`
--

CREATE TABLE `user_security` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `security_answer_color_hash` varchar(255) DEFAULT NULL,
  `security_answer_food_hash` varchar(255) DEFAULT NULL,
  `security_answer_birthday_hash` varchar(255) DEFAULT NULL,
  `failed_reset_attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `reset_locked_until` datetime DEFAULT NULL,
  `reset_attempt_window_started` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_security`
--

INSERT INTO `user_security` (`user_id`, `security_answer_color_hash`, `security_answer_food_hash`, `security_answer_birthday_hash`, `failed_reset_attempts`, `reset_locked_until`, `reset_attempt_window_started`) VALUES
(1, '$2y$10$yA0MkYFu61ZONaVG8GBOg.a0Dq8kp4Is.6kzN4kT5YA45ZJQpgfoO', '$2y$10$LvJMpowTkK9P716EyQJrW.XeyeqoQholKwO1P11SpnY3EUmbJIJY.', '$2y$10$w4MnvQyuQgVHBJ7RU0i3duvEZ7MV0J4bB.JRV8S00Rt566jz05duK', 0, NULL, NULL),
(5, '$2y$10$NaJngG6O9HLwIM1d0ALPHu4eECfxSsbbG0uYU2qtlK9J/UwDDn/Y6', '$2y$10$5iJ5UNr0eHwYeEYHrQNrSOAHnFuM3Zu9tIQZH6bF1AxDWf2VgDAxi', '$2y$10$ttQHa8wVGg8d9D/sgc3ojuOVLJMLDoHHTqprfEVhk3iuAvdFco/gy', 5, '2026-09-07 12:49:19', '2026-09-07 12:33:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `active_user_sessions`
--
ALTER TABLE `active_user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_active_user_sessions_user` (`user_id`);

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `idx_attachments_document` (`document_id`),
  ADD KEY `idx_attachments_uploaded_by` (`uploaded_by_user_id`),
  ADD KEY `idx_attachments_uploaded_at` (`uploaded_at`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`audit_log_id`),
  ADD KEY `idx_audit_logs_user` (`user_id`),
  ADD KEY `idx_audit_logs_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_audit_logs_action` (`action`),
  ADD KEY `idx_audit_logs_created_at` (`created_at`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `uq_departments_code` (`department_code`),
  ADD UNIQUE KEY `uq_departments_name` (`department_name`);

--
-- Indexes for table `device_login_requests`
--
ALTER TABLE `device_login_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `idx_device_login_requests_user` (`user_id`),
  ADD KEY `idx_device_login_requests_expires_at` (`expires_at`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`document_id`),
  ADD UNIQUE KEY `uq_documents_tracking_no` (`tracking_no`),
  ADD KEY `fk_documents_origin_department` (`origin_department_id`),
  ADD KEY `fk_documents_assignee` (`current_assignee_user_id`),
  ADD KEY `idx_documents_status` (`current_status_id`),
  ADD KEY `idx_documents_type` (`document_type_id`),
  ADD KEY `idx_documents_current_department` (`current_department_id`),
  ADD KEY `idx_documents_created_by` (`created_by_user_id`),
  ADD KEY `idx_documents_date_received` (`date_received`),
  ADD KEY `idx_documents_date_due` (`date_due`);

--
-- Indexes for table `document_access_grants`
--
ALTER TABLE `document_access_grants`
  ADD PRIMARY KEY (`grant_id`),
  ADD UNIQUE KEY `uq_document_access_grant` (`document_id`,`user_id`),
  ADD KEY `idx_document_access_grants_user` (`user_id`),
  ADD KEY `fk_access_grants_granted_by` (`granted_by_user_id`);

--
-- Indexes for table `document_assignment_receipts`
--
ALTER TABLE `document_assignment_receipts`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `idx_assignment_receipts_user_pending` (`user_id`,`accepted_at`),
  ADD KEY `fk_assignment_receipts_assigner` (`assigned_by_user_id`);

--
-- Indexes for table `document_history`
--
ALTER TABLE `document_history`
  ADD PRIMARY KEY (`document_history_id`),
  ADD KEY `fk_document_history_changed_by` (`changed_by_user_id`),
  ADD KEY `idx_document_history_document` (`document_id`),
  ADD KEY `idx_document_history_status` (`document_status_id`),
  ADD KEY `idx_document_history_changed_at` (`changed_at`);

--
-- Indexes for table `document_routes`
--
ALTER TABLE `document_routes`
  ADD PRIMARY KEY (`document_route_id`),
  ADD KEY `fk_document_routes_from_department` (`from_department_id`),
  ADD KEY `fk_document_routes_routed_by` (`routed_by_user_id`),
  ADD KEY `fk_document_routes_routed_to` (`routed_to_user_id`),
  ADD KEY `idx_document_routes_document` (`document_id`),
  ADD KEY `idx_document_routes_to_department` (`to_department_id`),
  ADD KEY `idx_document_routes_status` (`route_status`),
  ADD KEY `idx_document_routes_routed_at` (`routed_at`);

--
-- Indexes for table `document_slip_fields`
--
ALTER TABLE `document_slip_fields`
  ADD PRIMARY KEY (`document_id`);

--
-- Indexes for table `document_status`
--
ALTER TABLE `document_status`
  ADD PRIMARY KEY (`document_status_id`),
  ADD UNIQUE KEY `uq_document_status_code` (`status_code`),
  ADD UNIQUE KEY `uq_document_status_name` (`status_name`);

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`document_type_id`),
  ADD UNIQUE KEY `uq_document_types_code` (`type_code`),
  ADD UNIQUE KEY `uq_document_types_name` (`type_name`);

--
-- Indexes for table `login_device_requests`
--
ALTER TABLE `login_device_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `request_token_hash` (`request_token_hash`),
  ADD KEY `idx_login_device_requests_user_status` (`user_id`,`status`),
  ADD KEY `idx_login_device_requests_expires_at` (`expires_at`);

--
-- Indexes for table `notification_dismissals`
--
ALTER TABLE `notification_dismissals`
  ADD PRIMARY KEY (`user_id`,`notification_key`);

--
-- Indexes for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD PRIMARY KEY (`user_id`,`audit_log_id`),
  ADD KEY `idx_notification_reads_audit_log` (`audit_log_id`);

--
-- Indexes for table `page_access`
--
ALTER TABLE `page_access`
  ADD PRIMARY KEY (`page_name`,`role_name`),
  ADD KEY `idx_page_access_page` (`page_name`),
  ADD KEY `idx_page_access_role` (`role_name`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD UNIQUE KEY `uq_password_resets_selector` (`selector`),
  ADD KEY `idx_password_resets_user` (`user_id`),
  ADD KEY `idx_password_resets_expires_at` (`expires_at`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `uq_remember_tokens_selector` (`selector`),
  ADD KEY `idx_remember_tokens_user` (`user_id`),
  ADD KEY `idx_remember_tokens_expires_at` (`expires_at`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `uq_roles_role_name` (`role_name`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_employee_no` (`employee_no`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role_id`),
  ADD KEY `idx_users_department` (`department_id`),
  ADD KEY `idx_users_name` (`last_name`,`first_name`);

--
-- Indexes for table `user_messages`
--
ALTER TABLE `user_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_user_messages_sender` (`sender_id`),
  ADD KEY `idx_user_messages_recipient` (`recipient_id`),
  ADD KEY `idx_user_messages_created_at` (`created_at`);

--
-- Indexes for table `user_security`
--
ALTER TABLE `user_security`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `active_user_sessions`
--
ALTER TABLE `active_user_sessions`
  MODIFY `session_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `attachment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `audit_log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=905;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `device_login_requests`
--
ALTER TABLE `device_login_requests`
  MODIFY `request_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `document_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `document_access_grants`
--
ALTER TABLE `document_access_grants`
  MODIFY `grant_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `document_history`
--
ALTER TABLE `document_history`
  MODIFY `document_history_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `document_routes`
--
ALTER TABLE `document_routes`
  MODIFY `document_route_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `document_status`
--
ALTER TABLE `document_status`
  MODIFY `document_status_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `document_type_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `login_device_requests`
--
ALTER TABLE `login_device_requests`
  MODIFY `request_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `token_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `user_messages`
--
ALTER TABLE `user_messages`
  MODIFY `message_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `active_user_sessions`
--
ALTER TABLE `active_user_sessions`
  ADD CONSTRAINT `fk_active_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `fk_attachments_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_attachments_uploaded_by` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `device_login_requests`
--
ALTER TABLE `device_login_requests`
  ADD CONSTRAINT `fk_device_login_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `fk_documents_assignee` FOREIGN KEY (`current_assignee_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_documents_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_documents_current_department` FOREIGN KEY (`current_department_id`) REFERENCES `departments` (`department_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_documents_origin_department` FOREIGN KEY (`origin_department_id`) REFERENCES `departments` (`department_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_documents_status` FOREIGN KEY (`current_status_id`) REFERENCES `document_status` (`document_status_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_documents_type` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`document_type_id`) ON UPDATE CASCADE;

--
-- Constraints for table `document_access_grants`
--
ALTER TABLE `document_access_grants`
  ADD CONSTRAINT `fk_access_grants_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_access_grants_granted_by` FOREIGN KEY (`granted_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_access_grants_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `document_assignment_receipts`
--
ALTER TABLE `document_assignment_receipts`
  ADD CONSTRAINT `fk_assignment_receipts_assigner` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assignment_receipts_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assignment_receipts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `document_history`
--
ALTER TABLE `document_history`
  ADD CONSTRAINT `fk_document_history_changed_by` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_document_history_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_document_history_status` FOREIGN KEY (`document_status_id`) REFERENCES `document_status` (`document_status_id`) ON UPDATE CASCADE;

--
-- Constraints for table `document_routes`
--
ALTER TABLE `document_routes`
  ADD CONSTRAINT `fk_document_routes_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_document_routes_from_department` FOREIGN KEY (`from_department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_document_routes_routed_by` FOREIGN KEY (`routed_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_document_routes_routed_to` FOREIGN KEY (`routed_to_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_document_routes_to_department` FOREIGN KEY (`to_department_id`) REFERENCES `departments` (`department_id`) ON UPDATE CASCADE;

--
-- Constraints for table `login_device_requests`
--
ALTER TABLE `login_device_requests`
  ADD CONSTRAINT `fk_login_device_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notification_dismissals`
--
ALTER TABLE `notification_dismissals`
  ADD CONSTRAINT `fk_notification_dismissals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD CONSTRAINT `fk_notification_reads_audit_log` FOREIGN KEY (`audit_log_id`) REFERENCES `audit_logs` (`audit_log_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notification_reads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `fk_remember_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON UPDATE CASCADE;

--
-- Constraints for table `user_messages`
--
ALTER TABLE `user_messages`
  ADD CONSTRAINT `fk_user_messages_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_message_deletions`
--
CREATE TABLE `user_message_deletions` (
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `deleted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`,`user_id`),
  KEY `idx_user_message_deletions_user` (`user_id`,`deleted_at`),
  CONSTRAINT `fk_user_message_deletions_message` FOREIGN KEY (`message_id`) REFERENCES `user_messages` (`message_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_message_deletions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Constraints for table `user_security`
--
ALTER TABLE `user_security`
  ADD CONSTRAINT `fk_user_security_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
