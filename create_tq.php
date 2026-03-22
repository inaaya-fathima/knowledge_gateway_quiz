<?php
require 'config/db.php';
$pdo->exec("CREATE TABLE IF NOT EXISTS `test_questions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `admin_username` varchar(50) NOT NULL,
    `topic` varchar(100) NOT NULL,
    `question` text NOT NULL,
    `opt_a` varchar(255) NOT NULL,
    `opt_b` varchar(255) NOT NULL,
    `opt_c` varchar(255) NOT NULL,
    `opt_d` varchar(255) NOT NULL,
    `correct_answer` int(11) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
echo "OK";
