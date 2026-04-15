<?php
/**
 * PC Configurator — Admin Controller
 *
 * Handles admin panel: orders list, manual components, compatibility rules,
 * category editing, catalog preview, and module settings.
 *
 * @package  PC Configurator for OpenCart
 * @version  1.4.0
 * @author   gcomp.ge
 * @license  MIT
 * @link     https://github.com/YOUR_USERNAME/oc-pc-configurator
 */
class ControllerExtensionModuleConfigurator extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('extension/module/configurator');
        $this->load->model('extension/module/configurator');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->getList();
    }

    public function deleteOrderAction() {
        if (!$this->user->hasPermission('modify', 'extension/module/configurator')) {
            $this->session->data['error_warning'] = 'Permission denied';
            $this->response->redirect($this->url->link('extension/module/configurator', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $this->load->model('extension/module/configurator');

        if (isset($this->request->get['order_id'])) {
            $this->model_extension_module_configurator->deleteOrder((int)$this->request->get['order_id']);
            $this->session->data['success'] = 'Order deleted';
        }

        $this->response->redirect($this->url->link('extension/module/configurator', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function deleteComponentAction() {
        if (!$this->user->hasPermission('modify', 'extension/module/configurator')) {
            $this->session->data['error_warning'] = 'Permission denied';
            $this->response->redirect($this->url->link('extension/module/configurator', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $this->load->model('extension/module/configurator');

        if (isset($this->request->get['id'])) {
            $this->model_extension_module_configurator->deleteComponent((int)$this->request->get['id']);
            $this->session->data['success'] = 'Component deleted';
        }

        $this->response->redirect($this->url->link('extension/module/configurator', 'user_token=' . $this->session->data['user_token'] . '&tab=components', true));
    }

    public function deleteRuleAction() {
        if (!$this->user->hasPermission('modify', 'extension/module/configurator')) {
            $this->session->data['error_warning'] = 'Permission denied';
            $this->response->redirect($this->url->link('extension/module/configurator', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $this->load->model('extension/module/configurator');

        if (isset($this->request->get['id'])) {
            $this->model_extension_module_configurator->deleteRule((int)$this->request->get['id']);
            $this->session->data['success'] = 'Rule deleted';
        }

        $this->response->redirect($this->url->link('extension/module/configurator', 'user_token=' . $this->session->data['user_token'] . '&tab=rules', true));
    }

    public function updateCategory() {
        $this->load->model('extension/module/configurator');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['category_id'])) {
            $this->model_extension_module_configurator->updateCategory(
                (int)$this->request->post['category_id'],
                $this->request->post
            );
            $this->session->data['success'] = 'Category updated';
        }

        $this->response->redirect($this->url->link('extension/module/configurator', 'user_token=' . $this->session->data['user_token'] . '&tab=categories', true));
    }

    protected function getList() {
        $data['heading_title'] = $this->language->get('heading_title');

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/configurator', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['tab'] = isset($this->request->get['tab']) ? $this->request->get['tab'] : 'orders';

        // Settings
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('cfg');
        $data['cfg_show_save']     = isset($settings['cfg_show_save'])    ? (int)$settings['cfg_show_save']    : 0;
        $data['cfg_show_load']     = isset($settings['cfg_show_load'])    ? (int)$settings['cfg_show_load']    : 0;
        $data['cfg_qty_ram']       = isset($settings['cfg_qty_ram'])      ? (int)$settings['cfg_qty_ram']      : 4;
        $data['cfg_qty_ssd']       = isset($settings['cfg_qty_ssd'])      ? (int)$settings['cfg_qty_ssd']      : 4;
        $data['cfg_qty_hdd']       = isset($settings['cfg_qty_hdd'])      ? (int)$settings['cfg_qty_hdd']      : 4;
        $data['cfg_qty_casefan']   = isset($settings['cfg_qty_casefan'])  ? (int)$settings['cfg_qty_casefan']  : 8;
        $data['cfg_qty_monitor']   = isset($settings['cfg_qty_monitor'])  ? (int)$settings['cfg_qty_monitor']  : 3;
        $data['url_save_settings'] = $this->url->link('extension/module/configurator/saveSettings', 'user_token=' . $this->session->data['user_token'], true);

        // Success/error flash
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

        // Orders
        $data['orders'] = $this->model_extension_module_configurator->getOrders(0, 100);
        $data['total_orders'] = $this->model_extension_module_configurator->getTotalOrders();

        foreach ($data['orders'] as &$order) {
            $order['component_list'] = array();
            if ($order['components']) {
                $components = json_decode($order['components'], true);
                if (is_array($components)) {
                    foreach ($components as $cat_id => $comp_id) {
                        $order['component_list'][] = array(
                            'category' => $this->model_extension_module_configurator->getCategoryName((int)$cat_id),
                            'name' => $this->model_extension_module_configurator->getComponentName((int)$comp_id)
                        );
                    }
                }
            }
            $order['status_url'] = $this->url->link('extension/module/configurator/changeStatus', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order['order_id'], true);
            $order['delete_url'] = $this->url->link('extension/module/configurator/deleteOrderAction', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order['order_id'], true);
            // PDF URL via catalog frontend
            $cfg_data = base64_encode($order['components'] ? $order['components'] : '{}');
            $order['pdf_url'] = HTTP_CATALOG . 'index.php?route=information/configurator/downloadPdf&cfg=' . $cfg_data;
        }
        unset($order);

        // Categories
        $data['categories'] = $this->model_extension_module_configurator->getCategories();

        // Catalog
        $data['catalog_counts'] = $this->model_extension_module_configurator->getCatalogProductCounts();
        $catalog_category = isset($this->request->get['catalog_category']) ? (int)$this->request->get['catalog_category'] : 0;
        $data['catalog_category'] = $catalog_category;
        $catalog_page = isset($this->request->get['cpage']) ? max(1, (int)$this->request->get['cpage']) : 1;
        $catalog_limit = 40;
        $data['catalog_products'] = $catalog_category ? $this->model_extension_module_configurator->getCatalogProducts($catalog_category, $catalog_page, $catalog_limit) : array();
        $data['catalog_page'] = $catalog_page;
        $data['catalog_total'] = isset($data['catalog_counts'][$catalog_category]) ? $data['catalog_counts'][$catalog_category] : 0;
        $data['catalog_pages'] = $data['catalog_total'] > 0 ? ceil($data['catalog_total'] / $catalog_limit) : 0;

        $data['catalog_view_path'] = HTTP_CATALOG . 'catalog/view/theme/default/';
        if (defined('HTTPS_CATALOG')) {
            $data['catalog_view_path'] = HTTPS_CATALOG . 'catalog/view/';
        } else {
            $data['catalog_view_path'] = HTTP_CATALOG . 'catalog/view/';
        }

        // Manual components
        $data['components'] = $this->model_extension_module_configurator->getComponents(0);
        $token = 'user_token=' . $this->session->data['user_token'];
        foreach ($data['components'] as &$comp) {
            $comp['delete_url'] = $this->url->link('extension/module/configurator/deleteComponentAction', $token . '&id=' . $comp['component_id'], true);
        }
        unset($comp);

        // Category edit URL
        $data['url_update_category'] = $this->url->link('extension/module/configurator/updateCategory', $token, true);

        // Rules
        $data['rules'] = $this->model_extension_module_configurator->getRules();
        foreach ($data['rules'] as &$rule) {
            $rule['delete_url'] = $this->url->link('extension/module/configurator/deleteRuleAction', $token . '&id=' . $rule['rule_id'], true);
        }
        unset($rule);

        // Language keys for twig
        $lang_keys = array(
            'tab_orders', 'tab_catalog', 'tab_categories', 'tab_components', 'tab_rules', 'tab_settings', 'tab_help',
            'text_customer', 'text_phone', 'text_components', 'text_total', 'text_status', 'text_date', 'text_no_orders',
            'text_catalog_products', 'text_col_name', 'text_col_price', 'text_col_qty', 'text_col_status',
            'text_select_category', 'text_no_catalog_products',
            'text_categories_title',
            'text_btn_add', 'text_btn_delete', 'text_no_manual_components',
            'text_btn_add_rule', 'text_rules_hint', 'text_no_manual_rules', 'text_col_type',
            'text_settings_title', 'text_settings_buttons', 'text_settings_qty',
            'text_cfg_show_save', 'text_cfg_show_load', 'text_cfg_qty_ram', 'text_cfg_qty_casefan', 'text_cfg_qty_monitor',
            'text_help_how', 'text_help_products_title', 'text_help_products_body',
            'text_help_compat_title', 'text_help_compat_body',
            'text_help_manual_title', 'text_help_manual_body',
            'text_help_orders_title', 'text_help_orders_body',
            'text_help_link_title', 'text_help_settings_title', 'text_help_settings_body',
            'text_help_settings_li1', 'text_help_settings_li2', 'text_help_settings_li3',
            'text_help_settings_li4', 'text_help_settings_li5', 'text_help_settings_li6',
            'text_modal_add_component', 'text_modal_category', 'text_modal_name', 'text_modal_price',
            'text_modal_status', 'text_modal_attributes', 'text_modal_close', 'text_modal_save', 'text_modal_loading',
            'text_modal_add_rule', 'text_modal_rule_type', 'text_modal_component1', 'text_modal_component2',
            'text_modal_compatible', 'text_modal_incompatible', 'text_modal_compatible_yes',
            'text_order_details',
            'text_status_new_label', 'text_status_processing_label', 'text_status_completed_label', 'text_status_cancelled_label',
            'button_save', 'button_delete', 'button_close',
            'text_confirm_delete',
        );
        foreach ($lang_keys as $key) {
            $data[$key] = $this->language->get($key);
        }
        $data['text_confirm'] = $this->language->get('text_confirm_delete');

        // JS strings (passed as JSON to avoid escaping issues)
        $data['js_strings'] = json_encode(array(
            'order'      => $this->language->get('tab_orders'),
            'name'       => $this->language->get('text_modal_name'),
            'phone'      => $this->language->get('text_phone'),
            'email'      => $this->language->get('text_email'),
            'date'       => $this->language->get('text_date'),
            'status'     => $this->language->get('text_status'),
            'total'      => $this->language->get('text_total'),
            'components' => $this->language->get('tab_components'),
            'category'   => $this->language->get('text_modal_category'),
            'delete'     => $this->language->get('button_delete'),
            'cancel'     => $this->language->get('text_no'),
            'deleteN'    => $this->language->get('text_confirm_delete'),
        ));

        // URLs
        $token = 'user_token=' . $this->session->data['user_token'];
        $data['delete_orders'] = $this->url->link('extension/module/configurator', $token, true);
        $data['delete_components'] = $this->url->link('extension/module/configurator', $token . '&tab=components', true);
        $data['delete_rules'] = $this->url->link('extension/module/configurator', $token . '&tab=rules', true);
        $data['url_add_component'] = $this->url->link('extension/module/configurator/addComponent', $token, true);
        $data['url_add_rule'] = $this->url->link('extension/module/configurator/addRule', $token, true);
        $data['url_base'] = $this->url->link('extension/module/configurator', $token, true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/configurator', $data));
    }

    public function changeStatus() {
        if (!$this->user->hasPermission('modify', 'extension/module/configurator')) {
            $this->session->data['error_warning'] = 'Permission denied';
            $this->response->redirect($this->url->link('extension/module/configurator', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $this->load->model('extension/module/configurator');

        if (isset($this->request->get['order_id']) && isset($this->request->get['status'])) {
            $this->model_extension_module_configurator->updateOrderStatus(
                (int)$this->request->get['order_id'],
                $this->request->get['status']
            );
            $this->session->data['success'] = 'Status updated';
        }

        $this->response->redirect($this->url->link('extension/module/configurator', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function addComponent() {
        $this->load->language('extension/module/configurator');
        $this->load->model('extension/module/configurator');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateModify()) {
            $component_id = $this->model_extension_module_configurator->addComponent($this->request->post);

            if (isset($this->request->post['attributes'])) {
                $this->model_extension_module_configurator->saveAttributes($component_id, $this->request->post['attributes']);
            }

            $this->session->data['success'] = 'Component added';
            $this->response->redirect($this->url->link('extension/module/configurator', 'user_token=' . $this->session->data['user_token'] . '&tab=components', true));
        } else {
            // AJAX fallback
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array('error' => 'Validation failed')));
        }
    }

    public function addRule() {
        $this->load->language('extension/module/configurator');
        $this->load->model('extension/module/configurator');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateModify()) {
            $this->model_extension_module_configurator->addRule($this->request->post);
            $this->session->data['success'] = 'Rule added';
            $this->response->redirect($this->url->link('extension/module/configurator', 'user_token=' . $this->session->data['user_token'] . '&tab=rules', true));
        } else {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array('error' => 'Validation failed')));
        }
    }

    public function saveSettings() {
        $this->load->model('setting/setting');

        $data = array(
            'cfg_show_save'   => isset($this->request->post['cfg_show_save'])   ? 1 : 0,
            'cfg_show_load'   => isset($this->request->post['cfg_show_load'])   ? 1 : 0,
            'cfg_qty_ram'     => max(1, min(8,  (int)$this->request->post['cfg_qty_ram'])),
            'cfg_qty_ssd'     => max(1, min(8,  (int)$this->request->post['cfg_qty_ssd'])),
            'cfg_qty_hdd'     => max(1, min(8,  (int)$this->request->post['cfg_qty_hdd'])),
            'cfg_qty_casefan' => max(1, min(12, (int)$this->request->post['cfg_qty_casefan'])),
            'cfg_qty_monitor' => max(1, min(6,  (int)$this->request->post['cfg_qty_monitor'])),
        );

        $this->model_setting_setting->editSetting('cfg', $data);
        $this->session->data['success'] = 'Settings saved';
        $this->response->redirect($this->url->link('extension/module/configurator', 'user_token=' . $this->session->data['user_token'] . '&tab=settings', true));
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'extension/module/configurator')) {
            $this->error['warning'] = 'Permission denied';
            return false;
        }
        return true;
    }

    protected function validateModify() {
        if (!$this->user->hasPermission('modify', 'extension/module/configurator')) {
            $this->error['warning'] = 'Permission denied';
            return false;
        }
        return true;
    }
}
