-- BMI Tracker Database Schema

CREATE DATABASE IF NOT EXISTS bmi_tracker;
USE bmi_tracker;

-- Users table
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- BMI Records table
CREATE TABLE `bmi_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `height` float NOT NULL,
  `weight` float NOT NULL,
  `bmi` float NOT NULL,
  `category` varchar(20) NOT NULL,
  `measurement_system` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Food Logs table
CREATE TABLE `food_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `food_name` varchar(100) NOT NULL,
  `calories` int NOT NULL,
  `protein` float DEFAULT NULL,
  `carbs` float DEFAULT NULL,
  `fat` float DEFAULT NULL,
  `meal_type` enum('breakfast','lunch','dinner','snack') NOT NULL,
  `consumed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Views
CREATE OR REPLACE VIEW daily_nutrition_analysis AS
SELECT 
    DATE(consumed_at) as date,
    SUM(calories) as total_calories,
    ROUND(SUM(protein), 1) as total_protein,
    ROUND(SUM(carbs), 1) as total_carbs,
    ROUND(SUM(fat), 1) as total_fat,
    ROUND((SUM(protein) * 4 / SUM(calories)) * 100, 1) as protein_percent,
    ROUND((SUM(carbs) * 4 / SUM(calories)) * 100, 1) as carbs_percent,
    ROUND((SUM(fat) * 9 / SUM(calories)) * 100, 1) as fat_percent
FROM food_logs 
GROUP BY DATE(consumed_at), user_id
ORDER BY date DESC;

CREATE OR REPLACE VIEW nutrition_targets_analysis AS
SELECT 
    date,
    total_calories,
    ROUND((total_calories / 2000) * 100, 1) as calories_target_percent,
    protein_percent,
    ROUND((protein_percent / 30) * 100, 1) as protein_target_percent,
    carbs_percent,
    ROUND((carbs_percent / 50) * 100, 1) as carbs_target_percent,
    fat_percent,
    ROUND((fat_percent / 20) * 100, 1) as fat_target_percent
FROM daily_nutrition_analysis;
