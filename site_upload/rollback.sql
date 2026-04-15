-- =============================================
-- GCOMP.GE PC Configurator - ROLLBACK SQL
-- Run this to completely remove the configurator module from database
-- NOTE: This will DELETE all configurator orders and configurations!
-- =============================================

-- 1. Remove menu entry
DELETE FROM oc_oct_megamenu_to_store WHERE megamenu_id IN (
    SELECT megamenu_id FROM oc_oct_megamenu_description WHERE link LIKE '%information/configurator%'
);
DELETE FROM oc_oct_megamenu WHERE megamenu_id IN (
    SELECT megamenu_id FROM oc_oct_megamenu_description WHERE link LIKE '%information/configurator%'
);
DELETE FROM oc_oct_megamenu_description WHERE link LIKE '%information/configurator%';

-- 2. Drop all configurator tables
DROP TABLE IF EXISTS oc_pc_order;
DROP TABLE IF EXISTS oc_pc_configuration;
DROP TABLE IF EXISTS oc_pc_component_attribute;
DROP TABLE IF EXISTS oc_pc_compatibility_rule;
DROP TABLE IF EXISTS oc_pc_component;
DROP TABLE IF EXISTS oc_pc_component_category;

-- 3. Files to delete from server (manually via FTP):
-- catalog/controller/information/configurator.php
-- catalog/model/catalog/configurator.php
-- catalog/view/theme/chameleon/template/information/configurator.twig
-- catalog/view/javascript/configurator/ (entire folder)
-- catalog/language/en-gb/information/configurator.php
-- catalog/language/ge-ka/information/configurator.php
-- catalog/language/ka-ge/information/configurator.php
-- catalog/language/ru-ru/information/configurator.php
-- admin/controller/extension/module/configurator.php
-- admin/model/extension/module/configurator.php
-- admin/view/template/extension/module/configurator.twig
-- admin/language/en-gb/extension/module/configurator.php
-- admin/language/ge-ka/extension/module/configurator.php
-- admin/language/ru-ru/extension/module/configurator.php
