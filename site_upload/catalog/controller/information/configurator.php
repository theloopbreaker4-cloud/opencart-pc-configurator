<?php
/**
 * PC Configurator — Frontend Controller
 *
 * Handles frontend routes: page render, component list (AJAX), compatibility
 * check, PDF generation, order submit, save/load config, cart replace.
 *
 * @package  PC Configurator for OpenCart
 * @version  1.4.0
 * @author   gcomp.ge
 * @license  MIT
 * @link     https://github.com/YOUR_USERNAME/oc-pc-configurator
 */
class ControllerInformationConfigurator extends Controller {

    public function index() {
        $this->load->language('information/configurator');

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('catalog/view/javascript/configurator/configurator.css');
        $this->document->addScript('catalog/view/javascript/configurator/configurator.js');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('information/configurator')
        );

        $data['heading_title'] = $this->language->get('heading_title');

        // All text keys
        $text_keys = array(
            'text_system_unit', 'text_periphery', 'text_total_price',
            'text_clear', 'text_download_pdf', 'text_order', 'text_request_discount',
            'text_add', 'text_change', 'text_remove', 'text_select_component',
            'text_compatibility_ok', 'text_compatibility_error', 'text_no_components',
            'text_order_title', 'text_name', 'text_phone', 'text_email',
            'text_comment', 'text_submit_order', 'text_close', 'text_order_success',
            'text_save', 'text_load', 'text_load_title', 'text_load_label',
            'text_search', 'text_loading', 'text_warning',
            'text_select_components_first', 'text_sending',
            'text_tip_1', 'text_tip_2', 'text_tip_3',
            'text_js_confirm_clear', 'text_js_cancel', 'text_js_confirm',
            'text_js_total', 'text_js_fill_required', 'text_js_error',
            'text_js_config_saved', 'text_js_config_code', 'text_js_config_use_later',
            'text_js_config_loaded', 'text_js_discount_comment',
            'text_js_adding_to_cart', 'text_js_cart_success', 'text_js_cart_partial',
            'text_add_to_cart', 'text_js_missing_components',
            'error_socket_mismatch', 'error_ram_mismatch', 'error_form_factor_mismatch', 'error_psu_warning'
        );

        foreach ($text_keys as $key) {
            $data[$key] = $this->language->get($key);
        }

        // Get categories from DB
        $this->load->model('catalog/configurator');

        $categories = $this->model_catalog_configurator->getCategories();

        // Determine language field from current session language
        $lang_code = isset($this->session->data['language']) ? $this->session->data['language'] : $this->config->get('config_language');
        if (strpos($lang_code, 'ka') !== false || strpos($lang_code, 'ge') !== false) {
            $name_field = 'name_ka';
        } elseif (strpos($lang_code, 'ru') !== false) {
            $name_field = 'name_ru';
        } else {
            $name_field = 'name';
        }

        $data['system_categories'] = array();
        $data['periphery_categories'] = array();

        foreach ($categories as $category) {
            $cat_name = !empty($category[$name_field]) ? $category[$name_field] : $category['name'];
            $cat = array(
                'category_id' => $category['category_id'],
                'name'        => $cat_name,
                'icon'        => $category['icon'],
                'required'    => $category['required'],
            );

            if ($category['section'] == 'system') {
                $data['system_categories'][] = $cat;
            } else {
                $data['periphery_categories'][] = $cat;
            }
        }

