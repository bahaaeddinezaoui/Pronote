
-- Update changelog table to include release date and rename content for clarity
ALTER TABLE `changelog` ADD COLUMN `RELEASE_DATE` DATE NOT NULL AFTER `VERSION`;

-- Clear the sample with HTML
DELETE FROM `changelog` WHERE VERSION = '1.0.0';

-- Insert a new sample WITHOUT HTML tags
INSERT INTO `changelog` (VERSION, RELEASE_DATE, TITLE_EN, TITLE_AR, CONTENT_EN, CONTENT_AR) VALUES 
('1.0.0', '2026-03-24', 'Welcome to the New Update!', 'مرحباً بكم في التحديث الجديد!', 
'Added bilingual student reports.\nModernized admin dashboard with Bento Grid.\nFixed various UI issues.', 
'إضافة تقارير الطلاب باللغتين.\nتحديث لوحة تحكم المسؤول باستخدام Bento Grid.\nإصلاح مشاكل واجهة المستخدم المختلفة.');
