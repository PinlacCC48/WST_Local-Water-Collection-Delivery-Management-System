-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 15, 2026 at 08:34 AM
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
-- Database: `majestic_spring_wcdms`
--

-- --------------------------------------------------------

--
-- Table structure for table `container_product`
--

CREATE TABLE `container_product` (
  `product_id` varchar(15) NOT NULL,
  `container_name` varchar(100) NOT NULL,
  `container_type` varchar(50) NOT NULL DEFAULT '5-Gal - Slim',
  `capacity_gallons` int(11) NOT NULL DEFAULT 5,
  `refill_price` decimal(10,2) NOT NULL DEFAULT 30.00,
  `new_container_price` decimal(10,2) NOT NULL DEFAULT 250.00,
  `available_stock` int(11) NOT NULL DEFAULT 20,
  `inspection_note` varchar(255) DEFAULT NULL,
  `availability_status` enum('Available','Low Stock','Out of Stock') NOT NULL DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `container_product`
--

INSERT INTO `container_product` (`product_id`, `container_name`, `container_type`, `capacity_gallons`, `refill_price`, `new_container_price`, `available_stock`, `inspection_note`, `availability_status`) VALUES
('PRD-01', '5-Gallon Slim Container with Faucet', '5-Gal - Slim', 5, 30.00, 250.00, 24, 'Inspect faucet tap valve and cap seal before refilling', 'Available'),
('PRD-02', '5-Gallon Round Dispenser Bottle', '5-Gal - Round', 5, 30.00, 250.00, 19, 'Inspect bottle neck and bottom base for hairline cracks', 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` varchar(15) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) NOT NULL,
  `container_label` varchar(50) NOT NULL,
  `house_no` varchar(30) NOT NULL,
  `street_name` varchar(100) NOT NULL,
  `barangay` varchar(50) NOT NULL,
  `city` varchar(60) NOT NULL DEFAULT 'Caloocan City',
  `email_address` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `gate_rider_note` varchar(255) DEFAULT NULL,
  `date_registered` date NOT NULL DEFAULT curdate(),
  `cust_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `first_name`, `last_name`, `container_label`, `house_no`, `street_name`, `barangay`, `city`, `email_address`, `password_hash`, `gate_rider_note`, `date_registered`, `cust_status`) VALUES
('CUS-0001', 'Clark', 'Pinlac', 'Bautista', '209', 'Laon-laan Street', 'Barangay 24', 'Caloocan City', 'clarkpinlac28@gmail.com', '$2y$10$7ww4wIwSbZoM3mqAeooLP.L1CP4dCyLZkj9LqLaWTXQEMVN1MI5KW', 'Leave with guard at front gate if out', '2026-11-08', 'Active'),
('CUS-0002', 'Maricel', 'Dela Cruz', 'DelaCruz', '14', 'Dimasalang Street', 'Barangay 22', 'Caloocan City', 'maricel.delacruz@gmail.com', '$2y$10$abcdefghijklmnopqrstuuzODQUGMBrN0lNFsQUfWvYdS884xxG02', 'Ring doorbell twice, green gate', '2026-11-10', 'Active'),
('CUS-0003', 'Rodel', 'Bautista', 'Bautista', '209', 'Laon-laan Street', 'Barangay 24', 'Caloocan City', 'rodel.bautista@gmail.com', '$2y$10$abcdefghijklmnopqrstuuzODQUGMBrN0lNFsQUfWvYdS884xxG02', 'Leave with guard at front gate if out', '2026-11-10', 'Active'),
('CUS-0004', 'Lorna', 'Macapagal', 'Lorna', '77', 'M. H. Del Pilar St', 'Barangay 26', 'Caloocan City', 'lorna.macapagal@gmail.com', '$2y$10$abcdefghijklmnopqrstuuzODQUGMBrN0lNFsQUfWvYdS884xxG02', '2nd floor apartment, knock loudly', '2026-11-10', 'Active'),
('CUS-0005', 'Arnel', 'Pangilinan', 'Pangilinan', '32', 'P. Zamora Street', 'Barangay 27', 'Caloocan City', 'arnel.pangilinan@gmail.com', '$2y$10$abcdefghijklmnopqrstuuzODQUGMBrN0lNFsQUfWvYdS884xxG02', 'Call primary phone upon arrival', '2026-11-10', 'Active'),
('CUS-0006', 'Theresa', 'Mendoza', 'Mendoza', '115', '10th Avenue', 'Barangay 35', 'Caloocan City', 'theresa.mendoza@gmail.com', '$2y$10$abcdefghijklmnopqrstuuzODQUGMBrN0lNFsQUfWvYdS884xxG02', 'Beware of dog, wait at front porch', '2026-11-10', 'Active'),
('CUS-0007', 'Dennis', 'Gatchalian', 'Gatchalian', '58', '9th Avenue', 'Barangay 22', 'Caloocan City', 'dennis.gatchalian@gmail.com', '$2y$10$abcdefghijklmnopqrstuuzODQUGMBrN0lNFsQUfWvYdS884xxG02', 'Sari-sari store beside bakery', '2026-11-10', 'Active'),
('CUS-0008', 'Rowena', 'Manansala', 'Manansala', '142', 'A. Mabini Street', 'Barangay 24', 'Caloocan City', 'rowena.manansala@gmail.com', '$2y$10$abcdefghijklmnopqrstuuzODQUGMBrN0lNFsQUfWvYdS884xxG02', 'White gate with black mailbox', '2026-11-10', 'Active'),
('CUS-0009', 'Nestor', 'Evangelista', 'Nestor', '91', 'C. Namie Street', 'Barangay 26', 'Caloocan City', 'nestor.evangelista@gmail.com', '$2y$10$abcdefghijklmnopqrstuuzODQUGMBrN0lNFsQUfWvYdS884xxG02', 'Deliver before 4 PM if possible', '2026-11-10', 'Active'),
('CUS-0010', 'Glenda', 'Sarmiento', 'Sarmiento', '64', 'Laon-laan Street', 'Barangay 27', 'Caloocan City', 'glenda.sarmiento@gmail.com', '$2y$10$abcdefghijklmnopqrstuuzODQUGMBrN0lNFsQUfWvYdS884xxG02', 'Use side gate near carport', '2026-11-10', 'Active'),
('CUS-0011', 'Victor', 'Magbanua', 'Magbanua', '18', 'P. Burgos Street', 'Barangay 35', 'Caloocan City', 'victor.magbanua@gmail.com', '$2y$10$abcdefghijklmnopqrstuuzODQUGMBrN0lNFsQUfWvYdS884xxG02', 'Call backup number if primary is busy', '2026-11-10', 'Inactive'),
('CUS-0012', 'Camille', 'Dizon', 'Dizon', '103', 'Dimasalang Street', 'Barangay 24', 'Caloocan City', 'camille.dizon@gmail.com', '$2y$10$abcdefghijklmnopqrstuuzODQUGMBrN0lNFsQUfWvYdS884xxG02', '3rd door townhome, brown wooden gate', '2026-11-10', 'Inactive'),
('CUS-0013', 'Timothy', 'Sunder', 'Sunder', '123', 'Yoland Street', 'Barangay 24', 'Caloocan City', 'pinlac20877@gmail.com', 'mDxYHBd3VT3EbQx', 'Ring and call for person.', '2026-11-12', 'Active'),
('CUS-0014', 'Clark', 'Louise C. Pinlac', 'Clark', '128', 'Dimasalang Street', 'Barangay 22', 'Caloocan City', 'customer@gmail.com', '$2y$10$JFxWGsxAlguoousC1tfyXebCbj1ZhgwsPqLagvVOnoidppvtH7sn2', NULL, '2026-11-13', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `customer_cp_num`
--

CREATE TABLE `customer_cp_num` (
  `customer_id` varchar(15) NOT NULL,
  `cp_num` varchar(20) NOT NULL,
  `cp_type` enum('Primary','Backup') NOT NULL DEFAULT 'Primary'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_cp_num`
--

INSERT INTO `customer_cp_num` (`customer_id`, `cp_num`, `cp_type`) VALUES
('CUS-0001', '09173001003', 'Primary'),
('CUS-0002', '09173001002', 'Primary'),
('CUS-0002', '09183001002', 'Backup'),
('CUS-0003', '09173001003', 'Primary'),
('CUS-0004', '09173001004', 'Primary'),
('CUS-0004', '09183001004', 'Backup'),
('CUS-0005', '09173001005', 'Primary'),
('CUS-0006', '09173001006', 'Primary'),
('CUS-0006', '09183001006', 'Backup'),
('CUS-0007', '09173001007', 'Primary'),
('CUS-0008', '09173001008', 'Primary'),
('CUS-0008', '09183001008', 'Backup'),
('CUS-0009', '09173001009', 'Primary'),
('CUS-0010', '09173001010', 'Primary'),
('CUS-0011', '09173001011', 'Primary'),
('CUS-0011', '09183001011', 'Backup'),
('CUS-0012', '09173001012', 'Primary'),
('CUS-0013', '09678948000', 'Primary'),
('CUS-0013', '09678940000', 'Backup'),
('CUS-0014', '09678948333', 'Primary');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `employee_id` varchar(15) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) NOT NULL,
  `house_no` varchar(30) NOT NULL,
  `street_name` varchar(100) NOT NULL,
  `barangay` varchar(50) NOT NULL,
  `city` varchar(60) NOT NULL DEFAULT 'Caloocan City',
  `email_address` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `emp_role` enum('Admin','Bookkeeper','Delivery Rider','Dishwasher') NOT NULL DEFAULT 'Admin',
  `date_hired` date NOT NULL DEFAULT curdate(),
  `emp_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `manager_id` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`employee_id`, `first_name`, `last_name`, `house_no`, `street_name`, `barangay`, `city`, `email_address`, `password_hash`, `emp_role`, `date_hired`, `emp_status`, `manager_id`) VALUES
('EMP-0001', 'Primary', 'Admin', '1', 'Sample Street', 'Barangay 22', 'Caloocan City', 'admin@majesticspring.test', '$2y$10$kLLApGFexqaUnE2GrUKN3OpduUIWC8l8EGQzof784t2OTx9dfmrwK', 'Admin', '2026-11-08', 'Active', NULL),
('EMP-0003', 'Elena', 'Soriano', '45', 'Rizal Avenue Ext.', 'Barangay 22', 'Caloocan City', 'elena.admin@majesticspring.test', '$2y$10$abcdefghijklmnopqrstuurDHpKIEi2Mvm4OXE7/STNUgDOoXyzMa', 'Admin', '2026-11-10', 'Active', NULL),
('EMP-0004', 'Marco', 'Villanueva', '88', 'A. Mabini Street', 'Barangay 24', 'Caloocan City', 'marco.admin@majesticspring.test', '$2y$10$abcdefghijklmnopqrstuurDHpKIEi2Mvm4OXE7/STNUgDOoXyzMa', 'Admin', '2026-11-10', 'Active', NULL),
('EMP-0005', 'Boyet', 'Tolentino', '19', 'C. Namie Street', 'Barangay 26', 'Caloocan City', 'boyet.rider@majesticspring.test', '$2y$10$abcdefghijklmnopqrstuurDHpKIEi2Mvm4OXE7/STNUgDOoXyzMa', 'Delivery Rider', '2026-11-10', 'Active', 'EMP-0003'),
('EMP-0006', 'Jomar', 'Catacutan', '63', 'P. Burgos Street', 'Barangay 27', 'Caloocan City', 'jomar.rider@majesticspring.test', '$2y$10$abcdefghijklmnopqrstuurDHpKIEi2Mvm4OXE7/STNUgDOoXyzMa', 'Delivery Rider', '2026-11-10', 'Active', 'EMP-0003'),
('EMP-0007', 'Admin', 'Tester', '123', 'Test Street', 'Barangay 22', 'Caloocan City', 'Admin12345@gmail.com', 'admin12345', 'Admin', '2026-11-11', 'Active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_cp_num`
--

CREATE TABLE `employee_cp_num` (
  `employee_id` varchar(15) NOT NULL,
  `cp_num` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_cp_num`
--

INSERT INTO `employee_cp_num` (`employee_id`, `cp_num`) VALUES
('EMP-0001', '09000000000'),
('EMP-0003', '09171002001'),
('EMP-0003', '09181002001'),
('EMP-0004', '09171002002'),
('EMP-0005', '09171002003'),
('EMP-0006', '09171002004'),
('EMP-0007', '09999999910'),
('EMP-0007', '09999999999');

-- --------------------------------------------------------

--
-- Table structure for table `order_information`
--

CREATE TABLE `order_information` (
  `order_id` varchar(15) NOT NULL,
  `customer_id` varchar(15) NOT NULL,
  `employee_id` varchar(15) DEFAULT NULL,
  `slot_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL DEFAULT 1,
  `order_channel` varchar(50) NOT NULL DEFAULT 'Online Portal',
  `service_mode` varchar(50) NOT NULL DEFAULT 'Pickup/Delivery',
  `inspection_status` enum('Good','Damaged','Pending') NOT NULL DEFAULT 'Good',
  `delay_hold_notice` varchar(150) DEFAULT 'None',
  `additional_notes` varchar(255) DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL DEFAULT 30.00,
  `date_ordered` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_information`
--

INSERT INTO `order_information` (`order_id`, `customer_id`, `employee_id`, `slot_id`, `status_id`, `order_channel`, `service_mode`, `inspection_status`, `delay_hold_notice`, `additional_notes`, `total_price`, `date_ordered`) VALUES
('ORD-0001', 'CUS-0001', 'EMP-0001', 4, 5, 'Online', 'Pickup/Delivery', 'Good', 'Unpaid / Utang', 'Leave with guard at front gate if out', 30.00, '2026-11-10 14:51:12'),
('ORD-0002', 'CUS-0001', 'EMP-0001', 2, 5, 'Online Portal', 'Walk-In/Pickup', 'Good', 'None', 'Leave with guard at front gate if out', 280.00, '2026-11-10 14:54:06'),
('ORD-0003', 'CUS-0007', 'EMP-0001', 5, 5, 'Online', 'Pickup/Delivery', 'Good', 'None', 'Sari-sari store beside bakery', 30.00, '2026-11-10 18:31:15'),
('ORD-0101', 'CUS-0001', NULL, 1, 5, 'Online Portal', 'Pickup/Delivery', '', 'None (On Schedule)', 'Morning two-trip pickup and delivery completed on time.', 60.00, '2026-11-11 08:05:00'),
('ORD-0102', 'CUS-0011', 'EMP-0001', 1, 5, 'User/Admin Encoded', 'Walk-In/Pickup', '', 'None (On Schedule)', 'Walk-in customer (inactive online account) brought own Slim gallon directly to station counter.', 30.00, '2026-11-11 08:20:00'),
('ORD-0103', 'CUS-0002', 'EMP-0005', 1, 5, 'User/Admin Encoded', 'Walk-In/Delivery', '', 'None', 'Walk-in at station: ordered 1 brand-new 5-Gal Slim container with water refill for tricycle delivery.', 280.00, '2026-11-11 08:45:00'),
('ORD-0104', 'CUS-0003', NULL, 2, 5, 'Online Portal', 'Pickup/Delivery', '', 'None (On Schedule)', 'Regular weekly refill order; empty containers collected on Trip 1 and delivered on Trip 2.', 60.00, '2026-11-11 10:05:00'),
('ORD-0105', 'CUS-0012', 'EMP-0005', 2, 5, 'User/Admin Encoded', 'Pickup/Delivery', '', 'None (On Schedule)', 'Phone call order encoded by staff: customer requested rider to collect empty Round container at gate.', 30.00, '2026-11-11 10:25:00'),
('ORD-0106', 'CUS-0004', 'EMP-0006', 2, 5, 'Online Portal', 'Pickup/Delivery', 'Good', 'None', 'To be collected by rider upon Trip 2 delivery', 60.00, '2026-11-11 10:50:00'),
('ORD-0107', 'CUS-0005', NULL, 3, 5, 'Online Portal', 'Pickup/Delivery', '', 'None (On Schedule)', 'Delivered at front gate per customer rider note.', 30.00, '2026-11-11 12:10:00'),
('ORD-0108', 'CUS-0006', 'EMP-0005', 3, 5, 'User/Admin Encoded', 'Walk-In/Delivery', '', 'None', 'Walk-in/Delivery: customer ordered 2 brand-new 5-Gal Round dispenser containers with refill at counter for home delivery.', 560.00, '2026-11-11 12:30:00'),
('ORD-0109', 'CUS-0001', 'EMP-0006', 3, 5, 'User/Admin Encoded', 'Walk-In/Delivery', '', 'None', 'Walk-in/Delivery (No online booking): helper dropped off 2 empty Slim gallons at station; rider will deliver refilled.', 60.00, '2026-11-11 13:05:00'),
('ORD-0110', 'CUS-0007', NULL, 4, 5, 'Online Portal', 'Pickup/Delivery', '', 'None', 'Empty container picked up on Trip 1; currently washing and refilling at station.', 30.00, '2026-11-11 14:10:00'),
('ORD-0111', 'CUS-0008', 'EMP-0003', 4, 5, 'User/Admin Encoded', 'Walk-In/Pickup', '', 'None (On Schedule)', 'Walk-in priority refill: customer brought 2 Round containers to station and collected immediately.', 60.00, '2026-11-11 14:25:00'),
('ORD-0112', 'CUS-0009', 'EMP-0005', 4, 5, 'Online Portal', 'Walk-In/Delivery', 'Good', 'None', 'Ordered 1 brand-new 5-Gal Slim container with water refill; brief 15-min filter backwash hold.', 280.00, '2026-11-11 14:50:00'),
('ORD-0113', 'CUS-0010', 'EMP-0006', 5, 5, 'Online Portal', 'Pickup/Delivery', '', 'None', 'Scheduled for 4:00 PM - 6:00 PM batch window; awaiting Rider Trip 1 empty pickup.', 60.00, '2026-11-11 16:05:00'),
('ORD-0114', 'CUS-0003', NULL, 5, 5, 'User/Admin Encoded', 'Pickup/Delivery', 'Good', 'None', 'Admin-encoded SMS order: neighbour/household requested afternoon pickup of 2 Round containers.', 60.00, '2026-11-11 16:20:00'),
('ORD-0115', 'CUS-0004', 'EMP-0001', 5, 5, 'User/Admin Encoded', 'Walk-In/Pickup', '', 'None', 'Helper paid cash at counter upon collection', 30.00, '2026-11-11 16:35:00'),
('ORD-0116', 'CUS-0002', 'EMP-0001', 4, 5, 'Online Portal', 'Pickup/Delivery', 'Damaged', 'None', 'Ring doorbell twice, green gate', 30.00, '2026-11-11 15:24:46'),
('ORD-0117', 'CUS-0002', 'EMP-0005', 1, 5, 'Online Portal', 'Walk-In/Delivery', 'Good', 'None', 'Ring doorbell twice, green gate', 280.00, '2026-11-12 08:15:02'),
('ORD-0118', 'CUS-0013', 'EMP-0001', 2, 5, 'User/Admin Encoded', 'Walk-In/Delivery', 'Good', 'None', 'Ring and call for person.', 30.00, '2026-11-12 08:29:18'),
('ORD-0119', 'CUS-0002', 'EMP-0005', 4, 5, 'Online Portal', 'Pickup/Delivery', 'Good', 'None', 'Ring doorbell twice, green gate', 30.00, '2026-11-12 08:37:20'),
('ORD-0120', 'CUS-0013', 'EMP-0001', 4, 5, 'User/Admin Encoded', 'Pickup/Delivery', 'Good', 'None', 'Ring and call for person.', 30.00, '2026-11-12 12:50:00'),
('ORD-0121', 'CUS-0002', 'EMP-0005', 2, 5, 'Online Portal', 'Walk-In/Delivery', 'Good', 'None', 'Ring doorbell twice, green gate', 280.00, '2026-11-12 15:36:41'),
('ORD-0122', 'CUS-0002', 'EMP-0005', 3, 7, 'Online Portal', 'Pickup/Delivery', 'Good', 'None', 'Ring doorbell twice, green gate', 30.00, '2026-11-12 15:46:08'),
('ORD-0123', 'CUS-0002', 'EMP-0005', 4, 5, 'Online Portal', 'Pickup/Delivery', 'Good', 'None', 'Ring doorbell twice, green gate', 30.00, '2026-11-12 15:51:42'),
('ORD-0124', 'CUS-0002', 'EMP-0005', 5, 5, 'Online Portal', 'Walk-In/Delivery', 'Good', 'None', 'Ring doorbell twice, green gate', 280.00, '2026-11-13 08:10:15'),
('ORD-0125', 'CUS-0002', 'EMP-0005', 4, 5, 'Online Portal', 'Pickup/Delivery', 'Good', 'None', 'Ring doorbell twice, green gate', 30.00, '2026-11-12 15:53:22'),
('ORD-0126', 'CUS-0001', 'EMP-0005', 2, 7, 'Online Portal', 'Pickup/Delivery', 'Good', 'None', 'Cash on delivery', 30.00, '2026-11-13 08:12:21'),
('ORD-0127', 'CUS-0002', 'EMP-0005', 1, 5, 'Online Portal', 'Pickup/Delivery', 'Good', 'None', '1-Click Repeat Usual Refill submitted from Customer Dashboard.', 30.00, '2026-11-14 09:27:51'),
('ORD-0128', 'CUS-0014', 'EMP-0001', 4, 5, 'User/Admin Encoded', 'Pickup/Delivery', 'Good', 'None', 'Paid in cash upon delivery', 100.00, '2026-11-13 13:46:00');

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

CREATE TABLE `order_item` (
  `order_id` varchar(15) NOT NULL,
  `product_id` varchar(15) NOT NULL,
  `order_type` varchar(50) NOT NULL DEFAULT 'Refill Only',
  `unit_price` decimal(10,2) NOT NULL DEFAULT 30.00,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 30.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`order_id`, `product_id`, `order_type`, `unit_price`, `quantity`, `subtotal`) VALUES
('ORD-0001', 'PRD-01', 'Refill Only', 30.00, 1, 30.00),
('ORD-0002', 'PRD-02', 'New Container + Refill', 280.00, 1, 280.00),
('ORD-0003', 'PRD-01', 'Refill Only', 30.00, 1, 30.00),
('ORD-0101', 'PRD-01', 'Refill Only', 30.00, 2, 60.00),
('ORD-0102', 'PRD-01', 'Refill Only', 30.00, 1, 30.00),
('ORD-0103', 'PRD-01', 'New Container + Refill', 280.00, 1, 280.00),
('ORD-0104', 'PRD-02', 'Refill Only', 30.00, 2, 60.00),
('ORD-0105', 'PRD-02', 'Refill Only', 30.00, 1, 30.00),
('ORD-0106', 'PRD-01', 'Refill Only', 30.00, 2, 60.00),
('ORD-0107', 'PRD-01', 'Refill Only', 30.00, 1, 30.00),
('ORD-0108', 'PRD-02', 'New Container + Refill', 280.00, 2, 560.00),
('ORD-0109', 'PRD-01', 'Refill Only', 30.00, 2, 60.00),
('ORD-0110', 'PRD-02', 'Refill Only', 30.00, 1, 30.00),
('ORD-0111', 'PRD-02', 'Refill Only', 30.00, 2, 60.00),
('ORD-0112', 'PRD-01', 'New Container + Refill', 280.00, 1, 280.00),
('ORD-0113', 'PRD-01', 'Refill Only', 30.00, 2, 60.00),
('ORD-0114', 'PRD-02', 'Refill Only', 30.00, 2, 60.00),
('ORD-0115', 'PRD-01', 'Refill Only', 30.00, 1, 30.00),
('ORD-0116', 'PRD-01', 'Refill Only', 30.00, 1, 30.00),
('ORD-0117', 'PRD-01', 'New Container + Refill', 280.00, 1, 280.00),
('ORD-0118', 'PRD-01', 'Refill Only', 30.00, 1, 30.00),
('ORD-0119', 'PRD-01', 'Refill Only', 30.00, 1, 30.00),
('ORD-0120', 'PRD-01', 'Refill Only', 30.00, 1, 30.00),
('ORD-0121', 'PRD-01', 'New Container + Refill', 280.00, 1, 280.00),
('ORD-0122', 'PRD-01', 'Refill Only', 30.00, 1, 30.00),
('ORD-0123', 'PRD-01', 'Refill Only', 30.00, 1, 30.00),
('ORD-0124', 'PRD-02', 'New Container + Refill', 280.00, 1, 280.00),
('ORD-0125', 'PRD-01', 'Refill Only', 30.00, 1, 30.00),
('ORD-0126', 'PRD-01', 'Refill Only', 30.00, 1, 30.00),
('ORD-0127', 'PRD-02', 'Refill Only', 30.00, 1, 30.00),
('ORD-0128', 'PRD-01', 'Other', 100.00, 1, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_status`
--

CREATE TABLE `order_status` (
  `status_id` int(11) NOT NULL,
  `status_name` varchar(60) NOT NULL,
  `description` varchar(255) NOT NULL,
  `status_active` enum('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_status`
--

INSERT INTO `order_status` (`status_id`, `status_name`, `description`, `status_active`) VALUES
(1, 'Queued', 'Order queued in 2-hour batch window awaiting empty container pickup', 'Active'),
(2, 'Empty Picked Up', 'Tricycle rider collected empty labeled 5-gallon container from customer', 'Active'),
(3, 'Refilling', 'Container inspected, washed, and being refilled at the station', 'Active'),
(4, 'Out For Delivery', 'Sealed container loaded on tricycle for batch delivery', 'Active'),
(5, 'Delivered & Paid', 'Container delivered and cash payment collected/remitted', 'Active'),
(6, 'Rescheduled', 'Order moved to the next available 2-hour batch slot', 'Active'),
(7, 'Cancelled', 'Order cancelled while still in Queued status before rider pickup', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` varchar(15) NOT NULL,
  `order_id` varchar(15) NOT NULL,
  `payment_method` varchar(40) NOT NULL DEFAULT 'Cash Only',
  `total_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `recorded_by` varchar(60) NOT NULL,
  `payment_notes` varchar(255) DEFAULT NULL,
  `date_paid` datetime DEFAULT current_timestamp(),
  `payment_status` enum('Paid','Remitted','Unpaid') NOT NULL DEFAULT 'Unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `total_paid`, `recorded_by`, `payment_notes`, `date_paid`, `payment_status`) VALUES
('PAY-0001', 'ORD-0001', 'Cash Only', 0.00, 'EMP-0001', 'Flagged as Unpaid / Utang in Batch Orders', '2026-11-10 14:52:45', 'Unpaid'),
('PAY-0002', 'ORD-0002', 'Cash Only', 280.00, 'EMP-0001', 'Leave with guard at front gate if out', '2026-11-10 19:01:29', 'Paid'),
('PAY-0003', 'ORD-0003', 'Cash Only', 30.00, 'EMP-0001', 'Sari-sari store beside bakery', '2026-11-10 18:32:26', 'Paid'),
('PAY-0101', 'ORD-0101', 'Cash Only', 60.00, 'Micael Masta (EMP-0001)', 'Rider remitted cash after Trip 2 delivery', '2026-11-11 09:30:00', 'Remitted'),
('PAY-0102', 'ORD-0102', 'Cash Only', 30.00, 'Micael Masta (EMP-0001)', 'Walk-in cash paid directly at station counter', '2026-11-11 08:25:00', 'Paid'),
('PAY-0103', 'ORD-0103', 'Cash Only', 280.00, 'EMP-0004 (Marco)', 'Paid in cash upon delivery', '2026-11-11 22:39:41', 'Paid'),
('PAY-0104', 'ORD-0104', 'Cash Only', 60.00, 'Micael Masta (EMP-0001)', 'Rider remitted cash upon batch completion', '2026-11-11 11:40:00', 'Remitted'),
('PAY-0105', 'ORD-0105', 'Cash Only', 30.00, 'Clarissa Pinlac (EMP-0003)', 'Phone order delivered and remitted by rider', '2026-11-11 11:50:00', 'Remitted'),
('PAY-0106', 'ORD-0106', 'Cash Only', 60.00, 'CUS-0002 (Maricel)', 'Paid in cash upon delivery', '2026-11-12 08:20:35', 'Paid'),
('PAY-0107', 'ORD-0107', 'Cash Only', 30.00, 'Micael Masta (EMP-0001)', 'Rider remitted cash upon delivery', '2026-11-11 13:20:00', 'Remitted'),
('PAY-0108', 'ORD-0108', 'Cash Only', 560.00, 'EMP-0004 (Marco)', 'Paid in cash upon delivery', '2026-11-11 14:35:36', 'Paid'),
('PAY-0109', 'ORD-0109', 'Cash Only', 60.00, 'EMP-0004 (Marco)', 'Paid in cash upon delivery', '2026-11-11 14:35:33', 'Paid'),
('PAY-0110', 'ORD-0110', 'Cash Only', 30.00, 'EMP-0004 (Marco)', 'Paid in cash upon delivery', '2026-11-11 14:36:09', 'Paid'),
('PAY-0111', 'ORD-0111', 'Cash Only', 60.00, 'Clarissa Pinlac (EMP-0003)', 'Walk-in cash collected at station counter', '2026-11-11 14:35:00', 'Paid'),
('PAY-0112', 'ORD-0112', 'Cash Only', 280.00, 'EMP-0004 (Marco)', 'Ordered 1 brand-new 5-Gal Slim container with water refill; brief 15-min filter backwash hold.', '2026-11-11 23:08:48', 'Paid'),
('PAY-0113', 'ORD-0113', 'Cash Only', 60.00, 'EMP-0004 (Marco)', 'Paid in cash upon delivery', '2026-11-11 19:38:30', 'Paid'),
('PAY-0114', 'ORD-0114', 'Cash Only', 60.00, 'EMP-0004 (Marco)', 'Admin-encoded SMS order: neighbour/household requested afternoon pickup of 2 Round containers.', '2026-11-11 19:38:45', 'Paid'),
('PAY-0115', 'ORD-0115', 'Cash Only', 30.00, 'Micael Masta (EMP-0001)', 'Helper paid cash at counter upon collection', '2026-11-11 23:10:00', 'Paid'),
('PAY-0116', 'ORD-0116', 'Cash Only', 30.00, 'EMP-0004 (Marco)', 'Paid in cash upon delivery', '2026-11-11 19:38:21', 'Paid'),
('PAY-0117', 'ORD-0117', 'Cash Only', 280.00, 'EMP-0001 (Michael)', 'Paid in cash upon delivery', '2026-11-12 09:11:41', 'Paid'),
('PAY-0118', 'ORD-0119', 'Cash Only', 30.00, 'EMP-0001 (Michael)', 'Paid in cash upon delivery', '2026-11-12 08:38:15', 'Paid'),
('PAY-0119', 'ORD-0118', 'Cash Only', 30.00, 'EMP-0001 (Michael)', 'Paid in cash upon delivery', '2026-11-12 09:11:25', 'Paid'),
('PAY-0120', 'ORD-0121', 'Cash Only', 280.00, 'EMP-0001 (Michael)', 'Paid in cash upon delivery', '2026-11-12 15:38:28', 'Paid'),
('PAY-0121', 'ORD-0122', 'Cash Only', 0.00, 'Online Portal', 'Cash on delivery', NULL, 'Unpaid'),
('PAY-0122', 'ORD-0123', 'Cash Only', 30.00, 'EMP-0001 (Michael)', 'Ring doorbell twice, green gate', '2026-11-12 16:13:52', 'Paid'),
('PAY-0123', 'ORD-0124', 'Cash Only', 280.00, 'EMP-0004 (Marco)', 'Paid in cash upon delivery', '2026-11-13 08:10:15', 'Paid'),
('PAY-0124', 'ORD-0125', 'Cash Only', 30.00, 'EMP-0001 (Michael)', 'Paid in cash upon delivery', '2026-11-12 16:13:23', 'Paid'),
('PAY-0125', 'ORD-0120', 'Cash Only', 30.00, 'EMP-0001 (Michael)', 'Paid in cash upon delivery', '2026-11-12 16:13:26', 'Paid'),
('PAY-0126', 'ORD-0126', 'Cash Only', 30.00, 'EMP-0001 (Primary)', 'Cash on delivery', '2026-11-13 09:28:52', 'Paid'),
('PAY-0127', 'ORD-0127', 'Cash Only', 30.00, 'EMP-0001 (Primary)', 'Paid in cash upon delivery', '2026-11-14 09:27:51', 'Paid'),
('PAY-0128', 'ORD-0128', 'Cash Only', 100.00, 'EMP-0004 (Marco)', 'Paid in cash upon delivery', '2026-11-13 14:07:48', 'Paid');

-- --------------------------------------------------------

--
-- Table structure for table `time_slot`
--

CREATE TABLE `time_slot` (
  `slot_id` int(11) NOT NULL,
  `slot_label` varchar(50) NOT NULL,
  `time_from` time NOT NULL,
  `time_to` time NOT NULL,
  `batch_capacity` int(11) NOT NULL DEFAULT 20,
  `slot_status` enum('Open','Full','Closed') NOT NULL DEFAULT 'Open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `time_slot`
--

INSERT INTO `time_slot` (`slot_id`, `slot_label`, `time_from`, `time_to`, `batch_capacity`, `slot_status`) VALUES
(1, '8:00 AM - 10:00 AM', '08:00:00', '10:00:00', 20, 'Closed'),
(2, '10:00 AM - 12:00 PM', '10:00:00', '12:00:00', 20, 'Closed'),
(3, '12:00 PM - 2:00 PM', '12:00:00', '14:00:00', 20, 'Closed'),
(4, '2:00 PM - 4:00 PM', '14:00:00', '16:00:00', 20, 'Open'),
(5, '4:00 PM - 6:00 PM', '16:00:00', '18:00:00', 20, 'Open');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `container_product`
--
ALTER TABLE `container_product`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `uk_customer_email` (`email_address`);

--
-- Indexes for table `customer_cp_num`
--
ALTER TABLE `customer_cp_num`
  ADD PRIMARY KEY (`customer_id`,`cp_num`),
  ADD UNIQUE KEY `uk_cust_cp_type` (`customer_id`,`cp_type`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `uk_employee_email` (`email_address`),
  ADD KEY `idx_emp_manager` (`manager_id`);

--
-- Indexes for table `employee_cp_num`
--
ALTER TABLE `employee_cp_num`
  ADD PRIMARY KEY (`employee_id`,`cp_num`);

--
-- Indexes for table `order_information`
--
ALTER TABLE `order_information`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_order_customer` (`customer_id`),
  ADD KEY `idx_order_employee` (`employee_id`),
  ADD KEY `idx_order_slot` (`slot_id`),
  ADD KEY `idx_order_status` (`status_id`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`order_id`,`product_id`),
  ADD KEY `idx_item_product` (`product_id`);

--
-- Indexes for table `order_status`
--
ALTER TABLE `order_status`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `uk_status_name` (`status_name`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `uk_payment_order` (`order_id`);

--
-- Indexes for table `time_slot`
--
ALTER TABLE `time_slot`
  ADD PRIMARY KEY (`slot_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `order_status`
--
ALTER TABLE `order_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `time_slot`
--
ALTER TABLE `time_slot`
  MODIFY `slot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customer_cp_num`
--
ALTER TABLE `customer_cp_num`
  ADD CONSTRAINT `fk_cust_cp_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employee`
--
ALTER TABLE `employee`
  ADD CONSTRAINT `fk_employee_manager` FOREIGN KEY (`manager_id`) REFERENCES `employee` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `employee_cp_num`
--
ALTER TABLE `employee_cp_num`
  ADD CONSTRAINT `fk_emp_cp_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_information`
--
ALTER TABLE `order_information`
  ADD CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_slot` FOREIGN KEY (`slot_id`) REFERENCES `time_slot` (`slot_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_status` FOREIGN KEY (`status_id`) REFERENCES `order_status` (`status_id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `order_information` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `container_product` (`product_id`) ON UPDATE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `order_information` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
