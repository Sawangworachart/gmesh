-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3304
-- Generation Time: Jan 05, 2026 at 04:05 AM
-- Server version: 8.0.44-0ubuntu0.24.04.1
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mesh`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customers_id` int NOT NULL,
  `customers_name` varchar(255) NOT NULL,
  `agency` varchar(255) NOT NULL,
  `contact_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `province` varchar(255) NOT NULL,
  `group_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customers_id`, `customers_name`, `agency`, `contact_name`, `phone`, `address`, `province`, `group_id`) VALUES
(8, 'กองสืบสวนคดีพิเศษ DSI', 'แผนก IT', 'K\'เปรียว', '0999999999', '128 ถนนแจ้งวัฒนะ แขวงทุ่งสองห้อง เขตหลักสี่ กรุงเทพมหานคร 10210.', 'กรุงเทพ', 1),
(9, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'การกำลัง', 'คุณประวิตร วงษ์สุวรรณ', '0898756465', '99 ถนนแจ้งวัฒนะ แขวงทุ่งสองห้อง เขตหลักสี่ กรุงเทพฯ 10210.', 'กรุงเทพ', 2),
(14, 'ศูนย์รถยนตร์carloft', 'it', 'คุณปุ้ย', '0999999999', '41/1 ถนนศรีนครินทร์ แขวงหนองบอน เขตประเวศ กรุงเทพมหานคร 10250', 'กรุงเทพมหานคร ', NULL),
(31, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'การกำลัง', 'พี่วิ', '0818875824', 'กำเเพงเพชร', 'กำเเพงเพชร', 2),
(36, 'Global Mesh', 'แอดมินเซอร์วิส', 'คุณสรายุทธ์ จันทร์แจ้ง', '091-754-7679', 'กรุงเทพฯ', 'กรุงเทพมหานคร', NULL),
(38, 'นาย พงษ์ศักดิ์ (การกำลัง)', 'การกำลัง', 'นาย พงษ์ศักดิ์', '086-012-1188', 'ลำพูน', 'ลำพูน', 3),
(39, 'นาย สภาพ (การกำลัง)', 'การกำลัง', 'นาย สภาพ', '089-556-3200', 'ลำปาง', 'ลำปาง', 4),
(40, 'นาย สันติภาพ จันทร์เรืองฤทธิ์ (รพ.ภูมิพล)', 'กองแพทยศาสตร์ศึกษา', 'นาย สันติภาพ จันทร์เรืองฤทธิ์', '097-169-4915', 'กรุงเทพมหานคร', 'กรุงเทพมหานคร', 5),
(41, 'นาย ไฉน (การกำลัง)', 'การกำลัง', 'นาย ไฉน', '090-520-3197', 'เชียงใหม่', 'เชียงใหม่', 6),
(42, 'กองสืบสวนคดีพิเศษ DSI', 'กรมสอบสวนคดีพิเศษ DSI', 'คุณพฤกษ์', '087-568-1474', '128 ถนนแจ้งวัฒนะ แขวงทุ่งสองห้อง เขตหลักสี่ กรุงเทพมหานคร 10210.', 'กรุงเทพมหานคร', 1),
(43, 'นาย วิทยา (การกำลัง)', 'การกำลัง', 'นาย วิทยา', '063-664-6282', 'ยะลา', 'ยะลา', 7),
(44, 'กองสืบสวนคดีพิเศษ DSI', 'DSI', 'พี่เปรียว DSI', '-', '128 ถนนแจ้งวัฒนะ แขวงทุ่งสองห้อง เขตหลักสี่ กรุงเทพมหานคร 10210.', 'กรุงเทพมหานคร', 1),
(45, 'นาย วัชระ อารยางค์กูร (การกำลัง)', 'การกำลัง', 'นาย วัชระ อารยางค์กูร', '063-640-4142', 'กรุงเทพมหานคร', 'กรุงเทพมหานคร', 8),
(46, 'กองสืบสวนคดีพิเศษ DSI', 'DSI', 'เจ้าหน้าที่ DSI', '-', 'ราชบุรี', 'ราชบุรี', 1),
(47, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'NT ยะลา', 'นาย วิทยา', '063-664-6282', 'ยะลา', 'ยะลา', 2),
(48, 'ร.ท.รัชพล (คลังแสง 3 ตาคลี)', 'คลังแสง 3 ตาคลี', 'ร.ท.รัชพล', '061-335-1490', 'นครสวรรค์', 'นครสวรรค์', 9),
(49, 'บอส (เจ้าหน้าที่กรมอุทยาน)', 'กรมอุทยาน', 'บอส', '092-969-1544', 'กรุงเทพมหานคร', 'กรุงเทพมหานคร', NULL),
(50, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'กรรมการเชียงใหม่งานแอร์ 42 แห่ง', 'พี่ไฉน NT', '090-520-3197', 'เชียงใหม่', 'เชียงใหม่', 2),
(51, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'คณะกรรมการ NT', 'พี่วิ', 'ติดต่อทางไลน์', 'กำแพงเพชร', 'กำแพงเพชร', 2),
(52, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'เจ้าหน้าที่ NT ภูเก็ต', 'พี่ทวีป', 'ติดต่อทางไลน์', 'ภูเก็ต', 'ภูเก็ต', 2),
(53, 'อุทยานแห่งชาติเขลางค์บรรพต', 'ฝ่ายเตรียมการอุทยาน', 'นายกัณฑ์อเนก คำธัญวงษ์', '085-694-6501', 'ลำปาง', 'ลำปาง', NULL),
(54, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'คณะกรรมการงานแอร์ 42 แห่ง', 'พี่เกษม', '090-519-2978', 'แพร่', 'แพร่', 2),
(55, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'NT แพร่', 'พี่เกษม NT แพร่', '090-519-2978', 'แพร่', 'แพร่', 2),
(56, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'คณะกรรมการ NT เชียงใหม่', 'พี่ไฉน', '090-520-3197', 'เชียงใหม่', 'เชียงใหม่', 2),
(57, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'คณะกรรมการ NT ตาก', 'พี่หนุ่ม', '065-242-9247', 'ตาก', 'ตาก', 2),
(58, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'คณะกรรมการ NT', 'พี่วิ', 'ติดต่อทางไลน์', 'กำแพงเพชร', 'กำแพงเพชร', 2),
(59, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'คณะกรรมการ NT เชียงใหม่', 'พี่ไฉน', '090-520-3197', 'เชียงใหม่', 'เชียงใหม่', 2),
(60, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'คณะกรรมการ NT ตาก', 'พี่หนุ่ม', '065-242-9247', 'ตาก', 'ตาก', 2),
(61, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'คณะกรรมการ NT', 'พี่วิ', 'ติดต่อทางไลน์', 'กำแพงเพชร', 'กำแพงเพชร', 2),
(62, 'นาย พงษ์ศักดิ์ (การกำลัง)', 'การกำลัง', 'นาย พงษ์ศักดิ์', '086-012-1188', 'ลำพูน', 'ลำพูน', 3),
(63, 'คุณสรายุทธ์ จันทร์แจ้ง (Global Mesh)', 'แอดมินเซอร์วิส', 'คุณสรายุทธ์ จันทร์แจ้ง', '091-754-7679', 'กรุงเทพฯ', 'กรุงเทพมหานคร', NULL),
(64, 'นาย สภาพ (การกำลัง)', 'การกำลัง', 'นาย สภาพ', '089-556-3200', 'ลำปาง', 'ลำปาง', 4),
(65, 'นาย สันติภาพ จันทร์เรืองฤทธิ์ (รพ.ภูมิพล)', 'กองแพทยศาสตร์ศึกษา', 'นาย สันติภาพ จันทร์เรืองฤทธิ์', '097-169-4915', 'กรุงเทพมหานคร', 'กรุงเทพมหานคร', 5),
(66, 'นาย ไฉน (การกำลัง)', 'การกำลัง', 'นาย ไฉน', '090-520-3197', 'เชียงใหม่', 'เชียงใหม่', 6),
(67, 'คุณพฤกษ์ (DSI)', 'กรมสอบสวนคดีพิเศษ DSI', 'คุณพฤกษ์', '087-568-1474', 'กรุงเทพมหานคร', 'กรุงเทพมหานคร', NULL),
(68, 'นาย วิทยา (การกำลัง)', 'การกำลัง', 'นาย วิทยา', '063-664-6282', 'ยะลา', 'ยะลา', 7),
(69, 'พี่เปรียว (DSI)', 'DSI', 'พี่เปรียว', '-', 'กรุงเทพมหานคร', 'กรุงเทพมหานคร', NULL),
(70, 'นาย วัชระ อารยางค์กูร (การกำลัง)', 'การกำลัง', 'นาย วัชระ อารยางค์กูร', '063-640-4142', 'กรุงเทพมหานคร', 'กรุงเทพมหานคร', 8),
(71, 'เจ้าหน้าที่ DSI (ราชบุรี)', 'DSI', 'เจ้าหน้าที่ DSI', '-', 'ราชบุรี', 'ราชบุรี', NULL),
(72, 'นาย วิทยา (NT ยะลา)', 'NT ยะลา', 'นาย วิทยา', '063-664-6282', 'ยะลา', 'ยะลา', NULL),
(73, 'ร.ท.รัชพล (คลังแสง 3 ตาคลี)', 'คลังแสง 3 ตาคลี', 'ร.ท.รัชพล', '061-335-1490', 'นครสวรรค์', 'นครสวรรค์', 9),
(74, 'บอส (กรมอุทยาน)', 'กรมอุทยาน', 'บอส', '092-969-1544', 'กรุงเทพมหานคร', 'กรุงเทพมหานคร', NULL),
(75, 'พี่ไฉน NT (งานแอร์ 42 แห่ง)', 'NT', 'พี่ไฉน NT', '090-520-3197', 'เชียงใหม่', 'เชียงใหม่', NULL),
(76, 'พี่วิ (กำแพงเพชร)', 'คณะกรรมการ NT', 'พี่วิ', 'ติดต่อทางไลน์', 'กำแพงเพชร', 'กำแพงเพชร', NULL),
(77, 'พี่ทวีป (NT ภูเก็ต)', 'บริษัท โทรคมนาคม แห่งชาติ', 'พี่ทวีป', 'ติดต่อทางไลน์', 'ภูเก็ต', 'ภูเก็ต', NULL),
(78, 'นายกัณฑ์อเนก คำธัญวงษ์ (อุทยานแห่งชาติเขลางค์บรรพต)', 'อุทยานแห่งชาติเขลางค์บรรพต', 'นายกัณฑ์อเนก คำธัญวงษ์', '085-694-6501', 'ลำปาง', 'ลำปาง', NULL),
(79, 'กองสืบสวนคดีพิเศษ DSI', '', 'tthrthr', 'rthrhrh', '', 'rthrhtrh', 1);

-- --------------------------------------------------------

--
-- Table structure for table `customer_groups`
--

CREATE TABLE `customer_groups` (
  `group_id` int NOT NULL,
  `group_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customer_groups`
--

INSERT INTO `customer_groups` (`group_id`, `group_name`) VALUES
(1, 'กองสืบสวนคดีพิเศษ DSI'),
(2, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)'),
(3, 'นาย พงษ์ศักดิ์ (การกำลัง)'),
(4, 'นาย สภาพ (การกำลัง)'),
(5, 'นาย สันติภาพ จันทร์เรืองฤทธิ์ (รพ.ภูมิพล)'),
(6, 'นาย ไฉน (การกำลัง)'),
(7, 'นาย วิทยา (การกำลัง)'),
(8, 'นาย วัชระ อารยางค์กูร (การกำลัง)'),
(9, 'ร.ท.รัชพล (คลังแสง 3 ตาคลี)'),
(16, 'กองสืบสวนคดีพิเศษ DSI'),
(17, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)'),
(18, 'นาย พงษ์ศักดิ์ (การกำลัง)'),
(19, 'นาย สภาพ (การกำลัง)'),
(20, 'นาย สันติภาพ จันทร์เรืองฤทธิ์ (รพ.ภูมิพล)'),
(21, 'นาย ไฉน (การกำลัง)'),
(22, 'นาย วิทยา (การกำลัง)'),
(23, 'นาย วัชระ อารยางค์กูร (การกำลัง)'),
(24, 'ร.ท.รัชพล (คลังแสง 3 ตาคลี)');

-- --------------------------------------------------------

--
-- Table structure for table `ma_schedule`
--

CREATE TABLE `ma_schedule` (
  `ma_id` int NOT NULL,
  `pmproject_id` int NOT NULL,
  `ma_date` date NOT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ma_schedule`
