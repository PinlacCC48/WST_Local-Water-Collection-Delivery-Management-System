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
('EMP-0007', 'Admin', 'Tester', '123', 'Test Street', 'Barangay 22', 'Caloocan City', 'Admin12345@gmail.com', '$2y$10$m3YMf83vRKwVabUMUL7XH.y1OwIXgj6/OhVmoHH4JdL/wMT3zJKI2', 'Admin', '2026-11-11', 'Active', NULL);

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
(1, '8:00 AM - 10:00 AM', '08:00:00', '10:00:00', 20, 'Open'),
(2, '10:00 AM - 12:00 PM', '10:00:00', '12:00:00', 20, 'Open'),
(3, '12:00 PM - 2:00 PM', '12:00:00', '14:00:00', 20, 'Open'),
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
