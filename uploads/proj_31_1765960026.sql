-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3304
-- Generation Time: Dec 17, 2025 at 03:28 AM
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
  `province` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customers_id`, `customers_name`, `agency`, `contact_name`, `phone`, `address`, `province`) VALUES
(8, 'กองสืบสวนคดีพิเศษ DSI', 'แผนก IT', 'K\'เปรียว', '0999999999', '128 ถนนแจ้งวัฒนะ แขวงทุ่งสองห้อง เขตหลักสี่ กรุงเทพมหานคร 10210.', 'กรุงเทพ'),
(9, 'บริษัท โทรคมนาคมแห่งชาติ จำกัด (มหาชน)', 'การกำลัง', 'คุณประวิตร วงษ์สุวรรณ', '0898756465', '99 ถนนแจ้งวัฒนะ แขวงทุ่งสองห้อง เขตหลักสี่ กรุงเทพฯ 10210.', 'กรุงเทพ'),
(14, 'ศูนย์รถยนตร์carloft', 'it', 'คุณปุ้ย', '0999999999', '41/1 ถนนศรีนครินทร์ แขวงหนองบอน เขตประเวศ กรุงเทพมหานคร 10250', 'กรุงเทพมหานคร '),
(31, 'NT', 'การกำลัง', 'พี่วิ', '0818875824', 'กำเเพงเพชร', 'กำเเพงเพชร');

-- --------------------------------------------------------

--
-- Table structure for table `ma_schedule`
--

CREATE TABLE `ma_schedule` (
  `ma_id` int NOT NULL,
  `pmproject_id` int NOT NULL,
  `ma_date` date NOT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ma_schedule`
--

