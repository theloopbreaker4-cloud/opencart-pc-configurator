<?php
// Heading
$_['heading_title']            = 'Конфигуратор ПК';

// Text
$_['text_home']                = 'Главная';

// Tabs
$_['tab_orders']               = 'Заказы';
$_['tab_catalog']              = 'Каталог';
$_['tab_categories']           = 'Категории';
$_['tab_components']           = 'Вручную';
$_['tab_rules']                = 'Правила';
$_['tab_settings']             = 'Настройки';
$_['tab_help']                 = 'Помощь';

// Orders
$_['text_order_id']            = 'ID заказа';
$_['text_customer']            = 'Покупатель';
$_['text_phone']               = 'Телефон';
$_['text_email']               = 'E-Mail';
$_['text_total']               = 'Итого';
$_['text_status']              = 'Статус';
$_['text_date']                = 'Дата';
$_['text_components']          = 'Компоненты';
$_['text_actions']             = 'Действия';
$_['text_no_orders']           = 'Заказы не найдены.';

// Statuses
$_['text_status_new']          = 'Новый';
$_['text_status_processing']   = 'В обработке';
$_['text_status_completed']    = 'Завершён';
$_['text_status_cancelled']    = 'Отменён';

// Components
$_['text_category']            = 'Категория';
$_['text_name']                = 'Название';
$_['text_price']               = 'Цена';
$_['text_sort_order']          = 'Порядок сортировки';
$_['text_add_component']       = 'Добавить компонент';
$_['text_no_components']       = 'Компоненты не найдены.';

// Attributes
$_['text_socket']              = 'Сокет';
$_['text_ram_type']            = 'Тип RAM';
$_['text_form_factor']         = 'Форм-фактор';
$_['text_tdp']                 = 'TDP';
$_['text_wattage']             = 'Мощность';
$_['text_add_attribute']       = 'Добавить атрибут';

// Rules
$_['text_rule_type']           = 'Тип правила';
$_['text_component_1']         = 'Компонент 1';
$_['text_component_2']         = 'Компонент 2';
$_['text_compatible']          = 'Совместимый';
$_['text_incompatible']        = 'Несовместимый';
$_['text_add_rule']            = 'Добавить правило';
$_['text_no_rules']            = 'Правила не найдены.';
$_['text_rules_info']          = 'Определите правила совместимости между компонентами для обеспечения корректных конфигураций.';

// Help
$_['text_help_title']          = 'Помощь по конфигуратору ПК';
$_['text_help_intro']          = 'Модуль конфигуратора ПК позволяет покупателям собирать пользовательские конфигурации, выбирая совместимые компоненты.';
$_['text_help_auto']           = 'Автоматическая проверка совместимости проверяет выбранные компоненты по определённым правилам в реальном времени.';
$_['text_help_manual']         = 'Вы можете вручную добавить правила совместимости между компонентами на вкладке «Правила».';
$_['text_help_priority']       = 'Компоненты отображаются в соответствии с порядком сортировки. Меньшие значения отображаются первыми.';

// Catalog tab
$_['text_catalog_products']    = 'Продукты каталога в конфигураторе';
$_['text_col_name']            = 'Название';
$_['text_col_price']           = 'Цена';
$_['text_col_qty']             = 'Кол-во';
$_['text_col_status']          = 'Статус';
$_['text_total_page']          = 'Всего: %s | Страница %s/%s';
$_['text_select_category']     = 'Выберите категорию для просмотра продуктов';
$_['text_no_catalog_products'] = 'В этой категории продукты не найдены';

// Categories tab
$_['text_categories_title']    = 'Редактирование категорий';

// Components tab
$_['text_btn_add']             = 'Добавить';
$_['text_btn_delete']          = 'Удалить';
$_['text_no_manual_components'] = 'Ручные компоненты не добавлены. Продукты каталога используются автоматически.';

// Rules tab
$_['text_btn_add_rule']        = 'Добавить правило';
$_['text_rules_hint']          = 'Авто-определение: сокет, тип RAM, форм-фактор, мощность БП. Правила ниже заменяют авто-определение.';
$_['text_no_manual_rules']     = 'Ручные правила не добавлены. Авто-определение по названию продукта активно.';
$_['text_col_type']            = 'Тип';