--

INSERT INTO `ma_schedule` (`ma_id`, `pmproject_id`, `ma_date`, `note`, `remark`, `file_path`) VALUES
(13, 31, '2025-07-27', 'MA ทุก 5 เดือน #1', NULL, NULL),
(14, 31, '2025-12-15', 'MA ทุก 5 เดือน #2', NULL, NULL),
(15, 31, '2026-05-27', 'MA ทุก 5 เดือน #3', NULL, NULL),
(16, 31, '2026-10-27', 'MA ทุก 5 เดือน #4', NULL, NULL),
(17, 31, '2027-03-27', 'MA ทุก 5 เดือน #5', NULL, NULL),
(18, 31, '2027-08-27', 'MA ทุก 5 เดือน #6', NULL, NULL),
(19, 31, '2028-01-27', 'MA ทุก 5 เดือน #7', NULL, NULL),
(20, 31, '2028-06-27', 'MA ทุก 5 เดือน #8', NULL, NULL),
(21, 31, '2028-11-27', 'MA ทุก 5 เดือน #9', NULL, NULL),
(22, 31, '2029-04-27', 'MA ทุก 5 เดือน #10', NULL, NULL),
(23, 31, '2029-09-27', 'MA ทุก 5 เดือน #11', NULL, NULL),
(24, 31, '2030-02-27', 'MA ทุก 5 เดือน #12', NULL, NULL),
(98, 32, '2024-07-01', 'MA ครั้งที่ 1', NULL, NULL),
(99, 32, '2025-01-01', 'MA ครั้งที่ 2', NULL, NULL),
(100, 32, '2025-07-01', 'MA ครั้งที่ 3', NULL, NULL),
(101, 32, '2026-01-01', 'MA ครั้งที่ 4', NULL, NULL),
(130, 34, '2026-09-08', 'MA ครั้งที่ 1', NULL, NULL),
(131, 34, '2027-09-08', 'MA ครั้งที่ 2 ตรวจสอบเเบต', NULL, NULL),
(132, 34, '2028-09-08', 'MA ครั้งที่ 3', NULL, NULL),
(133, 34, '2029-09-08', 'MA ครั้งที่ 4', NULL, NULL),
(134, 34, '2030-09-08', 'MA ครั้งที่ 5', NULL, NULL),
(135, 33, '2024-08-15', 'MA ทุก 6 เดือน #1', NULL, NULL),
(136, 33, '2025-02-15', 'MA ทุก 6 เดือน #2', NULL, NULL),
(137, 33, '2025-08-15', 'MA ทุก 6 เดือน #3', NULL, NULL),
(138, 33, '2026-02-15', 'MA ทุก 6 เดือน #4', NULL, NULL),
(139, 33, '2026-08-15', 'MA ทุก 6 เดือน #5', NULL, NULL),
(140, 33, '2027-02-15', 'MA ทุก 6 เดือน #6', NULL, NULL),
(141, 33, '2027-08-15', 'MA ทุก 6 เดือน #7', NULL, NULL),
(142, 33, '2028-02-15', 'MA ทุก 6 เดือน #8', NULL, NULL),
(143, 33, '2028-08-15', 'MA ทุก 6 เดือน #9', NULL, NULL),
(144, 33, '2029-02-15', 'MA ทุก 6 เดือน #10', NULL, NULL),
(157, 49, '2025-01-24', 'MA ครั้งที่ 1', NULL, NULL),
(158, 49, '2025-02-24', 'MA ครั้งที่ 2', NULL, NULL),
(159, 49, '2025-03-24', 'MA ครั้งที่ 3', NULL, NULL),
(160, 49, '2025-04-24', 'MA ครั้งที่ 4', NULL, NULL),
(161, 49, '2025-05-24', 'MA ครั้งที่ 5', NULL, NULL),
(162, 49, '2025-06-24', 'MA ครั้งที่ 6', NULL, NULL),
(163, 49, '2025-07-24', 'MA ครั้งที่ 7', NULL, NULL),
(164, 49, '2025-08-24', 'MA ครั้งที่ 8', NULL, NULL),
(165, 49, '2025-09-24', 'MA ครั้งที่ 9', NULL, NULL),
(166, 49, '2025-10-24', 'MA ครั้งที่ 10', NULL, NULL),
(167, 49, '2025-11-24', 'MA ครั้งที่ 11', NULL, NULL),
(168, 49, '2025-12-24', 'MA ครั้งที่ 12', NULL, NULL),
(169, 50, '2025-08-22', 'MA ครั้งที่ 1', NULL, NULL),
(170, 50, '2026-02-22', 'MA ครั้งที่ 2', NULL, NULL),
(195, 51, '2025-06-27', 'MA ครั้งที่ 1', 'สวัสดีปีใหม่', 'uploads/ma/ma_51_0_1766655638.jpg'),
(196, 51, '2025-12-27', 'MA ครั้งที่ 2', 'l;yfu;yo0yomiN', 'uploads/ma/ma_51_1_1766655675.png');

