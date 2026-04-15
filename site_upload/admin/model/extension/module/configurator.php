<?php
/**
 * PC Configurator — Admin Model
 *
 * Database operations for the admin panel: orders, manual components,
 * compatibility rules, category management, catalog product queries.
 *
 * @package  PC Configurator for OpenCart
 * @version  1.4.0
 * @author   gcomp.ge
 * @license  MIT
 * @link     https://github.com/YOUR_USERNAME/oc-pc-configurator
 */
class ModelExtensionModuleConfigurator extends Model {

    // Orders
    public function getOrders($start = 0, $limit = 20) {
        $query = $this->db->query("SELECT o.*, c.components, c.total_price
            FROM `" . DB_PREFIX . "pc_order` o
            LEFT JOIN `" . DB_PREFIX . "pc_configuration` c ON o.config_id = c.config_id
            ORDER BY o.date_added DESC
            LIMIT " . (int)$start . "," . (int)$limit);
        return $query->rows;
    }

    public function getTotalOrders() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "pc_order`");
        return (int)$query->row['total'];
    }

    public function updateOrderStatus($order_id, $status) {
        $allowed = array('new', 'processing', 'completed', 'cancelled');
        if (in_array($status, $allowed)) {
            $this->db->query("UPDATE `" . DB_PREFIX . "pc_order` SET status = '" . $this->db->escape($status) . "' WHERE order_id = '" . (int)$order_id . "'");
        }
    }

    public function deleteOrder($order_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "pc_order` WHERE order_id = '" . (int)$order_id . "'");
    }

    public function getComponentName($component_id) {
        // Check manual components first
        $query = $this->db->query("SELECT name FROM `" . DB_PREFIX . "pc_component` WHERE component_id = '" . (int)$component_id . "'");
        if ($query->num_rows) return $query->row['name'];

        // Fallback to product catalog
        $lang_id = (int)$this->config->get('config_language_id');
        $query = $this->db->query("SELECT name FROM `" . DB_PREFIX . "product_description` WHERE product_id = '" . (int)$component_id . "' AND language_id = '" . $lang_id . "'");
        return $query->num_rows ? $query->row['name'] : 'ID: ' . $component_id;
    }

    public function getCategoryName($category_id) {
        $query = $this->db->query("SELECT name_ka, name FROM `" . DB_PREFIX . "pc_component_category` WHERE category_id = '" . (int)$category_id . "'");
        if ($query->num_rows) {
            return $query->row['name_ka'] ?: $query->row['name'];
        }
        return 'Category ' . $category_id;
    }

    // Categories
    public function getCategories() {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "pc_component_category` ORDER BY sort_order ASC");
        return $query->rows;
    }

    public function updateCategory($category_id, $data) {
        $this->db->query("UPDATE `" . DB_PREFIX . "pc_component_category` SET
            name_ka = '" . $this->db->escape(isset($data['name_ka']) ? $data['name_ka'] : '') . "',
            name_ru = '" . $this->db->escape(isset($data['name_ru']) ? $data['name_ru'] : '') . "',
            name = '" . $this->db->escape(isset($data['name']) ? $data['name'] : '') . "'
            WHERE category_id = '" . (int)$category_id . "'");
    }

    // Manual Components
    public function getComponents($category_id = 0) {
        $sql = "SELECT c.*, cat.name_ka as category_name FROM `" . DB_PREFIX . "pc_component` c
                LEFT JOIN `" . DB_PREFIX . "pc_component_category` cat ON c.category_id = cat.category_id";
        if ($category_id) {
            $sql .= " WHERE c.category_id = '" . (int)$category_id . "'";
        }
        $sql .= " ORDER BY c.category_id, c.sort_order, c.name";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getAllComponents() {
        // Manual + catalog products that are in configurator categories
        $query = $this->db->query("SELECT component_id, name, category_id FROM `" . DB_PREFIX . "pc_component` ORDER BY name");
        return $query->rows;
    }

    public function getCatalogProducts($cfg_category_id, $page = 1, $limit = 40) {
        $category_map = array(
            1  => array(271),
            2  => array(272),
            3  => array(274),
            4  => array(273),
            5  => array(277),
            6  => array(407),
            7  => array(278),
            8  => array(275),
            9  => array(275),
            10 => array(),
            11 => array(318),
            12 => array(284, 426, 429),
            13 => array(285, 427, 428),
            14 => array(287),
            15 => array(288),
            16 => array(286),
            17 => array(289),
        );

        if (!isset($category_map[$cfg_category_id]) || empty($category_map[$cfg_category_id])) {
            return array();
        }

        $cat_ids = implode(',', array_map('intval', $category_map[$cfg_category_id]));
        $lang_id = (int)$this->config->get('config_language_id');

        $query = $this->db->query("
            SELECT DISTINCT p.product_id, pd.name, p.price, p.quantity, p.status, p.image
            FROM `" . DB_PREFIX . "product` p
            JOIN `" . DB_PREFIX . "product_description` pd ON p.product_id = pd.product_id AND pd.language_id = '" . $lang_id . "'
            JOIN `" . DB_PREFIX . "product_to_category` p2c ON p.product_id = p2c.product_id
            WHERE p2c.category_id IN (" . $cat_ids . ")
            ORDER BY pd.name ASC
            LIMIT " . (int)(($page - 1) * $limit) . "," . (int)$limit . "
        ");

        return $query->rows;
    }

    public function getCatalogProductCounts() {
        $category_map = array(
            1  => array(271),
            2  => array(272),
            3  => array(274),
            4  => array(273),
            5  => array(277),
            6  => array(407),
            7  => array(278),
            8  => array(275),
            9  => array(275),
            10 => array(),
            11 => array(318),
            12 => array(284, 426, 429),
            13 => array(285, 427, 428),
            14 => array(287),
            15 => array(288),
            16 => array(286),
            17 => array(289),
        );

        $counts = array();
        foreach ($category_map as $cfg_id => $oc_ids) {
            if (empty($oc_ids)) { $counts[$cfg_id] = 0; continue; }
            $ids = implode(',', array_map('intval', $oc_ids));
            $q = $this->db->query("SELECT COUNT(DISTINCT p.product_id) as cnt FROM `" . DB_PREFIX . "product` p JOIN `" . DB_PREFIX . "product_to_category` p2c ON p.product_id = p2c.product_id WHERE p2c.category_id IN (" . $ids . ") AND p.status = 1 AND p.quantity > 0");
            $counts[$cfg_id] = (int)$q->row['cnt'];
        }
        return $counts;
    }

    public function addComponent($data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "pc_component` SET
            category_id = '" . (int)$data['category_id'] . "',
            name = '" . $this->db->escape($data['name']) . "',
            price = '" . (float)$data['price'] . "',
            image = '" . $this->db->escape(isset($data['image']) ? $data['image'] : '') . "',
            specs = '" . $this->db->escape(isset($data['specs']) ? $data['specs'] : '') . "',
            status = '" . (int)(isset($data['status']) ? $data['status'] : 1) . "',
            sort_order = '" . (int)(isset($data['sort_order']) ? $data['sort_order'] : 0) . "'");
        return $this->db->getLastId();
    }

    public function editComponent($component_id, $data) {
        $this->db->query("UPDATE `" . DB_PREFIX . "pc_component` SET
            category_id = '" . (int)$data['category_id'] . "',
            name = '" . $this->db->escape($data['name']) . "',
            price = '" . (float)$data['price'] . "',
            status = '" . (int)(isset($data['status']) ? $data['status'] : 1) . "',
            sort_order = '" . (int)(isset($data['sort_order']) ? $data['sort_order'] : 0) . "'
            WHERE component_id = '" . (int)$component_id . "'");
    }

    public function deleteComponent($component_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "pc_component` WHERE component_id = '" . (int)$component_id . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "pc_component_attribute` WHERE component_id = '" . (int)$component_id . "'");
    }

    public function saveAttributes($component_id, $attributes) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "pc_component_attribute` WHERE component_id = '" . (int)$component_id . "'");
        if (is_array($attributes)) {
            foreach ($attributes as $attr) {
                if (!empty($attr['name']) && !empty($attr['value'])) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "pc_component_attribute` SET
                        component_id = '" . (int)$component_id . "',
                        attribute_name = '" . $this->db->escape($attr['name']) . "',
                        attribute_value = '" . $this->db->escape($attr['value']) . "'");
                }
            }
        }
    }

    // Compatibility Rules
    public function getRules() {
        $query = $this->db->query("SELECT r.*,
            COALESCE(c1.name, pd1.name, CONCAT('ID:', r.component_id_1)) as comp1_name,
            COALESCE(c2.name, pd2.name, CONCAT('ID:', r.component_id_2)) as comp2_name
            FROM `" . DB_PREFIX . "pc_compatibility_rule` r
            LEFT JOIN `" . DB_PREFIX . "pc_component` c1 ON r.component_id_1 = c1.component_id
            LEFT JOIN `" . DB_PREFIX . "pc_component` c2 ON r.component_id_2 = c2.component_id
            LEFT JOIN `" . DB_PREFIX . "product_description` pd1 ON r.component_id_1 = pd1.product_id AND pd1.language_id = '" . (int)$this->config->get('config_language_id') . "'
            LEFT JOIN `" . DB_PREFIX . "product_description` pd2 ON r.component_id_2 = pd2.product_id AND pd2.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY r.rule_id DESC");
        return $query->rows;
    }

    public function addRule($data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "pc_compatibility_rule` SET
            rule_type = '" . $this->db->escape($data['rule_type']) . "',
            component_id_1 = '" . (int)$data['component_id_1'] . "',
            component_id_2 = '" . (int)$data['component_id_2'] . "',
            compatible = '" . (int)$data['compatible'] . "'");
        return $this->db->getLastId();
    }

    public function deleteRule($rule_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "pc_compatibility_rule` WHERE rule_id = '" . (int)$rule_id . "'");
    }
}
