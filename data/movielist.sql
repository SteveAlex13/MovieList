-- phpMyAdmin SQL Dump
-- CineList Database — with Auth (users + watchlist by user_id)
-- Run this in phpMyAdmin to set up the full database.
--
-- Default accounts:
--   Admin  → username: admin   | password: admin123
--   User   → username: user1   | password: user123

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Database: `movielist`
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `movielist`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;

USE `movielist`;

-- --------------------------------------------------------
-- Table: `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id`         int          NOT NULL AUTO_INCREMENT,
  `username`   varchar(50)  NOT NULL,
  `email`      varchar(100) NOT NULL,
  `password`   varchar(255) NOT NULL,
  `role`       enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Seed accounts
-- admin123 → $2y$10$CwFyGAJJkN4xdOLcqXIuMeQ/Mahl7/vL7BJ9ptyQpIJPZ1Jg.qQIC
-- user123  → $2y$10$vrISyOB.FfWYlZyNWXZ1xex0D3TVzMDucHpoMb1ReQYdWWCpvk/Yi
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES
('admin', 'admin@cinelist.com', '$2y$10$CwFyGAJJkN4xdOLcqXIuMeQ/Mahl7/vL7BJ9ptyQpIJPZ1Jg.qQIC', 'admin'),
('user1', 'user1@cinelist.com', '$2y$10$vrISyOB.FfWYlZyNWXZ1xex0D3TVzMDucHpoMb1ReQYdWWCpvk/Yi', 'user');

-- --------------------------------------------------------
-- Table: `movies`
-- --------------------------------------------------------

CREATE TABLE `movies` (
  `id`         int          NOT NULL AUTO_INCREMENT,
  `title`      varchar(255) NOT NULL,
  `director`   varchar(255) DEFAULT NULL,
  `genre`      varchar(100) DEFAULT NULL,
  `year`       year         DEFAULT NULL,
  `rating`     decimal(3,1) DEFAULT NULL,
  `synopsis`   text,
  `poster`     text,
  `created_at` timestamp    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `movies` (`id`, `title`, `director`, `genre`, `year`, `rating`, `synopsis`, `poster`, `created_at`) VALUES
(1, 'Interstellar', 'Christopher Nolan', 'Sci-Fi', '2014', 8.7, 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity''s survival.', 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg', '2026-05-18 19:15:16'),
(2, 'The Dark Knight', 'Christopher Nolan', 'Action', '2008', 9.0, NULL, 'https://image.tmdb.org/t/p/w500/qJ2tW6WMUDux911r6m7haRef0WH.jpg', '2026-05-18 19:15:16'),
(3, 'Parasite', 'Bong Joon-ho', 'Thriller', '2019', 8.5, NULL, 'https://image.tmdb.org/t/p/w500/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg', '2026-05-18 19:15:16'),
(4, 'Dune', 'Denis Villeneuve', 'Sci-Fi', '2021', 8.0, NULL, 'https://image.tmdb.org/t/p/w500/d5NXSklpcvzeBO6lLHoOOy3bZGS.jpg', '2026-05-18 19:15:16'),
(5, 'Oppenheimer', 'Christopher Nolan', 'Biography', '2023', 8.9, NULL, 'https://image.tmdb.org/t/p/w500/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg', '2026-05-18 19:15:16');

-- --------------------------------------------------------
-- Table: `watchlist`  (now keyed by user_id, not session_id)
-- --------------------------------------------------------

CREATE TABLE `watchlist` (
  `id`       int NOT NULL AUTO_INCREMENT,
  `user_id`  int NOT NULL,
  `movie_id` int NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_watchlist` (`user_id`, `movie_id`),
  CONSTRAINT `fk_watchlist_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_watchlist_movie` FOREIGN KEY (`movie_id`) REFERENCES `movies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- AUTO_INCREMENT
ALTER TABLE `movies` MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `users`  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
