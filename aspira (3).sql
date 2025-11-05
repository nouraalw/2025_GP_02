-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 05, 2025 at 07:41 AM
-- Server version: 5.7.24
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
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `email`, `password`) VALUES
(1, 'abd123@gmail.com', '$2y$10$aciU46JG/cgx3UtmmiJs9.kjAayltUQu/4/ExGlasLY7Ao60HtE/2'),
(1, 'abd123@gmail.com', '$2y$10$aciU46JG/cgx3UtmmiJs9.kjAayltUQu/4/ExGlasLY7Ao60HtE/2'),
(1, 'abd123@gmail.com', '$2y$10$aciU46JG/cgx3UtmmiJs9.kjAayltUQu/4/ExGlasLY7Ao60HtE/2'),
(1, 'abd123@gmail.com', '$2y$10$aciU46JG/cgx3UtmmiJs9.kjAayltUQu/4/ExGlasLY7Ao60HtE/2');

-- --------------------------------------------------------

--
-- Table structure for table `cv`
--

CREATE TABLE `cv` (
  `cv_id` int(10) UNSIGNED NOT NULL,
  `mentee_id` int(10) UNSIGNED NOT NULL,
  `first_name_snapshot` varchar(100) DEFAULT NULL,
  `last_name_snapshot` varchar(100) DEFAULT NULL,
  `email_snapshot` varchar(190) DEFAULT NULL,
  `phone_number` varchar(32) DEFAULT NULL,
  `title` varchar(120) DEFAULT 'Professional CV',
  `summary` text,
  `education` json DEFAULT NULL,
  `experience` json DEFAULT NULL,
  `skills` json DEFAULT NULL,
  `certifications` json DEFAULT NULL,
  `languages` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cv`
--

INSERT INTO `cv` (`cv_id`, `mentee_id`, `first_name_snapshot`, `last_name_snapshot`, `email_snapshot`, `phone_number`, `title`, `summary`, `education`, `experience`, `skills`, `certifications`, `languages`, `created_at`, `updated_at`) VALUES
(2, 26, 'Asmaa', 'YousefAAA', 'alawadtv0@gmail.comm', '0503451818', 'Professional CV', 'Motivated and fast-learning recent graduate with a strong foundation in data analysis, programming, and problem-solving. Skilled in Python, SQL, and data visualization tools, with a keen interest in applying analytical thinking to solve real-world challenges. Eager to contribute to team projects, gain industry experience, and grow professionally in a dynamic environment.', '[{\"end\": \"2023-05-27\", \"field\": \"IT\", \"start\": \"2019-01-27\", \"degree\": \"Bachelors\", \"school\": \"King Saud University\", \"details\": \"\", \"location\": \"Riyadh\"}]', '[{\"end\": \"2025-08-06\", \"start\": \"2024-01-27\", \"title\": \"Data Scienst\", \"company\": \"IBM\", \"location\": \"\", \"is_current\": 0, \"description\": \"\"}]', '[\"Python\", \"SQL\", \"Machine Learning\", \"Data Analysis\", \"Communication\", \"Problem Solving\", \"Node.js\", \"cyper\", \"HTML jj\"]', '[{\"name\": \"AWS Certified Machine Learning\", \"issue\": \"2021-01-27\", \"expiry\": \"2025-12-27\", \"issuer\": \"Amazon Web Services\"}, {\"name\": \"Professional Data Engineer\", \"issue\": \"2025-01-27\", \"expiry\": \"2027-09-27\", \"issuer\": \"Google Cloud\"}]', '[{\"level\": \"Native\", \"language\": \"Arabic\"}, {\"level\": \"Professional\", \"language\": \"English\"}]', '2025-08-27 00:23:55', '2025-09-08 12:17:33');

-- --------------------------------------------------------

--
-- Table structure for table `field`
--

CREATE TABLE `field` (
  `id` int(11) NOT NULL,
  `field_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `field`
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
-- Table structure for table `grp_sessions`
--

CREATE TABLE `grp_sessions` (
  `group_session_id` int(11) NOT NULL,
  `mentor_id` int(10) UNSIGNED NOT NULL,
  `session_date` date NOT NULL,
  `session_time` time NOT NULL,
  `room_id` varchar(255) DEFAULT NULL,
  `status` enum('available','upcoming','completed','cancelled') NOT NULL DEFAULT 'available',
  `title` varchar(120) NOT NULL,
  `capacity` int(11) DEFAULT '10',
  `photo` varchar(255) DEFAULT NULL,
  `duration` int(11) NOT NULL DEFAULT '45'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `grp_sessions`
--

INSERT INTO `grp_sessions` (`group_session_id`, `mentor_id`, `session_date`, `session_time`, `room_id`, `status`, `title`, `capacity`, `photo`, `duration`) VALUES
(30, 26, '2025-08-24', '11:00:00', NULL, 'completed', 'Test Public Session', 100, NULL, 45),
(33, 26, '2025-08-24', '11:00:00', NULL, 'completed', 'Test Public Session', 100, NULL, 45),
(34, 33, '2025-09-02', '00:09:00', NULL, 'completed', 'ai', 100, NULL, 45),
(38, 33, '2025-09-02', '07:28:00', NULL, 'completed', 'ai4', 100, NULL, 45),
(40, 33, '2025-09-02', '06:26:00', NULL, 'completed', 'fff7', 100, NULL, 45),
(44, 33, '2025-09-02', '21:53:00', NULL, 'upcoming', 'test5', 100, NULL, 45),
(48, 30, '2025-09-21', '13:05:00', NULL, 'completed', 'f', 100, NULL, 45),
(49, 30, '2025-09-26', '12:06:00', NULL, 'completed', 'f', 100, NULL, 45),
(56, 30, '2025-09-14', '19:33:00', NULL, 'completed', 'tatter', 100, NULL, 45),
(64, 25, '2025-09-30', '12:00:00', NULL, 'completed', 'ux', 100, NULL, 45),
(65, 25, '2025-11-09', '14:00:00', NULL, 'upcoming', 'NLP', 10, NULL, 45);

-- --------------------------------------------------------

--
-- Table structure for table `grp_session_participants`
--

CREATE TABLE `grp_session_participants` (
  `id` int(11) NOT NULL,
  `group_session_id` int(11) NOT NULL,
  `mentee_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `grp_session_participants`
--

INSERT INTO `grp_session_participants` (`id`, `group_session_id`, `mentee_id`) VALUES
(22, 34, 34),
(24, 36, 34),
(25, 38, 34),
(26, 40, 34),
(27, 41, 34),
(28, 42, 34),
(29, 44, 34),
(32, 45, 26),
(38, 49, 26),
(39, 50, 26),
(40, 51, 26),
(41, 52, 26),
(42, 53, 26),
(43, 54, 26),
(44, 55, 26),
(45, 56, 26),
(46, 58, 26),
(47, 59, 26),
(48, 60, 26),
(49, 61, 26),
(50, 62, 26),
(51, 63, 26),
(52, 64, 26),
(53, 65, 26);

-- --------------------------------------------------------

--
-- Table structure for table `mentees`
--

CREATE TABLE `mentees` (
  `mentee_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_status` enum('Undergraduate','Graduate') NOT NULL,
  `interests` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `mentees`
--

INSERT INTO `mentees` (`mentee_id`, `user_id`, `student_status`, `interests`) VALUES
(2, 26, 'Undergraduate', 'hi i am asma and i study at KSU'),
(4, 34, 'Graduate', 'mentee1');

-- --------------------------------------------------------

--
-- Table structure for table `mentors`
--

CREATE TABLE `mentors` (
  `mentor_id` int(10) UNSIGNED NOT NULL,
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
-- Dumping data for table `mentors`
--

INSERT INTO `mentors` (`mentor_id`, `user_id`, `email`, `cv_file`, `field_id`, `experience_id`, `status`, `profile_picture`, `brief_description`, `certificate_file`) VALUES
(2, 25, 'atata.4343@gmail.com', 'uploads/Topic-3.pdf', 98, 2, 'approved', 'uploads/1756509427_d3a573af8a0dabba.jpg', 'hi i am mentor', 'uploads/1742341327_Topic-3.pdf'),
(5, 30, 'aeshahalmukhlifi@gmail.com', 'uploads/Guide_to_Jira.pdf', 38, 3, 'approved', 'uploads/1744587308_personal18.png', 'I am  biology enthusiast with 4 years of experience in the field. My background includes working on topics related to cell biology, genetics, microbiology, and laboratory research techniques.', 'uploads/1744587308_Topic-3.pdf'),
(7, 33, 'Shaaals002@gmail.com', 'uploads/Lab1_sheet (1)aa.pdf', 98, 5, 'approved', 'uploads/1756777174_photo_2023-08-21_04-16-44.jpg', 'mentor 1 ', 'uploads/1756777174_photo_2023-08-21_04-16-44.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `public_sessions`
--

CREATE TABLE `public_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `mentor_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT '45',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('available','booked','full','completed') NOT NULL DEFAULT 'available',
  `capacity` int(11) NOT NULL DEFAULT '100'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `public_sessions`
--

INSERT INTO `public_sessions` (`id`, `mentor_id`, `title`, `date`, `time`, `duration_minutes`, `created_at`, `status`, `capacity`) VALUES
(4, 33, 'aii', '2025-08-31', '02:21:00', 45, '2025-08-27 23:19:17', 'available', 100),
(5, 33, 'bbnmdjdsbfkdbfkjdsbvnbsvkjbkj', '2025-08-29', '02:24:00', 45, '2025-08-27 23:20:04', 'available', 100),
(6, 33, 'ii', '2025-08-29', '03:27:00', 45, '2025-08-28 00:24:48', 'available', 100);

-- --------------------------------------------------------

--
-- Table structure for table `public_session_bookings`
--

CREATE TABLE `public_session_bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `public_session_id` int(10) UNSIGNED NOT NULL,
  `mentee_id` int(10) UNSIGNED NOT NULL,
  `booked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
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
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `session_id`, `mentor_id`, `mentee_id`, `rating`, `comment`) VALUES
(6, 38, 25, 26, 3, 'wow i like it'),
(12, 54, 25, 26, 5, 'cool'),
(14, 58, 30, 26, 5, 'Thank you, you wonderful'),
(16, 81, 30, 26, 3, 'perfect'),
(17, 82, 25, 26, 4, 'good');

-- --------------------------------------------------------

--
-- Table structure for table `saved_courses`
--

CREATE TABLE `saved_courses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(500) NOT NULL,
  `url` varchar(1000) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `skills` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `saved_courses`
--

INSERT INTO `saved_courses` (`id`, `user_id`, `title`, `url`, `category`, `site`, `skills`, `created_at`) VALUES
(5, 1, 'Data Science Fundamentals with Python and SQL Specialization', 'https://www.coursera.org/specializations/data-science-fundamentals-python-sql', 'Data Science', 'Coursera', 'Data Science, Github, Python Programming, Jupyter notebooks, Rstudio, Data Analysis, Pandas, Numpy, Probability And Statistics, Regression Analysis, Data Visualization (DataViz), Statistical Hypothesis Testing, ', '2025-09-23 20:03:34'),
(8, 1, 'Data Engineering Foundations Specialization', 'https://www.coursera.org/specializations/data-engineering-foundations', 'Information Technology', 'Coursera', 'Information Engineering, Python Programming, Extraction, Transformation And Loading (ETL), Relational Database Management System (RDBMS), SQL, Data Science, Database (DBMS), NoSQL, Data Analysis, Pandas, Numpy, Jupyter notebooks, ', '2025-09-23 20:18:44'),
(10, 1, 'Methods and Statistics in Social Sciences Specialization', 'https://www.coursera.org/specializations/social-science', 'Social Sciences', 'Coursera', 'Statistics, Statistical Inference, R Programming, Qualitative Research, Confidence Interval, Statistical Hypothesis Testing, Regression Analysis, Analysis Of Variance (ANOVA), ', '2025-09-23 20:34:40');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `mentor_id` int(10) UNSIGNED NOT NULL,
  `mentee_id` int(10) UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `room_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('available','booked','upcoming','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `summary` text COLLATE utf8mb4_unicode_ci,
  `summary_pdf` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `mentor_id`, `mentee_id`, `date`, `time`, `room_id`, `status`, `summary`, `summary_pdf`) VALUES
(36, 25, 26, '2025-04-02', '20:33:00', 'room_67ed7457a38c2', 'cancelled', NULL, NULL),
(38, 25, 26, '2025-04-02', '21:47:00', 'room_67ed85f0c7e10', 'completed', 'Key Points Discussed:\r\n\r\nIntroduction to Artificial Intelligence and its main branches (Machine Learning, Deep Learning, NLP).\r\n\r\nDiscussed real-world applications of AI in healthcare, finance, and education.\r\n\r\nCompared supervised and unsupervised learning with practical examples.\r\n\r\nHighlighted the importance of data quality in building accurate AI models.\r\n\r\n✅ Action Items for Mentee:\r\n\r\nExplore Python libraries for AI: TensorFlow, PyTorch, and Scikit-learn.\r\n\r\nRead about “Ethics in AI” and prepare a short reflection for the next session.\r\n\r\nTry building a simple ML model (linear regression) as a hands-on exercise.\r\n\r\n⭐ Mentor’s Feedback:\r\n\r\n\"You have a strong curiosity about AI concepts. Focus on practical exercises alongside theory to build solid skills.\"', 'uploads/summaries/session_38.pdf'),
(50, 25, 26, '2025-04-07', '10:16:00', 'room_67f229bb6a201', 'cancelled', NULL, NULL),
(54, 25, 26, '2025-04-09', '13:08:00', 'room_67f646cfb4b15', 'completed', 'data science principl', 'uploads/summaries/session_54.pdf'),
(58, 30, 26, '2025-04-14', '02:56:00', 'room_67fc4ed1680a8', 'completed', NULL, NULL),
(68, 25, 26, '2025-08-30', '17:00:00', 'room_68b23d17a545d', 'cancelled', NULL, NULL),
(72, 33, 34, '2025-09-02', '22:47:00', 'room_68b72d4266fcb', 'cancelled', NULL, NULL),
(73, 30, NULL, '2025-09-27', '16:06:00', NULL, 'available', NULL, NULL),
(74, 30, 26, '2025-08-14', '18:07:00', 'room_68be0327f03bb', 'cancelled', NULL, NULL),
(75, 30, NULL, '2025-09-22', '17:07:00', NULL, 'available', NULL, NULL),
(76, 30, NULL, '2025-09-25', '12:02:00', NULL, 'available', NULL, NULL),
(78, 30, 26, '2025-09-26', '14:55:00', 'room_68d411e8d9137', 'cancelled', NULL, NULL),
(79, 30, 26, '2025-09-24', '20:19:00', 'room_68d42798d9769', 'cancelled', NULL, NULL),
(81, 30, 26, '2025-09-24', '22:16:00', 'room_68d442dbd00a5', 'completed', NULL, NULL),
(82, 25, 26, '2025-09-29', '23:54:00', 'room_68daf1df10bc6', 'completed', 'first topic:\n1-', 'uploads/summaries/session_82.pdf'),
(83, 25, NULL, '2025-09-30', '13:00:00', NULL, 'available', NULL, NULL),
(84, 25, NULL, '2025-09-30', '00:07:00', NULL, 'available', NULL, NULL),
(85, 30, 26, '2025-09-30', '00:23:00', 'room_68daf83975651', 'cancelled', NULL, NULL),
(86, 25, 26, '2025-10-24', '12:00:00', 'room_68f5e877a33cd', 'cancelled', NULL, NULL),
(87, 25, 26, '2025-11-06', '10:15:00', 'room_690a3dadc7438', 'booked', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('mentor','mentee','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `phone_number`, `role`, `password_reset_token`) VALUES
(25, 'Ahmad', 'Mousa', 'atata.4343@gmail.com', '$2y$10$q8gg27B25bJVN6XURZE9sO.FRG.rNKkeN8v.H376dgKWrWcNJ3kR.', '0521341612', 'mentor', NULL),
(26, 'Asma', 'Yousef', 'alawadtv0@gmail.com', '$2y$10$4DJc5Awu/t07swxhjOAkFOpNOj/cpKPPR5RtVoZeSOmo6IUQHaW/e', '0503451818', 'mentee', NULL),
(30, 'hawazin', 'Saad', 'aeshahalmukhlifi@gmail.com', '$2y$10$OOwFLWKQPhQz/ZmOo6wIIuYkJZIIAWZemo.tTCjdcCg5obTBM4cDm', '050345198', 'mentor', NULL),
(32, 'Aspira', 'Admin', 'abd123@gmail.com', '$2y$10$hMdv0WdTHHVkDUSbwE49aeGAmkHddEcCcGjsEqDhd6fn2fEfq/NqS', '0541231632', 'admin', NULL),
(33, 'Shahad', 'ali', 'Shaaals002@gmail.com', '$2y$10$LWoJ87/4I.Dqu4P8P1F3iOQ0rEzdgUnM5Ew1bn522feHFKsx1GN4C', '0536156174', 'mentor', NULL),
(34, 'sara ', 'mente', 'wxlaas278@gmail.com', '$2y$10$8LMABV5muN5VwQo8PuwejOzlvd0t268/P.ysMs4P4fb41N7soO5Ku', '0536155555', 'mentee', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `years_of_experience`
--

CREATE TABLE `years_of_experience` (
  `id` int(11) NOT NULL,
  `experience_level` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `years_of_experience`
--

INSERT INTO `years_of_experience` (`id`, `experience_level`) VALUES
(2, '1-2 years'),
(5, '11-15 years'),
(6, '16-20 years'),
(3, '3-5 years'),
(4, '6-10 years'),
(1, 'Less than 1 year'),
(7, 'More than 20 years'),
(2, '1-2 years'),
(5, '11-15 years'),
(6, '16-20 years'),
(3, '3-5 years'),
(4, '6-10 years'),
(1, 'Less than 1 year'),
(7, 'More than 20 years'),
(2, '1-2 years'),
(5, '11-15 years'),
(6, '16-20 years'),
(3, '3-5 years'),
(4, '6-10 years'),
(1, 'Less than 1 year'),
(7, 'More than 20 years'),
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
-- Indexes for table `cv`
--
ALTER TABLE `cv`
  ADD PRIMARY KEY (`cv_id`),
  ADD KEY `mentee_id` (`mentee_id`);

--
-- Indexes for table `field`
--
ALTER TABLE `field`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `field_name` (`field_name`);

--
-- Indexes for table `grp_sessions`
--
ALTER TABLE `grp_sessions`
  ADD PRIMARY KEY (`group_session_id`),
  ADD KEY `mentor_id` (`mentor_id`);

--
-- Indexes for table `grp_session_participants`
--
ALTER TABLE `grp_session_participants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_session_id` (`group_session_id`),
  ADD KEY `mentee_id` (`mentee_id`);

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `public_sessions`
--
ALTER TABLE `public_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mentor_date_time` (`mentor_id`,`date`,`time`);

--
-- Indexes for table `public_session_bookings`
--
ALTER TABLE `public_session_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_session_mentee` (`public_session_id`,`mentee_id`),
  ADD KEY `mentee_id` (`mentee_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`,`mentee_id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `mentee_id` (`mentee_id`);

--
-- Indexes for table `saved_courses`
--
ALTER TABLE `saved_courses`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `cv`
--
ALTER TABLE `cv`
  MODIFY `cv_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `grp_sessions`
--
ALTER TABLE `grp_sessions`
  MODIFY `group_session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `grp_session_participants`
--
ALTER TABLE `grp_session_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `mentees`
--
ALTER TABLE `mentees`
  MODIFY `mentee_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `mentors`
--
ALTER TABLE `mentors`
  MODIFY `mentor_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `public_sessions`
--
ALTER TABLE `public_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `public_session_bookings`
--
ALTER TABLE `public_session_bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `saved_courses`
--
ALTER TABLE `saved_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cv`
--
ALTER TABLE `cv`
  ADD CONSTRAINT `fk_cv_user` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_3` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sessions_ibfk_2` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
