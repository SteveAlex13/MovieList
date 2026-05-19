-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 19, 2026 at 01:34 AM
-- Server version: 8.0.30
-- PHP Version: 8.4.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `movielist`
--

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `director` varchar(255) DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `year` year DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `poster` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`id`, `title`, `director`, `genre`, `year`, `rating`, `poster`, `created_at`) VALUES
(1, 'Interstellar', 'Christopher Nolan', 'Sci-Fi', '2014', 8.7, 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg', '2026-05-18 19:15:16'),
(2, 'The Dark Knight', 'Christopher Nolan', 'Action', '2008', 9.0, 'https://image.tmdb.org/t/p/w500/qJ2tW6WMUDux911r6m7haRef0WH.jpg', '2026-05-18 19:15:16'),
(3, 'Parasite', 'Bong Joon-ho', 'Thriller', '2019', 8.5, 'https://image.tmdb.org/t/p/w500/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg', '2026-05-18 19:15:16'),
(4, 'Dune', 'Denis Villeneuve', 'Sci-Fi', '2021', 8.0, 'https://image.tmdb.org/t/p/w500/d5NXSklpcvzeBO6lLHoOOy3bZGS.jpg', '2026-05-18 19:15:16'),
(5, 'Oppenheimer', 'Christopher Nolan', 'Biography', '2023', 8.9, 'https://image.tmdb.org/t/p/w500/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg', '2026-05-18 19:15:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
