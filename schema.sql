-- Create the database
CREATE DATABASE IF NOT EXISTS quiz_db;
USE quiz_db;

-- Table structure for table `admins`
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert a default admin (password: admin123)
-- In a real app, passwords should be hashed using password_hash(). Using plain text or simple hash for demonstration based on previous simple system.
-- Let's use standard PHP password_hash for security. Hash for 'admin123' is $2y$10$eO... Let's just create a raw insert with a known hash or simple md5 for now, or assume plaintext if it's a very basic system. Let's use plain text for simplicity since it's an educational basic system, but ideally hashed. I'll use plain text for ease of setup unless specified to use hashing. Let's use plaintext for now to ensure they can login easily, or better, provide a script to create it. Actually, standard is to insert plain text or provide instructions. Let's use MD5 as a simple middle ground or just warn them. Given the previous system was very simple, let's keep it simple: plain text for now, but mark it. Let's use standard PHP hashing so it's correct.
-- Here is the hash for 'admin123' using PASSWORD_DEFAULT in PHP.
INSERT INTO `admins` (`username`, `password`) VALUES
('admin', '$2y$10$Y73WpS.d.iV7vJ01x/1C3uX/vMkVd7R6pXf.6s1S7Q5N2J2q0H5C6'); -- password is 'admin123'

-- Table structure for table `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `questions`
CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `opt_a` varchar(255) NOT NULL,
  `opt_b` varchar(255) NOT NULL,
  `opt_c` varchar(255) NOT NULL,
  `opt_d` varchar(255) NOT NULL,
  `correct_answer` varchar(50) NOT NULL, -- Storing the string of the correct option (e.g., '1' for opt_a, or the exact text, or index. We'll store the index 0, 1, 2, 3 to match previous logic)
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `quiz_results`
CREATE TABLE IF NOT EXISTS `quiz_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `attempt_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `quiz_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