INSERT INTO `ma_schedule` (`ma_id`, `pmproject_id`, `ma_date`, `note`) VALUES
(3, 33, '2024-08-15', 'MA ทุก 6 เดือน #1'),
(4, 33, '2025-02-15', 'MA ทุก 6 เดือน #2'),
(5, 33, '2025-08-15', 'MA ทุก 6 เดือน #3'),
(6, 33, '2026-02-15', 'MA ทุก 6 เดือน #4'),
(7, 33, '2026-08-15', 'MA ทุก 6 เดือน #5'),
(8, 33, '2027-02-15', 'MA ทุก 6 เดือน #6'),
(9, 33, '2027-08-15', 'MA ทุก 6 เดือน #7'),
(10, 33, '2028-02-15', 'MA ทุก 6 เดือน #8'),
(11, 33, '2028-08-15', 'MA ทุก 6 เดือน #9'),
(12, 33, '2029-02-15', 'MA ทุก 6 เดือน #10'),
(13, 31, '2025-07-27', 'MA ทุก 5 เดือน #1'),
(14, 31, '2025-12-15', 'MA ทุก 5 เดือน #2'),
(15, 31, '2026-05-27', 'MA ทุก 5 เดือน #3'),
(16, 31, '2026-10-27', 'MA ทุก 5 เดือน #4'),
(17, 31, '2027-03-27', 'MA ทุก 5 เดือน #5'),
(18, 31, '2027-08-27', 'MA ทุก 5 เดือน #6'),
(19, 31, '2028-01-27', 'MA ทุก 5 เดือน #7'),
(20, 31, '2028-06-27', 'MA ทุก 5 เดือน #8'),
(21, 31, '2028-11-27', 'MA ทุก 5 เดือน #9'),
(22, 31, '2029-04-27', 'MA ทุก 5 เดือน #10'),
(23, 31, '2029-09-27', 'MA ทุก 5 เดือน #11'),
(24, 31, '2030-02-27', 'MA ทุก 5 เดือน #12'),
(25, 36, '2025-06-12', 'MA ทุก 6 เดือน #1'),
(26, 36, '2025-12-15', 'MA ทุก 6 เดือน #2'),
(46, 39, '2025-03-15', 'MA รอบที่ 1 (ทุก 3 เดือน)'),
(47, 39, '2025-06-15', 'MA รอบที่ 2 (ทุก 3 เดือน)'),
(48, 39, '2025-09-15', 'MA รอบที่ 3 (ทุก 3 เดือน)'),
(49, 39, '2025-12-15', 'MA รอบที่ 4 (ทุก 3 เดือน)'),
(50, 40, '2025-01-16', 'MA ครั้งที่ 1'),
(51, 40, '2025-02-16', 'MA ครั้งที่ 2'),
(52, 40, '2025-03-16', 'MA ครั้งที่ 3'),
(53, 40, '2025-04-16', 'MA ครั้งที่ 4'),
(54, 40, '2025-05-16', 'MA ครั้งที่ 5'),
(55, 40, '2025-06-16', 'MA ครั้งที่ 6'),
(56, 40, '2025-07-16', 'MA ครั้งที่ 7'),
(57, 40, '2025-08-16', 'MA ครั้งที่ 8'),
(58, 40, '2025-09-16', 'MA ครั้งที่ 9'),
(59, 40, '2025-10-16', 'MA ครั้งที่ 10'),
(60, 40, '2025-11-16', 'MA ครั้งที่ 11'),
(61, 40, '2025-12-16', 'MA ครั้งที่ 12'),
(74, 42, '2025-01-18', 'MA ครั้งที่ 1'),
(75, 42, '2025-02-18', 'MA ครั้งที่ 2'),
(76, 42, '2025-03-18', 'MA ครั้งที่ 3'),
(77, 42, '2025-04-18', 'MA ครั้งที่ 4'),
(78, 42, '2025-05-18', 'MA ครั้งที่ 5'),
(79, 42, '2025-06-18', 'MA ครั้งที่ 6'),
(80, 42, '2025-07-18', 'MA ครั้งที่ 7'),
(81, 42, '2025-08-18', 'MA ครั้งที่ 8'),
(82, 42, '2025-09-18', 'MA ครั้งที่ 9'),
(83, 42, '2025-10-18', 'MA ครั้งที่ 10'),
(84, 42, '2025-11-18', 'MA ครั้งที่ 11'),
(85, 42, '2025-12-18', 'MA ครั้งที่ 12'),
(86, 41, '2025-01-17', 'MA ครั้งที่ 1'),
(87, 41, '2025-02-17', 'MA ครั้งที่ 2'),
(88, 41, '2025-03-17', 'MA ครั้งที่ 3'),
(89, 41, '2025-04-17', 'MA ครั้งที่ 4'),
(90, 41, '2025-05-17', 'MA ครั้งที่ 5'),
(91, 41, '2025-06-17', 'MA ครั้งที่ 6'),
(92, 41, '2025-07-17', 'MA ครั้งที่ 7'),
(93, 41, '2025-08-17', 'MA ครั้งที่ 8'),
(94, 41, '2025-09-17', 'MA ครั้งที่ 9'),
(95, 41, '2025-10-17', 'MA ครั้งที่ 10'),
(96, 41, '2025-11-17', 'MA ครั้งที่ 11'),
(97, 41, '2025-12-17', 'MA ครั้งที่ 12'),
(98, 32, '2024-07-01', 'MA ครั้งที่ 1'),
(99, 32, '2025-01-01', 'MA ครั้งที่ 2'),
(100, 32, '2025-07-01', 'MA ครั้งที่ 3'),
(101, 32, '2026-01-01', 'MA ครั้งที่ 4'),
(102, 43, '2025-07-02', 'MA ครั้งที่ 1'),
(103, 43, '2026-01-02', 'MA ครั้งที่ 2');

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
(31, 'งานจัดซื้อแบตเตอรี่ ชนิดลิเที่ยมฟอตเฟต ขนาด48V 1000Ah 4ชุด', 9, 'BU2', 'In Progress', 'PJ6707013', '5 ปี', 'เข้า MA ทุก 6 เดือน', NULL, '2025-02-27', '2030-02-27'),
(32, 'ครุภัณฑ์สายสื่อสาร (คอมพิวเตอร์) กองทัพไทย', 9, 'BU3', 'In Progress', 'PJ6708009', '2 ปี และ 3 ปี', 'รับประกัน ไม่มี MA', NULL, '2024-01-01', '2026-01-01'),
(33, 'จ้างเหมาติดตั้ง Main Battery พื้นที่ ภน.1 จำนวน 14 แห่ง', 9, 'BU1', 'In Progress', 'PJ6605002', '5 ปี', 'เข้า MA 1 ครั้ง ก่อนหมดสัญญา', NULL, '2024-02-15', '2029-02-15'),
(34, 'ซื้อแบตเตอรี่ลิเทียมขนาด 48V/100 Ah และ Rectifier พร้อมการติดตั้ง สำหรับ สค.ในพื้นที่ ภน.1', 9, '-', 'In Progress', 'PJ6711008', 'แบต 5 ปี /Rectifier 2 ปี', 'รับประกัน ไม่มี MA', NULL, '2025-09-08', '2030-09-08'),
(35, 'งานกาเเฟ', 9, 'เอ็มจูน คู่หูมหาประลัย', 'Completed', '้12345678', '1', 'ฟหกดหเหห', NULL, '2025-12-11', '2025-12-11'),
(36, 'เรื่องมีอยู่ว่าาา ว่าาาา', 8, '-', 'In Progress', '1258', '1', 'นนนน', NULL, '2567-12-12', '2568-12-12'),
(39, 'เชิญร่วมงานเเต่งงาน จูน 👨‍❤️‍💋‍👨 เอ็ม', 8, 'ดร.หมอดู ดูหม้อสุรินทร์', 'In Progress', 'PJ660315151', '1 ปี', '', 'uploads/proj_39_1765876852.jpg', '2024-12-15', '2025-12-15'),
(40, 'จูนนี่ลา เอ็มลา', 8, 'เอ็มจูน คู่หูมหาประลัย', 'In Progress', '48963588555', '1', 'เช็คระยะหลังกินต้มทุกๆ1เดือน', 'uploads/proj_40_1765873783.jpg', '2024-12-16', '2025-12-16'),
(41, 'เอ็มสวยสะท้านโลกา', 8, 'เอ็มจูน คู่หูมหาประลัย', 'In Progress', '้12345678', '1', 'เช็คระยะหลังกินต้มกับจูนทุกๆ1เดือน', 'uploads/proj_41_1765869183.xlsx', '2024-12-17', '2025-12-17'),
(42, 'สวัสดี วันอังคาร', 8, 'เอ็มจูน คู่หูมหาประลัย', 'In Progress', 'P8660315151', '1 ปี', 'พบเเพทย์ทุกๆ1เดือน', 'uploads/proj_42_1765868923.jpg', '2024-12-18', '2025-12-18'),
(43, 'อยากกกกกกกกินต้มมมมมมมม', 8, 'เอ็มขยี้ จูนต้ม', 'In Progress', 'ดก่า่รเำพเะ', '1 ปี', 'สูตรต้มยาแก้ไอ2ขวด', 'uploads/proj_43_1765869871.xlsx', '2025-01-02', '2026-01-02');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int NOT NULL,
  `customers_id` int NOT NULL,
  `address` varchar(255) NOT NULL,
  `repair_details` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `device_name` varchar(255) NOT NULL,
  `serial_number` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `customers_id`, `address`, `repair_details`, `phone`, `device_name`, `serial_number`) VALUES