        // Settings
        $this->load->model('setting/setting');
        $cfg_settings = $this->model_setting_setting->getSetting('cfg');
        $data['cfg_show_save']   = isset($cfg_settings['cfg_show_save'])   ? (int)$cfg_settings['cfg_show_save']   : 0;
        $data['cfg_show_load']   = isset($cfg_settings['cfg_show_load'])   ? (int)$cfg_settings['cfg_show_load']   : 0;
        $data['cfg_qty_ram']     = isset($cfg_settings['cfg_qty_ram'])     ? (int)$cfg_settings['cfg_qty_ram']     : 4;
        $data['cfg_qty_ssd']     = isset($cfg_settings['cfg_qty_ssd'])     ? (int)$cfg_settings['cfg_qty_ssd']     : 4;
        $data['cfg_qty_hdd']     = isset($cfg_settings['cfg_qty_hdd'])     ? (int)$cfg_settings['cfg_qty_hdd']     : 4;
        $data['cfg_qty_casefan'] = isset($cfg_settings['cfg_qty_casefan']) ? (int)$cfg_settings['cfg_qty_casefan'] : 8;
        $data['cfg_qty_monitor'] = isset($cfg_settings['cfg_qty_monitor']) ? (int)$cfg_settings['cfg_qty_monitor'] : 3;

