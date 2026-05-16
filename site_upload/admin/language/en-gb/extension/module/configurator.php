<?php
// Heading
$_['heading_title']            = 'PC Configurator';

// Text
$_['text_home']                = 'Home';

// Tabs
$_['tab_orders']               = 'Orders';
$_['tab_catalog']              = 'Catalog';
$_['tab_categories']           = 'Categories';
$_['tab_components']           = 'Manual';
$_['tab_rules']                = 'Rules';
$_['tab_settings']             = 'Settings';
$_['tab_help']                 = 'Help';

// Orders
$_['text_order_id']            = 'Order ID';
$_['text_customer']            = 'Customer';
$_['text_phone']               = 'Phone';
$_['text_email']               = 'E-Mail';
$_['text_total']               = 'Total';
$_['text_status']              = 'Status';
$_['text_date']                = 'Date';
$_['text_components']          = 'Components';
$_['text_actions']             = 'Actions';
$_['text_no_orders']           = 'No orders found.';

// Statuses
$_['text_status_new']          = 'New';
$_['text_status_processing']   = 'Processing';
$_['text_status_completed']    = 'Completed';
$_['text_status_cancelled']    = 'Cancelled';

// Components
$_['text_category']            = 'Category';
$_['text_name']                = 'Name';
$_['text_price']               = 'Price';
$_['text_sort_order']          = 'Sort Order';
$_['text_add_component']       = 'Add Component';
$_['text_no_components']       = 'No components found.';

// Attributes
$_['text_socket']              = 'Socket';
$_['text_ram_type']            = 'RAM Type';
$_['text_form_factor']         = 'Form Factor';
$_['text_tdp']                 = 'TDP';
$_['text_wattage']             = 'Wattage';
$_['text_add_attribute']       = 'Add Attribute';

// Rules
$_['text_rule_type']           = 'Rule Type';
$_['text_component_1']         = 'Component 1';
$_['text_component_2']         = 'Component 2';
$_['text_compatible']          = 'Compatible';
$_['text_incompatible']        = 'Incompatible';
$_['text_add_rule']            = 'Add Rule';
$_['text_no_rules']            = 'No rules found.';
$_['text_rules_info']          = 'Define compatibility rules between components to ensure valid configurations.';

// Help
$_['text_help_title']          = 'PC Configurator Help';
$_['text_help_intro']          = 'The PC Configurator module allows customers to build custom PC configurations by selecting compatible components.';
$_['text_help_auto']           = 'Automatic compatibility checking validates selected components against defined rules in real time.';
$_['text_help_manual']         = 'You can manually add compatibility rules between components using the Rules tab.';
$_['text_help_priority']       = 'Components are displayed according to their sort order. Lower values appear first.';

// Catalog tab
$_['text_catalog_products']    = 'Catalog Products in Configurator';
$_['text_col_name']            = 'Name';
$_['text_col_price']           = 'Price';
$_['text_col_qty']             = 'Qty';
$_['text_col_status']          = 'Status';
$_['text_total_page']          = 'Total: %s | Page %s/%s';
$_['text_select_category']     = 'Select a category to view products';
$_['text_no_catalog_products'] = 'No products found in this category';

// Categories tab
$_['text_categories_title']    = 'Edit Categories';

// Components tab
$_['text_btn_add']             = 'Add';
$_['text_btn_delete']          = 'Delete';
$_['text_no_manual_components'] = 'No manual components added. Catalog products are used automatically.';

// Rules tab
$_['text_btn_add_rule']        = 'Add Rule';
$_['text_rules_hint']          = 'Auto-detection: socket, RAM type, form factor, PSU wattage. Rules added below override auto-detection.';
$_['text_no_manual_rules']     = 'No manual rules added. Auto-detection from product names is active.';
$_['text_col_type']            = 'Type';

