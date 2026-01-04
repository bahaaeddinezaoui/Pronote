-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 04, 2026 at 03:01 PM
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
  `ADMINISTRATOR_GRADE` varchar(20) NOT NULL,
  `ADMINISTRATOR_POSITION` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ADMINISTRATOR_PHOTO` mediumblob NOT NULL,
  PRIMARY KEY (`ADMINISTRATOR_ID`),
  KEY `FK_ADMINIST_ADMINISTR_USER_ACC` (`USER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `administrator`
--

INSERT INTO `administrator` (`ADMINISTRATOR_ID`, `USER_ID`, `ADMINISTRATOR_FIRST_NAME`, `ADMINISTRATOR_LAST_NAME`, `ADMINISTRATOR_GRADE`, `ADMINISTRATOR_POSITION`, `ADMINISTRATOR_PHOTO`) VALUES
(1, 1, 'Said', 'SIDI OUIS', 'General', 'Mr. le Général, le Commandant de l\'Ecole', ''),
(2, 2, 'Yacine', 'ROUABHIA', 'Lieutenant-colonel', 'Chef Brigade', ''),
(3, 3, 'Mohamed Taher', 'BADI', 'Colonel', 'DGE', ''),
(4, 4, 'Eldjilali', 'YETTOU', 'Colonel', 'DEM', ''),
(5, 5, 'Lakhdar', 'YOUSEFI', 'Lieutenant-colonel', 'DESU', '');

-- --------------------------------------------------------

--
-- Table structure for table `admin_read_observation`
--