// Settings tab
$_['text_settings_title']      = 'Настройки';
$_['text_settings_buttons']    = 'Кнопки';
$_['text_settings_qty']        = 'Лимиты количества';
$_['text_cfg_show_saveload']   = 'Сохранение / Загрузка конфигурации';
$_['text_cfg_show_pdf']        = 'Скачать PDF';
$_['text_cfg_show_excel']      = 'Скачать Excel';
$_['text_cfg_qty_ram']         = 'Оперативная память (RAM)';
$_['text_cfg_qty_casefan']     = 'Вентилятор корпуса (Case Fan)';
$_['text_cfg_qty_monitor']     = 'Монитор';

// Help tab
$_['text_help_how']            = 'Как работает';
$_['text_help_products_title'] = 'Продукты';
$_['text_help_products_body']  = 'Конфигуратор автоматически использует продукты из каталога по категориям. При добавлении или удалении продукта в магазине он автоматически появится или исчезнет в конфигураторе.';
$_['text_help_compat_title']   = 'Совместимость';
$_['text_help_compat_body']    = 'Авто-определение по названию продукта: <strong>Сокет</strong> (LGA1700, AM5...), <strong>Тип RAM</strong> (DDR4, DDR5), <strong>Форм-фактор</strong> (ATX, mATX), <strong>Мощность БП</strong> (750W). Правила на вкладке «Правила» заменяют авто-определение.';
$_['text_help_manual_title']   = 'Ручные компоненты';
$_['text_help_manual_body']    = 'Если вручную добавить компоненты для категории, они заменят продукты каталога для этой категории.';
$_['text_help_orders_title']   = 'Заказы';
$_['text_help_orders_body']    = 'Заказы из конфигуратора отобразятся здесь. На email магазина отправляется уведомление.';
$_['text_help_link_title']     = 'Ссылка';
$_['text_help_settings_title'] = 'Настройки';
$_['text_help_settings_body']  = 'На вкладке Настройки можно:';
$_['text_help_settings_li1']   = '<strong>Сохранение конфигурации</strong> — включить, чтобы пользователь мог сохранить конфигурацию с кодом';
$_['text_help_settings_li2']   = '<strong>Загрузка конфигурации</strong> — включить, чтобы можно было загрузить сохранённую конфигурацию по коду';
$_['text_help_settings_li3']   = '<strong>Лимит RAM</strong> — максимальное количество модулей RAM';
$_['text_help_settings_li4']   = '<strong>Лимит SSD / HDD</strong> — максимальное количество SSD или HDD';
$_['text_help_settings_li5']   = '<strong>Лимит Case Fan</strong> — максимальное количество вентиляторов корпуса';
$_['text_help_settings_li6']   = '<strong>Лимит Monitor</strong> — максимальное количество мониторов';

// Modal: Add Component
$_['text_modal_add_component'] = 'Добавить компонент';
$_['text_modal_category']      = 'Категория';
$_['text_modal_name']          = 'Название';
$_['text_modal_price']         = 'Цена (GEL)';
$_['text_modal_status']        = 'Статус';
$_['text_modal_attributes']    = 'Атрибуты';
$_['text_modal_close']         = 'Закрыть';
$_['text_modal_save']          = 'Сохранить';
$_['text_modal_loading']       = 'Сохраняется...';

// Modal: Add Rule
$_['text_modal_add_rule']      = 'Добавить правило совместимости';
$_['text_modal_rule_type']     = 'Тип правила';
$_['text_modal_component1']    = 'Компонент 1 (Product ID)';
$_['text_modal_component2']    = 'Компонент 2 (Product ID)';
$_['text_modal_compatible']    = 'Совместимы?';
$_['text_modal_incompatible']  = 'Несовместимы (блокировать)';
$_['text_modal_compatible_yes'] = 'Совместимы (разрешить)';

// Order view modal
$_['text_order_details']       = 'Детали заказа';

// Statuses (select options)
$_['text_status_new_label']    = 'Новый';
$_['text_status_processing_label'] = 'В обработке';
$_['text_status_completed_label']  = 'Завершён';
$_['text_status_cancelled_label']  = 'Отменён';

// Buttons
$_['button_save']              = 'Сохранить';
$_['button_delete']            = 'Удалить';
$_['button_close']             = 'Закрыть';

// Confirmation
$_['text_confirm_delete']      = 'Вы уверены, что хотите удалить?';
$_['text_yes']                 = 'Да';
$_['text_no']                  = 'Нет';
