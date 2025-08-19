-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 13, 2025 at 11:59 PM
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
-- Database: `medicare`
--

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `meal_timing` varchar(100) DEFAULT NULL,
  `intake_time` datetime DEFAULT NULL,
  `status` enum('Taken','Missed','Not Updated') DEFAULT 'Not Updated',
  `image_path` varchar(255) DEFAULT NULL,
  `audio_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status_updated_at` datetime DEFAULT NULL,
  `status_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `patient_id`, `name`, `dosage`, `frequency`, `meal_timing`, `intake_time`, `status`, `image_path`, `audio_path`, `created_at`, `updated_at`, `status_updated_at`, `status_time`) VALUES
(1, 1, 'Napa', '1 tablet', 'Twice a day', 'After meal', '2025-08-08 14:05:00', 'Taken', 'uploads/med_6895b01482b06.jpg', NULL, '2025-08-08 08:06:44', '2025-08-08 14:15:03', NULL, NULL),
(2, 1, 'napa', '5 ml', 'Every 6 hours', 'After meal', '2025-08-15 16:31:00', 'Not Updated', 'uploads/med_689c6991b8d6a.png', NULL, '2025-08-13 10:31:45', '2025-08-13 16:31:45', NULL, NULL),
(3, 4, 'histasin', '1 tablet', 'Twice a day', 'After meal', '2025-08-13 16:34:00', 'Taken', 'uploads/med_689c6a5abd0b4.webp', NULL, '2025-08-13 10:35:06', '2025-08-13 16:35:27', NULL, NULL),
(4, 4, 'seclo', '1 tablet', 'Twice a day', 'Before meal', '2025-08-14 01:00:00', 'Missed', 'uploads/med_689ce0cfa767a.webp', NULL, '2025-08-13 19:00:31', '2025-08-14 01:46:58', '2025-08-14 01:46:58', '2025-08-14 01:29:35'),
(5, 4, 'Amodix', '2 tablets', 'Twice a day', 'After meal', '2025-08-14 01:48:00', 'Taken', 'uploads/med_689cec2756736.webp', NULL, '2025-08-13 19:48:55', '2025-08-14 01:51:02', '2025-08-14 01:51:02', NULL),
(6, 4, 'napa extend', '2 tablets', 'Twice a day', 'After meal', '2025-08-14 01:53:00', 'Taken', 'uploads/med_689ced28b081f.webp', NULL, '2025-08-13 19:53:12', '2025-08-14 03:27:20', '2025-08-13 21:56:23', '2025-08-14 03:27:20'),
(7, 4, 'renamisin', '2 tablets', 'Twice a day', 'After meal', '2025-08-14 02:08:00', 'Taken', 'uploads/med_689cf0b4e529c.webp', NULL, '2025-08-13 20:08:20', '2025-08-14 02:08:48', NULL, '2025-08-14 02:08:48'),
(8, 4, 'zolax', '1 tablet', 'Once a day', 'Before meal', '2025-08-14 02:14:00', 'Missed', 'uploads/med_689cf2109530c.webp', NULL, '2025-08-13 20:14:08', '2025-08-14 02:30:27', NULL, '2025-08-14 02:30:27'),
(9, 4, 'dsfcsdfc', '2 tablets', 'Twice a day', 'Before meal', '2025-08-14 02:35:00', 'Taken', 'uploads/med_689cf7347fe7b.webp', NULL, '2025-08-13 20:36:04', '2025-08-14 02:36:49', NULL, '2025-08-14 02:36:49'),
(10, 4, 'fgd', '1 tablet', 'Once a day', 'Before meal', '2025-08-14 02:51:00', 'Taken', 'uploads/med_689cfae95ede6.webp', NULL, '2025-08-13 20:51:53', '2025-08-14 03:28:30', NULL, '2025-08-14 03:28:30'),
(11, 4, 'renata', '2 tablets', 'Twice a day', 'After meal', '2025-08-14 03:44:00', 'Not Updated', 'uploads/med_689d0764156d2.webp', 'uploads/audio/voice_689d076416e39.webm', '2025-08-13 21:45:08', '2025-08-14 03:45:08', NULL, NULL),
(12, 4, 'khanki', '2 tablets', 'Twice a day', 'Before meal', '2025-08-14 03:46:00', 'Taken', 'uploads/med_689d07cd856ba.webp', 'uploads/audio/voice_689d07cd86075.webm', '2025-08-13 21:46:53', '2025-08-14 03:47:30', NULL, '2025-08-14 03:47:30');

-- --------------------------------------------------------

--
-- Table structure for table `medicines_history`
--

CREATE TABLE `medicines_history` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `status` enum('Taken','Missed') NOT NULL,
  `status_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines_history`
--

INSERT INTO `medicines_history` (`id`, `medicine_id`, `patient_id`, `status`, `status_time`) VALUES
(1, 4, 4, 'Taken', '2025-08-14 01:40:03'),
(2, 4, 4, 'Missed', '2025-08-14 01:41:03'),
(3, 4, 4, 'Taken', '2025-08-14 01:41:12'),
(4, 4, 4, 'Missed', '2025-08-14 01:41:22'),
(5, 4, 4, 'Missed', '2025-08-14 01:46:58'),
(6, 5, 4, 'Taken', '2025-08-14 01:49:16'),
(7, 5, 4, 'Missed', '2025-08-14 01:49:59'),
(8, 5, 4, 'Taken', '2025-08-14 01:51:02'),
(9, 6, 4, 'Taken', '2025-08-14 03:27:11'),
(10, 6, 4, 'Taken', '2025-08-14 03:27:20'),
(11, 10, 4, 'Taken', '2025-08-14 03:27:31'),
(12, 10, 4, 'Taken', '2025-08-14 03:28:30'),
(13, 12, 4, 'Taken', '2025-08-14 03:47:25'),
(14, 12, 4, 'Taken', '2025-08-14 03:47:30');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_status_log`
--

