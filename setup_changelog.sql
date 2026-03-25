
-- Table for user changelog tracking
ALTER TABLE `user_account` ADD COLUMN `LAST_SEEN_CHANGELOG_ID` INT DEFAULT 0;

-- Table for changelog content
CREATE TABLE IF NOT EXISTS `changelog` (
  `CHANGELOG_ID` int NOT NULL AUTO_INCREMENT,
  `VERSION` varchar(20) NOT NULL,
  `TITLE_EN` varchar(255) NOT NULL,
  `TITLE_AR` varchar(255) NOT NULL,
  `CONTENT_EN` text NOT NULL,
  `CONTENT_AR` text NOT NULL,
  `CREATED_AT` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CHANGELOG_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a sample changelog entry
INSERT INTO `changelog` (VERSION, TITLE_EN, TITLE_AR, CONTENT_EN, CONTENT_AR) VALUES 
('1.0.0', 'Welcome to the New Update!', 'مرحباً بكم في التحديث الجديد!', 
'<ul><li>Added bilingual student reports.</li><li>Modernized admin dashboard with Bento Grid.</li><li>Fixed various UI issues.</li></ul>', 
'<ul><li>إضافة تقارير الطلاب باللغتين.</li><li>تحديث لوحة تحكم المسؤول باستخدام Bento Grid.</li><li>إصلاح مشاكل واجهة المستخدم المختلفة.</li></ul>');