        // API URLs
        $data['api_get_components'] = $this->url->link('information/configurator/getComponents');
        $data['api_check_compatibility'] = $this->url->link('information/configurator/checkCompatibility');
        $data['api_download_pdf'] = $this->url->link('information/configurator/downloadPdf');
        $data['api_submit_order'] = $this->url->link('information/configurator/submitOrder');
        $data['api_save_config'] = $this->url->link('information/configurator/saveConfig');
        $data['api_load_config'] = $this->url->link('information/configurator/loadConfig');
        $data['api_cart_replace'] = $this->url->link('information/configurator/cartReplace');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('information/configurator', $data));
    }

    public function getComponents() {
        $this->load->model('catalog/configurator');
        $this->load->model('tool/image');

        $json = array('components' => array());

        if (isset($this->request->get['category_id'])) {
            $category_id = (int)$this->request->get['category_id'];
            $components = $this->model_catalog_configurator->getComponentsByCategory($category_id);

            foreach ($components as $component) {
                $image = '';
                if ($component['image'] && file_exists(DIR_IMAGE . $component['image'])) {
                    $image = $this->model_tool_image->resize($component['image'], 80, 80);
                }

                $specs = array();
                if ($component['specs']) {
                    $decoded = json_decode($component['specs'], true);
                    if (is_array($decoded)) {
                        $specs = $decoded;
                    }
                }

                $price = (float)$component['price'];
                $original_price = isset($component['original_price']) ? (float)$component['original_price'] : 0;
                $has_special = ($original_price > 0 && $original_price > $price);

                $json['components'][] = array(
                    'component_id'   => $component['component_id'],
                    'name'           => $component['name'],
                    'price'          => $price,
                    'price_formatted' => $this->currency->format($price, $this->session->data['currency']),
                    'original_price' => $has_special ? $original_price : 0,
                    'original_price_formatted' => $has_special ? $this->currency->format($original_price, $this->session->data['currency']) : '',
                    'image'          => $image,
                    'specs'          => $specs,
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function checkCompatibility() {
        $this->load->language('information/configurator');
        $this->load->model('catalog/configurator');

        // Force load language keys if not available (AJAX context)
        if ($this->language->get('error_socket_mismatch') === 'error_socket_mismatch') {
            $lang_code = isset($this->session->data['language']) ? $this->session->data['language'] : $this->config->get('config_language');
            $lang_file = DIR_LANGUAGE . $lang_code . '/information/configurator.php';
            if (!is_file($lang_file)) {
                $lang_file = DIR_LANGUAGE . 'ge-ka/information/configurator.php';
            }
            if (is_file($lang_file)) {
                $_ = array();
                require($lang_file);
                foreach ($_ as $k => $v) {
                    $this->language->set($k, $v);
                }
            }
        }

        $json = array('compatible' => true, 'errors' => array(), 'warnings' => array());

        $input = json_decode(file_get_contents('php://input'), true);
        $selected = isset($input['components']) ? $input['components'] : array();

        if (count($selected) < 2) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        // Get component names and DB attributes
        $component_data = array();
        foreach ($selected as $cat_id => $comp_id) {
            $component = $this->model_catalog_configurator->getComponent((int)$comp_id);
            $db_attrs = $this->model_catalog_configurator->getComponentAttributes((int)$comp_id);
            $name = isset($component['name']) ? $component['name'] : '';

            // Parse attributes from product name if no DB attributes
            $parsed = $this->parseAttributesFromName($name, (int)$cat_id);

            // Merge: DB attributes override parsed ones
            $attrs = $parsed;
            foreach ($db_attrs as $a) {
                $attrs[$a['attribute_name']] = $a['attribute_value'];
            }

            $component_data[$cat_id] = array(
                'component_id' => (int)$comp_id,
                'name' => $name,
                'attrs' => $attrs
            );
        }

        // CPU + Motherboard socket
        $cpu_socket = isset($component_data[1]['attrs']['socket']) ? $component_data[1]['attrs']['socket'] : null;
        $mb_socket = isset($component_data[2]['attrs']['socket']) ? $component_data[2]['attrs']['socket'] : null;
        if ($cpu_socket && $mb_socket && strtolower($cpu_socket) !== strtolower($mb_socket)) {
            $json['compatible'] = false;
            $json['error_codes'][] = array('type' => 'socket', 'p1' => $cpu_socket, 'p2' => $mb_socket);
        }

        // RAM type compatibility with motherboard
        $ram_type = isset($component_data[3]['attrs']['ram_type']) ? $component_data[3]['attrs']['ram_type'] : null;
        $mb_ram_type = isset($component_data[2]['attrs']['ram_type']) ? $component_data[2]['attrs']['ram_type'] : null;
        if ($ram_type && $mb_ram_type && strtolower($ram_type) !== strtolower($mb_ram_type)) {
            $json['compatible'] = false;
            $json['error_codes'][] = array('type' => 'ram', 'p1' => $ram_type, 'p2' => $mb_ram_type);
        }

        // Form factor: motherboard vs case
        $mb_form = isset($component_data[2]['attrs']['form_factor']) ? $component_data[2]['attrs']['form_factor'] : null;
        $case_form = isset($component_data[7]['attrs']['form_factor']) ? $component_data[7]['attrs']['form_factor'] : null;
        if ($mb_form && $case_form) {
            $compatible_forms = $this->getCompatibleFormFactors($case_form);
            if (!in_array(strtolower($mb_form), $compatible_forms)) {
                $json['compatible'] = false;
                $json['error_codes'][] = array('type' => 'form', 'p1' => $mb_form, 'p2' => $case_form);
            }
        }

        // PSU wattage warning
        $psu_wattage = isset($component_data[5]['attrs']['wattage']) ? (int)$component_data[5]['attrs']['wattage'] : 0;
        if ($psu_wattage > 0) {
            $estimated_power = 0;
            if (isset($component_data[1])) $estimated_power += 125;
            if (isset($component_data[4])) $estimated_power += 200;
            $estimated_power += 100;
            if ($psu_wattage < $estimated_power) {
                $json['warning_codes'][] = array('type' => 'psu', 'p1' => $psu_wattage, 'p2' => ($estimated_power + 100));
            }
        }

        // DB rules
        $comp_ids = array();
        foreach ($component_data as $d) {
            $comp_ids[] = $d['component_id'];
        }
        $db_rules = $this->model_catalog_configurator->checkRules($comp_ids);
        if (!empty($db_rules)) {
            $json['compatible'] = false;
            foreach ($db_rules as $rule) {
                $json['error_codes'][] = array('type' => 'rule', 'p1' => $rule['name1'], 'p2' => $rule['name2']);
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function parseAttributesFromName($name, $category_id) {
        $attrs = array();

        // Parse socket
        $sockets = array('LGA1851', 'LGA1700', 'LGA1200', 'LGA1151', 'LGA1150', 'LGA2066', 'LGA1366', 'AM5', 'AM4', 'AM3', 'sTRX4', 'TR4', 'sTR5');
        foreach ($sockets as $socket) {
            if (stripos($name, $socket) !== false) {
                $attrs['socket'] = $socket;
                break;
            }
        }
        // Detect socket from CPU model if not found directly
        if (!isset($attrs['socket']) && ($category_id == 1 || $category_id == 2)) {
            if (preg_match('/\b(s|S)1200\b/', $name)) $attrs['socket'] = 'LGA1200';
            if (preg_match('/\b(s|S)1700\b/', $name)) $attrs['socket'] = 'LGA1700';
        }

        // Parse RAM type
        if (preg_match('/\b(DDR5|DDR4|DDR3)\b/i', $name, $m)) {
            $attrs['ram_type'] = strtoupper($m[1]);
        }

        // Parse wattage for PSU (category 5)
        if ($category_id == 5 && preg_match('/(\d{3,4})\s*W\b/i', $name, $m)) {
            $attrs['wattage'] = $m[1];
        }

        // Parse form factor for motherboards (category 2)
        if ($category_id == 2) {
            if (preg_match('/\bmATX\b|\bMicro[\s-]?ATX\b/i', $name)) {
                $attrs['form_factor'] = 'Micro-ATX';
            } elseif (preg_match('/\bMini[\s-]?ITX\b/i', $name)) {
                $attrs['form_factor'] = 'Mini-ITX';
            } elseif (preg_match('/\bE[\s-]?ATX\b/i', $name)) {
                $attrs['form_factor'] = 'E-ATX';
            } elseif (preg_match('/\bATX\b/', $name)) {
                $attrs['form_factor'] = 'ATX';
            }
            // Detect by model number: H/B with M suffix = Micro-ATX
            if (!isset($attrs['form_factor'])) {
                if (preg_match('/[HB]\d{3,4}M/i', $name)) {
                    $attrs['form_factor'] = 'Micro-ATX';
                }
            }
        }

        // Parse form factor for cases (category 7)
        if ($category_id == 7) {
            if (preg_match('/\bMidT\b|\bMid[\s-]?Tower\b/i', $name)) {
                $attrs['form_factor'] = 'Mid Tower';
            } elseif (preg_match('/\bFull[\s-]?Tower\b/i', $name)) {
                $attrs['form_factor'] = 'Full Tower';
            } elseif (preg_match('/\bMini[\s-]?Tower\b|\bSFF\b/i', $name)) {
                $attrs['form_factor'] = 'Mini Tower';
            }
        }

        return $attrs;
    }

    private function getCompatibleFormFactors($case_form) {
        $case_form = strtolower($case_form);
        $map = array(
            'full tower'  => array('eatx', 'atx', 'micro-atx', 'mini-itx'),
            'mid tower'   => array('atx', 'micro-atx', 'mini-itx'),
            'mini tower'  => array('micro-atx', 'mini-itx'),
            'sff'         => array('mini-itx'),
        );
        return isset($map[$case_form]) ? $map[$case_form] : array($case_form);
    }

    public function downloadPdf() {
        $this->load->language('information/configurator');
        $this->load->model('catalog/configurator');

        // Accept GET base64 param or POST
        if (isset($this->request->get['cfg'])) {
            $selected = json_decode(base64_decode($this->request->get['cfg']), true);
        } elseif (isset($this->request->get['data'])) {
            $selected = json_decode($this->request->get['data'], true);
        } elseif (isset($this->request->post['components'])) {
            $selected = json_decode($this->request->post['components'], true);
        } else {
            $input = json_decode(file_get_contents('php://input'), true);
            $selected = isset($input['components']) ? $input['components'] : array();
        }

        if (empty($selected) || !is_array($selected)) {
            $this->response->redirect($this->url->link('information/configurator'));
            return;
        }

        $lang_code = isset($this->session->data['language']) ? $this->session->data['language'] : $this->config->get('config_language');
        if (strpos($lang_code, 'ka') !== false || strpos($lang_code, 'ge') !== false) {
            $name_field = 'name_ka';
        } elseif (strpos($lang_code, 'ru') !== false) {
            $name_field = 'name_ru';
        } else {
            $name_field = 'name';
        }

        $components = array();
        $total = 0;

        foreach ($selected as $cat_id => $comp_data) {
            // Support both old format {cat_id: comp_id} and new {cat_id: {id, qty}}
            if (is_array($comp_data)) {
                $comp_id = isset($comp_data['id']) ? (int)$comp_data['id'] : 0;
                $qty     = isset($comp_data['qty']) ? max(1, (int)$comp_data['qty']) : 1;
            } else {
                $comp_id = (int)$comp_data;
                $qty     = 1;
            }

            $component = $this->model_catalog_configurator->getComponent($comp_id);
            $category  = $this->model_catalog_configurator->getCategory((int)$cat_id);

            if ($component && $category) {
                $cat_display = !empty($category[$name_field]) ? $category[$name_field] : $category['name'];
                $components[] = array(
                    'category' => $cat_display,
                    'name'     => $component['name'],
                    'price'    => (float)$component['price'],
                    'qty'      => $qty
                );
                $total += (float)$component['price'] * $qty;
            }
        }

        $html = $this->generatePdfHtml($components, $total);
        $this->response->addHeader('Content-Type: text/html; charset=utf-8');
        $this->response->setOutput($html . '<script>window.print();</script>');
    }

    private function generatePdfHtml($components, $total) {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">
<style>
body{font-family:DejaVu Sans,sans-serif;margin:40px;color:#333}
.header{text-align:center;margin-bottom:30px;border-bottom:2px solid #e74c3c;padding-bottom:20px}
.header h1{color:#e74c3c;margin:0;font-size:24px}
.header p{color:#666;margin:5px 0}
table{width:100%;border-collapse:collapse;margin-bottom:30px}
th{background:#e74c3c;color:#fff;padding:10px;text-align:left}
td{padding:10px;border-bottom:1px solid #eee}
tr:nth-child(even){background:#f9f9f9}
.total{text-align:right;font-size:20px;font-weight:bold;color:#e74c3c;margin-top:20px}
.requisites{margin-top:30px;padding:15px;border:1px solid #eee;font-size:12px;color:#555;line-height:1.8}
.requisites strong{display:block;margin-bottom:5px;color:#333}
.footer{margin-top:20px;text-align:center;font-size:11px;color:#999;border-top:1px solid #eee;padding-top:15px}
@media print{body{margin:20px}}
</style></head><body>
<div class="header"><h1>GCOMP.GE</h1><p>' . $this->language->get('text_pdf_title') . '</p></div>
<div style="font-size:12px;color:#888;margin-bottom:15px">' . $this->language->get('text_pdf_date') . ': ' . date('d/m/Y H:i') . '</div>
<table><thead><tr>
<th>#</th>
<th>' . $this->language->get('text_pdf_category') . '</th>
<th>' . $this->language->get('text_pdf_component') . '</th>
<th style="text-align:center">' . $this->language->get('text_pdf_qty') . '</th>
<th style="text-align:right;white-space:nowrap">' . $this->language->get('text_pdf_price') . '</th>
<th style="text-align:right;white-space:nowrap">' . $this->language->get('text_pdf_subtotal') . '</th>
</tr></thead><tbody>';

        $i = 1;
        foreach ($components as $comp) {
            $qty      = isset($comp['qty']) ? (int)$comp['qty'] : 1;
            $subtotal = $comp['price'] * $qty;
            $html .= '<tr>';
            $html .= '<td>' . $i++ . '</td>';
            $html .= '<td>' . htmlspecialchars($comp['category']) . '</td>';
            $html .= '<td>' . htmlspecialchars($comp['name']) . '</td>';
            $html .= '<td style="text-align:center">' . $qty . '</td>';
            $html .= '<td style="text-align:right;white-space:nowrap">' . number_format($comp['price'], 2) . ' ₾</td>';
            $html .= '<td style="text-align:right;white-space:nowrap">' . number_format($subtotal, 2) . ' ₾</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>
<div class="total">' . $this->language->get('text_pdf_total') . ': ' . number_format($total, 2) . ' ₾</div>

<div class="requisites">
<strong>' . $this->language->get('text_pdf_requisites_title') . '</strong>
' . $this->language->get('text_pdf_requisites_1') . '<br>
' . $this->language->get('text_pdf_requisites_2') . '<br><br>
' . $this->language->get('text_pdf_requisites_3') . '<br>
' . $this->language->get('text_pdf_requisites_4') . '
</div>

<div class="footer"><p>' . $this->language->get('text_pdf_footer') . '</p></div>
</body></html>';
        return $html;
    }

    public function submitOrder() {
        $this->load->model('catalog/configurator');
        $this->load->language('information/configurator');

        $json = array();

        // Rate limit - 1 order per 30 seconds
        if (isset($this->session->data['cfg_last_order']) && (time() - $this->session->data['cfg_last_order']) < 30) {
            $json['error'] = 'Please wait before submitting another order';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['customer_name'])) {
            $json['error'] = $this->language->get('error_name');
        } elseif (empty($input['customer_phone'])) {
            $json['error'] = $this->language->get('error_phone');
        } elseif (empty($input['components'])) {
            $json['error'] = $this->language->get('error_components');
        } elseif (utf8_strlen($input['customer_name']) < 2 || utf8_strlen($input['customer_name']) > 100) {
            $json['error'] = 'Name must be 2-100 characters';
        } elseif (utf8_strlen($input['customer_phone']) < 5 || utf8_strlen($input['customer_phone']) > 30) {
            $json['error'] = 'Invalid phone number';
        }

        if (!isset($json['error'])) {
            $config_id = $this->model_catalog_configurator->saveConfiguration(array(
                'customer_id' => $this->customer->isLogged() ? $this->customer->getId() : 0,
                'session_id'  => $this->session->getId(),
                'components'  => $input['components'],
            ));

            $order_id = $this->model_catalog_configurator->saveOrder(array(
                'config_id'      => $config_id,
                'customer_name'  => $input['customer_name'],
                'customer_phone' => $input['customer_phone'],
                'customer_email' => isset($input['customer_email']) ? $input['customer_email'] : '',
                'comment'        => isset($input['comment']) ? $input['comment'] : '',
            ));

            // Email notification
            $mail = new Mail($this->config->get('config_mail_engine'));
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

            $mail->setTo($this->config->get('config_email'));
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender($this->config->get('config_name'));
            $mail->setSubject(sprintf($this->language->get('text_email_subject'), $order_id));

            // Build HTML email in OpenCart style
            $components_data = is_array($input['components']) ? $input['components'] : json_decode($input['components'], true);
            $email_total = 0;
            $lang_code = isset($this->session->data['language']) ? $this->session->data['language'] : $this->config->get('config_language');
            if (strpos($lang_code, 'ka') !== false || strpos($lang_code, 'ge') !== false) {
                $nf = 'name_ka';
            } elseif (strpos($lang_code, 'ru') !== false) {
                $nf = 'name_ru';
            } else {
                $nf = 'name';
            }

            $store_name = $this->config->get('config_name');
            $store_url  = $this->config->get('config_url');
            $logo_url   = $store_url . 'image/' . $this->config->get('config_logo');

            $email_html  = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif">';
            $email_html .= '<div style="max-width:600px;margin:20px auto;background:#fff;border:1px solid #ddd">';

            // Header with logo
            $email_html .= '<div style="background:#fff;padding:20px;text-align:center;border-bottom:3px solid #e74c3c">';
            $email_html .= '<img src="' . $logo_url . '" alt="' . htmlspecialchars($store_name) . '" style="max-height:60px">';
            $email_html .= '</div>';

            // Intro text
            $email_html .= '<div style="padding:20px">';
            $email_html .= '<p style="color:#555">Thank you for your interest in ' . htmlspecialchars($store_name) . ' products. Your order has been received a processed once payment has been confirmed.</p>';

            // Order details block
            $email_html .= '<table style="width:100%;margin-bottom:20px"><tr>';
            $email_html .= '<td style="vertical-align:top;width:50%">';
            $email_html .= '<b>Order Details</b><br>';
            $email_html .= 'Order ID: #' . $order_id . '<br>';
            $email_html .= 'Date Added: ' . date('d/m/Y') . '<br>';
            $email_html .= 'Payment Method: კონფიგურატორი';
            $email_html .= '</td>';
            $email_html .= '<td style="vertical-align:top;width:50%;text-align:right">';
            $email_html .= 'E-Mail: ' . htmlspecialchars(!empty($input['customer_email']) ? $input['customer_email'] : '-') . '<br>';
            $email_html .= 'Telephone: ' . htmlspecialchars($input['customer_phone']);
            $email_html .= '</td></tr></table>';

            // Customer address block
            $email_html .= '<table style="width:100%;margin-bottom:20px"><tr>';
            $email_html .= '<td style="vertical-align:top;width:50%"><b>Customer</b><br>' . htmlspecialchars($input['customer_name']) . '</td>';
            if (!empty($input['comment'])) {
                $email_html .= '<td style="vertical-align:top;width:50%"><b>Comment</b><br>' . htmlspecialchars($input['comment']) . '</td>';
            }
            $email_html .= '</tr></table>';

            // Products table
            $email_html .= '<table style="width:100%;border-collapse:collapse;margin-bottom:20px">';
            $email_html .= '<thead><tr style="background:#e74c3c;color:#fff">';
            $email_html .= '<th style="padding:8px;text-align:left">Product</th>';
            $email_html .= '<th style="padding:8px;text-align:left">Model</th>';
            $email_html .= '<th style="padding:8px;text-align:center">Quantity</th>';
            $email_html .= '<th style="padding:8px;text-align:right">Unit Price</th>';
            $email_html .= '<th style="padding:8px;text-align:right">Total</th>';
            $email_html .= '</tr></thead><tbody>';

            $i = 1;
            if (is_array($components_data)) {
                foreach ($components_data as $cat_id => $comp_id) {
                    $comp = $this->model_catalog_configurator->getComponent((int)$comp_id);
                    $cat  = $this->model_catalog_configurator->getCategory((int)$cat_id);
                    if ($comp && $cat) {
                        $cat_name = !empty($cat[$nf]) ? $cat[$nf] : $cat['name'];
                        $price    = (float)$comp['price'];
                        $bg       = ($i % 2 == 0) ? '#f9f9f9' : '#fff';
                        $email_html .= '<tr style="background:' . $bg . '">';
                        $email_html .= '<td style="padding:8px;border-bottom:1px solid #eee">' . htmlspecialchars($comp['name']) . '</td>';
                        $email_html .= '<td style="padding:8px;border-bottom:1px solid #eee">' . htmlspecialchars($cat_name) . '</td>';
                        $email_html .= '<td style="padding:8px;border-bottom:1px solid #eee;text-align:center">1</td>';
                        $email_html .= '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right">' . number_format($price, 2) . ' ₾</td>';
                        $email_html .= '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right">' . number_format($price, 2) . ' ₾</td>';
                        $email_html .= '</tr>';
                        $email_total += $price;
                        $i++;
                    }
                }
            }

            $email_html .= '</tbody></table>';

            // Totals
            $email_html .= '<table style="width:100%;margin-bottom:20px"><tr>';
            $email_html .= '<td style="text-align:right;padding:5px"><b>Sub-Total:</b></td>';
            $email_html .= '<td style="text-align:right;padding:5px;width:120px">' . number_format($email_total, 2) . ' ₾</td>';
            $email_html .= '</tr><tr>';
            $email_html .= '<td style="text-align:right;padding:5px"><b>ჯამი (Total):</b></td>';
            $email_html .= '<td style="text-align:right;padding:5px;font-weight:bold;color:#e74c3c">' . number_format($email_total, 2) . ' ₾</td>';
            $email_html .= '</tr></table>';

            $email_html .= '<p style="color:#999;font-size:12px">Please reply to this e-mail if you have any questions.</p>';
            $email_html .= '</div></div></body></html>';

            $mail->setHtml($email_html);
            $mail->setText($this->language->get('text_email_name') . ": " . $input['customer_name'] . "\n" . $this->language->get('text_email_phone') . ": " . $input['customer_phone']);

            try { $mail->send(); } catch (\Exception $e) {
                $this->log->write('Configurator email error: ' . $e->getMessage());
            }

            $this->session->data['cfg_last_order'] = time();

            $json['success'] = $this->language->get('text_order_success');
            $json['order_id'] = $order_id;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function cartReplace() {
        $json = array();
        $input = json_decode(file_get_contents('php://input'), true);

        // product_ids to remove from cart
        $remove_ids = isset($input['remove_ids']) ? array_map('intval', $input['remove_ids']) : array();
        // new components to add: {product_id: qty}
        $add_items = isset($input['add_items']) ? $input['add_items'] : array();

        // Remove old items from cart by product_id
        if (!empty($remove_ids)) {
            $products = $this->cart->getProducts();
            foreach ($products as $product) {
                if (in_array((int)$product['product_id'], $remove_ids)) {
                    $this->cart->remove($product['cart_id']);
                }
            }
        }

        // Add new items
        $added = array();
        foreach ($add_items as $product_id => $qty) {
            $product_id = (int)$product_id;
            $qty = max(1, (int)$qty);
            if ($product_id > 0) {
                $this->cart->add($product_id, $qty);
                $added[] = $product_id;
            }
        }

        $json['success'] = true;
        $json['added'] = $added;
        $json['total'] = $this->cart->countProducts();

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveConfig() {
        $this->load->model('catalog/configurator');

        $json = array();
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['components'])) {
            $json['error'] = 'No components selected';
        } else {
            $config_id = $this->model_catalog_configurator->saveConfiguration(array(
                'customer_id' => $this->customer->isLogged() ? $this->customer->getId() : 0,
                'session_id'  => $this->session->getId(),
                'components'  => $input['components'],
            ));

            $code = 'CFG-' . str_pad($config_id, 5, '0', STR_PAD_LEFT);
            $json['success'] = true;
            $json['code'] = $code;
            $json['config_id'] = $config_id;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function loadConfig() {
        $this->load->model('catalog/configurator');

        $json = array();
        $input = json_decode(file_get_contents('php://input'), true);
        $code = isset($input['code']) ? trim($input['code']) : '';

        // Parse config_id from code CFG-00001
        $config_id = 0;
        if (preg_match('/CFG-0*(\d+)/i', $code, $m)) {
            $config_id = (int)$m[1];
        } elseif (is_numeric($code)) {
            $config_id = (int)$code;
        }

        if (!$config_id) {
            $json['error'] = $this->language->get('error_invalid_code');
        } else {
            $config = $this->model_catalog_configurator->getConfiguration($config_id);

            if (!$config) {
                $json['error'] = $this->language->get('error_config_not_found');
            } else {
                $components = json_decode($config['components'], true);
                if (!is_array($components) || empty($components)) {
                    $json['error'] = $this->language->get('error_config_empty');
                } else {
                    // Build component details for JS
                    $json['success'] = true;
                    $json['components'] = array();

                    foreach ($components as $cat_id => $comp_id) {
                        $component = $this->model_catalog_configurator->getComponent((int)$comp_id);
                        if ($component) {
                            $json['components'][$cat_id] = array(
                                'id' => (int)$comp_id,
                                'name' => $component['name'],
                                'price' => (float)$component['price'],
                                'price_formatted' => $this->currency->format($component['price'], $this->session->data['currency'])
                            );
                        }
                    }
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
