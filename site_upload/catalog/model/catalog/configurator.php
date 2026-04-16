<?php
/**
 * PC Configurator — Frontend Model
 *
 * Database queries for frontend: categories, components (catalog + manual),
 * attributes, configurations, orders, cart operations.
 *
 * @package  PC Configurator for OpenCart
 * @version  1.4.0
 * @author   gcomp.ge
 * @license  MIT
 * @link     https://github.com/YOUR_USERNAME/oc-pc-configurator
 */
class ModelCatalogConfigurator extends Model {

    // Mapping: configurator category_id => OpenCart category_id(s)
    private $category_map = array(
        1  => array(271),       // Processor
        2  => array(272),       // Motherboard
        3  => array(274),       // RAM
        4  => array(273),       // Video Card
        5  => array(277),       // Power Supply
        6  => array(276),       // Air Cooler (filter by name)
        7  => array(278),       // Case
        8  => array(275),       // SSD (filter by name)
        9  => array(275),       // HDD (filter by name)
        10 => array(276),       // Case Fan (filter by name)
        11 => array(318),       // Monitor
        12 => array(284, 426, 429), // Keyboard
        13 => array(285, 427, 428), // Mouse
        14 => array(287),       // Headset
        15 => array(288),       // Speakers
        16 => array(286),       // Mousepad
        17 => array(289),       // Microphone
        18 => array(276),       // Water Cooler (filter by name)
    );

    // Name filters for categories sharing same OC category
    private $name_filters = array(
        3  => array('include' => array(), 'exclude' => array('SODIMM', 'sodimm', 'SO-DIMM', 'so-dimm', 'Laptop', 'laptop', 'ნოუთბ')),
        6  => array('include' => array('პროცესორის ქულერ', 'CPU Cooler', 'cpu cooler', 'Gamma', 'GAMMAXX', 'AK400', 'AK620', 'NH-D', 'NH-U', 'Hyper 212'), 'exclude' => array('წყლ', 'Liquid', 'liquid', 'AIO', 'ქეის', 'Case Fan', 'XFAN', 'ვიდეო')),
        8  => array('include' => array('SSD', 'ssd', 'NVMe', 'nvme', 'M.2', 'm.2'), 'exclude' => array('HDD', 'ყუთ', 'Enclosure', 'გარე')),
        9  => array('include' => array('HDD', 'hdd', 'Barracuda', 'მყარი დისკი'), 'exclude' => array('SSD', 'ssd', 'NVMe', 'nvme', 'M.2', 'ყუთ', 'Enclosure', 'გარე')),
        10 => array('include' => array('ქეის ქულერ', 'Case Fan', 'case fan', 'XFAN', 'FC120'), 'exclude' => array()),
        18 => array('include' => array('წყლ', 'Liquid', 'liquid', 'AIO', 'CORELIQUID'), 'exclude' => array()),
    );

