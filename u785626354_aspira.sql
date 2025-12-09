-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- مضيف: 127.0.0.1:3306
-- وقت الجيل: 09 ديسمبر 2025 الساعة 12:01
-- إصدار الخادم: 11.8.3-MariaDB-log
-- نسخة PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- قاعدة بيانات: `u785626354_aspira`
--

-- --------------------------------------------------------

--
-- بنية الجدول `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `admins`
--

INSERT INTO `admins` (`admin_id`, `email`, `password`) VALUES
(1, 'abd123@gmail.com', '$2y$10$aciU46JG/cgx3UtmmiJs9.kjAayltUQu/4/ExGlasLY7Ao60HtE/2'),
(1, 'abd123@gmail.com', '$2y$10$aciU46JG/cgx3UtmmiJs9.kjAayltUQu/4/ExGlasLY7Ao60HtE/2'),
(1, 'abd123@gmail.com', '$2y$10$aciU46JG/cgx3UtmmiJs9.kjAayltUQu/4/ExGlasLY7Ao60HtE/2'),
(1, 'abd123@gmail.com', '$2y$10$aciU46JG/cgx3UtmmiJs9.kjAayltUQu/4/ExGlasLY7Ao60HtE/2'),
(1, 'abd123@gmail.com', '$2y$10$aciU46JG/cgx3UtmmiJs9.kjAayltUQu/4/ExGlasLY7Ao60HtE/2'),
(1, 'abd123@gmail.com', '$2y$10$aciU46JG/cgx3UtmmiJs9.kjAayltUQu/4/ExGlasLY7Ao60HtE/2'),
(1, 'abd123@gmail.com', '$2y$10$aciU46JG/cgx3UtmmiJs9.kjAayltUQu/4/ExGlasLY7Ao60HtE/2'),
(1, 'abd123@gmail.com', '$2y$10$aciU46JG/cgx3UtmmiJs9.kjAayltUQu/4/ExGlasLY7Ao60HtE/2');

-- --------------------------------------------------------

--
-- بنية الجدول `cv`
--

CREATE TABLE `cv` (
  `cv_id` int(10) UNSIGNED NOT NULL,
  `mentee_id` int(10) UNSIGNED NOT NULL,
  `first_name_snapshot` varchar(100) DEFAULT NULL,
  `last_name_snapshot` varchar(100) DEFAULT NULL,
  `email_snapshot` varchar(190) DEFAULT NULL,
  `phone_number` varchar(32) DEFAULT NULL,
  `title` varchar(120) DEFAULT 'Professional CV',
  `summary` text DEFAULT NULL,
  `education` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`education`)),
  `experience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`experience`)),
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills`)),
  `certifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`certifications`)),
  `languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`languages`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cv`
--

INSERT INTO `cv` (`cv_id`, `mentee_id`, `first_name_snapshot`, `last_name_snapshot`, `email_snapshot`, `phone_number`, `title`, `summary`, `education`, `experience`, `skills`, `certifications`, `languages`, `created_at`, `updated_at`) VALUES
(2, 26, 'Asmaa', 'YousefAAA', 'alawadtv0@gmail.comm', '+96617141618', 'Professional CV', 'Motivated and fast-learning recent graduate with a strong foundation in data analysis, programming, and problem-solving. Skilled in Python, SQL, and data visualization tools, with a keen interest in applying analytical thinking to solve real-world challenges. Eager to contribute to team projects, gain industry experience, and grow professionally in a dynamic environment.', '[{\"end\": \"2023-05-27\", \"field\": \"IT\", \"start\": \"2019-01-27\", \"degree\": \"Bachelors\", \"school\": \"King Saud University\", \"details\": \"\", \"location\": \"Riyadh\"}]', '[{\"end\": \"2025-08-06\", \"start\": \"2024-01-27\", \"title\": \"Data Scienst\", \"company\": \"IBM\", \"location\": \"\", \"is_current\": 0, \"description\": \"\"}]', '[\"Python\", \"SQL\", \"Machine Learning\", \"Data Analysis\", \"Communication\", \"Problem Solving\", \"Node.js\", \"cyper\", \"HTML jj\"]', '[{\"name\": \"AWS Certified Machine Learning\", \"issue\": \"2021-01-27\", \"expiry\": \"2025-12-27\", \"issuer\": \"Amazon Web Services\"}, {\"name\": \"Professional Data Engineer\", \"issue\": \"2025-01-27\", \"expiry\": \"2027-09-27\", \"issuer\": \"Google Cloud\"}]', '[{\"level\": \"Native\", \"language\": \"Arabic\"}, {\"level\": \"Professional\", \"language\": \"English\"}]', '2025-08-27 00:23:55', '2025-11-23 03:57:02');

