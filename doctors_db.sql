-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 02:34 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `doctors_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 1,
  `name` varchar(255) NOT NULL,
  `specialty` varchar(100) NOT NULL,
  `degree` varchar(100) DEFAULT 'general',
  `governorate` varchar(100) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `zone` varchar(255) DEFAULT NULL,
  `fees` decimal(10,2) DEFAULT 0.00,
  `phone` varchar(50) DEFAULT NULL,
  `whatsapp` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 5.0,
  `is_premium` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_images`
--

CREATE TABLE `doctor_images` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_profile` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `governorates`
--

CREATE TABLE `governorates` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `governorates`
--

INSERT INTO `governorates` (`id`, `name`) VALUES
(3, 'البحر الأحمر'),
(2, 'الجزيرة'),
(1, 'الخرطوم'),
(5, 'الشمالية'),
(6, 'القضارف'),
(9, 'النيل الأبيض'),
(10, 'النيل الأزرق'),
(15, 'جنوب دارفور'),
(12, 'جنوب كردفان'),
(8, 'سنار'),
(18, 'شرق دارفور'),
(14, 'شمال دارفور'),
(11, 'شمال كردفان'),
(16, 'غرب دارفور'),
(13, 'غرب كردفان'),
(7, 'كسلا'),
(4, 'نهر النيل'),
(17, 'وسط دارفور');

-- --------------------------------------------------------

--
-- Table structure for table `specialties`
--

CREATE TABLE `specialties` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `specialties`
--

INSERT INTO `specialties` (`id`, `name`) VALUES
(3, 'أمراض الباطنة'),
(14, 'أمراض الجهاز الهضمي والكبد'),
(18, 'أمراض الدم والتغذية العلاجية'),
(15, 'أمراض الصدر والجهاز التنفسي'),
(5, 'أمراض القلب والأوعية الدموية'),
(16, 'أمراض الكلى والمسالك البولية'),
(7, 'أمراض المخ والأعصاب'),
(11, 'أمراض النساء والتوليد'),
(9, 'أنف وأذن وحنجرة'),
(12, 'الأمراض الجلدية والتناسلية'),
(17, 'جراحة الأورام'),
(13, 'جراحة التجميل والحروق'),
(6, 'جراحة المخ والأعصاب'),
(4, 'طب الأطفال والحديثي الولادة'),
(20, 'طب الروماتيزم والمفاصل'),
(19, 'طب النفسي والأعصاب'),
(22, 'طب بيطري'),
(21, 'طب عام وجراحة عامة'),
(10, 'طب وجراحة الأسنان'),
(2, 'طب وجراحة العظام'),
(8, 'طب وجراحة العيون'),
(1, 'علاج طبيعي وتأهيل صحي');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctor_images`
--
ALTER TABLE `doctor_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `governorates`
--
ALTER TABLE `governorates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `specialties`
--
ALTER TABLE `specialties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `doctor_images`
--
ALTER TABLE `doctor_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `governorates`
--
ALTER TABLE `governorates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `specialties`
--
ALTER TABLE `specialties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `doctor_images`
--
ALTER TABLE `doctor_images`
  ADD CONSTRAINT `doctor_images_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