// Settings tab
$_['text_settings_title']      = 'Settings';
$_['text_settings_buttons']    = 'Buttons';
$_['text_settings_qty']        = 'Quantity Limits';
$_['text_cfg_show_saveload']   = 'Save / Load Configuration';
$_['text_cfg_show_pdf']        = 'PDF Download';
$_['text_cfg_show_excel']      = 'Excel Download';
$_['text_cfg_qty_ram']         = 'RAM';
$_['text_cfg_qty_casefan']     = 'Case Fan';
$_['text_cfg_qty_monitor']     = 'Monitor';

// Help tab
$_['text_help_how']            = 'How It Works';
$_['text_help_products_title'] = 'Products';
$_['text_help_products_body']  = 'The configurator automatically uses products from your catalog by category. When you add or remove a product in the store, it will automatically appear or disappear in the configurator.';
$_['text_help_compat_title']   = 'Compatibility';
$_['text_help_compat_body']    = 'Auto-detection from product names: <strong>Socket</strong> (LGA1700, AM5...), <strong>RAM type</strong> (DDR4, DDR5), <strong>Form factor</strong> (ATX, mATX), <strong>PSU wattage</strong> (750W). Rules added in the Rules tab override auto-detection.';
$_['text_help_manual_title']   = 'Manual Components';
$_['text_help_manual_body']    = 'If you manually add components for a category, they will replace catalog products for that category.';
$_['text_help_orders_title']   = 'Orders';
$_['text_help_orders_body']    = 'Orders submitted via the configurator will appear here. An email notification is sent to the store email.';
$_['text_help_link_title']     = 'Link';
$_['text_help_settings_title'] = 'Settings';
$_['text_help_settings_body']  = 'In the Settings tab you can:';
$_['text_help_settings_li1']   = '<strong>Save Configuration</strong> — enable if you want customers to save their configuration with a code';
$_['text_help_settings_li2']   = '<strong>Load Configuration</strong> — enable if you want saved configurations to be loaded by code';
$_['text_help_settings_li3']   = '<strong>RAM Limit</strong> — max number of RAM modules selectable';
$_['text_help_settings_li4']   = '<strong>SSD / HDD Limit</strong> — max number of SSDs or HDDs selectable';
$_['text_help_settings_li5']   = '<strong>Case Fan Limit</strong> — max number of case fans';
$_['text_help_settings_li6']   = '<strong>Monitor Limit</strong> — max number of monitors';

// Modal: Add Component
$_['text_modal_add_component'] = 'Add Component';
$_['text_modal_category']      = 'Category';
$_['text_modal_name']          = 'Name';
$_['text_modal_price']         = 'Price (GEL)';
$_['text_modal_status']        = 'Status';
$_['text_modal_attributes']    = 'Attributes';
$_['text_modal_close']         = 'Close';
$_['text_modal_save']          = 'Save';
$_['text_modal_loading']       = 'Saving...';

// Modal: Add Rule
$_['text_modal_add_rule']      = 'Add Compatibility Rule';
$_['text_modal_rule_type']     = 'Rule Type';
$_['text_modal_component1']    = 'Component 1 (Product ID)';
$_['text_modal_component2']    = 'Component 2 (Product ID)';
$_['text_modal_compatible']    = 'Compatible?';
$_['text_modal_incompatible']  = 'Incompatible (block)';
$_['text_modal_compatible_yes'] = 'Compatible (allow)';

// Order view modal
$_['text_order_details']       = 'Order Details';

// Statuses (select options)
$_['text_status_new_label']    = 'New';
$_['text_status_processing_label'] = 'Processing';
$_['text_status_completed_label']  = 'Completed';
$_['text_status_cancelled_label']  = 'Cancelled';

// Buttons
$_['button_save']              = 'Save';
$_['button_delete']            = 'Delete';
$_['button_close']             = 'Close';

// Confirmation
$_['text_confirm_delete']      = 'Are you sure you want to delete?';
$_['text_yes']                 = 'Yes';
$_['text_no']                  = 'No';
