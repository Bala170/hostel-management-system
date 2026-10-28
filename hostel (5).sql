-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 12:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hostel`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_batch`
--

CREATE TABLE `academic_batch` (
  `id` int(11) NOT NULL,
  `academic_batch` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_batch`
--

INSERT INTO `academic_batch` (`id`, `academic_batch`) VALUES
(1, '2022-2026'),
(2, '2023-2027'),
(3, '2024-2028'),
(4, '2025-2029');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `roll_number` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Late Entry','On Leave') DEFAULT 'Absent',
  `marked_by` int(11) DEFAULT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `roll_number`, `date`, `status`, `marked_by`, `marked_at`, `academic_year`, `created_at`, `reason`) VALUES
(29, 25, '927623BCB014', '2026-04-10', 'Present', NULL, '2026-04-10 07:01:06', NULL, '2026-04-10 07:01:06', 'marudhani');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_time_control`
--

CREATE TABLE `attendance_time_control` (
  `id` int(11) NOT NULL,
  `year` varchar(20) NOT NULL,
  `from_time` time NOT NULL,
  `to_time` time NOT NULL,
  `late_entry_time` time NOT NULL,
  `enabled_by` varchar(100) NOT NULL,
  `status` enum('enabled','disabled') DEFAULT 'enabled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_time_control`
--

INSERT INTO `attendance_time_control` (`id`, `year`, `from_time`, `to_time`, `late_entry_time`, `enabled_by`, `status`, `created_at`) VALUES
(25, '', '09:00:00', '10:00:00', '11:00:00', 'Admin', 'disabled', '2026-02-16 04:22:00');

-- --------------------------------------------------------

--
-- Table structure for table `biometric_machines`
--

CREATE TABLE `biometric_machines` (
  `id` int(10) UNSIGNED NOT NULL,
  `machine_name` varchar(100) NOT NULL,
  `machine_ip` varchar(45) NOT NULL,
  `device_id` varchar(50) DEFAULT NULL,
  `machine_port` int(11) DEFAULT 4370,
  `machine_type` enum('attendance','gate_in','gate_out','mess') NOT NULL,
  `hostel_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `menu_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `biometric_machines`
--

INSERT INTO `biometric_machines` (`id`, `machine_name`, `machine_ip`, `device_id`, `machine_port`, `machine_type`, `hostel_id`, `status`, `menu_id`) VALUES
(4, 'Bro', '10.0.250.253', 'EFE322', 4370, 'gate_out', 2, 'active', NULL),
(5, 'Test', '192.168.1.200', 'FIN23', 4370, 'mess', NULL, 'active', 21);

-- --------------------------------------------------------

--
-- Table structure for table `blocked_students`
--

CREATE TABLE `blocked_students` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `attendance_id` int(11) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` text DEFAULT NULL,
  `blocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unblocked_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blocked_students`
--

INSERT INTO `blocked_students` (`id`, `student_id`, `attendance_id`, `type`, `created_at`, `reason`, `blocked_at`, `unblocked_at`) VALUES
(23, 16, NULL, 'Both', '2026-04-10 08:30:28', 'asdfghjkl', '2026-04-10 08:30:28', '2026-04-10 10:02:05'),
(24, 17, NULL, 'Both', '2026-04-10 09:53:05', 'late attendance', '2026-04-10 09:53:05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `communication_preferences`
--

CREATE TABLE `communication_preferences` (
  `student_id` int(11) NOT NULL,
  `method` enum('sms','call','both') DEFAULT 'sms',
  `language` enum('tamil','english') DEFAULT 'english',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_notifications` tinyint(1) DEFAULT 1,
  `sms_notifications` tinyint(1) DEFAULT 1,
  `whatsapp_notifications` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_blocks`
--

CREATE TABLE `daily_blocks` (
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_blocks`
--

INSERT INTO `daily_blocks` (`date`) VALUES
('2026-02-02'),
('2026-02-15');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL,
  `faculty_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `department` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gate_log`
--

CREATE TABLE `gate_log` (
  `log_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `leave_id` int(11) DEFAULT NULL,
  `in_time` datetime DEFAULT NULL,
  `out` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `generated_at` datetime DEFAULT NULL,
  `device_ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gate_log`
--

INSERT INTO `gate_log` (`log_id`, `student_id`, `user_id`, `leave_id`, `in_time`, `out`, `created_at`, `generated_at`, `device_ip`) VALUES
(5, NULL, 10, 27, NULL, '2026-01-31 16:17:41', '2026-01-31 11:18:29', NULL, '10.0.250.163'),
(6, NULL, 8, 28, NULL, '2026-01-31 16:18:01', '2026-01-31 11:18:29', '2026-01-31 16:48:30', '10.0.250.163');

-- --------------------------------------------------------

--
-- Table structure for table `general_leave`
--

CREATE TABLE `general_leave` (
  `GeneralLeave_ID` int(11) NOT NULL,
  `Leave_Name` varchar(100) NOT NULL,
  `Created_Date` timestamp NOT NULL DEFAULT current_timestamp(),
  `From_Date` datetime NOT NULL,
  `To_Date` datetime NOT NULL,
  `Enabled_By` varchar(100) NOT NULL,
  `Instructions` text DEFAULT NULL,
  `Is_Enabled` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_leave`
--

INSERT INTO `general_leave` (`GeneralLeave_ID`, `Leave_Name`, `Created_Date`, `From_Date`, `To_Date`, `Enabled_By`, `Instructions`, `Is_Enabled`) VALUES
(1, 'Diwali leave', '2025-11-24 04:22:31', '2025-11-25 07:00:00', '2025-11-30 08:52:00', '', '', 0),
(2, 'Pongal Holiday', '2026-01-28 07:13:35', '2026-01-28 18:00:00', '2026-02-08 08:45:00', '', 'Happy Pongal', 0),
(3, 'General Leave', '2026-02-16 04:07:36', '2026-02-20 09:36:00', '2026-02-20 21:36:00', '', '', 0),
(5, 'General Leave', '2026-04-10 07:18:43', '2026-04-10 17:48:00', '2026-04-13 18:48:00', 'female_admin', '', 1),
(6, 'Diwali leave', '2026-04-10 07:24:33', '2026-04-10 18:54:00', '2026-04-13 06:54:00', 'male_admin', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `guardians`
--

CREATE TABLE `guardians` (
  `guardian_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `relation` enum('father','mother','guardian','other') NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `approval_type` enum('primary','alternate','none') DEFAULT 'none',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guardians`
--

INSERT INTO `guardians` (`guardian_id`, `student_id`, `relation`, `name`, `phone`, `photo_path`, `approval_type`, `created_at`, `updated_at`) VALUES
(37, 15, 'father', 'M SENGOTTUVEL', '9492633000', NULL, 'primary', '2026-02-16 03:57:01', '2026-02-16 03:57:01'),
(38, 15, 'mother', 'L KAVITHA', '9442277181', NULL, 'alternate', '2026-02-16 03:57:01', '2026-02-16 03:57:01'),
(39, 15, 'guardian', 'SANDEEP', '7402733000', NULL, 'none', '2026-02-16 03:57:01', '2026-02-16 03:57:01'),
(40, 16, 'father', 'SIVAKUMAR', '9751024841', NULL, 'primary', '2026-02-16 04:10:12', '2026-02-16 04:10:12'),
(41, 16, 'mother', 'VALARMATHI', '9751024841', NULL, 'alternate', '2026-02-16 04:10:12', '2026-02-16 04:10:12'),
(42, 16, 'guardian', 'KALIMUTHU', '9751024841', NULL, 'none', '2026-02-16 04:10:12', '2026-02-16 04:10:12'),
(43, 17, 'father', 'PALANIVEL', '8675031038', NULL, 'primary', '2026-02-16 04:10:51', '2026-02-16 04:10:51'),
(44, 17, 'mother', 'MAHESWARI', '9944802264', NULL, 'alternate', '2026-02-16 04:10:51', '2026-02-16 04:10:51'),
(45, 17, 'guardian', 'THARANI', '9597695346', NULL, 'none', '2026-02-16 04:10:51', '2026-02-16 04:10:51'),
(46, 18, 'father', 'Karmugilan T', '8015718360', NULL, 'primary', '2026-02-16 04:11:31', '2026-02-16 04:11:31'),
(47, 18, 'mother', 'Vanitha K', '8015718360', NULL, 'alternate', '2026-02-16 04:11:31', '2026-02-16 04:11:31'),
(48, 18, 'guardian', 'Jameson Lambert', '8015718360', NULL, 'none', '2026-02-16 04:11:31', '2026-02-16 04:11:31'),
(49, 19, 'father', 'N.SASIKUMAR', '8825529451', NULL, 'alternate', '2026-02-16 04:16:16', '2026-02-16 04:16:16'),
(50, 19, 'mother', 'S.SUBESWARI', '8148957051', NULL, 'primary', '2026-02-16 04:16:16', '2026-02-16 04:16:16'),
(51, 19, 'guardian', 'C.SINDHUJA', '9003851664', NULL, 'none', '2026-02-16 04:16:16', '2026-02-16 04:16:16'),
(52, 20, 'father', 'Balakandaiyan', '9342603366', NULL, 'primary', '2026-02-16 04:17:28', '2026-02-16 04:17:28'),
(53, 20, 'mother', 'Kanchana', '9944802264', NULL, 'alternate', '2026-02-16 04:17:28', '2026-02-16 04:17:28'),
(54, 20, 'guardian', 'Bakiyaraj', '9003851664', NULL, 'none', '2026-02-16 04:17:28', '2026-02-16 04:17:28'),
(55, 21, 'father', 'SUBRAMANIYAM K', '7603899379', NULL, 'primary', '2026-02-16 04:17:30', '2026-02-16 04:17:30'),
(56, 21, 'mother', 'AMUTHA S', '9790154369', NULL, 'alternate', '2026-02-16 04:17:30', '2026-02-16 04:17:30'),
(57, 21, 'guardian', 'NIL', 'NIL', NULL, 'none', '2026-02-16 04:17:30', '2026-02-16 04:17:30'),
(58, 22, 'father', 'Suthakar V', '9443206336', NULL, 'primary', '2026-02-16 04:18:31', '2026-02-16 04:21:46'),
(59, 22, 'mother', 'Chithra S', '9894368575', NULL, 'alternate', '2026-02-16 04:18:31', '2026-02-16 04:18:31'),
(60, 22, 'guardian', NULL, NULL, NULL, 'none', '2026-02-16 04:18:31', '2026-02-16 04:18:31'),
(61, 23, 'father', 'Anbu thambi B', '7397681158', NULL, 'alternate', '2026-02-16 04:22:05', '2026-02-16 04:22:05'),
(62, 23, 'mother', 'Munees wari S', '9345844943', NULL, 'primary', '2026-02-16 04:22:05', '2026-02-16 04:22:05'),
(63, 23, 'guardian', 'Gomathi S', '92345432421', NULL, 'none', '2026-02-16 04:22:05', '2026-02-16 04:22:05'),
(64, 24, 'father', 'Sivakumar', '8295105735', NULL, 'alternate', '2026-02-16 04:28:45', '2026-02-16 04:28:45'),
(65, 24, 'mother', 'Bama', '9629024635', NULL, 'primary', '2026-02-16 04:28:45', '2026-02-16 04:28:45'),
(66, 24, 'guardian', NULL, NULL, NULL, 'none', '2026-02-16 04:28:45', '2026-02-16 04:28:45'),
(67, 25, 'father', 'Kandhasamy A', '9345746571', NULL, 'primary', '2026-02-16 04:31:00', '2026-02-16 04:31:00'),
(68, 25, 'mother', 'RajaLakshmi K', '93213678761', NULL, 'alternate', '2026-02-16 04:31:00', '2026-02-16 04:31:00'),
(69, 25, 'guardian', 'Keerthana', '9315832690', NULL, 'none', '2026-02-16 04:31:00', '2026-02-16 04:31:00'),
(70, 26, 'father', 'suresh', '9600796697', NULL, 'primary', '2026-04-02 04:37:02', '2026-04-02 04:37:02'),
(71, 26, 'mother', 'ramya', '9944802264', NULL, 'alternate', '2026-04-02 04:37:02', '2026-04-02 04:37:02'),
(72, 26, 'guardian', 'nil', 'NIL', NULL, 'none', '2026-04-02 04:37:02', '2026-04-02 04:37:02'),
(73, 27, 'father', 'PALANIVEL S', '9600796697', NULL, 'primary', '2026-04-02 04:58:01', '2026-04-02 04:58:01'),
(74, 27, 'mother', 'MAHESWARI', '9944802264', NULL, 'alternate', '2026-04-02 04:58:01', '2026-04-02 04:58:01'),
(75, 27, 'guardian', 'nil', 'nil', NULL, 'none', '2026-04-02 04:58:01', '2026-04-02 04:58:01');

-- --------------------------------------------------------

--
-- Table structure for table `hostels`
--

CREATE TABLE `hostels` (
  `hostel_id` int(11) NOT NULL,
  `hostel_code` varchar(20) NOT NULL,
  `hostel_name` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Mixed') NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hostels`
--

INSERT INTO `hostels` (`hostel_id`, `hostel_code`, `hostel_name`, `gender`, `address`, `created_at`) VALUES
(1, 'MKCEML001', 'Muthulakshmi', 'Female', 'Block A Address', '2025-10-14 03:05:57'),
(2, 'MKCEOCTA002', 'Octa', 'Male', 'Block B Address', '2025-10-14 03:05:57'),
(3, 'MKCEVEDA003', 'Veda', 'Male', 'Block C Address', '2025-10-15 22:36:04');

-- --------------------------------------------------------

--
-- Table structure for table `hostel_faculty`
--

CREATE TABLE `hostel_faculty` (
  `f_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `f_name` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `additional_role` varchar(100) DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `gender` enum('Male','Female','Others') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `date_of_join` date DEFAULT NULL,
  `fingerprint_id` varchar(50) DEFAULT NULL,
  `aadhaar_number` varchar(20) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `hostel_id` int(11) DEFAULT NULL,
  `status` enum('1','0') DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hostel_faculty`
--

INSERT INTO `hostel_faculty` (`f_id`, `faculty_id`, `f_name`, `department`, `designation`, `role`, `additional_role`, `phone_number`, `email`, `gender`, `dob`, `date_of_join`, `fingerprint_id`, `aadhaar_number`, `room_id`, `hostel_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1001, 'Keerthihaa Sri P', 'computer_science_engineering', 'Professor', 'Class advisor', '3 floor incharge', '1212121212', 'keerthihaasri@gmail.com', 'Female', '0000-00-00', '0000-00-00', 'FAC1001', '', 5, 1, '0', '2025-11-24 04:30:18', '2025-11-24 04:37:58'),
(2, 1002, 'Rakshaa Bala B', 'information_technology', 'Associate Professor', 'Class advisor', '2 floor incharge', '2323232323', 'rakshaabala@gmail.com', 'Female', '0000-00-00', '0000-00-00', 'FAC1002', '', 7, 1, '0', '2025-11-24 04:32:16', '2026-02-16 03:54:48'),
(3, 1003, 'Shibin S', 'electronics_communication_engineering', 'Assistant Professor', 'Class advisor', '4 floor incharge', '3434343434', 'shibin@gmail.com', 'Male', '0000-00-00', '0000-00-00', 'FAC1003', '', 14, 2, '0', '2025-11-24 04:33:59', '2026-02-16 03:54:41'),
(4, 1004, 'Keerthihaa Sri P', 'computer_science_engineering', 'Assistant Professor', 'Assistant Professor ', '1st floor incharge', '1212121212', 'keerthihaasri@gmail.com', 'Female', '0000-00-00', '0000-00-00', 'FAC1004', '', 23, 1, '0', '2026-01-23 04:17:09', '2026-02-16 03:54:52'),
(5, 111009, 'Priya', 'computer_science_engineering', 'Assistant Professor', 'Faculty', 'Ward Incharge', '9273839289', 'priya@gmail.com', 'Female', '0000-00-00', '0000-00-00', '111009', '', 51, 1, '1', '2026-02-16 04:12:13', '2026-02-16 04:12:52'),
(6, 1001111, 'Surya', 'computer_science_engineering', 'Professor', 'Class advisor', '2 floor incharge', '9342603366', 'surya@gmail.com', 'Male', '0000-00-00', '0000-00-00', '1001111', '', 43, 3, '1', '2026-02-16 04:12:22', '2026-02-16 04:12:22');

-- --------------------------------------------------------

--
-- Table structure for table `ivr_calls`
--

CREATE TABLE `ivr_calls` (
  `id` int(11) NOT NULL,
  `leave_id` int(11) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `unique_id` varchar(100) DEFAULT NULL COMMENT 'Provider unique_id returned on trigger',
  `system_api_uniqueid` varchar(100) DEFAULT NULL,
  `call_status` enum('pending','dialed','answered','not_answered','busy','congestion','failed','processed') DEFAULT 'pending',
  `dtmf` varchar(10) DEFAULT NULL COMMENT 'DTMF pressed by parent (1=approve, 0=reject)',
  `duration` int(11) DEFAULT 0,
  `time_start` datetime DEFAULT NULL,
  `time_connect` datetime DEFAULT NULL,
  `time_end` datetime DEFAULT NULL,
  `retry_count` int(11) DEFAULT 0,
  `raw_trigger_response` text DEFAULT NULL,
  `raw_report_response` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ivr_calls`
--

INSERT INTO `ivr_calls` (`id`, `leave_id`, `contact_number`, `unique_id`, `system_api_uniqueid`, `call_status`, `dtmf`, `duration`, `time_start`, `time_connect`, `time_end`, `retry_count`, `raw_trigger_response`, `raw_report_response`, `created_at`, `updated_at`) VALUES
(13, 31, '9342603366', '2410825_1_3049-u6824', '2410825_1', 'answered', '', 12, '2026-02-16 09:52:56', '2026-02-16 09:53:05', '2026-02-16 09:53:23', 0, '{\"status\":\"success\",\"code\":\"000\",\"desc\":\"Call Submitted Successfully\",\"data\":[{\"contact_number\":\"9342603366\",\"unique_id\":\"2410825_1_3049-u6824\",\"system_api_uniqueid\":\"2410825_1\"}]}', '{\"status\":\"success\",\"code\":\"000\",\"desc\":\"Request Submitted Successfully\",\"data\":{\"2410825_1_3049-u6824\":{\"status\":\"success\",\"desc\":\"unique id fetched found\",\"data\":{\"status\":\"Dialed\",\"report\":\"Answered\",\"contact_number\":\"9342603366\",\"duration\":\"12\",\"time_start\":\"16-Feb-2026 9:52:56 AM\",\"time_connect\":\"16-Feb-2026 9:53:05 AM\",\"time_end\":\"16-Feb-2026 9:53:23 AM\",\"dtmf\":\"\",\"currentRetryCount\":0,\"ivrExecuteFlow\":\"#1\"}}}}', '2026-02-16 09:52:30', '2026-02-16 10:00:32'),
(14, 33, '8015718360', '2410827_1_3050-u6824', '2410827_1', 'busy', '', 0, '2026-02-16 09:54:25', NULL, '2026-02-16 09:54:55', 0, '{\"status\":\"success\",\"code\":\"000\",\"desc\":\"Call Submitted Successfully\",\"data\":[{\"contact_number\":\"8015718360\",\"unique_id\":\"2410827_1_3050-u6824\",\"system_api_uniqueid\":\"2410827_1\"}]}', '{\"status\":\"success\",\"code\":\"000\",\"desc\":\"Request Submitted Successfully\",\"data\":{\"2410827_1_3050-u6824\":{\"status\":\"success\",\"desc\":\"unique id fetched found\",\"data\":{\"status\":\"Dialed\",\"report\":\"Busy\",\"contact_number\":\"8015718360\",\"duration\":\"0\",\"time_start\":\"16-Feb-2026 9:54:25 AM\",\"time_connect\":\"-\",\"time_end\":\"16-Feb-2026 9:54:55 AM\",\"dtmf\":\"\",\"currentRetryCount\":0}}}}', '2026-02-16 09:54:18', '2026-02-16 10:00:32'),
(15, 32, '7603899379', '2410825_2_3049-u6824', '2410825_2', 'processed', '1', 9, '2026-02-16 09:55:01', '2026-02-16 09:55:03', '2026-02-16 09:55:12', 0, '{\"status\":\"success\",\"code\":\"000\",\"desc\":\"Call Submitted Successfully\",\"data\":[{\"contact_number\":\"7603899379\",\"unique_id\":\"2410825_2_3049-u6824\",\"system_api_uniqueid\":\"2410825_2\"}]}', '{\"status\":\"success\",\"code\":\"000\",\"desc\":\"Request Submitted Successfully\",\"data\":{\"2410825_2_3049-u6824\":{\"status\":\"success\",\"desc\":\"unique id fetched found\",\"data\":{\"status\":\"Dialed\",\"report\":\"Answered\",\"contact_number\":\"7603899379\",\"duration\":\"9\",\"time_start\":\"16-Feb-2026 9:55:01 AM\",\"time_connect\":\"16-Feb-2026 9:55:03 AM\",\"time_end\":\"16-Feb-2026 9:55:12 AM\",\"dtmf\":\"1\",\"currentRetryCount\":0,\"ivrExecuteFlow\":\"#1\"}}}}', '2026-02-16 09:54:25', '2026-02-16 10:00:33'),
(16, 35, '8675031038', NULL, NULL, 'failed', NULL, 0, NULL, NULL, NULL, 0, 'API call failed', NULL, '2026-04-02 10:48:51', '2026-04-02 10:48:51');

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

CREATE TABLE `leave_applications` (
  `Leave_ID` int(11) NOT NULL,
  `Reg_No` varchar(20) NOT NULL,
  `LeaveType_ID` int(11) DEFAULT NULL,
  `category_of_leave` varchar(100) DEFAULT NULL,
  `Applied_Date` timestamp NOT NULL DEFAULT current_timestamp(),
  `From_Date` datetime NOT NULL,
  `To_Date` datetime NOT NULL,
  `Reason` text DEFAULT NULL,
  `Proof` varchar(255) DEFAULT NULL,
  `Status` enum('Pending','Forwarded to Admin','Rejected by HOD','Rejected by Admin','Rejected by Parents','Approved','out','closed','late entry','Cancelled','IVR Pending') NOT NULL DEFAULT 'Pending',
  `Remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_applications`
--

INSERT INTO `leave_applications` (`Leave_ID`, `Reg_No`, `LeaveType_ID`, `category_of_leave`, `Applied_Date`, `From_Date`, `To_Date`, `Reason`, `Proof`, `Status`, `Remarks`) VALUES
(31, '927623bcs086', 3, 'Leave', '2026-02-16 04:21:18', '2026-02-17 06:00:00', '2026-02-19 18:00:00', 'Going to function', '', 'IVR Pending', 'Awaiting parent response via IVR call'),
(32, '927623BEC203', 2, 'onduty', '2026-02-16 04:22:54', '2026-02-17 06:00:00', '2026-02-17 18:00:00', 'Symposium', '', 'Approved', 'Awaiting parent response via IVR call'),
(33, '927623bit039', 3, 'Leave', '2026-02-16 04:23:51', '2026-02-16 18:00:00', '2026-02-19 18:00:00', 'Medical Issues', '', 'IVR Pending', 'Awaiting parent response via IVR call'),
(34, '927623BIT040', 5, 'Outing', '2026-02-16 04:31:54', '2026-02-17 08:30:00', '2026-02-17 18:00:00', 'For Updating Aadhar', '', 'Pending', NULL),
(35, '927623BCS046', 4, 'emergency Leave', '2026-04-02 05:14:35', '2026-04-02 18:00:00', '2026-04-04 18:00:00', 'FEVER', '', 'Pending', NULL),
(36, '927623BAD011', 5, 'Outing', '2026-04-08 04:42:00', '2026-04-08 10:00:00', '2026-04-08 12:40:00', 'parents visit', '', 'Pending', NULL),
(37, '927623BAD011', 5, 'Outing', '2026-04-08 04:47:57', '2026-04-08 18:00:00', '2026-04-08 23:30:00', 'wertyuiol;mjnhgfdxs', '', 'Cancelled', NULL),
(39, '927623BAD011', 5, NULL, '2026-04-10 08:51:18', '2026-04-10 18:00:00', '2026-04-10 23:30:00', 'qwertyuiokjhgfd', '', 'Pending', NULL),
(40, '927623BAD011', 4, NULL, '2026-04-10 09:00:50', '2026-04-10 18:00:00', '2026-04-12 18:00:00', 'dfghjk', '', 'Pending', NULL),
(41, '927623BCS046', 2, NULL, '2026-04-10 09:45:44', '2026-04-10 06:00:00', '2026-04-12 06:00:00', 'going to home', '', 'Pending', NULL),
(42, '927623BCS046', 4, NULL, '2026-04-10 09:50:47', '2026-04-10 18:00:00', '2026-04-12 18:00:00', 'hospital', '', 'Pending', NULL),
(43, '927623BCS046', 3, NULL, '2026-04-10 09:51:08', '2026-04-11 18:00:00', '2026-04-18 18:00:00', 'ghjkl', '', 'Pending', NULL),
(44, '927623BAD011', 3, NULL, '2026-04-10 10:07:25', '2026-04-11 18:00:00', '2026-04-13 18:00:00', 'home', 'Student/proofs/69d8cbdd63a82_1775815645.pdf', 'Pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `LeaveType_ID` int(11) NOT NULL,
  `Leave_Type_Name` varchar(100) NOT NULL,
  `Priority` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`LeaveType_ID`, `Leave_Type_Name`, `Priority`, `created_at`) VALUES
(1, 'general Leave', 4, '2025-10-15 04:31:58'),
(2, 'onduty', 2, '2025-10-15 04:31:58'),
(3, 'Leave', 3, '2025-10-15 04:31:58'),
(4, 'emergency Leave', 1, '2025-10-15 04:31:58'),
(5, 'Outing', 6, '2025-10-25 09:50:50');

-- --------------------------------------------------------

--
-- Table structure for table `mess_menu`
--

CREATE TABLE `mess_menu` (
  `menu_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `meal_type` enum('Breakfast','Lunch','Snacks','Dinner') NOT NULL,
  `items` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `fee` decimal(8,2) NOT NULL DEFAULT 0.00,
  `token_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mess_menu`
--

INSERT INTO `mess_menu` (`menu_id`, `date`, `meal_type`, `items`, `category`, `fee`, `token_type`, `created_at`) VALUES
(1, '2025-11-24', 'Breakfast', 'Dosa, Sambar, Chuntney', 'Regular', 0.00, NULL, '2025-11-24 04:16:45'),
(2, '2025-11-24', 'Lunch', 'Rice, Drumstick Sambar, Rasam, Butter milk, Potato poriyal', 'Regular', 0.00, NULL, '2025-11-24 04:18:08'),
(3, '2026-02-16', 'Snacks', 'Samosa, Tea, Milk', 'Regular', 0.00, NULL, '2025-11-24 04:18:37'),
(4, '2026-02-16', 'Dinner', 'Chappathi, Curd rice, channa masala', 'Regular', 0.00, NULL, '2025-11-24 04:19:23'),
(6, '2026-02-16', 'Lunch', 'Rice, Dhal, Appalam, Payasam, Spinach kootu, Rasam, Butter milk', 'Regular', 0.00, NULL, '2026-01-22 03:34:56'),
(7, '2026-02-16', 'Breakfast', 'Dosa, Mint Chutney, Sambar, Upma, Coffee, Milk', 'Regular', 0.00, NULL, '2026-01-28 03:38:12'),
(8, '2026-04-02', 'Breakfast', 'pongal \nidly', 'Regular', 0.00, NULL, '2026-04-02 04:39:35');

-- --------------------------------------------------------

--
-- Table structure for table `mess_supervisors`
--

CREATE TABLE `mess_supervisors` (
  `supervisor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `shift` enum('Morning','Evening','Night') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mess_tokens`
--

CREATE TABLE `mess_tokens` (
  `token_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `roll_number` varchar(50) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `meal_type` varchar(100) NOT NULL,
  `menu` varchar(100) NOT NULL,
  `token_type` enum('Special') NOT NULL,
  `token_date` date DEFAULT NULL,
  `special_fee` decimal(8,2) DEFAULT 0.00,
  `supervisor_id` int(11) DEFAULT NULL,
  `device_ip` varchar(45) DEFAULT NULL,
  `generated_at` datetime DEFAULT NULL,
  `status` enum('Generated','Used','Expired','Cancelled') DEFAULT 'Generated',
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mess_token_bills`
--

CREATE TABLE `mess_token_bills` (
  `bill_id` int(11) NOT NULL,
  `roll_number` varchar(50) NOT NULL,
  `month` year(4) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`id`, `title`, `content`, `created_at`, `updated_at`) VALUES
(1, '', 'Today meeting at 6pm in muthulakshmi hostel ground', '2026-02-16 04:21:46', '2026-02-16 04:21:46');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `hostel_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `block` enum('North','South','East','West') DEFAULT NULL,
  `floor` enum('I','II','III','IV','V') DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 3,
  `occupied` int(11) NOT NULL DEFAULT 0,
  `current_occupancy` int(11) NOT NULL DEFAULT 0,
  `room_type` enum('AC','Non-AC') NOT NULL DEFAULT 'Non-AC',
  `status` varchar(50) NOT NULL DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `hostel_id`, `room_number`, `block`, `floor`, `capacity`, `occupied`, `current_occupancy`, `room_type`, `status`, `created_at`, `updated_at`) VALUES
(34, 2, 'W302', 'West', 'III', 3, 0, 0, 'Non-AC', 'Available', '2026-02-16 03:54:50', '2026-02-16 03:54:50'),
(35, 3, 'S202', 'South', 'II', 3, 0, 0, 'Non-AC', 'Available', '2026-02-16 03:55:10', '2026-02-16 03:55:10'),
(36, 3, 'E101', 'East', 'II', 3, 0, 0, 'Non-AC', 'Available', '2026-02-16 03:55:30', '2026-02-16 03:58:17'),
(37, 2, 'N101', 'North', 'I', 3, 3, 0, 'Non-AC', 'Available', '2026-02-16 03:55:42', '2026-02-16 04:11:31'),
(38, 2, 'E110', 'East', 'I', 3, 0, 0, 'AC', 'Available', '2026-02-16 03:55:43', '2026-02-16 04:08:41'),
(39, 2, 'S101', 'South', 'I', 3, 0, 0, 'AC', 'Available', '2026-02-16 03:55:46', '2026-02-16 04:08:59'),
(40, 2, 'N102', 'North', 'I', 3, 0, 0, 'AC', 'Available', '2026-02-16 03:56:02', '2026-02-16 04:08:50'),
(41, 3, 'N102', 'North', 'I', 3, 0, 0, 'Non-AC', 'Available', '2026-02-16 03:56:03', '2026-02-16 03:56:03'),
(42, 3, 'W216', 'West', 'I', 3, 0, 0, 'Non-AC', 'Available', '2026-02-16 03:56:14', '2026-02-16 03:56:14'),
(43, 3, 'N201', 'North', 'II', 3, 0, 0, 'Non-AC', 'Available', '2026-02-16 03:56:21', '2026-02-16 04:12:51'),
(45, 3, 'N202', 'North', 'II', 3, 0, 0, 'Non-AC', 'Available', '2026-02-16 03:56:36', '2026-02-16 03:56:36'),
(46, 3, 'S301', 'South', 'III', 3, 0, 0, 'Non-AC', 'Available', '2026-02-16 03:56:58', '2026-02-16 03:56:58'),
(47, 3, 'S302', 'South', 'III', 3, 0, 0, 'Non-AC', 'Available', '2026-02-16 03:57:51', '2026-02-16 03:57:51'),
(48, 2, 'N202', 'North', 'II', 3, 0, 0, 'AC', 'Available', '2026-02-16 03:59:27', '2026-02-16 03:59:27'),
(49, 3, 'E201', 'East', 'II', 3, 0, 0, 'Non-AC', 'Available', '2026-02-16 03:59:47', '2026-02-16 03:59:47'),
(50, 3, 'W401', 'West', 'IV', 3, 0, 0, 'Non-AC', 'Available', '2026-02-16 04:00:16', '2026-02-16 04:00:16'),
(51, 1, 'N101', 'North', 'I', 3, 0, 0, 'Non-AC', 'Available', '2026-02-16 04:06:38', '2026-02-16 04:12:21'),
(52, 1, 'W202', 'West', 'II', 3, 2, 0, 'Non-AC', 'Available', '2026-02-16 04:07:30', '2026-02-16 04:17:28'),
(53, 1, 'S226', 'South', 'II', 3, 2, 0, 'Non-AC', 'Available', '2026-02-16 04:08:25', '2026-04-02 04:37:02'),
(54, 1, 'W304', 'West', 'III', 3, 2, 0, 'Non-AC', 'Available', '2026-02-16 04:15:47', '2026-02-16 04:18:31'),
(55, 1, 'E132', 'East', 'I', 3, 3, 0, 'AC', 'Available', '2026-02-16 04:17:52', '2026-02-16 04:31:00'),
(56, 1, 'E-404', 'East', 'IV', 3, 1, 0, 'Non-AC', 'Available', '2026-04-02 04:38:50', '2026-04-02 04:58:01'),
(57, 1, 'N-308', 'North', 'I', 3, 0, 0, 'Non-AC', 'Available', '2026-04-02 04:54:04', '2026-04-02 04:54:04'),
(59, 1, 'W-202', 'West', 'I', 3, 0, 0, 'Non-AC', 'Available', '2026-04-10 07:02:37', '2026-04-10 07:02:37');

-- --------------------------------------------------------

--
-- Table structure for table `room_students`
--

CREATE TABLE `room_students` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `roll_number` varchar(50) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vacated_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `assigned_date` date DEFAULT NULL,
  `vacated_date` date DEFAULT NULL,
  `status` enum('Active','Vacated') DEFAULT 'Active',
  `hostel_id` int(11) DEFAULT NULL,
  `hostel_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_students`
--

INSERT INTO `room_students` (`id`, `room_id`, `student_id`, `room_number`, `roll_number`, `assigned_at`, `vacated_at`, `is_active`, `assigned_date`, `vacated_date`, `status`, `hostel_id`, `hostel_name`) VALUES
(20, 37, 15, '', '', '2026-02-16 03:57:01', NULL, 1, NULL, NULL, 'Active', NULL, NULL),
(21, 37, 16, '', '', '2026-02-16 04:10:12', NULL, 1, NULL, NULL, 'Active', NULL, NULL),
(22, 52, 17, '', '', '2026-02-16 04:10:51', NULL, 1, NULL, NULL, 'Active', NULL, NULL),
(23, 37, 18, '', '', '2026-02-16 04:11:31', NULL, 1, NULL, NULL, 'Active', NULL, NULL),
(24, 53, 19, '', '', '2026-02-16 04:16:16', NULL, 1, NULL, NULL, 'Active', NULL, NULL),
(25, 52, 20, '', '', '2026-02-16 04:17:28', NULL, 1, NULL, NULL, 'Active', NULL, NULL),
(26, 54, 21, '', '', '2026-02-16 04:17:30', NULL, 1, NULL, NULL, 'Active', NULL, NULL),
(27, 54, 22, '', '', '2026-02-16 04:18:31', NULL, 1, NULL, NULL, 'Active', NULL, NULL),
(28, 55, 23, '', '', '2026-02-16 04:22:05', NULL, 1, NULL, NULL, 'Active', NULL, NULL),
(29, 55, 24, '', '', '2026-02-16 04:28:46', NULL, 1, NULL, NULL, 'Active', NULL, NULL),
(30, 55, 25, '', '', '2026-02-16 04:31:00', NULL, 1, NULL, NULL, 'Active', NULL, NULL),
(31, 53, 26, '', '', '2026-04-02 04:37:02', NULL, 1, NULL, NULL, 'Active', NULL, NULL),
(32, 56, 27, '', '', '2026-04-02 04:58:01', NULL, 1, NULL, NULL, 'Active', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `specialtokenenable`
--

CREATE TABLE `specialtokenenable` (
  `menu_id` int(11) NOT NULL,
  `from_date` date NOT NULL,
  `from_time` time NOT NULL,
  `to_date` date NOT NULL,
  `to_time` time NOT NULL,
  `token_date` date DEFAULT NULL,
  `meal_type` text NOT NULL,
  `menu_items` text NOT NULL,
  `fee` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive','ended') DEFAULT 'active',
  `max_usage` int(11) NOT NULL COMMENT '-1 = unlimited, >0 = limited',
  `used_count` int(11) NOT NULL DEFAULT 0,
  `free_limit` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `specialtokenenable`
--

INSERT INTO `specialtokenenable` (`menu_id`, `from_date`, `from_time`, `to_date`, `to_time`, `token_date`, `meal_type`, `menu_items`, `fee`, `created_at`, `status`, `max_usage`, `used_count`, `free_limit`) VALUES
(19, '2026-02-16', '16:00:00', '2026-02-16', '18:00:00', '2026-02-16', 'Snacks', 'Samosa', 0.00, '2026-02-16 04:08:12', 'active', 1, 0, 0),
(20, '2026-02-16', '08:00:00', '2026-02-16', '14:00:00', '2026-02-16', 'Dinner', 'Egg', 6.00, '2026-02-16 04:09:13', 'active', -1, 0, 0),
(21, '2026-02-16', '08:00:00', '2026-02-17', '14:00:00', '2026-02-18', 'Lunch', 'Chicken Briyani', 60.00, '2026-02-16 04:11:16', 'active', 1, 0, 0),
(22, '2026-02-16', '08:01:00', '2026-02-17', '14:01:00', '2026-02-18', 'Lunch', 'Mushroom Briyani', 50.00, '2026-02-16 04:11:36', 'active', 1, 0, 0),
(23, '2026-04-10', '12:10:00', '2026-04-11', '12:10:00', '2026-04-11', 'Lunch', 'briyani', 88.00, '2026-04-10 06:40:34', 'active', 1, 0, 0),
(24, '2026-04-10', '14:32:00', '2026-04-12', '14:32:00', '2026-04-13', 'Snacks', 'chilly', 50.00, '2026-04-10 09:03:00', 'active', 1, 0, 0),
(25, '2026-04-10', '14:32:00', '2026-04-12', '14:32:00', '2026-04-13', 'Snacks', 'chilly', 50.00, '2026-04-10 09:03:04', 'active', 1, 0, 0),
(26, '2026-04-10', '14:32:00', '2026-04-12', '14:32:00', '2026-04-13', 'Snacks', 'chilly', 50.00, '2026-04-10 09:03:05', 'active', 1, 0, 0),
(27, '2026-04-10', '14:32:00', '2026-04-12', '14:32:00', '2026-04-13', 'Snacks', 'chilly', 50.00, '2026-04-10 09:03:05', 'active', 1, 0, 0),
(28, '2026-04-10', '14:32:00', '2026-04-12', '14:32:00', '2026-04-13', 'Snacks', 'chilly', 50.00, '2026-04-10 09:03:05', 'active', 1, 0, 0),
(29, '2026-04-10', '14:32:00', '2026-04-12', '14:32:00', '2026-04-13', 'Snacks', 'chilly', 50.00, '2026-04-10 09:03:05', 'active', 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `stay_in_hostel_requests`
--

CREATE TABLE `stay_in_hostel_requests` (
  `request_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `reason` text NOT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stay_in_hostel_requests`
--

INSERT INTO `stay_in_hostel_requests` (`request_id`, `student_id`, `from_date`, `to_date`, `reason`, `proof_path`, `requested_at`) VALUES
(1, 19, '2026-02-16', '2026-02-16', 'FEVER', NULL, '2026-02-16 04:29:56'),
(2, 21, '2026-02-16', '2026-02-16', 'Fever, Stomach pain', 'Student/proofs/stay_hostel/stay_21_69929da080e235_74122215.jpg', '2026-02-16 04:31:28');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `roll_number` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date_of_join` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `academic_batch_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `hostel_id` int(11) DEFAULT NULL,
  `fingerprint_id` varchar(50) DEFAULT NULL,
  `gender` enum('Male','Female','Others') DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `student_mobile_no` varchar(15) DEFAULT NULL,
  `aadhaar` varchar(20) DEFAULT NULL,
  `aadhaar_number` varchar(20) DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `admission_type` enum('Counseling','Management') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `academic_batch` varchar(20) DEFAULT NULL,
  `status` enum('1','0') DEFAULT '1',
  `join_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `photo_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `roll_number`, `name`, `date_of_join`, `email`, `phone`, `department`, `year_of_study`, `academic_batch_id`, `room_id`, `hostel_id`, `fingerprint_id`, `gender`, `language`, `student_mobile_no`, `aadhaar`, `aadhaar_number`, `blood_group`, `date_of_birth`, `admission_type`, `address`, `academic_batch`, `status`, `join_date`, `created_at`, `updated_at`, `photo_path`) VALUES
(15, 10, '927623BCS011', 'M S ARUN SANJEEV', '2023-08-13', 'msarunsanjeev@gmail.com', NULL, 'CSE', 3, NULL, 37, NULL, '23BCS011', 'Male', 'English', '9492633000', '660984149224', NULL, NULL, '2005-08-13', 'Counseling', NULL, '2023-2027', '1', NULL, '2026-02-16 03:57:01', '2026-02-16 03:57:01', NULL),
(16, 11, '927623BAD011', 'BALAMURUGAN S V', '2023-08-27', 'svbalasiva10@gmail.com', NULL, 'artificial_intelligence_data_science', 3, NULL, 37, NULL, '23BAD011', 'Male', 'English', '9751024841', '668833228520', NULL, NULL, '2005-10-17', 'Counseling', NULL, '2023-2027', '1', NULL, '2026-02-16 04:10:12', '2026-02-16 04:13:05', 'C:\\xampp\\htdocs\\HMS/Student/uploads/students/student_927623BAD011.jpg'),
(17, 12, '927623BCS046', 'KEERTHIHAA SRI P', '2023-08-31', 'keerthihaasri09@gmail.com', NULL, 'computer_science_engineering', 3, NULL, 52, NULL, '23BCS046', 'Female', 'English', '8675031039', '5077 1096 1312', NULL, NULL, '2005-11-18', 'Counseling', NULL, '2023-2027', '1', NULL, '2026-02-16 04:10:51', '2026-02-16 04:14:11', NULL),
(18, 13, '927623bit039', 'K Harish', '2026-02-16', 'harishkarmugilan@gmail.com', NULL, 'IT', 1, NULL, 37, NULL, '927623bit039', 'Male', 'Tamil', '8015718360', '811373370023', NULL, NULL, '2005-08-31', 'Management', NULL, '2023-2027', '1', NULL, '2026-02-16 04:11:31', '2026-02-16 04:11:31', NULL),
(19, 14, '927623BAD002', 'N.S.ABINA', '2023-08-18', 'abinasasikumar16@gmail.com', NULL, 'AIDS', 3, NULL, 53, NULL, '927623BAD002', 'Female', 'English', '9791721424', '456701230234', NULL, NULL, '2006-08-16', 'Counseling', NULL, '2023-2027', '1', NULL, '2026-02-16 04:16:16', '2026-02-16 04:16:16', NULL),
(20, 15, '927623bcs086', 'Rakshaa Bala B', '2023-08-14', 'rakshaabala@gmail.com', NULL, 'AIDS', 3, NULL, 52, NULL, '927623bcs086', 'Female', 'English', '9090909090', '5077 1096 1512', NULL, NULL, '2005-09-08', 'Counseling', NULL, '2023-2027', '1', NULL, '2026-02-16 04:17:28', '2026-02-16 04:17:28', NULL),
(21, 16, '927623BEC203', 'SHIBINAYAA S', '2023-08-10', 'shibinayaas@gmail.com', NULL, 'ECE', 3, NULL, 54, NULL, '927623BEC203', 'Female', 'Tamil', '8675031038', '700000001234', NULL, NULL, '2005-12-11', 'Counseling', NULL, '2023-2027', '1', NULL, '2026-02-16 04:17:30', '2026-02-16 04:17:30', NULL),
(22, 17, '927623BIT040', 'Hema S', '2023-09-01', 'hemasuthakar@gmail.com', NULL, 'information_technology', 3, NULL, 54, NULL, '927623BIT040', 'Female', 'English', '9894968575', '934587907836', NULL, NULL, '2026-02-21', 'Counseling', NULL, '2023-2027', '1', NULL, '2026-02-16 04:18:31', '2026-02-16 04:21:46', NULL),
(23, 18, '927623BIT027', 'Dharshini Priya A', '2023-09-01', 'dharshinipriya.a426@gmail.com', NULL, 'information_technology', 3, NULL, 55, NULL, '927623BIT027', 'Female', 'English', '9345844943', '553412834567', NULL, NULL, '2006-03-23', 'Counseling', NULL, '2023-2027', '1', NULL, '2026-02-16 04:22:05', '2026-02-16 04:31:50', NULL),
(24, 19, '927623BCB020', 'Karunya S', '2023-09-08', 'karunyasivakumar@gmail.com', NULL, 'CSBS', 3, NULL, 55, NULL, '927623BCB020', 'Female', 'Tamil', '9163827953', NULL, NULL, NULL, '2026-06-07', 'Counseling', NULL, '2023-2027', '1', NULL, '2026-02-16 04:28:44', '2026-02-16 04:28:46', NULL),
(25, 20, '927623BCB014', 'Indhuja K', '2023-09-01', 'indhuja@gmail.com', NULL, 'CSBS', 3, NULL, 55, NULL, '927623BCB014', 'Female', 'English', '9345678901', '456412834567', NULL, NULL, '2006-07-19', 'Counseling', NULL, '2023-2027', '1', NULL, '2026-02-16 04:31:00', '2026-02-16 04:31:00', NULL),
(26, 21, '927625bit011', 'kaviya', '2026-04-02', 'kaviya@gmail.com', NULL, 'IT', 1, NULL, 53, NULL, '25bit011', 'Female', 'English', '8675031038', '5077 1096 1512', NULL, NULL, '2006-07-08', 'Counseling', NULL, '2025-2029', '1', NULL, '2026-04-02 04:37:02', '2026-04-02 04:37:02', NULL),
(27, 22, '927623BCS049', 'Sdha', '2026-04-02', 'keerthihaasri09@gmail.com', NULL, 'AIDS', 2, NULL, 56, NULL, '23BCS049', 'Female', 'Tamil', '8675031038', '265874569856', NULL, NULL, '2002-04-02', 'Management', NULL, '2024-2028', '1', NULL, '2026-04-02 04:58:01', '2026-04-02 04:58:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `temporary_stay`
--

CREATE TABLE `temporary_stay` (
  `stay_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `temporary_stay`
--

INSERT INTO `temporary_stay` (`stay_id`, `student_id`, `from_date`, `to_date`, `reason`, `created_at`, `updated_at`) VALUES
(4, 21, '2026-02-02', '2026-03-31', NULL, '2026-02-16 04:17:30', '2026-02-16 04:17:30');

-- --------------------------------------------------------

--
-- Table structure for table `token_actions`
--

CREATE TABLE `token_actions` (
  `action_id` int(11) NOT NULL,
  `token_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `action_type` enum('Issued','Approved','Revoked') NOT NULL,
  `action_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin','male_admin','female_admin','faculty','mess_supervisor','biometrics') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin', 'admin', '2026-01-30 09:01:04', '2026-01-30 09:01:04'),
(2, 'mess', 'mess', 'mess_supervisor', '2026-01-30 09:01:04', '2026-01-30 09:01:04'),
(3, 'faculty', 'faculty', 'faculty', '2026-01-30 09:01:04', '2026-01-30 09:54:44'),
(7, 'biometrics', 'biometrics', 'biometrics', '2026-01-30 09:53:49', '2026-01-30 09:54:50'),
(8, '927624BCS011', '927624BCS011', 'student', '2026-01-31 10:42:32', '2026-01-31 10:42:32'),
(9, '927620TIH001', '927620TIH001', 'student', '2026-02-02 09:50:27', '2026-02-02 09:50:27'),
(10, '927623BCS011', '927623BCS011', 'student', '2026-02-16 03:57:01', '2026-02-16 03:57:01'),
(11, '927623BAD011', '927623BAD011', 'student', '2026-02-16 04:10:12', '2026-02-16 04:10:12'),
(12, '927623BCS046', '927623BCS046', 'student', '2026-02-16 04:10:51', '2026-02-16 04:10:51'),
(13, '927623bit039', '927623bit039', 'student', '2026-02-16 04:11:31', '2026-02-16 04:11:31'),
(14, '927623BAD002', '927623BAD002', 'student', '2026-02-16 04:16:16', '2026-02-16 04:16:16'),
(15, '927623bcs086', '927623bcs086', 'student', '2026-02-16 04:17:28', '2026-02-16 04:17:28'),
(16, '927623BEC203', '927623BEC203', 'student', '2026-02-16 04:17:30', '2026-02-16 04:17:30'),
(17, '927623BIT040', '927623BIT040', 'student', '2026-02-16 04:18:31', '2026-02-16 04:18:31'),
(18, '927623BIT027', '927623BIT027', 'student', '2026-02-16 04:22:05', '2026-02-16 04:22:05'),
(19, '927623BCB020', '927623BCB020', 'student', '2026-02-16 04:28:45', '2026-02-16 04:28:45'),
(20, '927623BCB014', '927623BCB014', 'student', '2026-02-16 04:31:00', '2026-02-16 04:31:00'),
(21, '927625bit011', '927625bit011', 'student', '2026-04-02 04:37:02', '2026-04-02 04:37:02'),
(22, '927623BCS049', '927623BCS049', 'student', '2026-04-02 04:58:01', '2026-04-02 04:58:01'),
(23, 'maleadmin', 'maleadmin', 'male_admin', '2026-04-10 04:33:55', '2026-04-10 04:33:55'),
(24, 'femaleadmin', 'femaleadmin', 'female_admin', '2026-04-10 04:33:55', '2026-04-10 04:33:55');

-- --------------------------------------------------------

--
-- Table structure for table `vacated_students_history`
--

CREATE TABLE `vacated_students_history` (
  `id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `room_number` varchar(10) DEFAULT NULL,
  `roll_number` varchar(50) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `vacated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hostel_id` int(11) DEFAULT NULL,
  `hostel_name` varchar(100) DEFAULT NULL,
  `student_name` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `academic_batch` varchar(50) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_batch`
--
ALTER TABLE `academic_batch`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `idx_attendance_date` (`date`),
  ADD KEY `idx_attendance_student` (`student_id`),
  ADD KEY `roll_number` (`roll_number`);

--
-- Indexes for table `attendance_time_control`
--
ALTER TABLE `attendance_time_control`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `biometric_machines`
--
ALTER TABLE `biometric_machines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `machine_ip` (`machine_ip`),
  ADD KEY `machine_type` (`machine_type`);

--
-- Indexes for table `blocked_students`
--
ALTER TABLE `blocked_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_attendance` (`attendance_id`);

--
-- Indexes for table `communication_preferences`
--
ALTER TABLE `communication_preferences`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `uk_faculty_email` (`email`),
  ADD UNIQUE KEY `uk_faculty_phone` (`phone`);

--
-- Indexes for table `gate_log`
--
ALTER TABLE `gate_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_gate_student` (`student_id`),
  ADD KEY `fk_gate_user` (`user_id`);

--
-- Indexes for table `general_leave`
--
ALTER TABLE `general_leave`
  ADD PRIMARY KEY (`GeneralLeave_ID`),
  ADD UNIQUE KEY `uk_general_leave_dates` (`From_Date`,`To_Date`);

--
-- Indexes for table `guardians`
--
ALTER TABLE `guardians`
  ADD PRIMARY KEY (`guardian_id`),
  ADD KEY `idx_guardian_student` (`student_id`);

--
-- Indexes for table `hostels`
--
ALTER TABLE `hostels`
  ADD PRIMARY KEY (`hostel_id`),
  ADD UNIQUE KEY `uk_hostel_code` (`hostel_code`);

--
-- Indexes for table `hostel_faculty`
--
ALTER TABLE `hostel_faculty`
  ADD PRIMARY KEY (`f_id`),
  ADD KEY `idx_hostel_id` (`hostel_id`),
  ADD KEY `idx_room_id` (`room_id`);

--
-- Indexes for table `ivr_calls`
--
ALTER TABLE `ivr_calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_leave_id` (`leave_id`),
  ADD KEY `idx_unique_id` (`unique_id`),
  ADD KEY `idx_call_status` (`call_status`);

--
-- Indexes for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD PRIMARY KEY (`Leave_ID`),
  ADD KEY `idx_leave_reg` (`Reg_No`),
  ADD KEY `idx_leave_type` (`LeaveType_ID`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`LeaveType_ID`),
  ADD UNIQUE KEY `uk_leave_type_name` (`Leave_Type_Name`);

--
-- Indexes for table `mess_menu`
--
ALTER TABLE `mess_menu`
  ADD PRIMARY KEY (`menu_id`),
  ADD UNIQUE KEY `uk_mess_menu_date_meal` (`date`,`meal_type`);

--
-- Indexes for table `mess_supervisors`
--
ALTER TABLE `mess_supervisors`
  ADD PRIMARY KEY (`supervisor_id`),
  ADD KEY `idx_supervisor_user` (`user_id`);

--
-- Indexes for table `mess_tokens`
--
ALTER TABLE `mess_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `idx_tokens_student` (`student_id`),
  ADD KEY `idx_tokens_menu` (`menu_id`),
  ADD KEY `idx_tokens_supervisor` (`supervisor_id`);

--
-- Indexes for table `mess_token_bills`
--
ALTER TABLE `mess_token_bills`
  ADD PRIMARY KEY (`bill_id`),
  ADD KEY `idx_bills_student` (`roll_number`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `uk_hostel_room` (`hostel_id`,`room_number`);

--
-- Indexes for table `room_students`
--
ALTER TABLE `room_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `specialtokenenable`
--
ALTER TABLE `specialtokenenable`
  ADD PRIMARY KEY (`menu_id`);

--
-- Indexes for table `stay_in_hostel_requests`
--
ALTER TABLE `stay_in_hostel_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `idx_stay_request_student` (`student_id`),
  ADD KEY `idx_stay_request_dates` (`from_date`,`to_date`),
  ADD KEY `idx_stay_request_requested_at` (`requested_at`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `uk_roll_number` (`roll_number`),
  ADD KEY `idx_student_hostel` (`hostel_id`),
  ADD KEY `idx_student_room` (`room_id`),
  ADD KEY `fk_academic_batch` (`academic_batch_id`);

--
-- Indexes for table `temporary_stay`
--
ALTER TABLE `temporary_stay`
  ADD PRIMARY KEY (`stay_id`),
  ADD KEY `idx_temp_stay_student` (`student_id`);

--
-- Indexes for table `token_actions`
--
ALTER TABLE `token_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `idx_token_actions_token` (`token_id`),
  ADD KEY `idx_token_actions_supervisor` (`supervisor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uk_username` (`username`);

--
-- Indexes for table `vacated_students_history`
--
ALTER TABLE `vacated_students_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vacated_roll` (`roll_number`),
  ADD KEY `idx_vacated_at` (`vacated_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_batch`
--
ALTER TABLE `academic_batch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `attendance_time_control`
--
ALTER TABLE `attendance_time_control`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `biometric_machines`
--
ALTER TABLE `biometric_machines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `blocked_students`
--
ALTER TABLE `blocked_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gate_log`
--
ALTER TABLE `gate_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `general_leave`
--
ALTER TABLE `general_leave`
  MODIFY `GeneralLeave_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `guardians`
--
ALTER TABLE `guardians`
  MODIFY `guardian_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `hostels`
--
ALTER TABLE `hostels`
  MODIFY `hostel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `hostel_faculty`
--
ALTER TABLE `hostel_faculty`
  MODIFY `f_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ivr_calls`
--
ALTER TABLE `ivr_calls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `Leave_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `LeaveType_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `mess_menu`
--
ALTER TABLE `mess_menu`
  MODIFY `menu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `mess_supervisors`
--
ALTER TABLE `mess_supervisors`
  MODIFY `supervisor_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mess_tokens`
--
ALTER TABLE `mess_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=195;

--
-- AUTO_INCREMENT for table `mess_token_bills`
--
ALTER TABLE `mess_token_bills`
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `room_students`
--
ALTER TABLE `room_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `specialtokenenable`
--
ALTER TABLE `specialtokenenable`
  MODIFY `menu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `stay_in_hostel_requests`
--
ALTER TABLE `stay_in_hostel_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `temporary_stay`
--
ALTER TABLE `temporary_stay`
  MODIFY `stay_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `token_actions`
--
ALTER TABLE `token_actions`
  MODIFY `action_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `vacated_students_history`
--
ALTER TABLE `vacated_students_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`roll_number`) REFERENCES `students` (`roll_number`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `blocked_students`
--
ALTER TABLE `blocked_students`
  ADD CONSTRAINT `blocked_students_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `blocked_students_ibfk_2` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`attendance_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `communication_preferences`
--
ALTER TABLE `communication_preferences`
  ADD CONSTRAINT `communication_preferences_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `guardians`
--
ALTER TABLE `guardians`
  ADD CONSTRAINT `guardians_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ivr_calls`
--
ALTER TABLE `ivr_calls`
  ADD CONSTRAINT `fk_ivr_leave` FOREIGN KEY (`leave_id`) REFERENCES `leave_applications` (`Leave_ID`) ON DELETE CASCADE;

--
-- Constraints for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD CONSTRAINT `leave_applications_ibfk_1` FOREIGN KEY (`LeaveType_ID`) REFERENCES `leave_types` (`LeaveType_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `leave_applications_ibfk_2` FOREIGN KEY (`Reg_No`) REFERENCES `students` (`roll_number`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mess_supervisors`
--
ALTER TABLE `mess_supervisors`
  ADD CONSTRAINT `mess_supervisors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mess_tokens`
--
ALTER TABLE `mess_tokens`
  ADD CONSTRAINT `mess_tokens_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`hostel_id`) REFERENCES `hostels` (`hostel_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `room_students`
--
ALTER TABLE `room_students`
  ADD CONSTRAINT `room_students_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `room_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stay_in_hostel_requests`
--
ALTER TABLE `stay_in_hostel_requests`
  ADD CONSTRAINT `fk_stay_request_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_academic_batch` FOREIGN KEY (`academic_batch_id`) REFERENCES `academic_batch` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`hostel_id`) REFERENCES `hostels` (`hostel_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `temporary_stay`
--
ALTER TABLE `temporary_stay`
  ADD CONSTRAINT `temporary_stay_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `token_actions`
--
ALTER TABLE `token_actions`
  ADD CONSTRAINT `token_actions_ibfk_1` FOREIGN KEY (`token_id`) REFERENCES `mess_tokens` (`token_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