-- --------------------------------------------------------

--
-- بنية الجدول `field`
--

CREATE TABLE `field` (
  `id` int(11) NOT NULL,
  `field_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

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
-- بنية الجدول `grp_sessions`
--

CREATE TABLE `grp_sessions` (
  `group_session_id` int(11) NOT NULL,
  `mentor_id` int(10) UNSIGNED NOT NULL,
  `session_date` date NOT NULL,
  `session_time` time NOT NULL,
  `room_id` varchar(255) DEFAULT NULL,
  `status` enum('available','upcoming','completed','cancelled') NOT NULL DEFAULT 'available',
  `title` varchar(120) NOT NULL,
  `capacity` int(11) DEFAULT 10,
  `photo` varchar(255) DEFAULT NULL,
  `duration` int(11) NOT NULL DEFAULT 45
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `grp_sessions`
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
(65, 25, '2025-11-09', '14:00:00', NULL, 'completed', 'NLP', 10, NULL, 45),
(66, 25, '2025-11-29', '23:14:00', NULL, 'completed', 'hu', 10, NULL, 45),
(68, 25, '2025-11-25', '20:53:00', NULL, 'completed', 'tesst', 10, NULL, 45),
(69, 38, '2025-12-01', '20:28:00', NULL, 'completed', 'trading', 10, NULL, 45),
(70, 42, '2025-12-09', '12:00:00', NULL, 'completed', 'Data scince', 10, NULL, 45);

-- --------------------------------------------------------

--
-- بنية الجدول `grp_session_participants`
--

CREATE TABLE `grp_session_participants` (
  `id` int(11) NOT NULL,
  `group_session_id` int(11) NOT NULL,
  `mentee_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `grp_session_participants`
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
(53, 65, 26),
(54, 66, 26),
(55, 68, 26),
(56, 69, 36),
(57, 69, 39),
(58, 70, 41);

-- --------------------------------------------------------

--
-- بنية الجدول `mentees`
--

CREATE TABLE `mentees` (
  `mentee_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_status` enum('Undergraduate','Graduate') NOT NULL,
  `interests` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `mentees`
--

INSERT INTO `mentees` (`mentee_id`, `user_id`, `student_status`, `interests`) VALUES
(2, 26, 'Undergraduate', 'hi i am asma and i study at KSU.'),
(4, 34, 'Graduate', 'mentee1'),
(6, 37, 'Undergraduate', 'hi i am aeshah at 3 am'),
(7, 39, 'Undergraduate', 'heyheyhey'),
(8, 40, 'Graduate', 'heyheyheyhey'),
(9, 41, 'Undergraduate', 'IT candidate');

-- --------------------------------------------------------

--
-- بنية الجدول `mentors`
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
  `brief_description` text DEFAULT NULL,
  `certificate_file` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `mentors`
--

INSERT INTO `mentors` (`mentor_id`, `user_id`, `email`, `cv_file`, `field_id`, `experience_id`, `status`, `profile_picture`, `brief_description`, `certificate_file`) VALUES
(2, 25, 'atata.4343@gmail.com', 'uploads/Topic-3.pdf', 98, 2, 'approved', 'uploads/1764950923_5196d29db0e9c0cd.png', 'hi i am mentor.', 'uploads/1742341327_Topic-3.pdf'),
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
  `comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `ratings`
--

INSERT INTO `ratings` (`id`, `session_id`, `mentor_id`, `mentee_id`, `rating`, `comment`) VALUES
(17, 82, 25, 26, 4, 'good'),
(18, 92, 25, 26, 5, 'wonderful session');

-- --------------------------------------------------------

--
-- بنية الجدول `saved_courses`
--

CREATE TABLE `saved_courses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(500) NOT NULL,
  `url` varchar(1000) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `saved_courses`
--

INSERT INTO `saved_courses` (`id`, `user_id`, `title`, `url`, `category`, `site`, `skills`, `created_at`) VALUES
(5, 1, 'Data Science Fundamentals with Python and SQL Specialization', 'https://www.coursera.org/specializations/data-science-fundamentals-python-sql', 'Data Science', 'Coursera', 'Data Science, Github, Python Programming, Jupyter notebooks, Rstudio, Data Analysis, Pandas, Numpy, Probability And Statistics, Regression Analysis, Data Visualization (DataViz), Statistical Hypothesis Testing, ', '2025-09-23 20:03:34'),
(8, 1, 'Data Engineering Foundations Specialization', 'https://www.coursera.org/specializations/data-engineering-foundations', 'Information Technology', 'Coursera', 'Information Engineering, Python Programming, Extraction, Transformation And Loading (ETL), Relational Database Management System (RDBMS), SQL, Data Science, Database (DBMS), NoSQL, Data Analysis, Pandas, Numpy, Jupyter notebooks, ', '2025-09-23 20:18:44'),
(10, 1, 'Methods and Statistics in Social Sciences Specialization', 'https://www.coursera.org/specializations/social-science', 'Social Sciences', 'Coursera', 'Statistics, Statistical Inference, R Programming, Qualitative Research, Confidence Interval, Statistical Hypothesis Testing, Regression Analysis, Analysis Of Variance (ANOVA), ', '2025-09-23 20:34:40'),
(11, 26, 'Deep Learning Specialization', 'https://www.coursera.org/specializations/deep-learning', 'Data Science', 'Coursera', 'Artificial Neural Network, Convolutional Neural Network, Tensorflow, Recurrent Neural Network, Transformers, Deep Learning, Backpropagation, Python Programming, Neural Network Architecture, Mathematical Optimization, hyperparameter tuning, Inductive Transfer, ', '2025-11-22 14:06:38'),
(12, 36, 'Introduction to CAD, CAM, and Practical CNC Machining', 'https://www.coursera.org/learn/introduction-cad-cam-practical-cnc-machining?specialization=autodesk-cad-cam-manufacturing', 'Physical Science and Engineering', 'Coursera', 'Manufacturing Process Management,Computer-Aided Design (CAD),Computer-Aided Manufacturing,Autodesk Fusion 360,Mechanical Engineering,', '2025-11-23 00:48:30'),
(14, 39, 'Introduction to Data Science Specialization', 'https://www.coursera.org/specializations/introduction-data-science', 'Data Science', 'Coursera', 'Data Science, Relational Database Management System (RDBMS), Cloud Databases, Python Programming, SQL, Deep Learning, Machine Learning, Big Data, Data Mining, Github, Jupyter notebooks, Rstudio, ', '2025-12-01 20:04:18'),
(15, 39, 'Data Science Fundamentals with Python and SQL Specialization', 'https://www.coursera.org/specializations/data-science-fundamentals-python-sql', 'Data Science', 'Coursera', 'Data Science, Github, Python Programming, Jupyter notebooks, Rstudio, Data Analysis, Pandas, Numpy, Probability And Statistics, Regression Analysis, Data Visualization (DataViz), Statistical Hypothesis Testing, ', '2025-12-01 20:04:20');

-- --------------------------------------------------------

--
-- بنية الجدول `sessions`
--

CREATE TABLE `sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `mentor_id` int(10) UNSIGNED NOT NULL,
  `mentee_id` int(10) UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `room_id` varchar(255) DEFAULT NULL,
  `status` enum('available','booked','upcoming','completed','cancelled') DEFAULT 'available',
  `summary` text DEFAULT NULL,
  `summary_pdf` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `sessions`
--

INSERT INTO `sessions` (`id`, `mentor_id`, `mentee_id`, `date`, `time`, `room_id`, `status`, `summary`, `summary_pdf`) VALUES
(82, 25, 26, '2025-09-29', '23:54:00', 'room_68daf1df10bc6', 'completed', 'first topic:\n1-', 'uploads/summaries/session_82.pdf'),
(92, 25, 26, '2025-11-25', '19:38:00', 'room_6925db1dbd174', 'completed', 'she need more practice and she loves math', 'uploads/summaries/session_92.pdf'),
(97, 25, 26, '2025-12-05', '19:32:00', 'room_693308c2d2301', 'cancelled', NULL, NULL),
(98, 25, 26, '2025-12-11', '10:00:00', NULL, 'booked', NULL, NULL),
(99, 25, NULL, '2025-12-10', '16:30:00', NULL, 'available', NULL, NULL),
(100, 25, NULL, '2025-12-11', '08:15:00', NULL, 'available', NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('mentor','mentee','admin') NOT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `role`, `password_reset_token`) VALUES
(25, 'Salam', 'ahmed', 'atata.4343@gmail.com', '$2y$10$q8gg27B25bJVN6XURZE9sO.FRG.rNKkeN8v.H376dgKWrWcNJ3kR.', 'mentor', NULL),
(26, 'maha', 'Yousef', 'alawadtv0@gmail.com', '$2y$10$4DJc5Awu/t07swxhjOAkFOpNOj/cpKPPR5RtVoZeSOmo6IUQHaW/e', 'mentee', 'bbe7621f0c909a23e97ccc97ff7aa2392dd4eeffd16b30ce7e2c12f826872013d2f0c599168dd4959bf97415abc61e635be8'),
(30, 'hawazin', 'Saad', 'aeshahalmukhlifi@gmail.com', '$2y$10$OOwFLWKQPhQz/ZmOo6wIIuYkJZIIAWZemo.tTCjdcCg5obTBM4cDm', 'mentor', NULL),
(32, 'Aspira', 'Admin', 'abd123@gmail.com', '$2y$10$hMdv0WdTHHVkDUSbwE49aeGAmkHddEcCcGjsEqDhd6fn2fEfq/NqS', 'admin', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `years_of_experience`
--

CREATE TABLE `years_of_experience` (
  `id` int(11) NOT NULL,
  `experience_level` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `years_of_experience`
--

INSERT INTO `years_of_experience` (`id`, `experience_level`) VALUES
(1, '1-2 years'),
(2, '11-15 years'),
(3, '16-20 years'),
(4, '3-5 years'),
(5, '6-10 years'),
(6, 'Less than 1 year'),
(7, 'More than 20 years');

--
-- Indexes for dumped tables
--

--
-- فهارس للجدول `cv`
--
ALTER TABLE `cv`
  ADD PRIMARY KEY (`cv_id`),
  ADD KEY `mentee_id` (`mentee_id`);

--
-- فهارس للجدول `field`
--
ALTER TABLE `field`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `field_name` (`field_name`);

--
-- فهارس للجدول `grp_sessions`
--
ALTER TABLE `grp_sessions`
  ADD PRIMARY KEY (`group_session_id`),
  ADD KEY `mentor_id` (`mentor_id`);

--
-- فهارس للجدول `grp_session_participants`
--
ALTER TABLE `grp_session_participants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_session_id` (`group_session_id`),
  ADD KEY `mentee_id` (`mentee_id`);

--
-- فهارس للجدول `mentees`
--
ALTER TABLE `mentees`
  ADD PRIMARY KEY (`mentee_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- فهارس للجدول `mentors`
--
ALTER TABLE `mentors`
  ADD PRIMARY KEY (`mentor_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `field_id` (`field_id`),
  ADD KEY `experience_id` (`experience_id`);

--
-- فهارس للجدول `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`,`mentee_id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `mentee_id` (`mentee_id`);

--
-- فهارس للجدول `saved_courses`
--
ALTER TABLE `saved_courses`
  ADD PRIMARY KEY (`id`);

--
-- فهارس للجدول `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `mentee_id` (`mentee_id`);

--
-- فهارس للجدول `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- فهارس للجدول `years_of_experience`
--
ALTER TABLE `years_of_experience`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cv`
--
ALTER TABLE `cv`
  MODIFY `cv_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `grp_sessions`
--
ALTER TABLE `grp_sessions`
  MODIFY `group_session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `grp_session_participants`
--
ALTER TABLE `grp_session_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `mentees`
--
ALTER TABLE `mentees`
  MODIFY `mentee_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `mentors`
--
ALTER TABLE `mentors`
  MODIFY `mentor_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `saved_courses`
--
ALTER TABLE `saved_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- القيود المفروضة على الجداول الملقاة
--

--
-- قيود الجداول `cv`
--
ALTER TABLE `cv`
  ADD CONSTRAINT `fk_cv_user` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- قيود الجداول `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_3` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- قيود الجداول `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sessions_ibfk_2` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