CREATE TABLE `medicine_status_log` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `status` enum('Taken','Missed') NOT NULL,
  `status_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicine_status_log`
--

INSERT INTO `medicine_status_log` (`id`, `patient_id`, `medicine_id`, `status`, `status_time`) VALUES
(1, 4, 6, 'Taken', '2025-08-14 01:53:35'),
(2, 4, 6, 'Taken', '2025-08-14 01:58:21'),
(3, 4, 7, 'Taken', '2025-08-14 02:08:37'),
(4, 4, 7, 'Taken', '2025-08-14 02:08:48'),
(5, 4, 8, 'Taken', '2025-08-14 02:14:22'),
(6, 4, 8, 'Taken', '2025-08-14 02:18:33'),
(7, 4, 8, 'Missed', '2025-08-14 02:24:16'),
(8, 4, 8, 'Missed', '2025-08-14 02:30:27'),
(9, 4, 9, 'Taken', '2025-08-14 02:36:17'),
(10, 4, 9, 'Taken', '2025-08-14 02:36:49'),
(11, 4, 10, 'Taken', '2025-08-14 02:52:10'),
(12, 4, 10, 'Taken', '2025-08-14 02:53:15'),
(13, 4, 10, 'Missed', '2025-08-14 02:53:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('patient','caretaker') DEFAULT 'patient',
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `role`, `registered_at`) VALUES
(1, 'saif', '2002047@icte.bdu.ac.bd', '1314445466', '$2y$10$yBgF3FExkVvil8dxlR.vGeHv/Y6t3wFcrq89bt6at7vfqjb3Fhtxa', 'patient', '2025-08-04 06:07:10'),
(2, 'saiful', 'msijewel09@gmail.com', '1314445466', '$2y$10$kYo3ds4QEYkznGSFerdZqu2K9nEmOdZGhKMikUS5C0Q4o0rB/IR.i', 'caretaker', '2025-08-04 06:43:55'),
(3, 'mamu', 'sajib@gmail.com', '1874117136', '$2y$10$TpsGfpXTeH7lTnR9PqC2z.sGssn5xWlckI6WkzMjtxWcCMUrarQza', 'caretaker', '2025-08-13 10:29:03'),
(4, 'sajiba', 'sajiba@gmail.com', '01933333333', '$2y$10$LNpPzRHYA3dxwCXtdjGrG.SRNN34GSfKdut2hZHVzTWRaHZKrInNO', 'patient', '2025-08-13 10:33:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `medicines_history`
--
ALTER TABLE `medicines_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `medicine_status_log`
--
ALTER TABLE `medicine_status_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `medicines_history`
--
ALTER TABLE `medicines_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `medicine_status_log`
--
ALTER TABLE `medicine_status_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medicines_history`
--
ALTER TABLE `medicines_history`
  ADD CONSTRAINT `medicines_history_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`),
  ADD CONSTRAINT `medicines_history_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
