-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 20, 2026 at 07:46 PM
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
-- Database: `test_class_edition_no_data`
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
  KEY `FK_ABSENCE_SESSION` (`STUDY_SESSION_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

DROP TABLE IF EXISTS `address`;
CREATE TABLE IF NOT EXISTS `address` (
  `ADDRESS_ID` int NOT NULL AUTO_INCREMENT,
  `ADDRESS_STREET_EN` varchar(255) DEFAULT NULL,
  `ADDRESS_STREET_AR` varchar(255) DEFAULT NULL,
  `COMMUNE_ID` int DEFAULT NULL,
  `DAIRA_ID` int DEFAULT NULL,
  `WILAYA_ID` int DEFAULT NULL,
  `COUNTRY_ID` int NOT NULL,
  PRIMARY KEY (`ADDRESS_ID`),
  KEY `FK_ADDR_COMMUNE` (`COMMUNE_ID`),
  KEY `FK_ADDR_COUNTRY` (`COUNTRY_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `administrator`
--

DROP TABLE IF EXISTS `administrator`;
CREATE TABLE IF NOT EXISTS `administrator` (
  `ADMINISTRATOR_ID` int NOT NULL,
  `USER_ID` int NOT NULL,
  `ADMINISTRATOR_FIRST_NAME_EN` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ADMINISTRATOR_LAST_NAME_EN` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ADMINISTRATOR_FIRST_NAME_AR` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ADMINISTRATOR_LAST_NAME_AR` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ADMINISTRATOR_POSITION` varchar(60) DEFAULT NULL,
  `ADMINISTRATOR_PHOTO` mediumblob NOT NULL,
  `ADMINISTRATOR_GRADE_ID` int DEFAULT NULL,
  PRIMARY KEY (`ADMINISTRATOR_ID`),
  KEY `FK_ADMIN_USER` (`USER_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  KEY `FK_ARO_ADMIN` (`ADMINISTRATOR_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `army`
--

DROP TABLE IF EXISTS `army`;
CREATE TABLE IF NOT EXISTS `army` (
  `ARMY_ID` int NOT NULL,
  `ARMY_LABEL_EN` varchar(30) DEFAULT NULL,
  `ARMY_LABEL_AR` varchar(30) DEFAULT NULL,
  `ARMY_NAME_EN` varchar(60) DEFAULT NULL,
  `ARMY_NAME_AR` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`ARMY_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
CREATE TABLE IF NOT EXISTS `category` (
  `CATEGORY_ID` int NOT NULL,
  `CATEGORY_NAME_EN` varchar(30) DEFAULT NULL,
  `CATEGORY_NAME_AR` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`CATEGORY_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

DROP TABLE IF EXISTS `class`;
CREATE TABLE IF NOT EXISTS `class` (
  `CLASS_ID` int NOT NULL,
  `CLASS_NAME_EN` varchar(12) DEFAULT NULL,
  `CLASS_NAME_AR` varchar(12) DEFAULT NULL,
  PRIMARY KEY (`CLASS_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commune`
--

DROP TABLE IF EXISTS `commune`;
CREATE TABLE IF NOT EXISTS `commune` (
  `COMMUNE_ID` int NOT NULL AUTO_INCREMENT,
  `DAIRA_ID` int NOT NULL,
  `COMMUNE_NAME_EN` varchar(100) NOT NULL,
  `COMMUNE_NAME_AR` varchar(100) NOT NULL,
  PRIMARY KEY (`COMMUNE_ID`),
  KEY `FK_COMMUNE_DAIRA` (`DAIRA_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=1542 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `country`
--

DROP TABLE IF EXISTS `country`;
CREATE TABLE IF NOT EXISTS `country` (
  `COUNTRY_ID` int NOT NULL AUTO_INCREMENT,
  `COUNTRY_CODE` varchar(3) NOT NULL,
  `COUNTRY_NAME_EN` varchar(100) NOT NULL,
  `COUNTRY_NAME_AR` varchar(100) NOT NULL,
  PRIMARY KEY (`COUNTRY_ID`),
  UNIQUE KEY `UK_COUNTRY_CODE` (`COUNTRY_CODE`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daira`
--

DROP TABLE IF EXISTS `daira`;
CREATE TABLE IF NOT EXISTS `daira` (
  `DAIRA_ID` int NOT NULL AUTO_INCREMENT,
  `WILAYA_ID` int NOT NULL,
  `DAIRA_NAME_EN` varchar(100) NOT NULL,
  `DAIRA_NAME_AR` varchar(100) NOT NULL,
  PRIMARY KEY (`DAIRA_ID`),
  KEY `FK_DAIRA_WILAYA` (`WILAYA_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=5803 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade`
--

DROP TABLE IF EXISTS `grade`;
CREATE TABLE IF NOT EXISTS `grade` (
  `GRADE_ID` int NOT NULL,
  `GRADE_LABEL_EN` varchar(30) DEFAULT NULL,
  `GRADE_NAME_EN` varchar(30) DEFAULT NULL,
  `GRADE_TYPE_EN` varchar(30) DEFAULT NULL,
  `GRADE_NAME_AR` varchar(24) DEFAULT NULL,
  `GRADE_LABEL_AR` varchar(30) DEFAULT NULL,
  `GRADE_TYPE_AR` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`GRADE_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `major`
--

DROP TABLE IF EXISTS `major`;
CREATE TABLE IF NOT EXISTS `major` (
  `MAJOR_ID` varchar(12) NOT NULL,
  `MAJOR_NAME_EN` varchar(48) DEFAULT NULL,
  `MAJOR_NAME_AR` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`MAJOR_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
CREATE TABLE IF NOT EXISTS `notification` (
  `NOTIFICATION_ID` int NOT NULL,
  PRIMARY KEY (`NOTIFICATION_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `observation`
--

DROP TABLE IF EXISTS `observation`;
CREATE TABLE IF NOT EXISTS `observation` (
  `OBSERVATION_ID` int NOT NULL,
  `STUDY_SESSION_ID` int NOT NULL,
  PRIMARY KEY (`OBSERVATION_ID`),
  KEY `FK_OBS_SESSION` (`STUDY_SESSION_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receives`
--

DROP TABLE IF EXISTS `receives`;
CREATE TABLE IF NOT EXISTS `receives` (
  `NOTIFICATION_ID` int NOT NULL,
  `ADMINISTRATOR_ID` int NOT NULL,
  PRIMARY KEY (`NOTIFICATION_ID`,`ADMINISTRATOR_ID`),
  KEY `FK_RECEIVE_ADMIN` (`ADMINISTRATOR_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recruitment_source`
--

DROP TABLE IF EXISTS `recruitment_source`;
CREATE TABLE IF NOT EXISTS `recruitment_source` (
  `RECRUITMENT_SOURCE_ID` int NOT NULL AUTO_INCREMENT,
  `RECRUITMENT_TYPE_EN` enum('Civil','ECN') NOT NULL,
  `RECRUITMENT_TYPE_AR` varchar(60) DEFAULT NULL,
  `ECN_SCHOOL_NAME_EN` varchar(100) DEFAULT NULL,
  `ECN_SCHOOL_NAME_AR` varchar(100) DEFAULT NULL,
  `ECN_SCHOOL_WILAYA_ID` int DEFAULT NULL,
  PRIMARY KEY (`RECRUITMENT_SOURCE_ID`),
  KEY `FK_RECRUIT_WILAYA` (`ECN_SCHOOL_WILAYA_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `secretary`
--

DROP TABLE IF EXISTS `secretary`;
CREATE TABLE IF NOT EXISTS `secretary` (
  `SECRETARY_ID` int NOT NULL,
  `USER_ID` int NOT NULL,
  `SECRETARY_FIRST_NAME_EN` varchar(24) DEFAULT NULL,
  `SECRETARY_LAST_NAME_EN` varchar(24) DEFAULT NULL,
  `SECRETARY_FIRST_NAME_AR` varchar(24) DEFAULT NULL,
  `SECRETARY_LAST_NAME_AR` varchar(24) DEFAULT NULL,
  `SECRETARY_GRADE_ID` int NOT NULL,
  `SECRETARY_POSITION` varchar(60) DEFAULT NULL,
  `SECRETARY_PHOTO` mediumblob NOT NULL,
  PRIMARY KEY (`SECRETARY_ID`),
  KEY `FK_SECRETARY_USER` (`USER_ID`),
  KEY `FK_SECRETARY_GRADE` (`SECRETARY_GRADE_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

DROP TABLE IF EXISTS `section`;
CREATE TABLE IF NOT EXISTS `section` (
  `SECTION_ID` int NOT NULL,
  `CATEGORY_ID` int NOT NULL,
  `SECTION_NAME_EN` varchar(30) DEFAULT NULL,
  `SECTION_NAME_AR` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`SECTION_ID`),
  KEY `FK_SECTION_CAT` (`CATEGORY_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sends`
--

DROP TABLE IF EXISTS `sends`;
CREATE TABLE IF NOT EXISTS `sends` (
  `NOTIFICATION_ID` int NOT NULL,
  `CLASS_ID` int NOT NULL,
  PRIMARY KEY (`NOTIFICATION_ID`,`CLASS_ID`),
  KEY `FK_SEND_CLASS` (`CLASS_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
CREATE TABLE IF NOT EXISTS `student` (
  `STUDENT_SERIAL_NUMBER` varchar(16) NOT NULL,
  `CATEGORY_ID` int NOT NULL,
  `SECTION_ID` int NOT NULL,
  `STUDENT_FIRST_NAME_EN` varchar(42) DEFAULT NULL,
  `STUDENT_LAST_NAME_EN` varchar(42) DEFAULT NULL,
  `STUDENT_FIRST_NAME_AR` varchar(42) DEFAULT NULL,
  `STUDENT_LAST_NAME_AR` varchar(42) DEFAULT NULL,
  `STUDENT_GRADE_ID` int DEFAULT NULL,
  `STUDENT_SEX` enum('Male','Female') DEFAULT NULL,
  `STUDENT_BIRTH_DATE` date DEFAULT NULL,
  `STUDENT_BLOOD_TYPE` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `STUDENT_PERSONAL_PHONE` varchar(20) DEFAULT NULL,
  `STUDENT_HEIGHT_CM` decimal(5,2) DEFAULT NULL,
  `STUDENT_WEIGHT_KG` decimal(5,2) DEFAULT NULL,
  `STUDENT_IS_FOREIGN` enum('Yes','No') DEFAULT 'No',
  `STUDENT_ACADEMIC_AVERAGE` decimal(5,2) DEFAULT NULL,
  `STUDENT_SPECIALITY` varchar(60) DEFAULT NULL,
  `STUDENT_ACADEMIC_LEVEL` varchar(60) DEFAULT NULL,
  `STUDENT_BACCALAUREATE_SUB_NUMBER` varchar(30) DEFAULT NULL,
  `STUDENT_EDUCATIONAL_CERTIFICATES` text,
  `STUDENT_MILITARY_CERTIFICATES` text,
  `STUDENT_SCHOOL_SUB_DATE` date DEFAULT NULL,
  `STUDENT_SCHOOL_SUB_CARD_NUMBER` varchar(30) DEFAULT NULL,
  `STUDENT_LAPTOP_SERIAL_NUMBER` varchar(50) DEFAULT NULL,
  `STUDENT_BIRTHDATE_CERTIFICATE_NUMBER` varchar(30) DEFAULT NULL,
  `STUDENT_ID_CARD_NUMBER` varchar(30) DEFAULT NULL,
  `STUDENT_POSTAL_ACCOUNT_NUMBER` varchar(30) DEFAULT NULL,
  `STUDENT_HOBBIES` text,
  `STUDENT_HEALTH_STATUS` text,
  `STUDENT_MILITARY_NECKLACE` enum('Yes','No') DEFAULT 'No',
  `STUDENT_NUMBER_OF_SIBLINGS` int DEFAULT NULL,
  `STUDENT_NUMBER_OF_SISTERS` int DEFAULT NULL,
  `STUDENT_ORDER_AMONG_SIBLINGS` int DEFAULT NULL,
  `STUDENT_ARMY_ID` int DEFAULT NULL,
  `STUDENT_ORPHAN_STATUS` enum('None','Father','Mother','Both') DEFAULT 'None',
  `STUDENT_PARENTS_SITUATION` enum('Married','Divorced','Separated','Widowed') DEFAULT 'Married',
  `STUDENT_BIRTH_PLACE_ID` int DEFAULT NULL,
  `STUDENT_PERSONAL_ADDRESS_ID` int DEFAULT NULL,
  `STUDENT_RECRUITMENT_SOURCE_ID` int DEFAULT NULL,
  PRIMARY KEY (`STUDENT_SERIAL_NUMBER`),
  KEY `FK_STUDENT_SECTION` (`SECTION_ID`),
  KEY `FK_STUDENT_CAT` (`CATEGORY_ID`),
  KEY `FK_STUDENT_BIRTH` (`STUDENT_BIRTH_PLACE_ID`),
  KEY `FK_STUDENT_ADDR` (`STUDENT_PERSONAL_ADDRESS_ID`),
  KEY `FK_STUDENT_RECRUIT_REF` (`STUDENT_RECRUITMENT_SOURCE_ID`),
  KEY `FK_STUDENT_GRADE` (`STUDENT_GRADE_ID`),
  KEY `FK_STUDENT_ARMY` (`STUDENT_ARMY_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_combat_outfit`
--

DROP TABLE IF EXISTS `student_combat_outfit`;
CREATE TABLE IF NOT EXISTS `student_combat_outfit` (
  `STUDENT_SERIAL_NUMBER` varchar(16) NOT NULL,
  `FIRST_OUTFIT_NUMBER` varchar(30) DEFAULT NULL,
  `FIRST_OUTFIT_SIZE` varchar(10) DEFAULT NULL,
  `SECOND_OUTFIT_NUMBER` varchar(30) DEFAULT NULL,
  `SECOND_OUTFIT_SIZE` varchar(10) DEFAULT NULL,
  `COMBAT_SHOE_SIZE` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`STUDENT_SERIAL_NUMBER`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_emergency_contact`
--

DROP TABLE IF EXISTS `student_emergency_contact`;
CREATE TABLE IF NOT EXISTS `student_emergency_contact` (
  `EMERGENCY_CONTACT_ID` int NOT NULL AUTO_INCREMENT,
  `STUDENT_SERIAL_NUMBER` varchar(16) NOT NULL,
  `CONTACT_FIRST_NAME_EN` varchar(42) DEFAULT NULL,
  `CONTACT_LAST_NAME_EN` varchar(42) DEFAULT NULL,
  `CONTACT_FIRST_NAME_AR` varchar(42) DEFAULT NULL,
  `CONTACT_LAST_NAME_AR` varchar(42) DEFAULT NULL,
  `CONTACT_RELATION_EN` varchar(30) DEFAULT NULL,
  `CONTACT_RELATION_AR` varchar(30) DEFAULT NULL,
  `CONTACT_PHONE_NUMBER` varchar(20) DEFAULT NULL,
  `CONTACT_ADDRESS_ID` int DEFAULT NULL,
  `CONSULATE_NUMBER` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`EMERGENCY_CONTACT_ID`),
  KEY `FK_EMERGENCY_STUDENT` (`STUDENT_SERIAL_NUMBER`),
  KEY `FK_EMERGENCY_ADDR` (`CONTACT_ADDRESS_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_gets_absent`
--

DROP TABLE IF EXISTS `student_gets_absent`;
CREATE TABLE IF NOT EXISTS `student_gets_absent` (
  `STUDENT_SERIAL_NUMBER` varchar(16) NOT NULL,
  `ABSENCE_ID` int NOT NULL,
  PRIMARY KEY (`STUDENT_SERIAL_NUMBER`,`ABSENCE_ID`),
  KEY `FK_SGA_ABSENCE` (`ABSENCE_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_parade_uniform`
--

DROP TABLE IF EXISTS `student_parade_uniform`;
CREATE TABLE IF NOT EXISTS `student_parade_uniform` (
  `STUDENT_SERIAL_NUMBER` varchar(16) NOT NULL,
  `SUMMER_JACKET_SIZE` varchar(10) DEFAULT NULL,
  `WINTER_JACKET_SIZE` varchar(10) DEFAULT NULL,
  `SUMMER_TROUSERS_SIZE` varchar(10) DEFAULT NULL,
  `WINTER_TROUSERS_SIZE` varchar(10) DEFAULT NULL,
  `SUMMER_SHIRT_SIZE` varchar(10) DEFAULT NULL,
  `WINTER_SHIRT_SIZE` varchar(10) DEFAULT NULL,
  `SUMMER_HAT_SIZE` varchar(10) DEFAULT NULL,
  `WINTER_HAT_SIZE` varchar(10) DEFAULT NULL,
  `SUMMER_SKIRT_SIZE` varchar(10) DEFAULT NULL,
  `WINTER_SKIRT_SIZE` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`STUDENT_SERIAL_NUMBER`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_parent_info`
--

DROP TABLE IF EXISTS `student_parent_info`;
CREATE TABLE IF NOT EXISTS `student_parent_info` (
  `STUDENT_SERIAL_NUMBER` varchar(16) NOT NULL,
  `FATHER_FIRST_NAME_EN` varchar(42) DEFAULT NULL,
  `FATHER_LAST_NAME_EN` varchar(42) DEFAULT NULL,
  `FATHER_FIRST_NAME_AR` varchar(42) DEFAULT NULL,
  `FATHER_LAST_NAME_AR` varchar(42) DEFAULT NULL,
  `FATHER_PROFESSION_EN` varchar(60) DEFAULT NULL,
  `FATHER_PROFESSION_AR` varchar(60) DEFAULT NULL,
  `MOTHER_FIRST_NAME_EN` varchar(42) DEFAULT NULL,
  `MOTHER_LAST_NAME_EN` varchar(42) DEFAULT NULL,
  `MOTHER_FIRST_NAME_AR` varchar(42) DEFAULT NULL,
  `MOTHER_LAST_NAME_AR` varchar(42) DEFAULT NULL,
  `MOTHER_PROFESSION_EN` varchar(60) DEFAULT NULL,
  `MOTHER_PROFESSION_AR` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`STUDENT_SERIAL_NUMBER`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `studies`
--

DROP TABLE IF EXISTS `studies`;
CREATE TABLE IF NOT EXISTS `studies` (
  `SECTION_ID` int NOT NULL,
  `MAJOR_ID` varchar(12) NOT NULL,
  PRIMARY KEY (`SECTION_ID`,`MAJOR_ID`),
  KEY `FK_STUDIES_MAJOR` (`MAJOR_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `studies_in`
--

DROP TABLE IF EXISTS `studies_in`;
CREATE TABLE IF NOT EXISTS `studies_in` (
  `SECTION_ID` int NOT NULL,
  `STUDY_SESSION_ID` int NOT NULL,
  PRIMARY KEY (`SECTION_ID`,`STUDY_SESSION_ID`),
  KEY `FK_SI_SESSION` (`STUDY_SESSION_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  KEY `FK_SESSION_CLASS` (`CLASS_ID`),
  KEY `FK_SESSION_TEACHER` (`TEACHER_SERIAL_NUMBER`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

DROP TABLE IF EXISTS `teacher`;
CREATE TABLE IF NOT EXISTS `teacher` (
  `TEACHER_SERIAL_NUMBER` varchar(16) NOT NULL,
  `USER_ID` int NOT NULL,
  `TEACHER_FIRST_NAME_EN` varchar(24) DEFAULT NULL,
  `TEACHER_LAST_NAME_EN` varchar(24) DEFAULT NULL,
  `TEACHER_PHOTO` mediumblob NOT NULL,
  `TEACHER_FIRST_NAME_AR` varchar(24) DEFAULT NULL,
  `TEACHER_LAST_NAME_AR` varchar(24) DEFAULT NULL,
  `TEACHER_GRADE_ID` int DEFAULT NULL,
  PRIMARY KEY (`TEACHER_SERIAL_NUMBER`),
  KEY `FK_TEACHER_USER` (`USER_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  KEY `FK_TMO_OBS` (`OBSERVATION_ID`),
  KEY `FK_TMO_TEACHER` (`TEACHER_SERIAL_NUMBER`),
  KEY `FK_TMO_SESSION` (`STUDY_SESSION_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teaches`
--

DROP TABLE IF EXISTS `teaches`;
CREATE TABLE IF NOT EXISTS `teaches` (
  `MAJOR_ID` varchar(12) NOT NULL,
  `TEACHER_SERIAL_NUMBER` varchar(16) NOT NULL,
  PRIMARY KEY (`MAJOR_ID`,`TEACHER_SERIAL_NUMBER`),
  KEY `FK_TEACHES_TEACHER_REF` (`TEACHER_SERIAL_NUMBER`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wilaya`
--

DROP TABLE IF EXISTS `wilaya`;
CREATE TABLE IF NOT EXISTS `wilaya` (
  `WILAYA_ID` int NOT NULL AUTO_INCREMENT,
  `COUNTRY_ID` int NOT NULL,
  `WILAYA_CODE` varchar(2) NOT NULL,
  `WILAYA_NAME_EN` varchar(50) NOT NULL,
  `WILAYA_NAME_AR` varchar(50) NOT NULL,
  PRIMARY KEY (`WILAYA_ID`),
  KEY `FK_WILAYA_COUNTRY` (`COUNTRY_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absence`
--
ALTER TABLE `absence`
  ADD CONSTRAINT `FK_ABSENCE_SESSION` FOREIGN KEY (`STUDY_SESSION_ID`) REFERENCES `study_session` (`STUDY_SESSION_ID`);

--
-- Constraints for table `address`
--
ALTER TABLE `address`
  ADD CONSTRAINT `FK_ADDR_COMMUNE` FOREIGN KEY (`COMMUNE_ID`) REFERENCES `commune` (`COMMUNE_ID`),
  ADD CONSTRAINT `FK_ADDR_COUNTRY` FOREIGN KEY (`COUNTRY_ID`) REFERENCES `country` (`COUNTRY_ID`);

--
-- Constraints for table `administrator`
--
ALTER TABLE `administrator`
  ADD CONSTRAINT `FK_ADMIN_USER` FOREIGN KEY (`USER_ID`) REFERENCES `user_account` (`USER_ID`);

--
-- Constraints for table `admin_read_observation`
--
ALTER TABLE `admin_read_observation`
  ADD CONSTRAINT `FK_ARO_ADMIN` FOREIGN KEY (`ADMINISTRATOR_ID`) REFERENCES `administrator` (`ADMINISTRATOR_ID`),
  ADD CONSTRAINT `FK_ARO_OBS` FOREIGN KEY (`OBSERVATION_ID`) REFERENCES `observation` (`OBSERVATION_ID`);

--
-- Constraints for table `commune`
--
ALTER TABLE `commune`
  ADD CONSTRAINT `FK_COMMUNE_DAIRA` FOREIGN KEY (`DAIRA_ID`) REFERENCES `daira` (`DAIRA_ID`);

--
-- Constraints for table `daira`
--
ALTER TABLE `daira`
  ADD CONSTRAINT `FK_DAIRA_WILAYA` FOREIGN KEY (`WILAYA_ID`) REFERENCES `wilaya` (`WILAYA_ID`);

--
-- Constraints for table `observation`
--
ALTER TABLE `observation`
  ADD CONSTRAINT `FK_OBS_SESSION` FOREIGN KEY (`STUDY_SESSION_ID`) REFERENCES `study_session` (`STUDY_SESSION_ID`);

--
-- Constraints for table `receives`
--
ALTER TABLE `receives`
  ADD CONSTRAINT `FK_RECEIVE_ADMIN` FOREIGN KEY (`ADMINISTRATOR_ID`) REFERENCES `administrator` (`ADMINISTRATOR_ID`),
  ADD CONSTRAINT `FK_RECEIVE_NOTIF` FOREIGN KEY (`NOTIFICATION_ID`) REFERENCES `notification` (`NOTIFICATION_ID`);

--
-- Constraints for table `section`
--
ALTER TABLE `section`
  ADD CONSTRAINT `FK_SECTION_CAT` FOREIGN KEY (`CATEGORY_ID`) REFERENCES `category` (`CATEGORY_ID`);

--
-- Constraints for table `sends`
--
ALTER TABLE `sends`
  ADD CONSTRAINT `FK_SEND_CLASS` FOREIGN KEY (`CLASS_ID`) REFERENCES `class` (`CLASS_ID`),
  ADD CONSTRAINT `FK_SEND_NOTIF` FOREIGN KEY (`NOTIFICATION_ID`) REFERENCES `notification` (`NOTIFICATION_ID`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `FK_STUDENT_ADDR` FOREIGN KEY (`STUDENT_PERSONAL_ADDRESS_ID`) REFERENCES `address` (`ADDRESS_ID`),
  ADD CONSTRAINT `FK_STUDENT_ARMY` FOREIGN KEY (`STUDENT_ARMY_ID`) REFERENCES `army` (`ARMY_ID`),
  ADD CONSTRAINT `FK_STUDENT_BIRTH` FOREIGN KEY (`STUDENT_BIRTH_PLACE_ID`) REFERENCES `address` (`ADDRESS_ID`),
  ADD CONSTRAINT `FK_STUDENT_CAT` FOREIGN KEY (`CATEGORY_ID`) REFERENCES `category` (`CATEGORY_ID`),
  ADD CONSTRAINT `FK_STUDENT_GRADE` FOREIGN KEY (`STUDENT_GRADE_ID`) REFERENCES `grade` (`GRADE_ID`),
  ADD CONSTRAINT `FK_STUDENT_RECRUIT_REF` FOREIGN KEY (`STUDENT_RECRUITMENT_SOURCE_ID`) REFERENCES `recruitment_source` (`RECRUITMENT_SOURCE_ID`),
  ADD CONSTRAINT `FK_STUDENT_SECTION` FOREIGN KEY (`SECTION_ID`) REFERENCES `section` (`SECTION_ID`);

--
-- Constraints for table `student_combat_outfit`
--
ALTER TABLE `student_combat_outfit`
  ADD CONSTRAINT `FK_COMBAT_STUDENT` FOREIGN KEY (`STUDENT_SERIAL_NUMBER`) REFERENCES `student` (`STUDENT_SERIAL_NUMBER`) ON DELETE CASCADE;

--
-- Constraints for table `student_gets_absent`
--
ALTER TABLE `student_gets_absent`
  ADD CONSTRAINT `FK_SGA_ABSENCE` FOREIGN KEY (`ABSENCE_ID`) REFERENCES `absence` (`ABSENCE_ID`),
  ADD CONSTRAINT `FK_SGA_STUDENT` FOREIGN KEY (`STUDENT_SERIAL_NUMBER`) REFERENCES `student` (`STUDENT_SERIAL_NUMBER`);

--
-- Constraints for table `student_parade_uniform`
--
ALTER TABLE `student_parade_uniform`
  ADD CONSTRAINT `FK_PARADE_STUDENT` FOREIGN KEY (`STUDENT_SERIAL_NUMBER`) REFERENCES `student` (`STUDENT_SERIAL_NUMBER`) ON DELETE CASCADE;

--
-- Constraints for table `student_parent_info`
--
ALTER TABLE `student_parent_info`
  ADD CONSTRAINT `FK_PARENT_INFO_STUDENT` FOREIGN KEY (`STUDENT_SERIAL_NUMBER`) REFERENCES `student` (`STUDENT_SERIAL_NUMBER`) ON DELETE CASCADE;

--
-- Constraints for table `studies`
--
ALTER TABLE `studies`
  ADD CONSTRAINT `FK_STUDIES_MAJOR` FOREIGN KEY (`MAJOR_ID`) REFERENCES `major` (`MAJOR_ID`),
  ADD CONSTRAINT `FK_STUDIES_SECTION` FOREIGN KEY (`SECTION_ID`) REFERENCES `section` (`SECTION_ID`);

--
-- Constraints for table `studies_in`
--
ALTER TABLE `studies_in`
  ADD CONSTRAINT `FK_SI_SECTION` FOREIGN KEY (`SECTION_ID`) REFERENCES `section` (`SECTION_ID`),
  ADD CONSTRAINT `FK_SI_SESSION` FOREIGN KEY (`STUDY_SESSION_ID`) REFERENCES `study_session` (`STUDY_SESSION_ID`);

--
-- Constraints for table `study_session`
--
ALTER TABLE `study_session`
  ADD CONSTRAINT `FK_SESSION_CLASS` FOREIGN KEY (`CLASS_ID`) REFERENCES `class` (`CLASS_ID`),
  ADD CONSTRAINT `FK_SESSION_TEACHER` FOREIGN KEY (`TEACHER_SERIAL_NUMBER`) REFERENCES `teacher` (`TEACHER_SERIAL_NUMBER`);

--
-- Constraints for table `teacher`
--
ALTER TABLE `teacher`
  ADD CONSTRAINT `FK_TEACHER_USER` FOREIGN KEY (`USER_ID`) REFERENCES `user_account` (`USER_ID`);

--
-- Constraints for table `teacher_makes_an_observation_for_a_student`
--
ALTER TABLE `teacher_makes_an_observation_for_a_student`
  ADD CONSTRAINT `FK_TMO_OBS` FOREIGN KEY (`OBSERVATION_ID`) REFERENCES `observation` (`OBSERVATION_ID`),
  ADD CONSTRAINT `FK_TMO_SESSION` FOREIGN KEY (`STUDY_SESSION_ID`) REFERENCES `study_session` (`STUDY_SESSION_ID`),
  ADD CONSTRAINT `FK_TMO_STUDENT` FOREIGN KEY (`STUDENT_SERIAL_NUMBER`) REFERENCES `student` (`STUDENT_SERIAL_NUMBER`),
  ADD CONSTRAINT `FK_TMO_TEACHER` FOREIGN KEY (`TEACHER_SERIAL_NUMBER`) REFERENCES `teacher` (`TEACHER_SERIAL_NUMBER`);

--
-- Constraints for table `teaches`
--
ALTER TABLE `teaches`
  ADD CONSTRAINT `FK_TEACHES_MAJOR_REF` FOREIGN KEY (`MAJOR_ID`) REFERENCES `major` (`MAJOR_ID`),
  ADD CONSTRAINT `FK_TEACHES_TEACHER_REF` FOREIGN KEY (`TEACHER_SERIAL_NUMBER`) REFERENCES `teacher` (`TEACHER_SERIAL_NUMBER`);

--
-- Constraints for table `wilaya`
--
ALTER TABLE `wilaya`
  ADD CONSTRAINT `FK_WILAYA_COUNTRY` FOREIGN KEY (`COUNTRY_ID`) REFERENCES `country` (`COUNTRY_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