-- --------------------------------------------------------

--
-- Table structure for table `ma_schedule_files`
--

CREATE TABLE `ma_schedule_files` (
  `file_id` int NOT NULL,
  `ma_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_project`
--

CREATE TABLE `pm_project` (
  `pmproject_id` int NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `customers_id` int NOT NULL,
  `responsible_person` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `number` varchar(255) NOT NULL,
  `contract_period` varchar(255) NOT NULL,
  `going_ma` varchar(1500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `deliver_work_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pm_project`
--

INSERT INTO `pm_project` (`pmproject_id`, `project_name`, `customers_id`, `responsible_person`, `status`, `number`, `contract_period`, `going_ma`, `file_path`, `deliver_work_date`, `end_date`) VALUES
(5, 'งานจ้างเหมาติดตั้ง MAIN BATTERY ชนิด Lithium Iron จำนวน 39 แห่ง พื้นที่กลุ่มขายและปฏิบัติการลูกค้าภาคใต้', 9, '-', 'In Progress', 'PJ6602001', '5 ปี', 'เข้า MA ทุก 6 เดือน TOR ขอบเขตงานหน้า 15 PDF', NULL, '2023-06-14', '2028-06-14'),
(6, 'โครงการจัดซื้ออุปกรณ์สนับสนุนปฏิบัติหน้าที่ DSI', 8, 'พี่นิ้ง', 'In Progress', 'PJ6603005', '4 ปี', '(เข้า MA ทุก 4 เดือน) เข้ารอบแรก เขต 5,6 เขต 1,2,3,4,7 และ 8,9,10 เข้าปีหน้า 68', NULL, '2024-04-08', '2029-04-07'),
(7, 'งานจ้างเหมาติดตั้ง Battery จำนวน 13แห่ง พร้อมรื้อถอน', 9, 'พี่แต๋ม', 'In Progress', 'PJ6510001', '5 ปี', 'เข้า MA ทุก 6 เดือน', NULL, '2023-06-30', '2028-06-30'),
(8, 'งานจ้างเหมาติดตั้ง Battery จำนวน 18 ชุมสาย พื้นที่ บน.3.3', 9, 'พี่แต๋ม', 'In Progress', 'PJ6604002', '5 ปี', 'เข้า MA ทุก 6 เดือน (ปรับจาก MA ทุก 6 เดือน/ครั้ง เป็น MA ทุก 5 เดือน/ครั้ง เนื่องจากเข้าไป MA ล่าช้า)', NULL, '2024-05-04', '2029-05-04'),
(9, 'งานจ้างเหมาติดตั้ง Rectifier จำนวน 7 ชุมสาย พื้นที่ บน.3.3', 9, '-', 'In Progress', 'PJ6604004', '2 ปี', 'MA ทุก 6 เดือน (มีปรับรอบ MA เร็วขึ้น เนื่องจากเข้าไป MA ล่าช้า)', NULL, '2024-05-04', '2026-05-04'),
(10, 'รับประกันงานแบต 62 ชุมสาย', 9, '-', 'In Progress', 'PJ6604003', '5 ปี', 'เข้า MA ทุก 6 เดือน TOR ขอบเขตงานหน้า 15 PDF (ปรับจาก MA ทุก 6 เดือน/ครั้ง เป็น MA ทุก 5 เดือน/ครั้ง เนื่องจากเข้าไป MA ล่าช้า)', NULL, '2023-10-31', '2028-10-31'),
(11, 'งานสัญญาซื้อขายและติดตั้งแบตเตอรี่เครื่องไฟฟ้าสํารอง (UPS) และเครื่องปรับอากาศ ศูนย์คอมพิวเตอร์หลัก SME', 9, '-', 'Completed', 'PJ6703008', '1 ปี', '3 เดือน/1ครั้ง 1ปี', NULL, '2024-12-02', '2025-12-01'),
(12, 'OSS การยาง', 9, '-', 'In Progress', 'PJ6308005', '1 ปี', 'เข้า MA ทุก 3 เดือน เฉพาะตัวเซิฟเวอร์ ติดต่อคุณตั้ม 095-442-4519 (มีการปรับรอบไป MA เร็วขึ้น เนื่องจาก MA ล่าช้า)', NULL, '2024-07-08', '2025-07-08'),
(13, 'งานจ้างเหมาติดตั้ง Main Battery จำนวน 51 แห่ง และงานจ้างเหมาติดตั้ง Switchmode Rectifier จำนวน 18 แห่ง ในพื้นที่ ภน.1 (เฉพาะRectifier)', 9, '-', 'In Progress', 'PJ6406004', 'MA 2 ปี รับประกัน 5 ปี', 'รับประกันไม่มี MA, MA เฉพาะ Rectifier เริ่ม MA 1 เมษา 67 ( รับประกัน Main Battery 5 ปี, MA Switchmode Rectifier 2 ปี,MA ทุก 6 เดือน )', NULL, '2023-08-04', '2025-08-04'),
(14, 'งานจ้างเหมาติดตั้ง Main Battery พื้นที่ กลุ่มขายและปฏิบัติการลูกค้าภาคเหนือ จำนวน 35 แห่ง', 9, '-', 'In Progress', '6505001', '5ปี', 'การ MA, MA 1 ครั้งก่อนหมดสัญญา', NULL, '2022-11-24', '2027-11-24'),
(15, 'โครงการปรับปรุงระบบสายส่งไฟฟ้าลงใต้ดินท่าอากาศยานหัวหิน', 9, '-', 'In Progress', 'PJ6603016', '4 ปี', '3 เดือน/1ครั้ง 1ปี', NULL, '2024-06-07', '2028-06-07'),
(16, 'โครงการซ่อมแซมท่อน้ำประปาหลักอาคารกรมทรัพยากรน้ำ', 9, '-', 'In Progress', 'PJ6706004', '1 ปี', 'รอใบรับรองของพี่เปิ้ล 6 มิถุนายน', NULL, '2024-08-26', '2025-08-26'),
(17, 'งานล้างแอร์ 42 แห่ง', 9, 'พี่หวาน', 'In Progress', 'PJ6607003', '2 ปี', 'รับประกันสินค้า', NULL, '2024-04-11', '2026-04-11'),
(18, 'โครงการ ฟูกที่นอน กรมอุทยาน (120วัน)', 9, '-', 'In Progress', 'PJ6603020', '7 ปี', 'ไม่มี MA รับประกัน', NULL, '2024-12-06', '2031-12-06'),
(19, 'โครงการจัดซื้อเครื่อง Scanner 152 เครื่อง และเครื่องปรับอากาศ ศูนย์คอมพิวเตอร์หลัก', 9, '-', 'In Progress', 'PJ6703006', '3 ปี', 'ไม่มี MA รับประกัน 3 ปี', NULL, '2024-11-02', '2027-11-02'),
(20, 'โครงการปรับปรุงห้องทำงานส่วนอาคารสถานที่และยานพาหนะ กรมทรัพยากรน้ำ', 9, '-', 'In Progress', 'PJ6709005', '1 ปี', 'รับประกันสินค้า', NULL, '2024-12-19', '2025-12-19'),
(21, 'โครงการจ้างปรับปรุงห้องทำงานส่วนช่วยอำนวยการ สำนักงานเลขานุการกรมทรัพยากรน้ำ ชั้น 6', 9, '-', 'In Progress', 'PJ6709004', '1 ปี', 'รับประกันสินค้า', NULL, '2024-12-19', '2025-12-19'),
(22, 'โครงการจ้างเหมาติดตั้ง Rectifier ขนาด 300A จำนวน 5 แห่ง', 9, 'BU1 (คุณน้ำหวาน)', 'In Progress', 'PJ6702003', '2 ปี', 'MA 6 เดือน/ครั้ง', NULL, '2025-03-07', '2027-03-07'),
(23, 'งานจ้างเหมาติดตั้ง Battery Lithium จำนวน 10 แห่ง', 9, 'BU1 (คุณน้ำหวาน)', 'In Progress', 'PJ6702002', '5 ปี', 'MA 6 เดือน/ครั้ง', NULL, '2025-03-06', '2030-03-06'),
(24, 'โครงการซื้ออุปกรณ์ ATA', 9, 'BU7', 'In Progress', 'PJ6711005', '2 ปี', 'รับประกัน 2 ปี ไม่มี MA', NULL, '2025-05-17', '2027-05-16'),
(25, 'งาน Rectifier 20 ชุมสาย (พื้นที่นครหลวงที่ 3.2)', 9, 'BU7', 'In Progress', 'PJ6711003', '2 ปี', 'เข้า MA ทุก 6 เดือน', NULL, '2025-06-09', '2027-06-09'),
(26, 'งานเช่าคอม 645 ชุด', 9, 'BU6', 'In Progress', 'PJ6705008', '3 ปี', 'ไม่มี MA', NULL, '2025-05-01', '2028-04-30'),
(27, 'งานโดรน', 9, 'BU5', 'In Progress', 'PJ6610010', '1 ปี', 'รับประกัน 1 ปี ไม่มี MA', NULL, '2025-07-14', '2026-07-14'),
(28, 'โครงการจัดซื้อกล้องถ่ายภาพอัตโนมัติ (NCAPS)', 9, 'BU5', 'In Progress', 'PJ6607002', '6 เดือน', 'ไม่มี MA', NULL, '2025-01-21', '2025-07-21'),
(29, 'งานโครงการกล้อง กรมทะเล', 9, 'BU5', 'In Progress', 'PJ6708003', '1 ปี', 'รับประกัน 1 ปี ไม่มี MA', NULL, '2024-12-27', '2025-12-27'),
(30, 'งาน CCTV บน.4', 9, 'BU3', 'In Progress', 'PJ6705001', '2 ปี', 'รับประกัน 2 ปี ไม่มี MA', NULL, '2025-04-28', '2027-04-28'),
(31, 'งานจัดซื้อแบตเตอรี่ ชนิดลิเที่ยมฟอตเฟต ขนาด48V 1000Ah 4ชุด', 9, 'BU2', 'In Progress', 'PJ6707013', '5 ปี', 'เข้า MA ทุก 6 เดือน', 'uploads/proj_31_1765960026.sql', '2025-02-27', '2030-02-27'),
(32, 'ครุภัณฑ์สายสื่อสาร (คอมพิวเตอร์) กองทัพไทย', 9, 'BU3', 'In Progress', 'PJ6708009', '2 ปี และ 3 ปี', 'รับประกัน ไม่มี MA', NULL, '2024-01-01', '2026-01-01'),
(33, 'จ้างเหมาติดตั้ง Main Battery พื้นที่ ภน.1 จำนวน 14 แห่ง', 9, 'BU1', 'In Progress', 'PJ6605002', '5 ปี', 'เข้า MA 1 ครั้ง ก่อนหมดสัญญา', 'uploads/proj_33_1766386014.jpg', '2024-02-15', '2029-02-15'),
(34, 'ซื้อแบตเตอรี่ลิเทียมขนาด 48V/100 Ah และ Rectifier พร้อมการติดตั้ง สำหรับ สค.ในพื้นที่ ภน.1', 9, '-', 'In Progress', 'PJ6711008', 'แบต 5 ปี /Rectifier 2 ปี', 'รับประกัน ไม่มี MA1', 'uploads/proj_34_1766129867.sql', '2025-09-08', '2030-09-08'),
(49, 'ทดสอบA', 31, 'ผู้ทดสอบ', 'In Progress', '123456', '1 ปี', 'ทดสอบ 2 วัน', 'uploads/proj_49_1766388618.crdownload', '2024-12-24', '2025-12-24'),
(50, 'ทดสอบB', 31, 'ผู้ทดสอบ', 'In Progress', '1234567', '1 ปี', 'ทดสอบB', NULL, '2025-02-22', '2026-02-22'),
(51, 'ทดสอบC', 31, 'ผู้ทดสอบC', 'Pending', '้123456789', '1 ปี', 'ทดสอบ5วัน', NULL, '2024-12-27', '2025-12-27');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int NOT NULL,
  `customers_id` int NOT NULL,
  `address` varchar(255) NOT NULL,
  `repair_details` varchar(255) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `device_name` varchar(255) NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_project`
--

CREATE TABLE `service_project` (
  `service_id` int NOT NULL,
  `project_name` varchar(255) NOT NULL COMMENT 'ชื่อโครงการ',
  `customers_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `service_project`
--

INSERT INTO `service_project` (`service_id`, `project_name`, `customers_id`) VALUES
(1, 'dsi', 8);

-- --------------------------------------------------------

--
-- Table structure for table `service_project_detail`
--

CREATE TABLE `service_project_detail` (
  `detail_id` int NOT NULL,
  `service_id` int NOT NULL,
  `equipment` varchar(255) NOT NULL,
  `symptom` text NOT NULL,
  `action_taken` text,
  `status` varchar(50) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user' COMMENT 'สิทธิ์ผู้ใช้งาน',
  `status` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `role`, `status`) VALUES
(10, 'admin', '$2y$10$oi.v9v5SHkF2EJZVDc4dUuS1i24NCsQCAzMUoYjpWDt0SO08A.006', 'admin', 1),
(11, 'user01', '$2y$10$oi.v9v5SHkF2EJZVDc4dUuS1i24NCsQCAzMUoYjpWDt0SO08A.006', 'user', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customers_id`),
  ADD KEY `fk_customer_group` (`group_id`);

--
-- Indexes for table `customer_groups`
--
ALTER TABLE `customer_groups`
  ADD PRIMARY KEY (`group_id`);

--
-- Indexes for table `ma_schedule`
--
ALTER TABLE `ma_schedule`
  ADD PRIMARY KEY (`ma_id`),
  ADD KEY `pmproject_id` (`pmproject_id`);

--
-- Indexes for table `ma_schedule_files`
--
ALTER TABLE `ma_schedule_files`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `ma_id` (`ma_id`);

--
-- Indexes for table `pm_project`
--
ALTER TABLE `pm_project`
  ADD PRIMARY KEY (`pmproject_id`),
  ADD KEY `k2` (`customers_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `k4` (`customers_id`);

--
-- Indexes for table `service_project`
--
ALTER TABLE `service_project`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `idx_customer` (`customers_id`);

--
-- Indexes for table `service_project_detail`
--
ALTER TABLE `service_project_detail`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `idx_service` (`service_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customers_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `customer_groups`
--
ALTER TABLE `customer_groups`
  MODIFY `group_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `ma_schedule`
--
ALTER TABLE `ma_schedule`
  MODIFY `ma_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=197;

--
-- AUTO_INCREMENT for table `ma_schedule_files`
--
ALTER TABLE `ma_schedule_files`
  MODIFY `file_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_project`
--
ALTER TABLE `pm_project`
  MODIFY `pmproject_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `service_project`
--
ALTER TABLE `service_project`
  MODIFY `service_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `service_project_detail`
--
ALTER TABLE `service_project_detail`
  MODIFY `detail_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customer_group` FOREIGN KEY (`group_id`) REFERENCES `customer_groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `ma_schedule`
--
ALTER TABLE `ma_schedule`
  ADD CONSTRAINT `fk_ma_project` FOREIGN KEY (`pmproject_id`) REFERENCES `pm_project` (`pmproject_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ma_schedule_files`
--
ALTER TABLE `ma_schedule_files`
  ADD CONSTRAINT `fk_ma_files` FOREIGN KEY (`ma_id`) REFERENCES `ma_schedule` (`ma_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pm_project`
--
ALTER TABLE `pm_project`
  ADD CONSTRAINT `k2` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`customers_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `k4` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`customers_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `service_project`
--
ALTER TABLE `service_project`
  ADD CONSTRAINT `fk_service_customer` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`customers_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `service_project_detail`
--
ALTER TABLE `service_project_detail`
  ADD CONSTRAINT `fk_detail_service` FOREIGN KEY (`service_id`) REFERENCES `service_project` (`service_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
