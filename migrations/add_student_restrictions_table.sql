-- Create student_restrictions table for admin to block students from applying leave/outing

CREATE TABLE IF NOT EXISTS `student_restrictions` (
  `restriction_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `roll_number` varchar(50) NOT NULL,
  `restriction_type` enum('leave', 'outing') NOT NULL,
  `created_by` varchar(100) NOT NULL COMMENT 'admin, male_admin, or female_admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `removed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`restriction_id`),
  KEY `student_id` (`student_id`),
  KEY `roll_number` (`roll_number`),
  KEY `restriction_type` (`restriction_type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
