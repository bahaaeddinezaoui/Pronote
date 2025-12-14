-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 09, 2025 at 08:41 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `test_class_edition`
--

-- --------------------------------------------------------

--
-- Table structure for table `absence`
--

DROP TABLE IF EXISTS `absence`;
CREATE TABLE IF NOT EXISTS `absence` (
  `ABSENCE_ID` int NOT NULL,
  `STUDY_SESSION_ID` int NOT NULL,
  `ABSENCE_DATE_AND_TIME` datetime DEFAULT NULL,
  `ABSENCE_MOTIF` varchar(30) DEFAULT NULL,
  `ABSENCE_OBSERVATION` varchar(258) DEFAULT NULL,
  PRIMARY KEY (`ABSENCE_ID`),
  KEY `FK_ABSENCE_TAKES_PLA_STUDY_SE` (`STUDY_SESSION_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `absence`
--

INSERT INTO `absence` (`ABSENCE_ID`, `STUDY_SESSION_ID`, `ABSENCE_DATE_AND_TIME`, `ABSENCE_MOTIF`, `ABSENCE_OBSERVATION`) VALUES
(14, 12, '2025-12-08 15:05:22', 'e', 'e'),
(13, 12, '2025-12-08 15:05:22', 'r', 'r'),
(12, 12, '2025-12-08 15:05:22', 'r', 'r'),
(11, 11, '2025-12-08 15:03:41', 'z', 'z'),
(10, 11, '2025-12-08 15:03:41', 'e', 'e'),
(9, 10, '2025-12-08 15:52:14', 'Late as usual', 'Overtime Work'),
(8, 9, '2025-12-08 15:33:06', 'zzzzzzzzzz', 'zzzzzzzzzzzzzzzzzzzzzzz'),
(7, 8, '2025-12-08 11:52:55', 'Late as usual', 'Overtime Work'),
(6, 7, '2025-12-08 10:12:38', 'Late as usual', 'Overtime Work'),
(4, 5, '2025-11-02 15:55:18', 'z', 'z'),
(5, 6, '2025-12-08 10:09:04', 'Late as usual', 'Overtime Work'),
(2, 3, '2025-11-02 15:34:51', 'z', 'z'),
(3, 4, '2025-11-02 15:45:01', 'Late as usual', 'Overtime Work'),
(1, 2, '2025-11-02 15:32:28', 'zzzzzzzzzz', 'zzzzzzzzzzzzzzzzzzzzzzz');

-- --------------------------------------------------------

--
-- Table structure for table `administrator`
--

DROP TABLE IF EXISTS `administrator`;
CREATE TABLE IF NOT EXISTS `administrator` (
  `ADMINISTRATOR_ID` int NOT NULL,
  `USER_ID` int NOT NULL,
  `ADMINISTRATOR_FIRST_NAME` varchar(24) DEFAULT NULL,
  `ADMINISTRATOR_LAST_NAME` varchar(24) DEFAULT NULL,
  `ADMINISTRATOR_POSITION` varchar(24) DEFAULT NULL,
  PRIMARY KEY (`ADMINISTRATOR_ID`),
  KEY `FK_ADMINIST_ADMINISTR_USER_ACC` (`USER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `administrator`
--

INSERT INTO `administrator` (`ADMINISTRATOR_ID`, `USER_ID`, `ADMINISTRATOR_FIRST_NAME`, `ADMINISTRATOR_LAST_NAME`, `ADMINISTRATOR_POSITION`) VALUES
(1, 2, 'ad', 'min', 'Chef Brigade');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
CREATE TABLE IF NOT EXISTS `category` (
  `CATEGORY_ID` int NOT NULL,
  `CATEGORY_NAME` varchar(12) DEFAULT NULL,
  PRIMARY KEY (`CATEGORY_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`CATEGORY_ID`, `CATEGORY_NAME`) VALUES
(7, 'EOA 1'),
(1, 'Master'),
(2, 'Etat-Major'),
(3, 'Spécialité'),
(4, 'Recyclage'),
(5, 'EOA 2'),
(6, 'EOA 3');

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

DROP TABLE IF EXISTS `class`;
CREATE TABLE IF NOT EXISTS `class` (
  `CLASS_ID` int NOT NULL,
  `CLASS_NAME` varchar(12) DEFAULT NULL,
  PRIMARY KEY (`CLASS_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `class`
--

INSERT INTO `class` (`CLASS_ID`, `CLASS_NAME`) VALUES
(1, 'Class 1'),
(2, 'Class 2'),
(3, 'Class 3');

-- --------------------------------------------------------

--
-- Table structure for table `major`
--

DROP TABLE IF EXISTS `major`;
CREATE TABLE IF NOT EXISTS `major` (
  `MAJOR_ID` varchar(12) NOT NULL,
  `MAJOR_NAME` varchar(48) DEFAULT NULL,
  PRIMARY KEY (`MAJOR_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `major`
--

INSERT INTO `major` (`MAJOR_ID`, `MAJOR_NAME`) VALUES
('2', 'Algorithms 1'),
('1', 'Stats 1');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
CREATE TABLE IF NOT EXISTS `notification` (
  `NOTIFICATION_ID` int NOT NULL,
  PRIMARY KEY (`NOTIFICATION_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `observation`
--

DROP TABLE IF EXISTS `observation`;
CREATE TABLE IF NOT EXISTS `observation` (
  `OBSERVATION_ID` int NOT NULL,
  `STUDY_SESSION_ID` int NOT NULL,
  PRIMARY KEY (`OBSERVATION_ID`),
  KEY `FK_OBSERVAT_HAPPENS_I_STUDY_SE` (`STUDY_SESSION_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `observation`
--

INSERT INTO `observation` (`OBSERVATION_ID`, `STUDY_SESSION_ID`) VALUES
(5, 10),
(4, 9),
(3, 8),
(2, 6),
(1, 3),
(6, 13),
(7, 14),
(8, 15),
(9, 15),
(10, 15);

-- --------------------------------------------------------

--
-- Table structure for table `receives`
--

DROP TABLE IF EXISTS `receives`;
CREATE TABLE IF NOT EXISTS `receives` (
  `NOTIFICATION_ID` int NOT NULL,
  `ADMINISTRATOR_ID` int NOT NULL,
  PRIMARY KEY (`NOTIFICATION_ID`,`ADMINISTRATOR_ID`),
  KEY `FK_RECEIVES_RECEIVES_ADMINIST` (`ADMINISTRATOR_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

DROP TABLE IF EXISTS `section`;
CREATE TABLE IF NOT EXISTS `section` (
  `SECTION_ID` int NOT NULL,
  `CATEGORY_ID` int NOT NULL,
  `SECTION_NAME` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`SECTION_ID`),
  KEY `FK_SECTION_BELONGS_T_CATEGORY` (`CATEGORY_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`SECTION_ID`, `CATEGORY_ID`, `SECTION_NAME`) VALUES
(1, 7, 'Section 1'),
(2, 7, 'Section 2'),
(3, 7, 'Section 3'),
(4, 7, 'Section 4'),
(5, 2, 'Section 1');

-- --------------------------------------------------------

--
-- Table structure for table `sends`
--

DROP TABLE IF EXISTS `sends`;
CREATE TABLE IF NOT EXISTS `sends` (
  `NOTIFICATION_ID` int NOT NULL,
  `CLASS_ID` int NOT NULL,
  PRIMARY KEY (`NOTIFICATION_ID`,`CLASS_ID`),
  KEY `FK_SENDS_SENDS_CLASS` (`CLASS_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
CREATE TABLE IF NOT EXISTS `student` (
  `STUDENT_SERIAL_NUMBER` varchar(16) NOT NULL,
  `CATEGORY_ID` int NOT NULL,
  `SECTION_ID` int NOT NULL,
  `STUDENT_FIRST_NAME` varchar(24) DEFAULT NULL,
  `STUDENT_LAST_NAME` varchar(24) DEFAULT NULL,
  `STUDNET_GRADE` varchar(24) DEFAULT NULL,
  PRIMARY KEY (`STUDENT_SERIAL_NUMBER`),
  KEY `FK_STUDENT_BELONGS_T_SECTION` (`SECTION_ID`),
  KEY `FK_STUDENT_IS_OF_CAT_CATEGORY` (`CATEGORY_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`STUDENT_SERIAL_NUMBER`, `CATEGORY_ID`, `SECTION_ID`, `STUDENT_FIRST_NAME`, `STUDENT_LAST_NAME`, `STUDNET_GRADE`) VALUES
('2', 7, 1, 'Yazid', 'BELFRAG', 'EOA'),
('3', 7, 1, 'Mohamed Wassim', 'OUHAB', 'EOA'),
('4', 7, 2, 'Brahim Abderezak', 'BOUDRA', 'EOA');

-- --------------------------------------------------------

--
-- Table structure for table `student_gets_absent`
--

DROP TABLE IF EXISTS `student_gets_absent`;
CREATE TABLE IF NOT EXISTS `student_gets_absent` (
  `STUDENT_SERIAL_NUMBER` varchar(16) NOT NULL,
  `ABSENCE_ID` int NOT NULL,
  PRIMARY KEY (`STUDENT_SERIAL_NUMBER`,`ABSENCE_ID`),
  KEY `FK_STUDENT__STUDENT_G_ABSENCE` (`ABSENCE_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `student_gets_absent`
--

INSERT INTO `student_gets_absent` (`STUDENT_SERIAL_NUMBER`, `ABSENCE_ID`) VALUES
('2', 1),
('2', 2),
('2', 3),
('2', 4),
('2', 6),
('2', 11),
('2', 13),
('3', 14),
('4', 5),
('4', 7),
('4', 8),
('4', 9),
('4', 10),
('4', 12);

-- --------------------------------------------------------

--
-- Table structure for table `studies`
--

DROP TABLE IF EXISTS `studies`;
CREATE TABLE IF NOT EXISTS `studies` (
  `SECTION_ID` int NOT NULL,
  `MAJOR_ID` varchar(12) NOT NULL,
  PRIMARY KEY (`SECTION_ID`,`MAJOR_ID`),
  KEY `FK_STUDIES_STUDIES_MAJOR` (`MAJOR_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `studies`
--

INSERT INTO `studies` (`SECTION_ID`, `MAJOR_ID`) VALUES
(1, '2'),
(2, '2');

-- --------------------------------------------------------

--
-- Table structure for table `studies_in`
--

DROP TABLE IF EXISTS `studies_in`;
CREATE TABLE IF NOT EXISTS `studies_in` (
  `SECTION_ID` int NOT NULL,
  `STUDY_SESSION_ID` int NOT NULL,
  PRIMARY KEY (`SECTION_ID`,`STUDY_SESSION_ID`),
  KEY `FK_STUDIES__STUDIES_I_STUDY_SE` (`STUDY_SESSION_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `studies_in`
--

INSERT INTO `studies_in` (`SECTION_ID`, `STUDY_SESSION_ID`) VALUES
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(2, 12),
(2, 13),
(2, 14),
(2, 15);

-- --------------------------------------------------------

--
-- Table structure for table `study_session`
--

DROP TABLE IF EXISTS `study_session`;
CREATE TABLE IF NOT EXISTS `study_session` (
  `STUDY_SESSION_ID` int NOT NULL,
  `CLASS_ID` int DEFAULT NULL,
  `TEACHER_SERIAL_NUMBER` varchar(16) NOT NULL,
  `STUDY_SESSION_DATE` date DEFAULT NULL,
  `STUDY_SESSION_START_TIME` time DEFAULT NULL,
  `STUDY_SESSION_END_TIME` time DEFAULT NULL,
  PRIMARY KEY (`STUDY_SESSION_ID`),
  KEY `FK_STUDY_SE_TEACHES_I_TEACHER` (`TEACHER_SERIAL_NUMBER`),
  KEY `FK_STUDY_SESSION_CLASS` (`CLASS_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `study_session`
--

INSERT INTO `study_session` (`STUDY_SESSION_ID`, `CLASS_ID`, `TEACHER_SERIAL_NUMBER`, `STUDY_SESSION_DATE`, `STUDY_SESSION_START_TIME`, `STUDY_SESSION_END_TIME`) VALUES
(12, 1, '1', '2025-12-08', '14:30:00', '16:00:00'),
(11, 1, '1', '2025-12-08', '14:30:00', '16:00:00'),
(10, 1, '1', '2025-12-08', '14:30:00', '16:00:00'),
(9, 1, '1', '2025-12-08', '14:30:00', '16:00:00'),
(8, 1, '1', '2025-12-08', '10:00:00', '12:00:00'),
(7, 1, '1', '2025-12-08', '10:00:00', '12:00:00'),
(6, 1, '1', '2025-12-08', '10:00:00', '12:00:00'),
(5, 1, '1', '2025-11-02', '14:30:00', '16:00:00'),
(4, 1, '1', '2025-11-02', '14:30:00', '16:00:00'),
(3, 1, '1', '2025-11-02', '14:30:00', '16:00:00'),
(2, 1, '1', '2025-11-02', '14:30:00', '16:00:00'),
(1, 1, '1', '2025-11-02', '14:30:00', '16:00:00'),
(13, 1, '1', '2025-12-09', '08:00:00', '10:00:00'),
(14, 1, '1', '2025-12-09', '08:00:00', '10:00:00'),
(15, 1, '1', '2025-12-09', '08:00:00', '10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

DROP TABLE IF EXISTS `teacher`;
CREATE TABLE IF NOT EXISTS `teacher` (
  `TEACHER_SERIAL_NUMBER` varchar(16) NOT NULL,
  `USER_ID` int NOT NULL,
  `TEACHER_GRADE` varchar(24) DEFAULT NULL,
  `TEACHER_FIRST_NAME` varchar(24) DEFAULT NULL,
  `TEACHER_LAST_NAME` varchar(24) DEFAULT NULL,
  PRIMARY KEY (`TEACHER_SERIAL_NUMBER`),
  KEY `FK_TEACHER_TEACHER_U_USER_ACC` (`USER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teacher`
--

INSERT INTO `teacher` (`TEACHER_SERIAL_NUMBER`, `USER_ID`, `TEACHER_GRADE`, `TEACHER_FIRST_NAME`, `TEACHER_LAST_NAME`) VALUES
('1', 1, 'PCA', 'ABCD', 'EFGH');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_makes_an_observation_for_a_student`
--

DROP TABLE IF EXISTS `teacher_makes_an_observation_for_a_student`;
CREATE TABLE IF NOT EXISTS `teacher_makes_an_observation_for_a_student` (
  `STUDENT_SERIAL_NUMBER` varchar(16) NOT NULL,
  `OBSERVATION_ID` int NOT NULL,
  `TEACHER_SERIAL_NUMBER` varchar(16) NOT NULL,
  `STUDY_SESSION_ID` int NOT NULL,
  `OBSERVATION_DATE_AND_TIME` datetime DEFAULT NULL,
  `OBSERVATION_MOTIF` varchar(30) DEFAULT NULL,
  `OBSERVATION_NOTE` varchar(256) DEFAULT NULL,
  `IS_NEW_FOR_ADMIN` tinyint(1) DEFAULT '1' COMMENT '1 = new/unread by admin, 0 = read by admin',
  PRIMARY KEY (`STUDENT_SERIAL_NUMBER`,`OBSERVATION_ID`,`TEACHER_SERIAL_NUMBER`),
  KEY `FK_TEACHER__TEACHER_M_TEACHER` (`TEACHER_SERIAL_NUMBER`),
  KEY `FK_TEACHER__TEACHER_M_OBSERVAT` (`OBSERVATION_ID`),
  KEY `FK_TEACHER__OBSERVATION_STUDY_SESSION` (`STUDY_SESSION_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teacher_makes_an_observation_for_a_student`
--

INSERT INTO `teacher_makes_an_observation_for_a_student` (`STUDENT_SERIAL_NUMBER`, `OBSERVATION_ID`, `TEACHER_SERIAL_NUMBER`, `STUDY_SESSION_ID`, `OBSERVATION_DATE_AND_TIME`, `OBSERVATION_MOTIF`, `OBSERVATION_NOTE`, `IS_NEW_FOR_ADMIN`) VALUES
('4', 3, '1', 8, '2025-12-08 11:53:19', 'fdsgfdfhgfcj', 'fdxgfdhgcfhgcfjh', 1),
('4', 2, '1', 6, '2025-12-08 10:09:12', 'zzzzz', 'zzzzz', 1),
('2', 1, '1', 3, '2025-11-02 15:34:57', 'z', 'z', 1),
('2', 4, '1', 9, '2025-12-08 15:34:35', 'feqsfsdwgfdgf', 'gdfxhgfhgc', 1),
('2', 5, '1', 10, '2025-12-08 15:53:45', 'vxcvcxbcvxvc n', 'vdxvcgfxhgdxhxhgfc', 1),
('2', 7, '1', 14, '2025-12-09 09:26:31', 'z', 'd', 1),
('2', 8, '1', 15, '2025-12-09 09:28:47', 'zdsdsqf', 'sdfdsgdfs', 1),
('2', 9, '1', 15, '2025-12-09 09:28:56', 'esfrsgd', 'gfdxgfxdhxgf', 1),
('2', 10, '1', 15, '2025-12-09 09:34:34', 'bgfgfg', 'hgbdhb', 1);

-- --------------------------------------------------------

--
-- Table structure for table `teaches`
--

DROP TABLE IF EXISTS `teaches`;
CREATE TABLE IF NOT EXISTS `teaches` (
  `MAJOR_ID` varchar(12) NOT NULL,
  `TEACHER_SERIAL_NUMBER` varchar(16) NOT NULL,
  PRIMARY KEY (`MAJOR_ID`,`TEACHER_SERIAL_NUMBER`),
  KEY `FK_TEACHES_TEACHES_TEACHER` (`TEACHER_SERIAL_NUMBER`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teaches`
--

INSERT INTO `teaches` (`MAJOR_ID`, `TEACHER_SERIAL_NUMBER`) VALUES
('2', '1');

-- --------------------------------------------------------

--
-- Table structure for table `user_account`
--

DROP TABLE IF EXISTS `user_account`;
CREATE TABLE IF NOT EXISTS `user_account` (
  `USER_ID` int NOT NULL,
  `USERNAME` varchar(30) DEFAULT NULL,
  `PASSWORD_HASH` varchar(1024) DEFAULT NULL,
  `EMAIL` varchar(60) DEFAULT NULL,
  `ROLE` varchar(15) DEFAULT NULL,
  `ACCOUNT_STATUS` varchar(12) DEFAULT NULL,
  `CREATED_AT` datetime DEFAULT NULL,
  `LAST_LOGIN_AT` datetime DEFAULT NULL,
  PRIMARY KEY (`USER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_account`
--

INSERT INTO `user_account` (`USER_ID`, `USERNAME`, `PASSWORD_HASH`, `EMAIL`, `ROLE`, `ACCOUNT_STATUS`, `CREATED_AT`, `LAST_LOGIN_AT`) VALUES
(1, 'teacher1', '$2y$10$FPgoRYiqkoXZVi9PefLek.iByOCxECtzCisUJoih0Hr6PFGElfSjW', 'teacher1@esam.com', 'Teacher', 'Active', NULL, NULL),
(2, 'admin1', '$2y$10$A5zmNOHnXmMxVeD4.fJHPu0Ww64OKkxm0UdxTD7bWzi3wYNKkOwye', 'admin1@esam.com', 'Admin', 'Active', NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
