-- =============================================
-- GCOMP.GE PC Configurator - Deploy SQL
-- Run ONCE on production database
-- NOTE: Uses oc_ prefix. If your prefix differs, replace all oc_ with your prefix.
-- =============================================

-- 1. Create configurator tables (IF NOT EXISTS = safe to re-run)

CREATE TABLE IF NOT EXISTS `oc_pc_component_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_ka` varchar(255) NOT NULL,
  `name_ru` varchar(255) NOT NULL DEFAULT '',
  `icon` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `section` enum('system','periphery','other') NOT NULL DEFAULT 'system',
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `oc_pc_component` (
  `component_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `specs` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`component_id`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `oc_pc_compatibility_rule` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_type` varchar(50) NOT NULL,
  `component_id_1` int(11) NOT NULL,
  `component_id_2` int(11) NOT NULL,
  `compatible` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `oc_pc_component_attribute` (
  `attribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `component_id` int(11) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `attribute_value` varchar(255) NOT NULL,
  PRIMARY KEY (`attribute_id`),
  KEY `idx_component_id` (`component_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `oc_pc_configuration` (
  `config_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `components` text NOT NULL,
  `total_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `oc_pc_order` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`order_id`),
  KEY `idx_config_id` (`config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Insert categories (IGNORE = skip if name already exists)

INSERT IGNORE INTO `oc_pc_component_category` (`name`, `name_ka`, `name_ru`, `icon`, `sort_order`, `section`, `required`) VALUES
('Processor', 'პროცესორი', 'Процессор', 'cpu.svg', 1, 'system', 1),
('Motherboard', 'დედაფლატა', 'Материнская плата', 'motherboard.svg', 2, 'system', 1),
('RAM', 'ოპერატიული მეხსიერება', 'Оперативная память', 'ram.svg', 3, 'system', 1),
('Video Card', 'ვიდეობარათი', 'Видеокарта', 'gpu.svg', 4, 'system', 0),
('Power Supply', 'კვების ბლოკი', 'Блок питания', 'psu.svg', 5, 'system', 1),
('CPU Cooler', 'პროცესორის გაგრილება', 'Охлаждение процессора', 'cooler.svg', 6, 'system', 0),
('Case', 'კორპუსი', 'Корпус', 'case.svg', 7, 'system', 1),
('SSD', 'SSD', 'SSD', 'ssd.svg', 8, 'system', 0),
('HDD', 'HDD', 'HDD', 'hdd.svg', 9, 'system', 0),
('Case Fan', 'კორპუსის ვენტილატორი', 'Вентилятор корпуса', 'fan.svg', 10, 'system', 0),
('Monitor', 'მონიტორი', 'Монитор', 'monitor.svg', 11, 'periphery', 0),
('Keyboard', 'კლავიატურა', 'Клавიатура', 'keyboard.svg', 12, 'periphery', 0),
('Mouse', 'მაუსი', 'Мышь', 'mouse.svg', 13, 'periphery', 0),
('Headset', 'ყურსასმენი', 'Наушники', 'headset.svg', 14, 'periphery', 0),
('Speakers', 'დინამიკები', 'Колонки', 'speakers.svg', 15, 'periphery', 0),
('Mousepad', 'მაუსპადი', 'Коврик для мыши', 'mousepad.svg', 16, 'periphery', 0),
('Microphone', 'მიკროფონი', 'Микрофон', 'microphone.svg', 17, 'periphery', 0);

-- 3. Add configurator to megamenu
-- Check if already added (skip if exists)

SET @cfg_exists = (SELECT COUNT(*) FROM `oc_oct_megamenu_description` WHERE `link` LIKE '%information/configurator%');

-- Only insert if not already in menu
SET @do_insert = IF(@cfg_exists = 0, 1, 0);

-- Conditional insert via prepared statement
SET @sql1 = IF(@do_insert, "INSERT INTO `oc_oct_megamenu` (`item_type`, `sort_order`, `status`, `info_text`, `display_type`, `date_added`, `banner_image_button_hover_color`, `banner_image_link_hover_color`) VALUES (0, 3, 1, 0, 1, NOW(), '', '')", "SELECT 1");
PREPARE stmt1 FROM @sql1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

SET @cfg_menu_id = IF(@do_insert, LAST_INSERT_ID(), 0);

SET @sql2 = IF(@do_insert, CONCAT("INSERT INTO `oc_oct_megamenu_description` (`megamenu_id`, `language_id`, `link`, `title`, `custom_html`, `banner_title`, `banner_text`, `banner_link`, `banner_button`) VALUES (", @cfg_menu_id, ", 1, 'index.php?route=information/configurator', 'Configurator', '', '', '', '', ''), (", @cfg_menu_id, ", 2, 'index.php?route=information/configurator', 'კონფიგურატორი', '', '', '', '', ''), (", @cfg_menu_id, ", 3, 'index.php?route=information/configurator', 'Конфигуратор', '', '', '', '', '')"), "SELECT 1");
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

SET @sql3 = IF(@do_insert, CONCAT("INSERT INTO `oc_oct_megamenu_to_store` (`megamenu_id`, `store_id`) VALUES (", @cfg_menu_id, ", 0)"), "SELECT 1");
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- 4. Add configurator permissions to Administrator group
-- This needs to be done via the fix_perms.py script or manually in admin
-- Route: extension/module/configurator (add to both access and modify in user_group permissions)

-- 5. Patch 3: Split CPU Cooler into Air Cooler + Water Cooler

UPDATE oc_pc_component_category SET name='Air Cooler', name_ka='ჰაერის გაგრილება', name_ru='Воздушное охлаждение', icon='cooler.svg' WHERE category_id = 6;

INSERT IGNORE INTO oc_pc_component_category (name, name_ka, name_ru, icon, sort_order, section, required, status) VALUES ('Water Cooler', 'წყლის გაგრილება', 'Водяное охлаждение', 'cooler.svg', 7, 'system', 0, 1);

UPDATE oc_pc_component_category SET sort_order = 8 WHERE name = 'Case';
UPDATE oc_pc_component_category SET sort_order = 9 WHERE name = 'SSD';
UPDATE oc_pc_component_category SET sort_order = 10 WHERE name = 'HDD';
UPDATE oc_pc_component_category SET sort_order = 11 WHERE name = 'Case Fan';

-- 6. Patch 4: Default module settings

INSERT IGNORE INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
(0, 'cfg', 'cfg_show_save',   '0', 0),
(0, 'cfg', 'cfg_show_load',   '0', 0),
(0, 'cfg', 'cfg_qty_ram',     '4', 0),
(0, 'cfg', 'cfg_qty_ssd',     '4', 0),
(0, 'cfg', 'cfg_qty_hdd',     '4', 0),
(0, 'cfg', 'cfg_qty_casefan', '8', 0),
(0, 'cfg', 'cfg_qty_monitor', '3', 0);
