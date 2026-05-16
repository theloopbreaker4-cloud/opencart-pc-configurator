<?php
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
        99 => array(),          // Other - см. ниже: специальная логика "товары не из других категорий"
    );

    // Name filters for categories sharing same OC category
    private $name_filters = array(
        6  => array('include' => array('პროცესორის ქულერ', 'CPU Cooler', 'cpu cooler', 'процессорный кулер', 'кулер для процессора', 'Gamma', 'GAMMAXX', 'AK400', 'AK620', 'NH-D', 'NH-U', 'Hyper 212'), 'exclude' => array('წყლ', 'Liquid', 'liquid', 'водян', 'жидкост', 'AIO', 'ქეის', 'Case Fan', 'корпус', 'XFAN', 'ვიდეო')),
        // Cat 8 = "SSD M.2": только M.2 / NVMe
        8  => array('include' => array('M.2', 'm.2', 'NVMe', 'nvme', 'NVME'), 'exclude' => array('ყუთ', 'Enclosure', 'გარე', 'внешн')),
        // Cat 9 = "HDD/SSD": все HDD + SATA SSD (т.е. любые накопители, но НЕ M.2/NVMe)
        9  => array('include' => array('HDD', 'hdd', 'SSD', 'ssd', 'Barracuda', 'მყარი დისკი', 'жёсткий диск', 'жесткий диск'), 'exclude' => array('M.2', 'm.2', 'NVMe', 'nvme', 'NVME', 'ყუთ', 'Enclosure', 'გარე', 'внешн')),
        10 => array('include' => array('ქეის ქულერ', 'Case Fan', 'case fan', 'корпусной', 'корпусный', 'кулер корпус', 'XFAN', 'FC120'), 'exclude' => array()),
        18 => array('include' => array('წყლ', 'Liquid', 'liquid', 'водян', 'жидкост', 'AIO', 'CORELIQUID'), 'exclude' => array()),
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
        // Спец-раздел "სხვა / Другое / Other": товары, чьи OC-категории не входят
        // ни в одну из уже замапленных категорий конфигуратора.
        if ($category_id == 99) {
            return $this->getOtherProducts();
        }

        if (!isset($this->category_map[$category_id])) {
            return array();
        }

        $oc_categories = $this->category_map[$category_id];

        if (empty($oc_categories)) {
            return array();
        }

        $language_id = (int)$this->config->get('config_language_id');
        $cat_ids = implode(',', array_map('intval', $oc_categories));

        // Display name in current language; fallback to any non-empty name if missing
        $query = $this->db->query("
            SELECT DISTINCT p.product_id as component_id,
                   '" . (int)$category_id . "' as category_id,
                   COALESCE(NULLIF(pd_cur.name,''), pd_fb.name) as name,
                   COALESCE(ps.price, p.price) as price,
                   IF(ps.price IS NOT NULL AND ps.price < p.price, p.price, NULL) as original_price,
                   p.image,
                   NULL as specs,
                   p.status,
                   p.sort_order
            FROM `" . DB_PREFIX . "product` p
            LEFT JOIN `" . DB_PREFIX . "product_description` pd_cur
                ON p.product_id = pd_cur.product_id AND pd_cur.language_id = '" . $language_id . "'
            JOIN `" . DB_PREFIX . "product_description` pd_fb
                ON p.product_id = pd_fb.product_id
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
            GROUP BY p.product_id
            ORDER BY p.sort_order ASC, name ASC
        ");

        // Apply name filters if defined. Filter against ALL language names of the product
        // (because Georgian original may match the filter while RU translation doesn't).
        if (isset($this->name_filters[$category_id])) {
            $filter = $this->name_filters[$category_id];

            // Pre-fetch all names for products in this batch
            $ids = array();
            foreach ($query->rows as $row) { $ids[] = (int)$row['component_id']; }
            $names_by_pid = array();
            if (!empty($ids)) {
                $name_q = $this->db->query("SELECT product_id, name FROM `" . DB_PREFIX . "product_description` WHERE product_id IN (" . implode(',', $ids) . ")");
                foreach ($name_q->rows as $nr) {
                    $names_by_pid[(int)$nr['product_id']][] = $nr['name'];
                }
            }

            $filtered = array();
            foreach ($query->rows as $row) {
                $pid = (int)$row['component_id'];
                $names = isset($names_by_pid[$pid]) ? $names_by_pid[$pid] : array($row['name']);

                $match = false;
                foreach ($filter['include'] as $pattern) {
                    foreach ($names as $n) {
                        if (stripos($n, $pattern) !== false) { $match = true; break 2; }
                    }
                }
                if (!$match) continue;

                $excluded = false;
                foreach ($filter['exclude'] as $pattern) {
                    foreach ($names as $n) {
                        if (stripos($n, $pattern) !== false) { $excluded = true; break 2; }
                    }
                }
                if (!$excluded) $filtered[] = $row;
            }
            return $filtered;
        }

        return $query->rows;
    }

    // "Other" pseudo-category: products whose OC categories are NOT in any other
    // configurator-mapped category. Allows the customer to pick anything from the
    // shop that doesn't fit the predefined component types.
    private function getOtherProducts() {
        $mapped = array();
        foreach ($this->category_map as $cfg_id => $ocs) {
            if ($cfg_id == 99) continue;
            foreach ($ocs as $oc) { $mapped[(int)$oc] = true; }
        }
        if (empty($mapped)) return array();

        $excluded_ids = implode(',', array_keys($mapped));
        $language_id = (int)$this->config->get('config_language_id');

        // Take products that are in SOME OC category but none of the mapped ones.
        // Limit to 200 to keep payload reasonable.
        $query = $this->db->query("
            SELECT DISTINCT p.product_id as component_id,
                   '99' as category_id,
                   COALESCE(NULLIF(pd_cur.name,''), pd_fb.name) as name,
                   COALESCE(ps.price, p.price) as price,
                   IF(ps.price IS NOT NULL AND ps.price < p.price, p.price, NULL) as original_price,
                   p.image,
                   NULL as specs,
                   p.status,
                   p.sort_order
            FROM `" . DB_PREFIX . "product` p
            LEFT JOIN `" . DB_PREFIX . "product_description` pd_cur
                ON p.product_id = pd_cur.product_id AND pd_cur.language_id = '" . $language_id . "'
            JOIN `" . DB_PREFIX . "product_description` pd_fb
                ON p.product_id = pd_fb.product_id
            JOIN `" . DB_PREFIX . "product_to_category` p2c ON p.product_id = p2c.product_id
            LEFT JOIN (
                SELECT product_id, MIN(price) as price
                FROM `" . DB_PREFIX . "product_special`
                WHERE (date_start = '0000-00-00' OR date_start <= NOW())
                AND (date_end = '0000-00-00' OR date_end >= NOW())
                GROUP BY product_id
            ) ps ON p.product_id = ps.product_id
            WHERE p.status = 1
              AND p.quantity > 0
              AND p.product_id NOT IN (
                  SELECT DISTINCT product_id FROM `" . DB_PREFIX . "product_to_category`
                  WHERE category_id IN (" . $excluded_ids . ")
              )
            GROUP BY p.product_id
            ORDER BY p.sort_order ASC, name ASC
            LIMIT 200
        ");

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
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "pc_component_attribute` WHERE component_id = '" . (int)$component_id . "'");
        return $query->rows;
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
            $errors[] = $rule['comp1_name'] . ' არ არის თავსებადი ' . $rule['comp2_name'] . '-თან';
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