(17, 9, '99 ถนนแจ้งวัฒนะ แขวงทุ่งสองห้อง เขตหลักสี่ กรุงเทพฯ 10210.', 'หน้าจอแสดงผลมีเส้นลายรบกวน และปุ่มกดตอบสนองช้า', '0898756465', 'Philips Patient Monitor', 'PH-PM-223344'),
(19, 9, '99 ถนนแจ้งวัฒนะ แขวงทุ่งสองห้อง เขตหลักสี่ กรุงเทพฯ 10210.', 'เปิดเครื่องไม่ติด (No Power) ไฟสถานะชาร์จไม่ขึ้น', '0898756465', 'Lenovo ThinkPad X1 Carbon', 'NB-LNV-445566'),
(22, 9, '99 ถนนแจ้งวัฒนะ แขวงทุ่งสองห้อง เขตหลักสี่ กรุงเทพฯ 10210.', 'กระดาษติด (Paper Jam) บ่อยครั้ง และพิมพ์ออกมามีรอยเปื้อนหมึก', '0898756465', 'HP LaserJet Pro M404dn', 'PR-HP-667788');

-- --------------------------------------------------------

--
-- Table structure for table `service_attachments`
--

CREATE TABLE `service_attachments` (
  `attachment_id` int NOT NULL,
  `service_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_project`
--

CREATE TABLE `service_project` (
  `service_id` int NOT NULL,
  `customers_id` int NOT NULL,
  `project_name` varchar(255) NOT NULL COMMENT 'ชื่อโครงการ',
  `equipment` varchar(255) NOT NULL COMMENT 'วัสดุ/อุปกรณ์',
  `symptom` text NOT NULL COMMENT 'อาการที่แจ้ง',
  `action_taken` text COMMENT 'การดำเนินการ',
  `status` varchar(50) DEFAULT NULL COMMENT 'สถานะการดำเนินงาน (On-site/Remote/Subcontractor)',
  `start_date` date NOT NULL COMMENT 'วันที่รับเรื่อง',
  `end_date` date DEFAULT NULL COMMENT 'วันที่ปิดงาน',
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `service_project`
--

INSERT INTO `service_project` (`service_id`, `customers_id`, `project_name`, `equipment`, `symptom`, `action_taken`, `status`, `start_date`, `end_date`, `file_path`, `created_at`) VALUES
(5, 8, 'โครงการจัดซื้ออุปกรณ์สนับสนุนปฏิบัติหน้าที่ DSI', 'Server Dell R740', 'กล้องไม่สามารถไฟล์ภาพสดกลับมาที่ส่วนกลางได้', 'เรียบร้อยดี', 'Remote', '2025-11-04', '2025-10-04', NULL, '2025-12-12 02:18:18'),
(6, 9, 'จ้างเหมาติดตั้งแบตเตอรี่ 14 ภน.', ' 1.RN2310124810023112700007 2.RN2310124810023112700034', 'แบตเตอรี่ไม่สามารถสำรองไฟได้ ทำให้อุปกรณ์ภายในชุมสายดับเป็นเวลา 30 นาที', 'เปลี่ยนแบตเตอรี่เรียบร้อย', 'On-site', '2024-11-27', '2024-12-03', NULL, '2025-12-12 06:16:34'),
(7, 14, 'โทรศัพท์', 'IPPBX', 'ATA อาจขัดข้องระหว่างการใช้งาน', 'รีบูตATAเเละZycoo', 'On-site', '2025-02-21', '2025-12-21', NULL, '2025-12-16 03:38:58'),
(8, 14, 'โทรศัพท์', 'IP PHONE C62GP \"DINSTAR\"', 'ตัว Zycoo เเละ ATA ที่โทรศัพท์เชื่อมต่ออยู่ Error', 'รีบูตตัว Zycoo เเละ ATA เเละเซ็ตค่าโทรศัพท์ใหม่', 'On-site', '2025-05-05', '2025-05-05', NULL, '2025-12-16 04:21:01'),
(9, 31, 'เเบตเตอรี่ 35 เเห่ง ภน.3', 'Battery Lithium 48V 100AH', 'ใช้งานไปเหมือนเเบตเตอรี่ตัดไม่สำรองไฟ', 'อัปเดต BMS เพราะ BMS เเบตเตอรี่รวน', 'On-site', '2025-03-03', '2025-03-12', NULL, '2025-12-16 04:42:40');

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
(2, 'admin', '$2y$10$sYgBXbTIWkGBeR6bzfZgve7CgEaokwJ4X5Lj/evbjkti6bczk6pbS', 'admin', 1),
(3, 'user01', '$2y$10$CcrTeWZRcUsm7XKxhBi4Bu6/q2E66y8ufnQXbYvcc4EJWkvhlVJpu', 'user', 1),
(4, '0001', '$2y$10$XSEmqQpWQMKUJeeIfbJmIuCYECluszOzKUjDGP4DqanCsQZuTkD7K', 'admin', 1),
(5, 'jame', '$2y$10$IYpEJ1cNEG3SlcmjVfyBQu2E6.7G3He55PPW4Tqr7/M3IQyJykkLa', 'admin', 1),
(6, 'user02', '$2y$10$G5rM.UhACTMJSBlmg2ZyTeoVKeuNKWPx1dmKr1Ho75alR2ANYJSLe', 'user', 1),
(7, 'user03', '$2y$10$1AObJ/D3.eljO7had2sdEOfJ7bG2OQJue4qMZ5TowDn3vwT03CCx2', 'user', 0),
(8, 'สินสอดเท่าใดน้อ', '$2y$10$xn4LCw3aMrDhhWq/Xh1.PuZ5Q5zoSZ7Uz3ghgligAc4VgHmIhmhji', 'admin', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customers_id`);

--
-- Indexes for table `ma_schedule`
--
ALTER TABLE `ma_schedule`
  ADD PRIMARY KEY (`ma_id`),
  ADD KEY `pmproject_id` (`pmproject_id`);

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
-- Indexes for table `service_attachments`
--
ALTER TABLE `service_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `service_project`
--
ALTER TABLE `service_project`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `idx_customer` (`customers_id`);

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
  MODIFY `customers_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `ma_schedule`
--
ALTER TABLE `ma_schedule`
  MODIFY `ma_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `pm_project`
--
ALTER TABLE `pm_project`
  MODIFY `pmproject_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `service_attachments`
--
ALTER TABLE `service_attachments`
  MODIFY `attachment_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_project`
--
ALTER TABLE `service_project`
  MODIFY `service_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ma_schedule`
--
ALTER TABLE `ma_schedule`
  ADD CONSTRAINT `fk_ma_project` FOREIGN KEY (`pmproject_id`) REFERENCES `pm_project` (`pmproject_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
