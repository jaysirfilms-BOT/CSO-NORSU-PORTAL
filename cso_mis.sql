-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: Nov 08, 2026 at 11:13 AM
-- Server version: 11.4.13-MariaDB-ubu2404
-- PHP Version: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cso_mis`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` mediumtext NOT NULL,
  `deadline` datetime DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `app_secrets`
--

CREATE TABLE `app_secrets` (
  `name` varchar(50) NOT NULL,
  `value` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `app_secrets`
--

INSERT INTO `app_secrets` (`name`, `value`) VALUES
('otp_key', 'c0a307be4ef10b921f9d1bffbcac7818cba9cc66a4f43704d52eb6ed1170e0f9');

-- --------------------------------------------------------

--
-- Table structure for table `app_state`
--

CREATE TABLE `app_state` (
  `k` varchar(50) NOT NULL,
  `v` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `app_state`
--

INSERT INTO `app_state` (`k`, `v`) VALUES
('reminders_ran', '1794134526'),
('require_member_approval', '1');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('present','absent') NOT NULL DEFAULT 'present',
  `recorded_by` int(11) NOT NULL,
  `checked_in_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_out` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `event_id`, `user_id`, `status`, `recorded_by`, `checked_in_at`, `time_out`) VALUES
(10, 8, 8, 'present', 8, '2026-11-06 09:21:19', NULL),
(12, 7, 13, 'present', 8, '2026-11-06 09:25:19', NULL),
(15, 7, 2, 'present', 8, '2026-11-06 11:07:35', '2026-11-06 11:08:08'),
(16, 5, 2, 'present', 8, '2026-11-06 11:09:57', '2026-11-06 11:11:33'),
(20, 6, 2, 'present', 8, '2026-11-06 11:32:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_reminders`
--

CREATE TABLE `attendance_reminders` (
  `reminder_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `verify_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code_hash` char(64) NOT NULL,
  `requested_ip` varchar(45) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `event_end_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `event_end_time` time DEFAULT NULL,
  `venue` varchar(150) NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `facebook_url` varchar(500) DEFAULT NULL,
  `fb_image` varchar(255) DEFAULT NULL,
  `fb_description` text DEFAULT NULL,
  `qr_token` varchar(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `max_attendees` int(11) DEFAULT NULL,
  `committee` varchar(100) DEFAULT NULL,
  `reminder_sent` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `title`, `description`, `event_date`, `event_end_date`, `event_time`, `event_end_time`, `venue`, `cover_image`, `facebook_url`, `fb_image`, `fb_description`, `qr_token`, `created_by`, `created_at`, `max_attendees`, `committee`, `reminder_sent`) VALUES
(5, '𝐒𝐭𝐫𝐨𝐧𝐠𝐞𝐫 𝐭𝐨𝐠𝐞𝐭𝐡𝐞𝐫 𝐟𝐨𝐫 𝐬𝐨𝐥𝐢𝐝𝐚𝐫𝐢𝐭𝐲, 𝐮𝐧𝐢𝐭𝐲, 𝐚𝐧𝐝 𝐩𝐮𝐫𝐩𝐨𝐬𝐞𝐟𝐮𝐥 𝐥𝐞𝐚𝐝𝐞𝐫𝐬𝐡𝐢𝐩!', 'Representing the komsays, the 𝗖𝗦𝗢-𝗡𝗢𝗥𝗦𝗨 proudly took part in the Globe Telecom Student Leaders’ Engagement held on September 12, 2026.\r\n𝗧𝗵𝗶𝘀 𝗶𝗻𝗰𝗿𝗲𝗱𝗶𝗯𝗹𝗲 𝗲𝘅𝗽𝗲𝗿𝗶𝗲𝗻𝗰𝗲 𝗴𝗮𝘃𝗲 𝗼𝘂𝗿 𝘀𝘁𝘂𝗱𝗲𝗻𝘁 𝗹𝗲𝗮𝗱𝗲𝗿𝘀 𝘁𝗵𝗲 𝗼𝗽𝗽𝗼𝗿𝘁𝘂𝗻𝗶𝘁𝘆 𝘁𝗼 𝗰𝗼𝗻𝗻𝗲𝗰𝘁 𝗮𝗻𝗱 𝗻𝗲𝘁𝘄𝗼𝗿𝗸 𝘄𝗶𝘁𝗵 𝘁𝗵𝗲 𝗖𝗘𝗢 𝗼𝗳 𝗚𝗹𝗼𝗯𝗲 𝗧𝗲𝗹𝗲𝗰𝗼𝗺 𝗮𝗻𝗱 𝘁𝗵𝗲 𝗯𝗿𝗶𝗹𝗹𝗶𝗮𝗻𝘁 𝗺𝗶𝗻𝗱𝘀 𝗯𝗲𝗵𝗶𝗻𝗱 𝘁𝗵𝗲 𝗻𝗲𝘁𝘄𝗼𝗿𝗸, 𝘀𝗵𝗮𝗿𝗲 𝗮𝗻𝗱 𝗶𝗻𝗰𝘂𝗹𝗰𝗮𝘁𝗲 𝗳𝗿𝗲𝘀𝗵 𝗽𝗲𝗿𝘀𝗽𝗲𝗰𝘁𝗶𝘃𝗲𝘀 𝗼𝗻 𝘄𝗵𝗮𝘁 𝗶𝘁 𝘁𝗿𝘂𝗹𝘆 𝗺𝗲𝗮𝗻𝘀 𝘁𝗼 𝗯𝗲 𝗮 𝘃𝗶𝘀𝗶𝗼𝗻𝗮𝗿𝘆, 𝗮𝗰𝘁𝗶𝗼𝗻-𝗱𝗿𝗶𝘃𝗲𝗻 𝗹𝗲𝗮𝗱𝗲𝗿, 𝗮𝗻𝗱 𝗲𝗻𝗴𝗮𝗴𝗲 𝗶𝗻 𝗶𝗻𝘀𝗽𝗶𝗿𝗶𝗻𝗴 𝗱𝗶𝘀𝗰𝘂𝘀𝘀𝗶𝗼𝗻𝘀 𝗼𝗻 𝗵𝗼𝘄 𝘁𝗲𝗰𝗵𝗻𝗼𝗹𝗼𝗴𝘆 𝗮𝗻𝗱 𝗹𝗲𝗮𝗱𝗲𝗿𝘀𝗵𝗶𝗽 𝗶𝗻𝘁𝗲𝗿𝘀𝗲𝗰𝘁 𝘁𝗼 𝗯𝗿𝗶𝗻𝗴 𝗽𝗲𝗼𝗽𝗹𝗲 𝘁𝗼𝗴𝗲𝘁𝗵𝗲𝗿.\r\nWe are proud to see our student leaders continuously stepping up, learning from the best, and bringing back valuable insights to our community.', NULL, NULL, NULL, NULL, '', NULL, 'https://www.facebook.com/share/p/1FRtoBakma/', '/assets/uploads/events/fb_215aebfbacab30e9d44d.jpg', '𝐒𝐭𝐫𝐨𝐧𝐠𝐞𝐫 𝐭𝐨𝐠𝐞𝐭𝐡𝐞𝐫 𝐟𝐨𝐫 𝐬𝐨𝐥𝐢𝐝𝐚𝐫𝐢𝐭𝐲, 𝐮𝐧𝐢𝐭𝐲, 𝐚𝐧𝐝 𝐩𝐮𝐫𝐩𝐨𝐬𝐞𝐟𝐮𝐥 𝐥𝐞𝐚𝐝𝐞𝐫𝐬𝐡𝐢𝐩!🤝✨\n\nRepresenting the komsays, the 𝗖𝗦𝗢-𝗡𝗢𝗥𝗦𝗨 proudly took part in the Globe Telecom Student Leaders’ Engagement held on September 12,...', '015e211cbed209cbe6260799ec47a4d8', 12, '2026-11-06 06:54:22', NULL, NULL, 0),
(6, 'NORSU WELCOMES MISS EARTH PHILIPPINES 2026!', 'The CSO-NORSU proudly took part in welcoming Miss Earth Philippines 2026, Rina Andrea Delos Santos, to our university on October 15, 2026. \r\n𝗧𝗵𝗶𝘀 𝗺𝗲𝗮𝗻𝗶𝗻𝗴𝗳𝘂𝗹 𝗼𝗰𝗰𝗮𝘀𝗶𝗼𝗻 𝗿𝗲𝗺𝗶𝗻𝗱𝗲𝗱 𝘂𝘀 𝘁𝗵𝗮𝘁 𝗯𝗲𝗮𝘂𝘁𝘆 𝗴𝗼𝗲𝘀 𝗯𝗲𝘆𝗼𝗻𝗱 𝗮 𝗰𝗿𝗼𝘄𝗻. Through her advocacy for environmental conservation and sustainable tourism, she inspires us to take part in protecting our planet, preserving our natural resources, and supporting our local communities. As student leaders, we are reminded that even small actions can create a meaningful impact when we work together toward a greener and more sustainable future.', NULL, NULL, NULL, NULL, '', NULL, 'https://www.facebook.com/share/p/1A9xPm5985/', '/assets/uploads/events/fb_f002c7ab505434a0f88f.jpg', 'NORSU WELCOMES MISS EARTH PHILIPPINES 2026! 👑💚\n\nThe CSO-NORSU proudly took part in welcoming Miss Earth Philippines 2026, Rina Andrea Delos Santos, to our university on October 15, 2026. ✨\n\n𝗧𝗵𝗶𝘀...', '76b44fedb34420630c18ae791e87cd01', 12, '2026-11-06 06:56:32', NULL, NULL, 0),
(7, 'A remarkably productive morning at the CAS Building!', 'Today, CSO-NORSU eagerly stepped up to take charge of our designated area for the college-wide CAS DAGYAW Clean-Up Drive, and we couldn’t be prouder of what we accomplished together.\r\nFrom the early morning call time to sweeping the corridors, ensuring proper waste segregation, and clearing out clutter, the transformation is incredible. It’s always inspiring to see our community come together to ensure our facilities remain a clean, welcoming space for everyone.\r\nA massive thank you to every single person who lent a hand, grabbed a broom, and showed up for our campus today. Your dedication proves that collective action makes a lasting difference in our college environment. Let\'s keep this momentum going!', NULL, NULL, NULL, NULL, '', NULL, 'https://www.facebook.com/share/p/1KuH61uCtv/', '/assets/uploads/events/fb_09753cfaca4d43a5535b.jpg', 'A remarkably productive morning at the CAS Building! 🧹✨\n\nToday, CSO-NORSU eagerly stepped up to take charge of our designated area for the college-wide CAS DAGYAW Clean-Up Drive, and we couldn’t be...', 'e28f3fbe77a9d8395bb83a107382818a', 12, '2026-11-06 06:58:10', NULL, NULL, 0),
(8, 'Leading the charge for truth and civic action!', 'The Computer Science Organization - NORSU proudly represented the Computer Science at the Team Pinas GenZennial Tour: We Decide the Future held at the NORSU Main Campus!\r\nFrom interactive myth-busting sessions to vital discussions on national sovereignty and digital literacy, our officers joined fellow NORSUnians in taking an active stance on youth civic engagement and combating online disinformation.\r\nAs student leaders in technology, we recognize that staying informed and advocating for media literacy are key to shaping a better, more accountable society.\r\nTogether, we don\'t just prepare for the future—we decide it!', NULL, NULL, NULL, NULL, 'https://www.facebook.com/share/p/1A8e28dP32/', NULL, NULL, NULL, NULL, 'f00bc37c6ac54ed3e2312fc3efecb419', 12, '2026-11-06 06:59:41', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `event_photos`
--

CREATE TABLE `event_photos` (
  `photo_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `caption` varchar(200) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `registration_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `waitlisted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` mediumtext NOT NULL,
  `rating` tinyint(4) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `user_id`, `subject`, `message`, `rating`, `created_at`) VALUES
(1, 2, 'Needs improvement i like the UI/UX but still needs more love', 'Nice idea and more improvements', 3, '2026-10-12 15:11:59');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `throttle_key` char(40) NOT NULL,
  `fails` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`throttle_key`, `fails`, `locked_until`) VALUES
('0e21969967f7fd2ad39024ad1122a4a873495104', 1, NULL),
('19b680a1431646b4699efa6335f65679c6c1a958', 5, '2026-11-05 02:32:58'),
('638da5e955adf8166bc3b80d19f8cff4603218b9', 1, NULL),
('74eba632b5ce8e90e3512b50e399027fa7a7ced3', 2, NULL),
('93e15ced26a4199273914847aa8a74cb4d97ba88', 1, NULL),
('971869e16aab6bf785ae9ef91d3e90c5bfdd27dc', 2, NULL),
('a8c08a4515b5491563f781c40fddc4fda3abad88', 1, NULL),
('b484cc073a436abdbe455fcdf22894a13740969e', 5, NULL),
('bec18276cd7b67dec2c2e1b209ec9204a41d087e', 4, NULL),
('d6b4f18c03a65bbfe5247e6fff59e69182083cc2', 4, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `map_buildings`
--

CREATE TABLE `map_buildings` (
  `building_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `category` enum('building','facility','office','restroom','gate') NOT NULL DEFAULT 'building',
  `floor_mode` enum('floors','rooms_only','none') NOT NULL DEFAULT 'floors',
  `pos_x` decimal(5,2) NOT NULL DEFAULT 50.00,
  `pos_y` decimal(5,2) NOT NULL DEFAULT 50.00,
  `status` enum('open','limited','maintenance','closed') NOT NULL DEFAULT 'open',
  `status_note` varchar(160) DEFAULT NULL,
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL,
  `date_opened` date DEFAULT NULL,
  `date_closed` date DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `map_buildings`
--

INSERT INTO `map_buildings` (`building_id`, `name`, `code`, `category`, `floor_mode`, `pos_x`, `pos_y`, `status`, `status_note`, `open_time`, `close_time`, `date_opened`, `date_closed`, `updated_by`, `updated_at`) VALUES
(1, 'College of Teacher Education', NULL, 'building', 'floors', 26.92, 8.93, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(2, 'College of Law', NULL, 'building', 'floors', 13.98, 19.97, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(3, 'Student Lounge', NULL, 'facility', 'none', 17.86, 18.92, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(4, 'CTED Restroom', NULL, 'restroom', 'none', 34.42, 25.75, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(5, 'Care Center', NULL, 'office', 'rooms_only', 40.89, 30.48, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(6, 'Student Affairs Office', NULL, 'office', 'rooms_only', 42.44, 34.68, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(7, 'Science and Technology Building', NULL, 'building', 'floors', 15.53, 48.87, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(8, 'CIT Open Court', NULL, 'facility', 'none', 37.01, 43.62, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(9, 'CIT Canteen', NULL, 'facility', 'none', 37.53, 50.45, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(10, 'Supply Room', NULL, '', 'rooms_only', 44.00, 44.67, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(11, 'College of Industrial Technology', NULL, 'building', 'floors', 33.13, 56.75, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(12, 'CIT Building', NULL, 'building', 'floors', 31.06, 66.74, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(13, 'Clinic', NULL, 'office', 'floors', 37.27, 66.74, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(14, 'University Bus Shed', NULL, 'facility', 'none', 43.48, 58.33, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(15, 'Automotive Building', NULL, 'building', 'floors', 52.28, 55.70, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(16, 'Physical Education Department', NULL, 'office', 'floors', 64.70, 55.18, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(17, 'College of Nursing, Pharmacy and Allied Health Sciences', NULL, 'building', 'floors', 77.12, 54.65, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(18, 'AVR', NULL, '', 'rooms_only', 86.44, 52.55, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(19, 'University Registrar Cashier', NULL, 'office', 'none', 20.70, 73.57, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(20, 'Research and Extension Office', NULL, 'office', 'none', 38.30, 75.67, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(21, 'Open Court', NULL, 'facility', 'floors', 47.62, 73.57, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(22, 'NORSU Cultural Center (Gym)', NULL, 'facility', 'floors', 60.04, 72.52, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(23, 'Mathematics and Social Science Department', NULL, 'office', 'rooms_only', 54.35, 72.52, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(24, 'Amphi Theater', NULL, 'facility', 'none', 73.50, 67.79, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(25, 'Interfaith', NULL, 'facility', 'none', 75.57, 77.25, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(26, 'College of Business Administration', NULL, 'building', 'floors', 85.92, 63.06, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(27, 'CAS Student Lounge', NULL, 'facility', 'none', 46.58, 79.87, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(28, 'Admin Building', NULL, 'office', 'floors', 59.01, 84.08, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(29, 'Hospitality Management and Tourism Building', NULL, 'building', 'floors', 73.50, 81.98, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(30, 'College of Arts and Sciences', NULL, 'building', 'floors', 41.41, 85.65, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(31, 'Tech Room', NULL, '', 'rooms_only', 66.77, 76.20, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(32, 'Gate 2', NULL, 'gate', 'none', 13.98, 63.06, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(33, 'Gate 3', NULL, 'gate', 'none', 15.01, 5.78, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(34, 'Gate 4', NULL, 'gate', 'none', 81.78, 87.76, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08'),
(35, 'Main Gate', NULL, 'gate', 'none', 59.52, 89.33, 'open', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 05:54:08');

-- --------------------------------------------------------

--
-- Table structure for table `map_floors`
--

CREATE TABLE `map_floors` (
  `floor_id` int(11) NOT NULL,
  `building_id` int(11) NOT NULL,
  `label` varchar(40) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `map_floors`
--

INSERT INTO `map_floors` (`floor_id`, `building_id`, `label`, `sort_order`) VALUES
(1, 5, 'Rooms', 0),
(2, 6, 'Rooms', 0),
(3, 10, 'Rooms', 0),
(4, 18, 'Rooms', 0),
(5, 23, 'Rooms', 0),
(6, 31, 'Rooms', 0);

-- --------------------------------------------------------

--
-- Table structure for table `map_locations`
--

CREATE TABLE `map_locations` (
  `location_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` enum('building','office','room','facility','restroom','gate') NOT NULL DEFAULT 'building',
  `building` varchar(150) DEFAULT NULL,
  `floor` varchar(40) DEFAULT NULL,
  `room` varchar(60) DEFAULT NULL,
  `status` enum('open','limited','maintenance','closed') NOT NULL DEFAULT 'open',
  `status_note` varchar(160) DEFAULT NULL,
  `pos_x` decimal(5,2) NOT NULL DEFAULT 50.00,
  `pos_y` decimal(5,2) NOT NULL DEFAULT 50.00,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `map_reports`
--

CREATE TABLE `map_reports` (
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `building_id` int(11) DEFAULT NULL,
  `place_text` varchar(150) DEFAULT NULL,
  `message` varchar(500) NOT NULL,
  `status` enum('open','resolved') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `map_rooms`
--

CREATE TABLE `map_rooms` (
  `room_id` int(11) NOT NULL,
  `floor_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `room_type` enum('room','office','lab','facility','restroom') NOT NULL DEFAULT 'room',
  `status` enum('open','limited','maintenance','closed') NOT NULL DEFAULT 'open',
  `status_note` varchar(160) DEFAULT NULL,
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL,
  `date_opened` date DEFAULT NULL,
  `date_closed` date DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `org_info`
--

CREATE TABLE `org_info` (
  `id` int(11) NOT NULL DEFAULT 1,
  `title` varchar(150) NOT NULL DEFAULT 'About CSO',
  `content` mediumtext NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `org_info`
--

INSERT INTO `org_info` (`id`, `title`, `content`, `image`, `updated_by`, `updated_at`) VALUES
(1, 'About CSO', 'The Computer Science Organization (CSO) brings together BSCS students for academic growth, technical skill-building, and community. This system helps us manage membership, events, announcements, and attendance in one place.', NULL, NULL, '2026-10-12 14:06:53');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code_hash` char(64) NOT NULL,
  `channel` varchar(20) NOT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `requested_ip` varchar(45) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`reset_id`, `user_id`, `code_hash`, `channel`, `issued_by`, `requested_ip`, `attempts`, `created_at`, `expires_at`, `verified_at`, `used_at`) VALUES
(1, 2, '568f7d7338066c5af9be0c69edd99dbcf1b56850471361ff54786f3622f491e7', 'officer', 12, '143.44.169.106', 0, '2026-11-04 23:08:27', '2026-11-04 23:09:27', NULL, '2026-11-05 01:49:46'),
(2, 2, '1c8ad65a030944c91fff51f9e871588fbd5edd7c87266e0a59bb62de2e1c572b', 'officer', 12, '143.44.169.106', 1, '2026-11-05 01:49:46', '2026-11-05 01:50:46', '2026-11-05 01:50:06', '2026-11-05 01:56:46');

-- --------------------------------------------------------

--
-- Table structure for table `post_images`
--

CREATE TABLE `post_images` (
  `image_id` int(11) NOT NULL,
  `post_type` enum('event','announcement') NOT NULL,
  `post_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_images`
--

INSERT INTO `post_images` (`image_id`, `post_type`, `post_id`, `image_path`, `sort_order`, `created_at`) VALUES
(1, 'event', 1, '/assets/uploads/events/3451b563d5e8a9b432cecf89.jpg', 0, '2026-10-12 14:49:44'),
(4, 'event', 3, '/assets/uploads/events/66976234f9d4fdc03f59dc4a.jpg', 1, '2026-10-12 14:58:35'),
(5, 'announcement', 1, '/assets/uploads/announcements/76b41998784d0b5fbc1502f0.jpg', 0, '2026-10-12 15:03:08');

-- --------------------------------------------------------

--
-- Table structure for table `register_attempts`
--

CREATE TABLE `register_attempts` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `register_attempts`
--

INSERT INTO `register_attempts` (`id`, `ip`, `created_at`) VALUES
(6, '172.18.0.1', '2026-11-07 13:00:38'),
(7, '172.18.0.1', '2026-11-07 16:20:25');

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `selector` char(24) NOT NULL,
  `validator_hash` char(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_version` int(11) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `remember_tokens`
--

INSERT INTO `remember_tokens` (`selector`, `validator_hash`, `user_id`, `token_version`, `expires_at`, `created_at`) VALUES
('fdf0ce7e35fcc13c0531e7e9', '52280d4fe9bc6e597f43d9deaa130f2d1da7163ff87ac4f788bfadacb64a2c83', 8, 0, '2027-12-11 19:28:54', '2026-11-06 11:28:54');

-- --------------------------------------------------------

--
-- Table structure for table `schema_meta`
--

CREATE TABLE `schema_meta` (
  `id` int(11) NOT NULL DEFAULT 1,
  `version` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schema_meta`
--

INSERT INTO `schema_meta` (`id`, `version`) VALUES
(1, 30);

-- --------------------------------------------------------

--
-- Table structure for table `student_roster`
--

CREATE TABLE `student_roster` (
  `student_id` varchar(30) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `student_id` varchar(30) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `course` varchar(80) NOT NULL,
  `year_level` varchar(20) NOT NULL,
  `role` enum('admin','moderator','officer','member') NOT NULL DEFAULT 'member',
  `committee_role` varchar(500) DEFAULT NULL,
  `user_type` enum('student','faculty') NOT NULL DEFAULT 'student',
  `faculty_position` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `profile_photo` varchar(255) DEFAULT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `status_note` varchar(80) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notif_seen_at` datetime DEFAULT NULL,
  `notif_cleared_at` datetime DEFAULT NULL,
  `approval_pending` tinyint(1) NOT NULL DEFAULT 0,
  `email_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `verified_as` enum('none','member','officer','faculty','developer') NOT NULL DEFAULT 'none',
  `token_version` int(11) NOT NULL DEFAULT 0,
  `show_in_members` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `id_status` enum('none','pending','approved','rejected') NOT NULL DEFAULT 'none',
  `id_front` varchar(255) DEFAULT NULL,
  `id_back` varchar(255) DEFAULT NULL,
  `id_submitted_at` datetime DEFAULT NULL,
  `id_reviewed_at` datetime DEFAULT NULL,
  `id_reviewed_by` int(11) DEFAULT NULL,
  `id_reject_reason` varchar(255) DEFAULT NULL,
  `card_token` char(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `student_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `password`, `course`, `year_level`, `role`, `committee_role`, `user_type`, `faculty_position`, `status`, `profile_photo`, `cover_photo`, `status_note`, `created_at`, `notif_seen_at`, `notif_cleared_at`, `approval_pending`, `email_alerts`, `verified_as`, `token_version`, `show_in_members`, `email_verified`, `id_status`, `id_front`, `id_back`, `id_submitted_at`, `id_reviewed_at`, `id_reviewed_by`, `id_reject_reason`, `card_token`) VALUES
(2, '202403580', 'Jay Moen', 'Matias', 'Redula', '', 'moenjaymatias@gmail.com', '$2y$12$Kqdn4nLacLExjIRBPUlT8exTOaSFqh7Ly9QTIXpyW21tHYsmhZ9AK', 'BSCS', '3rd Year', 'officer', 'Documentation Committee', 'student', NULL, 'active', '/assets/uploads/profiles/6212d7ddca25405676e7f96f.jpg', '/assets/uploads/covers/bee73d7941132f8beb050080.jpg', NULL, '2026-10-12 14:36:11', '2026-11-05 14:19:54', NULL, 0, 1, 'developer', 1, 1, 1, 'approved', NULL, NULL, NULL, NULL, NULL, NULL, 'f43b208b3744bdab3d53502a9b8e5018'),
(3, '202403550', 'Cristianery Khim', 'Boca', 'Guzman', '', 'gzcristian031706@gmail.com', '$2y$12$f5DEQ5VX8i4b2o4lyuRDWuVVzaTmkDhE0ZGfvc2I8C4Y0uiDzGKLC', 'BSCS', '3rd Year', 'officer', 'Secretary, Secretariat Head', 'student', NULL, 'active', '/assets/uploads/profiles/b671121be8c944a93220a34c.jpg', NULL, NULL, '2026-10-12 15:38:36', '2026-10-12 08:39:36', NULL, 0, 1, 'officer', 0, 1, 1, 'approved', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, '202403603', 'JM Vivien Grace', 'Mapula', 'Viola', '', 'jmviviengraceviola@gmail.com', '$2y$12$LR9.xo6AMnvkoWxDYChtCOWVrtF8IRT4fLzxolgF9H/g0fqF2atjm', 'BSCS', '3rd Year', 'officer', 'Vice President', 'student', NULL, 'active', '/assets/uploads/profiles/b9b6a8aa1e7e9d265c2625f3.jpg', NULL, 'i\'m scared for my grades', '2026-10-12 22:20:47', '2026-10-12 16:16:33', NULL, 0, 1, 'officer', 0, 1, 1, 'approved', NULL, NULL, NULL, NULL, NULL, NULL, '86eb41e367c51509f84cdb65b570a486'),
(5, '202403594', 'Altheo Miguel', 'Sayson', 'Ticon', '', 'altheoticon@gmail.com', '$2y$12$DBI8r.1yIbBerBZk28TO4eTv3CukeNuwGtEz/IiC.lcBCTwQb4biu', 'BSCS', '3rd Year', 'officer', 'Documentation Head', 'student', NULL, 'active', '/assets/uploads/profiles/75712c72243794348edb5e20.jpg', '/assets/uploads/covers/e864c4a516cd49837a15d568.jpg', NULL, '2026-10-14 07:21:37', '2026-11-05 06:51:55', NULL, 0, 1, 'officer', 0, 1, 1, 'approved', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, '202403538', 'Eddifranz', 'Alberastine', 'Ellaga', '', 'eeddifranz@gmail.com', '$2y$12$T.uChucpRggLxgEWc27cPejQkZBu80V0iv10JRRNZxm0p.jCf4GXa', 'BSCS', '3rd Year', 'member', NULL, 'student', NULL, 'active', NULL, NULL, NULL, '2026-10-17 00:11:20', '2026-10-16 17:11:53', NULL, 0, 1, 'member', 0, 1, 1, 'approved', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, '202403573', 'Charles Toshiaki', '', 'Ono', '', 'charlesmotion001@gmail.com', '$2y$12$6erxC83NpJWISu6aSi8M8eSFhjztHQyi.D/PN9P1gtIWKsVDsJYBS', 'BSCS', '3rd Year', 'officer', 'Auditor', 'student', NULL, 'active', '/assets/uploads/profiles/cbef562b5f39989bc67b826d.jpg', NULL, NULL, '2026-10-17 01:15:01', '2026-10-16 18:16:48', NULL, 0, 1, 'officer', 0, 1, 1, 'approved', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, '202403593', 'Henmyr Vincent', 'Legaspina', 'Tepacia', '', 'hvtepacia11@gmail.com', '$2y$12$p0Atp0amLrqvaZlhuIaatOGXoPoCN3rRdUVRsgtVxgnWrrTQmFHJa', 'BSCS', '3rd Year', 'officer', 'Marketing Committee', 'student', NULL, 'active', '/assets/uploads/profiles/fe9d0909a3193f041d329224.jpg', '/assets/uploads/covers/e3d11951ba9e7cfe007329af.webp', 'I hate Period Cramps', '2026-10-21 09:00:41', '2026-11-05 23:10:19', NULL, 0, 1, 'developer', 0, 1, 1, 'approved', NULL, NULL, NULL, NULL, NULL, NULL, '9b819706b3af29c46a32776fcab95cbe'),
(12, 'ADMIN-001', 'System', NULL, 'Administrator', NULL, 'admin@cso-mis.local', '$2y$10$LDl0TgR8q6I/q37SFDiyt.4k4KLkEqg5cJxz0Kq5W8UXCewPNQvMO', 'BSCS', '4th Year', 'admin', NULL, 'student', NULL, 'active', '/assets/uploads/profiles/03301fa13daf87f48739ad79.jpg', '/assets/uploads/covers/25bb0412ac695d84ae8d5017.png', NULL, '2026-11-05 06:02:57', '2026-11-07 13:01:32', NULL, 0, 1, 'none', 0, 0, 1, 'none', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, '202403588', 'Alcher Meekko', 'Funa', 'Suganob', '', 'suganobmeekko@gmail.com', '$2y$12$xSZvhNsOYFfELWkBWptcmeHe9bXqeID46X6gWkk9SWd6OSI9ycELS', 'BSCS', '3rd Year', 'member', NULL, 'student', NULL, 'active', '/assets/uploads/profiles/cc55d99f2d5c8a16bc74ecc9.jpg', '/assets/uploads/covers/4bb989910239162719f88aea.jpg', NULL, '2026-11-05 07:07:01', '2026-11-04 23:12:04', NULL, 0, 1, 'member', 0, 1, 1, 'approved', '9fadd3646f745ab41c1a31681b0e1dbc.jpg', '07793bdc0d23c86c409d7359f04828fa.jpg', '2026-11-04 23:10:56', '2026-11-04 23:11:15', 12, NULL, '2e7755b9c4cf9c3d2030e89e3c446b80'),
(14, '20201231', 'Moen Jay', 'Matias', 'Redula', '', 'jaysirfilms@gmail.com', '$2y$12$nqIdakr7ONe1LWV4J48WG../txnSMAdv/PP9Hx573UmOaJpWneA3.', 'BSCS', '3rd Year', 'member', NULL, 'student', NULL, 'active', '/assets/uploads/profiles/e164716085f393038e07d26b.jpg', '/assets/uploads/covers/6ca17dcdcd5e474ef80f2c5e.jpg', NULL, '2026-11-05 10:05:05', '2026-11-05 02:07:33', NULL, 0, 1, 'member', 0, 1, 1, 'none', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, '202403533', 'Carl', 'Ruiz', 'Doro-on', '', 'carlruizdoroon16@gmail.com', '$2y$12$MWl5/Qf1HVa7KW5EmStkROEgRZvWl3bEXLmvvxrgbgn1/u1JH2lQO', 'BSCS', '3rd Year', 'officer', 'President', 'student', NULL, 'active', '/assets/uploads/profiles/075acee81d34dcf52151b87c.jpg', NULL, NULL, '2026-11-06 07:11:59', '2026-11-05 23:15:32', NULL, 0, 1, 'officer', 0, 1, 1, 'none', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, '202404297', 'Xhander Cyd', 'Flores', 'Tayros', '', 'xcyd0810@gmail.com', '$2y$12$pq26CtPJMTlXuP1kyESaCeyRPTiV5lxnvTPjYpQ/zAMRqnKrFbXTS', 'BSCS', '3rd Year', 'officer', 'Marketing Committee', 'student', NULL, 'active', '/assets/uploads/profiles/9590123b5b08f5c79a8f1f0f.jpg', NULL, 'Maliiiii', '2026-11-06 07:26:48', '2026-11-05 23:29:42', NULL, 0, 1, 'officer', 0, 1, 1, 'none', NULL, NULL, NULL, NULL, NULL, NULL, 'ef27358b12fbd29504b50320ccbdf21e'),
(17, '202403604', 'Samantha May', 'Carcueva', 'Wenham', '', 'wsamamthamay@gmail.com', '$2y$12$QbmtA3bU2Zstqo/YbCC5.uNg9yyf9cAFyqw8j19dUk7gJnjPYYkYW', 'BSCS', '3rd Year', 'officer', 'Assistant Secretary', 'student', NULL, 'active', '/assets/uploads/profiles/623ebe7f433ab4435181d431.jpg', NULL, NULL, '2026-11-06 07:31:55', NULL, NULL, 0, 1, 'officer', 0, 1, 1, 'none', NULL, NULL, NULL, NULL, NULL, NULL, 'aad6d65175fb08380cdeb5274aec1ce0'),
(118, '202403547', 'Gem Kristly', 'Cadalso', 'Go', '', 'kristlygocadz.gem@gmail.com', '$2y$12$6WIf30NjJ0GxuPnCIaOwHuKCg.wc8yQIL7j.o8sgd8QXbc49XpZza', 'BSCS', '3rd Year', 'officer', 'Treasurer', 'student', NULL, 'active', '/assets/uploads/profiles/95f83d54b1de3584ecc69900.jpg', NULL, NULL, '2026-11-07 05:00:38', '2026-11-07 13:07:24', NULL, 0, 1, 'officer', 0, 1, 1, 'none', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(119, '202403571', 'Jola Mae', 'Sitoy', 'Nepomuceno', '', 'jolamaenepomuceno@gmail.com', '$2y$12$YpjDymajY5/H5jWfENtCpeKNSGsLXBi.ojy1VgEf0tSt8IHs2ENV.', 'BSCS', '3rd Year', 'officer', 'Secretariat Committee', 'student', NULL, 'active', NULL, NULL, NULL, '2026-11-07 08:20:25', NULL, NULL, 0, 1, 'officer', 0, 1, 1, 'none', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_notices`
--

CREATE TABLE `user_notices` (
  `notice_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_notices`
--

INSERT INTO `user_notices` (`notice_id`, `user_id`, `message`, `created_at`) VALUES
(1, 13, 'Your NORSU ID was verified. Your account is now fully active.', '2026-11-05 07:11:15'),
(2, 14, 'Your CSO account was approved. Welcome!', '2026-11-05 10:07:02'),
(3, 15, 'Your CSO account was approved. Welcome!', '2026-11-06 07:14:13'),
(4, 16, 'Your CSO account was approved. Welcome!', '2026-11-06 07:28:05'),
(5, 17, 'Your CSO account was approved. Welcome!', '2026-11-06 08:33:42'),
(6, 118, 'Your CSO account was approved. Welcome!', '2026-11-07 05:01:55'),
(7, 119, 'Your CSO account was approved. Welcome!', '2026-11-07 08:21:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `app_secrets`
--
ALTER TABLE `app_secrets`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `app_state`
--
ALTER TABLE `app_state`
  ADD PRIMARY KEY (`k`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `uniq_attendance` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `attendance_reminders`
--
ALTER TABLE `attendance_reminders`
  ADD PRIMARY KEY (`reminder_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`verify_id`),
  ADD KEY `idx_ev_user` (`user_id`,`created_at`),
  ADD KEY `idx_ev_ip` (`requested_ip`,`created_at`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD UNIQUE KEY `qr_token` (`qr_token`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `event_photos`
--
ALTER TABLE `event_photos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD UNIQUE KEY `uniq_event_user` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`throttle_key`);

--
-- Indexes for table `map_buildings`
--
ALTER TABLE `map_buildings`
  ADD PRIMARY KEY (`building_id`);

--
-- Indexes for table `map_floors`
--
ALTER TABLE `map_floors`
  ADD PRIMARY KEY (`floor_id`),
  ADD KEY `building_id` (`building_id`);

--
-- Indexes for table `map_locations`
--
ALTER TABLE `map_locations`
  ADD PRIMARY KEY (`location_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `map_reports`
--
ALTER TABLE `map_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `status` (`status`,`created_at`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `map_rooms`
--
ALTER TABLE `map_rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `floor_id` (`floor_id`);

--
-- Indexes for table `org_info`
--
ALTER TABLE `org_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD KEY `idx_pr_user` (`user_id`,`created_at`),
  ADD KEY `idx_pr_ip` (`requested_ip`,`created_at`);

--
-- Indexes for table `post_images`
--
ALTER TABLE `post_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_post` (`post_type`,`post_id`,`sort_order`);

--
-- Indexes for table `register_attempts`
--
ALTER TABLE `register_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reg_ip_time` (`ip`,`created_at`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`selector`),
  ADD KEY `idx_remember_user` (`user_id`);

--
-- Indexes for table `schema_meta`
--
ALTER TABLE `schema_meta`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_roster`
--
ALTER TABLE `student_roster`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uniq_card_token` (`card_token`);

--
-- Indexes for table `user_notices`
--
ALTER TABLE `user_notices`
  ADD PRIMARY KEY (`notice_id`),
  ADD KEY `user_id` (`user_id`,`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `attendance_reminders`
--
ALTER TABLE `attendance_reminders`
  MODIFY `reminder_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `verify_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `event_photos`
--
ALTER TABLE `event_photos`
  MODIFY `photo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `map_buildings`
--
ALTER TABLE `map_buildings`
  MODIFY `building_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `map_floors`
--
ALTER TABLE `map_floors`
  MODIFY `floor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `map_locations`
--
ALTER TABLE `map_locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `map_reports`
--
ALTER TABLE `map_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `map_rooms`
--
ALTER TABLE `map_rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `post_images`
--
ALTER TABLE `post_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `register_attempts`
--
ALTER TABLE `register_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `user_notices`
--
ALTER TABLE `user_notices`
  MODIFY `notice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_reminders`
--
ALTER TABLE `attendance_reminders`
  ADD CONSTRAINT `attendance_reminders_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_reminders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_photos`
--
ALTER TABLE `event_photos`
  ADD CONSTRAINT `event_photos_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_photos_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `map_floors`
--
ALTER TABLE `map_floors`
  ADD CONSTRAINT `map_floors_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `map_buildings` (`building_id`) ON DELETE CASCADE;

--
-- Constraints for table `map_locations`
--
ALTER TABLE `map_locations`
  ADD CONSTRAINT `map_locations_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `map_reports`
--
ALTER TABLE `map_reports`
  ADD CONSTRAINT `map_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `map_rooms`
--
ALTER TABLE `map_rooms`
  ADD CONSTRAINT `map_rooms_ibfk_1` FOREIGN KEY (`floor_id`) REFERENCES `map_floors` (`floor_id`) ON DELETE CASCADE;

--
-- Constraints for table `org_info`
--
ALTER TABLE `org_info`
  ADD CONSTRAINT `org_info_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_notices`
--
ALTER TABLE `user_notices`
  ADD CONSTRAINT `user_notices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