    public function getCategories() {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "pc_component_category` WHERE status = 1 ORDER BY sort_order ASC");
        return $query->rows;
    }

    public function getCategory($category_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "pc_component_category` WHERE category_id = '" . (int)$category_id . "'");
        return $query->row;
    }

    public function getComponentsByCategory($category_id) {
        // First check if we have manual components in our table
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "pc_component` WHERE category_id = '" . (int)$category_id . "' AND status = 1 ORDER BY sort_order ASC, name ASC");

        if ($query->num_rows > 0) {
            return $query->rows;
        }

        // Fallback: pull from OpenCart product catalog
        return $this->getProductsFromCatalog($category_id);
    }

    private function getProductsFromCatalog($category_id) {
        if (!isset($this->category_map[$category_id])) {
            return array();
        }

        $oc_categories = $this->category_map[$category_id];

        if (empty($oc_categories)) {
            return array();
        }

        $language_id = (int)$this->config->get('config_language_id');
        $cat_ids = implode(',', array_map('intval', $oc_categories));

        $query = $this->db->query("
            SELECT DISTINCT p.product_id as component_id,
                   '" . (int)$category_id . "' as category_id,
                   pd.name,
                   COALESCE(ps.price, p.price) as price,
                   IF(ps.price IS NOT NULL AND ps.price < p.price, p.price, NULL) as original_price,
                   p.image,
                   NULL as specs,
                   p.status,
                   p.sort_order
            FROM `" . DB_PREFIX . "product` p
            JOIN `" . DB_PREFIX . "product_description` pd ON p.product_id = pd.product_id AND pd.language_id = '" . $language_id . "'
            JOIN `" . DB_PREFIX . "product_to_category` p2c ON p.product_id = p2c.product_id
            LEFT JOIN (
                SELECT product_id, MIN(price) as price
                FROM `" . DB_PREFIX . "product_special`
                WHERE (date_start = '0000-00-00' OR date_start <= NOW())
                AND (date_end = '0000-00-00' OR date_end >= NOW())
                GROUP BY product_id
            ) ps ON p.product_id = ps.product_id
            WHERE p2c.category_id IN (" . $cat_ids . ")
            AND p.status = 1
            AND p.quantity > 0
            ORDER BY p.sort_order ASC, pd.name ASC
        ");

        // Apply name filters if defined
        if (isset($this->name_filters[$category_id])) {
            $filter = $this->name_filters[$category_id];
            $filtered = array();
            foreach ($query->rows as $row) {
                $name = $row['name'];
                $match = empty($filter['include']); // if no include patterns, match all

                // Check include patterns
                foreach ($filter['include'] as $pattern) {
                    if (stripos($name, $pattern) !== false) {
                        $match = true;
                        break;
                    }
                }

                if (!$match) continue;

                // Check exclude patterns
                $excluded = false;
                foreach ($filter['exclude'] as $pattern) {
                    if (stripos($name, $pattern) !== false) {
                        $excluded = true;
                        break;
                    }
                }

                if (!$excluded) {
                    $filtered[] = $row;
                }
            }
            return $filtered;
        }

        return $query->rows;
    }

    public function getComponent($component_id) {
        // Check our table first
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "pc_component` WHERE component_id = '" . (int)$component_id . "'");

        if ($query->num_rows > 0) {
            return $query->row;
        }

        // Fallback: get from OpenCart products
        $language_id = (int)$this->config->get('config_language_id');
        $query = $this->db->query("
            SELECT p.product_id as component_id,
                   pd.name,
                   COALESCE(ps.price, p.price) as price,
                   p.image
            FROM `" . DB_PREFIX . "product` p
            JOIN `" . DB_PREFIX . "product_description` pd ON p.product_id = pd.product_id AND pd.language_id = '" . $language_id . "'
            LEFT JOIN (
                SELECT product_id, MIN(price) as price
                FROM `" . DB_PREFIX . "product_special`
                WHERE (date_start = '0000-00-00' OR date_start <= NOW())
                AND (date_end = '0000-00-00' OR date_end >= NOW())
                GROUP BY product_id
            ) ps ON p.product_id = ps.product_id
            WHERE p.product_id = '" . (int)$component_id . "'
        ");

        return $query->row;
    }

    public function getComponentAttributes($component_id) {
        $rows = array();

        // Try custom pc_component_attribute table
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "pc_component_attribute` WHERE component_id = '" . (int)$component_id . "'");
        $rows = $query->rows;

        // Read from standard OpenCart oc_product_attribute (component_id = product_id)
        // Use any language_id since attribute names (sockets) are the same in all languages
        $attrs = $this->db->query("
            SELECT ad.name AS attribute_name, pa.text AS attribute_value
            FROM `" . DB_PREFIX . "product_attribute` pa
            LEFT JOIN `" . DB_PREFIX . "attribute_description` ad ON pa.attribute_id = ad.attribute_id
            WHERE pa.product_id = '" . (int)$component_id . "'
            GROUP BY pa.attribute_id
        ");
        foreach ($attrs->rows as $attr) {
            $rows[] = array('attribute_name' => $attr['attribute_name'], 'attribute_value' => $attr['attribute_value']);
        }

        return $rows;
    }

    public function getConfiguration($config_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "pc_configuration` WHERE config_id = '" . (int)$config_id . "'");
        return $query->row;
    }

    public function checkRules($component_ids) {
        $errors = array();
        if (count($component_ids) < 2) return $errors;

        $ids = implode(',', array_map('intval', $component_ids));
        $query = $this->db->query("SELECT r.*, c1.name as comp1_name, c2.name as comp2_name
            FROM `" . DB_PREFIX . "pc_compatibility_rule` r
            LEFT JOIN `" . DB_PREFIX . "pc_component` c1 ON r.component_id_1 = c1.component_id
            LEFT JOIN `" . DB_PREFIX . "pc_component` c2 ON r.component_id_2 = c2.component_id
            WHERE r.compatible = 0 AND r.component_id_1 IN (" . $ids . ") AND r.component_id_2 IN (" . $ids . ")");

        foreach ($query->rows as $rule) {
            $errors[] = array('name1' => $rule['comp1_name'], 'name2' => $rule['comp2_name']);
        }
        return $errors;
    }

    public function saveConfiguration($data) {
        $components_json = is_array($data['components']) ? json_encode($data['components']) : $data['components'];
        $total = 0;
        $components = is_array($data['components']) ? $data['components'] : json_decode($data['components'], true);
        if (is_array($components)) {
            foreach ($components as $comp_id) {
                $comp = $this->getComponent((int)$comp_id);
                if ($comp) $total += (float)$comp['price'];
            }
        }

        $this->db->query("INSERT INTO `" . DB_PREFIX . "pc_configuration` SET
            customer_id = '" . (int)$data['customer_id'] . "',
            session_id = '" . $this->db->escape($data['session_id']) . "',
            components = '" . $this->db->escape($components_json) . "',
            total_price = '" . (float)$total . "',
            date_added = NOW(), date_modified = NOW()");
        return $this->db->getLastId();
    }

    public function saveOrder($data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "pc_order` SET
            config_id = '" . (int)$data['config_id'] . "',
            customer_name = '" . $this->db->escape($data['customer_name']) . "',
            customer_phone = '" . $this->db->escape($data['customer_phone']) . "',
            customer_email = '" . $this->db->escape($data['customer_email']) . "',
            comment = '" . $this->db->escape($data['comment']) . "',
            status = 'new', date_added = NOW()");
        return $this->db->getLastId();
    }
}
