-- Class Booking System Database Export
-- Generated on: 2025-06-24 19:18:21

SET FOREIGN_KEY_CHECKS = 0;

-- Table structure for `admin`
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `admin`
INSERT INTO `admin` (`id`, `username`, `password_hash`, `created_at`) VALUES ('2', 'admin', '$2y$10$P9bYjSVBk2EiYc4fZtop.uvUhgDeOL5FcuNGw7eQOJbLk3QrkKflm', '2025-06-18 12:34:56');

-- Table structure for `bookings`
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `membership_cycle` varchar(7) DEFAULT NULL COMMENT 'YYYY-MM format for tracking monthly limits',
  `is_free_trial` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `idx_bookings_user_id` (`user_id`),
  KEY `idx_bookings_membership_cycle` (`membership_cycle`),
  KEY `idx_bookings_free_trial` (`is_free_trial`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `bookings`
INSERT INTO `bookings` (`id`, `user_id`, `class_id`, `name`, `email`, `created_at`, `membership_cycle`, `is_free_trial`) VALUES ('1', NULL, '6', 'John Doe', 'john@example.com', '2025-06-19 16:27:37', NULL, '0');
INSERT INTO `bookings` (`id`, `user_id`, `class_id`, `name`, `email`, `created_at`, `membership_cycle`, `is_free_trial`) VALUES ('2', NULL, '6', 'Jane Smith', 'jane@example.com', '2025-06-19 16:27:38', NULL, '0');
INSERT INTO `bookings` (`id`, `user_id`, `class_id`, `name`, `email`, `created_at`, `membership_cycle`, `is_free_trial`) VALUES ('3', NULL, '8', 'Bob Johnson', 'bob@example.com', '2025-06-19 16:27:38', NULL, '0');
INSERT INTO `bookings` (`id`, `user_id`, `class_id`, `name`, `email`, `created_at`, `membership_cycle`, `is_free_trial`) VALUES ('4', NULL, '13', 'Alice Brown', 'alice@example.com', '2025-06-19 16:27:38', NULL, '0');
INSERT INTO `bookings` (`id`, `user_id`, `class_id`, `name`, `email`, `created_at`, `membership_cycle`, `is_free_trial`) VALUES ('5', NULL, '13', 'Charlie Wilson', 'charlie@example.com', '2025-06-19 16:27:38', NULL, '0');
INSERT INTO `bookings` (`id`, `user_id`, `class_id`, `name`, `email`, `created_at`, `membership_cycle`, `is_free_trial`) VALUES ('7', '2', '6', 'DeVante Johnson-Rose', 'djrnw9@live.co.uk', '2025-06-19 16:57:32', NULL, '0');
INSERT INTO `bookings` (`id`, `user_id`, `class_id`, `name`, `email`, `created_at`, `membership_cycle`, `is_free_trial`) VALUES ('8', '2', '7', 'DeVante Johnson-Rose', 'djrnw9@live.co.uk', '2025-06-19 16:57:37', NULL, '0');
INSERT INTO `bookings` (`id`, `user_id`, `class_id`, `name`, `email`, `created_at`, `membership_cycle`, `is_free_trial`) VALUES ('9', '2', '10', 'DeVante Johnson-Rose', 'djrnw9@live.co.uk', '2025-06-24 11:24:32', NULL, '0');

-- Table structure for `classes`
DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `capacity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `instructor_id` int(11) DEFAULT NULL,
  `recurring` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_classes_instructor` (`instructor_id`),
  CONSTRAINT `fk_classes_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `classes`
INSERT INTO `classes` (`id`, `name`, `description`, `date`, `time`, `capacity`, `created_at`, `instructor_id`, `recurring`) VALUES ('6', 'Morning Yoga', 'Start your day with a peaceful yoga session', '2025-06-20', '11:15:00', '15', '2025-06-19 16:27:37', '1', '1');
INSERT INTO `classes` (`id`, `name`, `description`, `date`, `time`, `capacity`, `created_at`, `instructor_id`, `recurring`) VALUES ('7', 'Evening Pilates', 'Core strengthening and flexibility training', '2025-06-20', '18:00:00', '12', '2025-06-19 16:27:37', '2', '1');
INSERT INTO `classes` (`id`, `name`, `description`, `date`, `time`, `capacity`, `created_at`, `instructor_id`, `recurring`) VALUES ('8', 'HIIT Training', 'High-intensity interval training for all levels', '2025-06-21', '07:00:00', '10', '2025-06-19 16:27:37', '1', '1');
INSERT INTO `classes` (`id`, `name`, `description`, `date`, `time`, `capacity`, `created_at`, `instructor_id`, `recurring`) VALUES ('9', 'Mindfulness Meditation', 'Guided meditation and relaxation techniques', '2025-06-21', '19:00:00', '20', '2025-06-19 16:27:37', '2', '0');
INSERT INTO `classes` (`id`, `name`, `description`, `date`, `time`, `capacity`, `created_at`, `instructor_id`, `recurring`) VALUES ('10', 'Beginner Yoga', 'Perfect introduction to yoga practice', '2025-06-22', '10:00:00', '16', '2025-06-19 16:27:37', '3', '0');
INSERT INTO `classes` (`id`, `name`, `description`, `date`, `time`, `capacity`, `created_at`, `instructor_id`, `recurring`) VALUES ('11', 'Advanced Pilates', 'Challenging Pilates workout for experienced practitioners', '2025-06-22', '17:30:00', '8', '2025-06-19 16:27:37', '4', '0');
INSERT INTO `classes` (`id`, `name`, `description`, `date`, `time`, `capacity`, `created_at`, `instructor_id`, `recurring`) VALUES ('12', 'Hatha Yoga', 'Traditional yoga focusing on postures and breathing', '2025-06-23', '11:00:00', '14', '2025-06-19 16:27:37', '5', '0');
INSERT INTO `classes` (`id`, `name`, `description`, `date`, `time`, `capacity`, `created_at`, `instructor_id`, `recurring`) VALUES ('13', 'Weekend Bootcamp', 'Full-body workout combining cardio and strength', '2025-06-24', '08:00:00', '12', '2025-06-19 16:27:37', '1', '0');
INSERT INTO `classes` (`id`, `name`, `description`, `date`, `time`, `capacity`, `created_at`, `instructor_id`, `recurring`) VALUES ('14', 'Gentle Yoga', 'Slow-paced yoga perfect for beginners and seniors', '2025-06-25', '14:00:00', '18', '2025-06-19 16:27:37', '3', '0');
INSERT INTO `classes` (`id`, `name`, `description`, `date`, `time`, `capacity`, `created_at`, `instructor_id`, `recurring`) VALUES ('15', 'Power Pilates', 'Dynamic Pilates class for strength and endurance', '2025-06-26', '16:00:00', '10', '2025-06-19 16:27:37', '4', '0');

-- Table structure for `instructors`
DROP TABLE IF EXISTS `instructors`;
CREATE TABLE `instructors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `specialties` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `instructors`
INSERT INTO `instructors` (`id`, `first_name`, `last_name`, `email`, `phone`, `bio`, `specialties`, `created_at`, `updated_at`, `status`) VALUES ('1', 'Sarah', 'Johnson', 'sarah.johnson@studio.com', '555-0101', 'Certified yoga instructor with 10+ years experience. Specializes in Hatha and Vinyasa yoga.', 'Hatha Yoga, Vinyasa, Meditation', '2025-06-19 15:01:30', '2025-06-19 15:01:30', 'active');
INSERT INTO `instructors` (`id`, `first_name`, `last_name`, `email`, `phone`, `bio`, `specialties`, `created_at`, `updated_at`, `status`) VALUES ('2', 'Mike', 'Chen', 'mike.chen@studio.com', '555-0102', 'Personal trainer and Pilates instructor. Former athlete with expertise in strength training.', 'Pilates, HIIT, Strength Training', '2025-06-19 15:01:30', '2025-06-19 15:01:30', 'active');
INSERT INTO `instructors` (`id`, `first_name`, `last_name`, `email`, `phone`, `bio`, `specialties`, `created_at`, `updated_at`, `status`) VALUES ('3', 'Emma', 'Davis', 'emma.davis@studio.com', '555-0103', 'Mindfulness coach and meditation teacher. Creates calming environments for healing and growth.', 'Meditation, Mindfulness, Breathwork', '2025-06-19 15:01:30', '2025-06-19 15:01:30', 'active');
INSERT INTO `instructors` (`id`, `first_name`, `last_name`, `email`, `phone`, `bio`, `specialties`, `created_at`, `updated_at`, `status`) VALUES ('4', 'Alex', 'Rodriguez', 'alex.rodriguez@studio.com', '555-0104', 'High-intensity training specialist. Motivational coach focused on fitness transformations.', 'HIIT, CrossFit, Weight Training', '2025-06-19 15:01:30', '2025-06-19 15:01:30', 'active');
INSERT INTO `instructors` (`id`, `first_name`, `last_name`, `email`, `phone`, `bio`, `specialties`, `created_at`, `updated_at`, `status`) VALUES ('5', 'Lisa', 'Thompson', 'lisa.thompson@studio.com', '555-0105', 'Beginner-friendly yoga instructor. Patient and encouraging approach to wellness.', 'Beginner Yoga, Gentle Yoga, Seniors Fitness', '2025-06-19 15:01:30', '2025-06-19 15:01:30', 'active');

-- Table structure for `membership_payments`
DROP TABLE IF EXISTS `membership_payments`;
CREATE TABLE `membership_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_membership_id` int(11) NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `payment_method` enum('cash','card','online_transfer','bank_transfer') NOT NULL,
  `status` enum('paid','pending','failed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `recorded_by_admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `recorded_by_admin_id` (`recorded_by_admin_id`),
  KEY `idx_payments_user_membership` (`user_membership_id`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_date` (`payment_date`),
  CONSTRAINT `membership_payments_ibfk_1` FOREIGN KEY (`user_membership_id`) REFERENCES `user_memberships` (`id`) ON DELETE CASCADE,
  CONSTRAINT `membership_payments_ibfk_2` FOREIGN KEY (`recorded_by_admin_id`) REFERENCES `admin` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for `membership_plans`
DROP TABLE IF EXISTS `membership_plans`;
CREATE TABLE `membership_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `monthly_class_limit` int(11) DEFAULT NULL COMMENT 'NULL for unlimited plans',
  `price` decimal(8,2) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `membership_plans`
INSERT INTO `membership_plans` (`id`, `name`, `description`, `monthly_class_limit`, `price`, `status`, `created_at`, `updated_at`) VALUES ('1', 'Free Trial', 'One free trial class for new members', '1', '0.00', 'active', '2025-06-24 15:29:57', '2025-06-24 15:29:57');
INSERT INTO `membership_plans` (`id`, `name`, `description`, `monthly_class_limit`, `price`, `status`, `created_at`, `updated_at`) VALUES ('2', 'Basic Plan', '4 classes per month - perfect for beginners', '4', '39.99', 'active', '2025-06-24 15:29:57', '2025-06-24 15:29:57');
INSERT INTO `membership_plans` (`id`, `name`, `description`, `monthly_class_limit`, `price`, `status`, `created_at`, `updated_at`) VALUES ('3', 'Standard Plan', '8 classes per month - great for regular attendees', '8', '69.99', 'active', '2025-06-24 15:29:57', '2025-06-24 15:29:57');
INSERT INTO `membership_plans` (`id`, `name`, `description`, `monthly_class_limit`, `price`, `status`, `created_at`, `updated_at`) VALUES ('4', 'Premium Plan', '12 classes per month - for fitness enthusiasts', '12', '89.99', 'active', '2025-06-24 15:29:57', '2025-06-24 15:29:57');
INSERT INTO `membership_plans` (`id`, `name`, `description`, `monthly_class_limit`, `price`, `status`, `created_at`, `updated_at`) VALUES ('5', 'Unlimited Plan', 'Unlimited classes - for serious practitioners', NULL, '129.99', 'active', '2025-06-24 15:29:57', '2025-06-24 15:29:57');

-- Table structure for `user_memberships`
DROP TABLE IF EXISTS `user_memberships`;
CREATE TABLE `user_memberships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `free_trial_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `plan_id` (`plan_id`),
  KEY `idx_user_memberships_user_id` (`user_id`),
  KEY `idx_user_memberships_status` (`status`),
  KEY `idx_user_memberships_dates` (`start_date`,`end_date`),
  CONSTRAINT `user_memberships_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_memberships_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `membership_plans` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `user_memberships`
INSERT INTO `user_memberships` (`id`, `user_id`, `plan_id`, `start_date`, `end_date`, `status`, `free_trial_used`, `created_at`, `updated_at`) VALUES ('1', '2', '2', '2025-06-24', '2025-07-24', 'active', '0', '2025-06-24 15:38:02', '2025-06-24 15:38:02');
INSERT INTO `user_memberships` (`id`, `user_id`, `plan_id`, `start_date`, `end_date`, `status`, `free_trial_used`, `created_at`, `updated_at`) VALUES ('2', '2', '2', '2025-06-24', '2025-07-24', 'active', '0', '2025-06-24 15:38:26', '2025-06-24 15:38:26');
INSERT INTO `user_memberships` (`id`, `user_id`, `plan_id`, `start_date`, `end_date`, `status`, `free_trial_used`, `created_at`, `updated_at`) VALUES ('3', '2', '2', '2025-06-24', '2025-07-24', 'active', '0', '2025-06-24 17:30:51', '2025-06-24 17:30:51');

-- Table structure for `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `health_questionnaire` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`health_questionnaire`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `free_trial_used` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_dob` (`date_of_birth`),
  KEY `idx_users_free_trial` (`free_trial_used`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `users`
INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password_hash`, `phone`, `date_of_birth`, `gender`, `health_questionnaire`, `created_at`, `updated_at`, `status`, `free_trial_used`) VALUES ('1', 'John', 'Doe', 'john@example.com', '$2y$10$8htsLEiArXD41b/uEBu9p.wY6OkRuXHkFK0vJwna7YEYCMik9JWwC', '555-1234', NULL, NULL, NULL, '2025-06-18 14:31:31', '2025-06-18 14:31:31', 'active', '0');
INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password_hash`, `phone`, `date_of_birth`, `gender`, `health_questionnaire`, `created_at`, `updated_at`, `status`, `free_trial_used`) VALUES ('2', 'DeVante', 'Johnson-Rose', 'djrnw9@live.co.uk', '$2y$10$cWYMfIQDfjt96kcX37uxL.0WfKYytiVCwZs9t8zv0Cfg51ypvUcGq', '07429490333', '1992-09-30', 'male', '{\"has_medical_conditions\":true,\"medical_conditions\":\"Knee surgery recently\",\"takes_medication\":false,\"medication_details\":\"\",\"has_injuries\":false,\"injury_details\":\"\",\"emergency_contact_name\":\"Shalome\",\"emergency_contact_phone\":\"07974256580\",\"emergency_contact_relationship\":\"Mum\",\"fitness_level\":\"advanced\",\"exercise_limitations\":\"\",\"has_allergies\":false,\"allergy_details\":\"\",\"consent_medical_emergency\":true,\"completed_at\":\"2025-06-19 17:49:37\",\"updated_at\":\"2025-06-19 18:19:11\"}', '2025-06-19 16:49:37', '2025-06-19 17:19:11', 'active', '0');
INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password_hash`, `phone`, `date_of_birth`, `gender`, `health_questionnaire`, `created_at`, `updated_at`, `status`, `free_trial_used`) VALUES ('3', 'DeVante', 'Test', 'devante@test.com', '$2y$10$pSZWpH/4LbGS5EsIFCfKZOiOa0N4k.rxWfnnysnluE/XbCBcUwzDC', '555-123-4567', '1990-01-01', NULL, '{\"has_medical_conditions\":false,\"medical_conditions\":\"\",\"takes_medication\":false,\"medication_details\":\"\",\"has_injuries\":false,\"injury_details\":\"\",\"emergency_contact_name\":\"John Test\",\"emergency_contact_phone\":\"555-999-8888\",\"emergency_contact_relationship\":\"Brother\",\"fitness_level\":\"\",\"exercise_limitations\":\"\",\"has_allergies\":false,\"allergy_details\":\"\",\"consent_medical_emergency\":true,\"completed_at\":\"2025-06-24 11:54:50\"}', '2025-06-24 10:54:50', '2025-06-24 10:54:50', 'active', '0');
INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password_hash`, `phone`, `date_of_birth`, `gender`, `health_questionnaire`, `created_at`, `updated_at`, `status`, `free_trial_used`) VALUES ('4', 'Test', 'User', 'test@membership.com', '$2y$10$M0U0n5M3PEOtR.zgtB.lyu..fwAyl2OErvjbnDE1eFGbJ4cWQnSku', '1234567890', NULL, NULL, NULL, '2025-06-24 16:19:22', '2025-06-24 16:19:22', 'active', '0');

-- Table structure for `video_series`
DROP TABLE IF EXISTS `video_series`;
CREATE TABLE `video_series` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_video_series_status` (`status`),
  KEY `idx_video_series_sort` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `video_series`
INSERT INTO `video_series` (`id`, `title`, `description`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES ('1', 'Beginner Fundamentals', 'Essential techniques and movements for beginners', NULL, '1', 'active', '2025-06-24 15:29:57', '2025-06-24 15:29:57');
INSERT INTO `video_series` (`id`, `title`, `description`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES ('2', 'Advanced Techniques', 'Advanced movements for experienced practitioners', NULL, '2', 'active', '2025-06-24 15:29:57', '2025-06-24 15:29:57');
INSERT INTO `video_series` (`id`, `title`, `description`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES ('3', 'Flexibility & Mobility', 'Stretching and mobility routines', NULL, '3', 'active', '2025-06-24 15:29:57', '2025-06-24 15:29:57');
INSERT INTO `video_series` (`id`, `title`, `description`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES ('4', 'Nutrition & Wellness', 'Health tips and nutritional guidance', NULL, '4', 'active', '2025-06-24 15:29:57', '2025-06-24 15:29:57');
INSERT INTO `video_series` (`id`, `title`, `description`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES ('5', 'Beginner Fundamentals', 'Essential techniques and movements for beginners', NULL, '1', 'active', '2025-06-24 15:39:18', '2025-06-24 15:39:18');
INSERT INTO `video_series` (`id`, `title`, `description`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES ('6', 'Advanced Techniques', 'Advanced movements for experienced practitioners', NULL, '2', 'active', '2025-06-24 15:39:18', '2025-06-24 15:39:18');
INSERT INTO `video_series` (`id`, `title`, `description`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES ('7', 'Flexibility & Mobility', 'Stretching and mobility routines', NULL, '3', 'active', '2025-06-24 15:39:18', '2025-06-24 15:39:18');
INSERT INTO `video_series` (`id`, `title`, `description`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES ('8', 'Nutrition & Wellness', 'Health tips and nutritional guidance', NULL, '4', 'active', '2025-06-24 15:39:18', '2025-06-24 15:39:18');

-- Table structure for `videos`
DROP TABLE IF EXISTS `videos`;
CREATE TABLE `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `series_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `format` varchar(20) NOT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_videos_series` (`series_id`),
  KEY `idx_videos_status` (`status`),
  KEY `idx_videos_sort` (`sort_order`),
  CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`series_id`) REFERENCES `video_series` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
