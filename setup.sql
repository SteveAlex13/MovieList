-- Run this SQL in your MySQL/phpMyAdmin to set up the database

CREATE DATABASE IF NOT EXISTS movielist;
USE movielist;

CREATE TABLE IF NOT EXISTS movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    director VARCHAR(255),
    genre VARCHAR(100),
    year YEAR,
    rating DECIMAL(3,1),
    synopsis TEXT,
    poster TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Watchlist/Favorites table (session-based, stored as cookie/localStorage in frontend)
-- For server-side watchlist, use this table:
CREATE TABLE IF NOT EXISTS watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    session_id VARCHAR(128) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_watchlist (movie_id, session_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- Sample data
INSERT INTO movies (title, director, genre, year, rating, synopsis, poster) VALUES
('Interstellar', 'Christopher Nolan', 'Sci-Fi', 2014, 8.7, 'Seorang mantan pilot NASA bergabung dengan tim astronot yang melakukan perjalanan melalui lubang cacing di dekat Saturnus untuk mencari planet baru yang layak huni bagi umat manusia yang sekarat.', 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg'),
('The Dark Knight', 'Christopher Nolan', 'Action', 2008, 9.0, 'Batman menghadapi kekacauan yang ditimbulkan oleh Joker, seorang kriminal misterius yang berniat menghancurkan kepercayaan rakyat Gotham dan membuktikan bahwa setiap orang bisa menjadi jahat.', 'https://image.tmdb.org/t/p/w500/qJ2tW6WMUDux911r6m7haRef0WH.jpg'),
('Parasite', 'Bong Joon-ho', 'Thriller', 2019, 8.5, 'Sebuah keluarga miskin perlahan-lahan menyusup ke kehidupan keluarga kaya dengan cara yang cerdik. Namun kejadian tak terduga membawa konsekuensi yang mengejutkan dan brutal.', 'https://image.tmdb.org/t/p/w500/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg'),
('Dune', 'Denis Villeneuve', 'Sci-Fi', 2021, 8.0, 'Paul Atreides, seorang pemuda berbakat dari keluarga bangsawan, melakukan perjalanan ke planet paling berbahaya di alam semesta untuk mengamankan komoditas terpenting — rempah melange.', 'https://image.tmdb.org/t/p/w500/d5NXSklpcvzeBO6lLHoOOy3bZGS.jpg'),
('Oppenheimer', 'Christopher Nolan', 'Biography', 2023, 8.9, 'Kisah nyata J. Robert Oppenheimer, fisikawan teoritis yang memimpin Proyek Manhattan untuk mengembangkan bom atom pertama di dunia selama Perang Dunia II.', 'https://image.tmdb.org/t/p/w500/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg');