DROP TABLE IF EXISTS `admin_read_observation`;
CREATE TABLE IF NOT EXISTS `admin_read_observation` (
  `OBSERVATION_ID` int NOT NULL,
  `ADMINISTRATOR_ID` int NOT NULL,
  `READ_AT` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`OBSERVATION_ID`,`ADMINISTRATOR_ID`),
  KEY `FK_ARO_ADMINISTRATOR` (`ADMINISTRATOR_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin_read_observation`
--

INSERT INTO `admin_read_observation` (`OBSERVATION_ID`, `ADMINISTRATOR_ID`, `READ_AT`) VALUES
(4, 1, '2026-01-04 15:18:32'),
(4, 2, '2026-01-04 15:18:55');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
CREATE TABLE IF NOT EXISTS `category` (
  `CATEGORY_ID` int NOT NULL,
  `CATEGORY_NAME` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`CATEGORY_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`CATEGORY_ID`, `CATEGORY_NAME`) VALUES
(1, 'LMD 1'),
(9, 'Master Professionel 1'),
(12, 'Etat-Major'),
(5, 'Spécialisation 1'),
(11, 'Recyclage'),
(2, 'LMD 2'),
(3, 'LMD 3'),
(4, 'Perfectionnement'),
(10, 'Master Professionel 2'),
(6, 'Spécialisation 2'),
(7, 'Spécialisation 3'),
(8, 'Spécialisation 4');

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
(3, 'Class 3'),
(4, 'Class 4'),
(5, 'Class 5'),
(6, 'Class 6'),
(7, 'Class 7'),
(8, 'Class 8'),
(9, 'Class 9'),
(10, 'Class 10'),
(11, 'Class 11'),
(12, 'Class 12'),
(13, 'Class 13'),
(14, 'Class 14'),
(15, 'Class 15'),
(16, 'Class 16'),
(17, 'Class 17'),
(18, 'Class 18'),
(19, 'Class 19'),
(20, 'Class 20'),
(21, 'Class 21');

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
('1', 'Algorithmes et Structures de Données');

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
(1, 1),
(2, 1),
(3, 1),
(4, 1);

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
(1, 1, 'Section 1 EOA 1'),
(2, 1, 'Section 2 EOA 1'),
(3, 1, 'Section 3 EOA 1'),
(4, 1, 'Section 4 EOA 1'),
(5, 2, 'Section 1 EOA 2'),
(6, 2, 'Section 2 EOA 2'),
(7, 2, 'Section 2 EOA 3'),
(8, 2, 'Section 4 EOA 2'),
(9, 3, 'Section 1 EOA 3'),
(10, 3, 'Section 2 EOA 3'),
(11, 3, 'Section 3 EOA 2'),
(12, 3, 'Section 4 EOA 2'),
(13, 8, 'Section Etat-major'),
(14, 9, 'Section Perfectionnement'),
(15, 9, 'Section Recyclage'),
(16, 0, 'Section Master 1'),
(17, 0, 'Section Master 2'),
(18, 0, 'Section Spécialisation 1'),
(19, 0, 'Section Spécialisation 2'),
(20, 0, 'Section Spécialisation 3'),
(21, 0, 'Section Spécialisation 4');

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
  `STUDENT_FIRST_NAME` varchar(42) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `STUDENT_LAST_NAME` varchar(42) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `STUDNET_GRADE` varchar(24) DEFAULT NULL,
  PRIMARY KEY (`STUDENT_SERIAL_NUMBER`),
  KEY `FK_STUDENT_BELONGS_T_SECTION` (`SECTION_ID`),
  KEY `FK_STUDENT_IS_OF_CAT_CATEGORY` (`CATEGORY_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`STUDENT_SERIAL_NUMBER`, `CATEGORY_ID`, `SECTION_ID`, `STUDENT_FIRST_NAME`, `STUDENT_LAST_NAME`, `STUDNET_GRADE`) VALUES
('9996', 1, 1, 'Yazid', 'BELFRAG', 'EOA'),
('9997', 1, 1, 'Mohamed Wassim', 'OUHAB', 'EOA'),
('9998', 1, 2, 'Brahim Abderezak', 'BOUDRA', 'EOA'),
('9999', 1, 2, 'Imed Eddine', 'AMRANI', 'EOA');

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
(1, '1'),
(2, '1');

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
(1, 1);

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
(1, 19, '1', '2026-01-04', '14:30:00', '16:00:00');

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
  `TEACHER_PHOTO` mediumblob NOT NULL,
  PRIMARY KEY (`TEACHER_SERIAL_NUMBER`),
  KEY `FK_TEACHER_TEACHER_U_USER_ACC` (`USER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teacher`
--

INSERT INTO `teacher` (`TEACHER_SERIAL_NUMBER`, `USER_ID`, `TEACHER_GRADE`, `TEACHER_FIRST_NAME`, `TEACHER_LAST_NAME`, `TEACHER_PHOTO`) VALUES
('1', 100, 'Lieutenant', 'Bahaa Eddine', 'ZAOUI', '');

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
  PRIMARY KEY (`STUDENT_SERIAL_NUMBER`,`OBSERVATION_ID`,`TEACHER_SERIAL_NUMBER`),
  KEY `FK_TEACHER__TEACHER_M_TEACHER` (`TEACHER_SERIAL_NUMBER`),
  KEY `FK_TEACHER__TEACHER_M_OBSERVAT` (`OBSERVATION_ID`),
  KEY `FK_TEACHER__OBSERVATION_STUDY_SESSION` (`STUDY_SESSION_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teacher_makes_an_observation_for_a_student`
--

INSERT INTO `teacher_makes_an_observation_for_a_student` (`STUDENT_SERIAL_NUMBER`, `OBSERVATION_ID`, `TEACHER_SERIAL_NUMBER`, `STUDY_SESSION_ID`, `OBSERVATION_DATE_AND_TIME`, `OBSERVATION_MOTIF`, `OBSERVATION_NOTE`) VALUES
('9997', 4, '1', 1, '2026-01-04 15:18:22', 'eee', 'eeee');

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
('1', '1');

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
(1, 'admin1', '$2y$10$A5zmNOHnXmMxVeD4.fJHPu0Ww64OKkxm0UdxTD7bWzi3wYNKkOwye', 'admin1@esam.com', 'Admin', 'Active', NULL, NULL),
(2, 'admin2', '$2y$10$CqthaDg0uYTHa5gFKd.JhOdvpcLbSso6y1PSawJKJKfD3osAIgNqC', NULL, 'Admin', 'Active', NULL, NULL),
(100, 'teacher2', '$2y$10$w50.DE1y2mi4n4AdBoip5eVoOuJpw5FHkMN0VN17947w4fGsDNRIy', 'zaouibahaaeddine@esam.com', 'Teacher', 'Active', NULL, NULL),
(3, 'admin3', '$2y$10$LWXpgJSZd1S84r/f0EZv.uHvU478.72beR6mIkclDJD.yfbbd0/.y', NULL, 'Admin', 'Active', NULL, NULL),
(4, 'admin4', '$2y$10$rMrapk8NAzb1HQbOFHFkFO7tGzCw.EmI4Nt8R/Bo9m/jiSxYm6ukm', NULL, 'Admin', 'Active', NULL, NULL),
(5, 'admin5', '$2y$10$gLZy3M7C2q2/AOsF6OdpP.WxYbCa1LfCpuHlYjGbDloa5ldY.okam', NULL, 'Admin', 'Active', NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
