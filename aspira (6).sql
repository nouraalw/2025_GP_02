-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: 14 أبريل 2025 الساعة 01:16
-- إصدار الخادم: 5.7.24
-- PHP Version: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aspira`
--

-- --------------------------------------------------------

--
-- بنية الجدول `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- إرجاع أو استيراد بيانات الجدول `admins`
--

INSERT INTO `admins` (`admin_id`, `email`, `password`) VALUES
(1, 'abd123@gmail.com', '$2y$10$aciU46JG/cgx3UtmmiJs9.kjAayltUQu/4/ExGlasLY7Ao60HtE/2');

-- --------------------------------------------------------

--
-- بنية الجدول `field`
--

CREATE TABLE `field` (
  `id` int(11) NOT NULL,
  `field_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- إرجاع أو استيراد بيانات الجدول `field`
--

INSERT INTO `field` (`id`, `field_name`) VALUES
(98, '3D Modeling'),
(70, 'Accounting'),
(80, 'Actuarial Science'),
(95, 'Advertising'),
(26, 'Aerospace Engineering'),
(64, 'Anesthesiology'),
(104, 'Anthropology'),
(88, 'Architecture'),
(3, 'Artificial Intelligence'),
(43, 'Astronomy'),
(44, 'Astrophysics'),
(119, 'Athletic Training'),
(17, 'Augmented Reality (AR)'),
(39, 'Biochemistry'),
(38, 'Biology'),
(32, 'Biomedical Engineering'),
(42, 'Biotechnology'),
(10, 'Blockchain'),
(79, 'Business Analytics'),
(69, 'Business Management'),
(61, 'Cardiology'),
(27, 'Chemical Engineering'),
(37, 'Chemistry'),
(23, 'Civil Engineering'),
(9, 'Cloud Computing'),
(1, 'Computer Science'),
(93, 'Creative Writing'),
(105, 'Criminology'),
(115, 'Curriculum Development'),
(5, 'Cybersecurity'),
(2, 'Data Science'),
(19, 'Database Administration'),
(53, 'Dentistry'),
(60, 'Dermatology'),
(13, 'DevOps'),
(16, 'Digital Forensics'),
(76, 'E-Commerce'),
(113, 'Early Childhood Education'),
(111, 'Educational Technology'),
(24, 'Electrical Engineering'),
(25, 'Electronics Engineering'),
(74, 'Entrepreneurship'),
(33, 'Environmental Engineering'),
(50, 'Environmental Science'),
(21, 'Ethical Hacking'),
(86, 'Fashion Design'),
(89, 'Film & Animation'),
(71, 'Finance'),
(91, 'Fine Arts'),
(96, 'Game Art & Design'),
(12, 'Game Development'),
(40, 'Genetics'),
(46, 'Geology'),
(84, 'Graphic Design'),
(73, 'Human Resources'),
(108, 'Human Rights'),
(30, 'Industrial Engineering'),
(82, 'Insurance'),
(87, 'Interior Design'),
(83, 'International Business'),
(101, 'International Relations'),
(11, 'Internet of Things (IoT)'),
(75, 'Investment Banking'),
(14, 'IT Support'),
(94, 'Journalism'),
(99, 'Law'),
(114, 'Linguistics'),
(4, 'Machine Learning'),
(72, 'Marketing'),
(35, 'Materials Science'),
(22, 'Mechanical Engineering'),
(31, 'Mechatronics'),
(58, 'Medical Laboratory Science'),
(51, 'Medicine'),
(47, 'Meteorology'),
(41, 'Microbiology'),
(8, 'Mobile App Development'),
(92, 'Music Production'),
(34, 'Nanotechnology'),
(15, 'Network Security'),
(62, 'Neurology'),
(48, 'Neuroscience'),
(52, 'Nursing'),
(67, 'Nutrition & Dietetics'),
(45, 'Oceanography'),
(65, 'Ophthalmology'),
(122, 'Personal Training'),
(28, 'Petroleum Engineering'),
(54, 'Pharmacy'),
(107, 'Philosophy'),
(90, 'Photography'),
(118, 'Physical Education'),
(36, 'Physics'),
(55, 'Physiotherapy'),
(100, 'Political Science'),
(78, 'Project Management'),
(63, 'Psychiatry'),
(102, 'Psychology'),
(106, 'Public Administration'),
(59, 'Public Health'),
(81, 'Public Relations'),
(49, 'Quantum Physics'),
(57, 'Radiology'),
(20, 'Robotics'),
(109, 'Social Work'),
(103, 'Sociology'),
(6, 'Software Engineering'),
(112, 'Special Education'),
(120, 'Sports Coaching'),
(117, 'Sports Science'),
(29, 'Structural Engineering'),
(77, 'Supply Chain Management'),
(66, 'Surgery'),
(110, 'Teaching'),
(116, 'TESOL (Teaching English as a Second Language)'),
(68, 'Traditional Medicine'),
(85, 'UX/UI Design'),
(56, 'Veterinary Medicine'),
(97, 'Video Editing'),
(18, 'Virtual Reality (VR)'),
(7, 'Web Development'),
(121, 'Yoga & Meditation');

-- --------------------------------------------------------

--
-- بنية الجدول `mentees`
--

CREATE TABLE `mentees` (
  `mentee_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_status` enum('Undergraduate','Graduate') NOT NULL,
  `interests` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- إرجاع أو استيراد بيانات الجدول `mentees`
--

INSERT INTO `mentees` (`mentee_id`, `user_id`, `student_status`, `interests`) VALUES
(2, 26, 'Undergraduate', 'hi i am asma and i study at KSU');

-- --------------------------------------------------------

--
-- بنية الجدول `mentors`
--

CREATE TABLE `mentors` (
  `mentor_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `cv_file` varchar(255) NOT NULL,
  `field_id` int(11) NOT NULL,
  `experience_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `profile_picture` varchar(255) DEFAULT NULL,
  `brief_description` text,
  `certificate_file` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- إرجاع أو استيراد بيانات الجدول `mentors`
--

INSERT INTO `mentors` (`mentor_id`, `user_id`, `email`, `cv_file`, `field_id`, `experience_id`, `status`, `profile_picture`, `brief_description`, `certificate_file`) VALUES
(2, 25, 'atata.4343@gmail.com', 'uploads/Topic-3.pdf', 98, 2, 'approved', 'uploads/1742341327_logoAM.png', 'hi', 'uploads/1742341327_Topic-3.pdf'),
(5, 30, 'aeshahalmukhlifi@gmail.com', 'uploads/Guide_to_Jira.pdf', 38, 3, 'approved', 'uploads/1744587308_personal18.png', 'I am  biology enthusiast with 4 years of experience in the field. My background includes working on topics related to cell biology, genetics, microbiology, and laboratory research techniques.', 'uploads/1744587308_Topic-3.pdf');

-- --------------------------------------------------------

--
-- بنية الجدول `ratings`
--

CREATE TABLE `ratings` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_id` int(10) UNSIGNED NOT NULL,
  `mentor_id` int(10) UNSIGNED NOT NULL,
  `mentee_id` int(10) UNSIGNED NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `ratings`
--

INSERT INTO `ratings` (`id`, `session_id`, `mentor_id`, `mentee_id`, `rating`, `comment`) VALUES
(6, 38, 25, 26, 3, 'wow i like it'),
(12, 54, 25, 26, 5, 'cool'),
(14, 58, 30, 26, 5, 'Thank you, you wonderful');

-- --------------------------------------------------------

--
-- بنية الجدول `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) UNSIGNED NOT NULL,
  `mentor_id` int(11) UNSIGNED NOT NULL,
  `mentee_id` int(11) UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `room_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('available','booked','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `sessions`
--

INSERT INTO `sessions` (`id`, `mentor_id`, `mentee_id`, `date`, `time`, `room_id`, `status`) VALUES
(36, 25, 26, '2025-04-02', '20:33:00', 'room_67ed7457a38c2', 'booked'),
(38, 25, 26, '2025-04-02', '21:47:00', 'room_67ed85f0c7e10', 'completed'),
(50, 25, 26, '2025-04-07', '10:16:00', 'room_67f229bb6a201', 'booked'),
(54, 25, 26, '2025-04-09', '13:08:00', 'room_67f646cfb4b15', 'completed'),
(58, 30, 26, '2025-04-14', '02:56:00', 'room_67fc4ed1680a8', 'completed');

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `user_id` int(11) UNSIGNED NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('mentor','mentee','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `phone_number`, `role`, `password_reset_token`) VALUES
(25, 'Ahmad ', 'Mousa', 'atata.4343@gmail.com', '$2y$10$q8gg27B25bJVN6XURZE9sO.FRG.rNKkeN8v.H376dgKWrWcNJ3kR.', '0521341612', 'mentor', NULL),
(26, 'Asma', 'Yousef', 'alawadtv0@gmail.com', '$2y$10$4DJc5Awu/t07swxhjOAkFOpNOj/cpKPPR5RtVoZeSOmo6IUQHaW/e', '0503451818', 'mentee', NULL),
(30, 'hawazin', 'Saad', 'aeshahalmukhlifi@gmail.com', '$2y$10$OOwFLWKQPhQz/ZmOo6wIIuYkJZIIAWZemo.tTCjdcCg5obTBM4cDm', '050345198', 'mentor', NULL),
(32, 'Aspira', 'Admin', 'abd123@gmail.com', '$2y$10$hMdv0WdTHHVkDUSbwE49aeGAmkHddEcCcGjsEqDhd6fn2fEfq/NqS', '0541231632', 'admin', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `years_of_experience`
--

CREATE TABLE `years_of_experience` (
  `id` int(11) NOT NULL,
  `experience_level` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- إرجاع أو استيراد بيانات الجدول `years_of_experience`
--

INSERT INTO `years_of_experience` (`id`, `experience_level`) VALUES
(2, '1-2 years'),
(5, '11-15 years'),
(6, '16-20 years'),
(3, '3-5 years'),
(4, '6-10 years'),
(1, 'Less than 1 year'),
(7, 'More than 20 years');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `field`
--
ALTER TABLE `field`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `field_name` (`field_name`);

--
-- Indexes for table `mentees`
--
ALTER TABLE `mentees`
  ADD PRIMARY KEY (`mentee_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `mentors`
--
ALTER TABLE `mentors`
  ADD PRIMARY KEY (`mentor_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `field_id` (`field_id`),
  ADD KEY `experience_id` (`experience_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`,`mentee_id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `mentee_id` (`mentee_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `mentee_id` (`mentee_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mentees`
--
ALTER TABLE `mentees`
  MODIFY `mentee_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mentors`
--
ALTER TABLE `mentors`
  MODIFY `mentor_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- قيود الجداول المحفوظة
--

--
-- القيود للجدول `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_3` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- القيود للجدول `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sessions_ibfk_2` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
