# PC Configurator for OpenCart 3

A PC builder / configurator module for OpenCart 3.0.3.x.  
Lets customers assemble a PC from compatible components, check compatibility, get a PDF quote, and place an order — all without leaving the store.

---

## Features

- Component selection by category (CPU, Motherboard, RAM, GPU, PSU, Cooler, Case, SSD, HDD, Case Fan, Monitor, Peripherals)
- Real-time compatibility checking (socket, RAM type, form factor, PSU wattage)
- Manual compatibility rules via admin panel
- PDF generation with full component list, quantities, subtotals, and payment details
- Order form with email notification to store admin
- Add configured components directly to OpenCart cart
- Persist selected components across page refresh and language switch (localStorage)
- Admin panel: orders list, catalog preview, manual components, compatibility rules, settings
- Settings: toggle Save/Load config buttons, configure max quantity per category
- Multilingual: Georgian (ge-ka), English (en-gb), Russian (ru-ru)

---

## Requirements

- OpenCart 3.0.3.x
- PHP 7.4 (not compatible with PHP 8.x)
- Chameleon theme (frontend template uses Chameleon layout; admin is theme-independent)

---

## Installation

### 1. Upload files

Upload the contents of `site_upload/` to your OpenCart root directory:

```
site_upload/
├── admin/
├── catalog/
└── storage/
```

### 2. Run SQL

Execute `site_upload/deploy.sql` in phpMyAdmin (or any MySQL client).

This will:
- Create configurator tables (`oc_pc_component_category`, `oc_pc_component`, etc.)
- Insert default categories
- Add configurator link to megamenu
- Insert default module settings

### 3. Set permissions

In OpenCart admin go to **System → Users → User Groups → Administrator** and add to both Access and Modify:

```
extension/module/configurator
```

Or run `data_dump/fix_perms.py` (requires Python + MySQL access).

### 4. Clear modification cache

Admin → Extensions → Modifications → Refresh

---

## OCMOD Packaging — Critical Rules

OpenCart 3 на этом проекте (3.0.3.8 + Chameleon, Hostinger shared hosting) очень капризен к OCMOD-zip. Несоблюдение любого пункта = мод установится в БД (status=1) но патч **не применится**, в логах тишина, в скомпилированном файле исходник без правок. Часы отладки.

### Структура zip

- `install.xml` строго **в корне** zip, НЕ в `upload/`. Папка `upload/` нужна только для full-extension zip с файлами модуля; для чистого OCMOD-патча — корень.
- Forward slashes (`/`) в путях zip, не backslashes. PowerShell `Compress-Archive` файла напрямую (без обёртки в подпапку) даёт правильно.
- Эталон рабочих структур на проекте: `gcomp_social_global.ocmod.zip`, `gcomp_only_online.ocmod.zip` — оба содержат только `install.xml` в корне.

### Кодировка XML

- **Без BOM**. Первые 3 байта должны быть `3C 3F 78` (`<?x`), не `EF BB BF`.
- **LF, не CRLF**. PowerShell:
  ```powershell
  $content = $content -replace "`r`n", "`n"
  $utf8NoBom = New-Object System.Text.UTF8Encoding $false
  [System.IO.File]::WriteAllText($path, $content, $utf8NoBom)
  ```

### `<search>` блок

- **Однострочный якорь** надёжнее многострочного. Многострочный `<search>` чувствителен к каждому пробелу/табу/EOL — на Chameleon-сборке часто фейлит молча.
- Якорь = **уникальная** строка, которую другие моды не трогают. Грепнуть в исходнике: должна быть ровно одна копия.
- Хороший якорь в `column_left.php`: `if ($this->user->hasPermission('access', 'catalog/category')) {` — встречается 1 раз, Chameleon не правит.
- Плохой якорь: `// Catalog\n\t\t\t$catalog = array();` — двухстрочный + Chameleon сдвигает отступы перед этой зоной.

### Версия мода

При каждой правке XML поднимать `<version>` (3.0 → 4.0 → 5.0). OpenCart по `<code>` определяет дубль; если version не поменялась — может оставить старую запись в БД.

### Refresh-фейлы

На Hostinger shared hosting Refresh иногда не может рекурсивно удалить `storage/modification/`:
```
PHP Warning: rmdir(...): Directory not empty in admin/controller/marketplace/modification.php on line 105
```
Refresh обрывается, патчи не записываются. Лечится **ручным удалением** `storage/modification/admin/` (или всей `storage/modification/`) через FTP, потом Refresh с нуля.

PHP-скрипт для очистки (положить в `site/public_html/clear_mod.php`, открыть в браузере):
```php
<?php
function rrmdir($d) { if (!is_dir($d)) return; foreach (scandir($d) as $i) { if ($i==='.'||$i==='..') continue; $p=$d.'/'.$i; is_dir($p)?rrmdir($p):unlink($p); } rmdir($d); }
rrmdir(__DIR__.'/storage/modification/admin');
echo "ok"; unlink(__FILE__);
```

### OPcache

После Refresh обязательно сбросить OPcache, иначе PHP отдаёт старый байткод:
```php
<?php opcache_reset(); echo "ok"; unlink(__FILE__);
```

### Диагностика — порядок шагов

1. SQL: `SELECT modification_id, code, version, status FROM oc_modification WHERE code LIKE '%мой_код%';` — мод вообще зарегистрирован?
2. SQL: `SELECT xml FROM oc_modification WHERE code='мой_код';` — XML в БД корректный (без BOM, не битый)?
3. FTP: `system/storage/logs/error.log` — есть rmdir-ошибки?
4. FTP: `storage/modification/admin/controller/common/column_left.php` — содержит ли наши строки после Refresh? Грепнуть по уникальному id типа `menu-configurator`.
5. Если в скомпилированном файле наших строк нет, но в БД мод есть → `<search>` не сматчился. Менять якорь на однострочный, поднимать version, переустанавливать.

---

## File Structure

```
site_upload/
├── admin/
│   ├── controller/extension/module/configurator.php   — admin controller
│   ├── model/extension/module/configurator.php        — admin model
│   ├── view/template/extension/module/configurator.twig
│   └── language/{en-gb,ge-ka,ru-ru}/extension/module/configurator.php
├── catalog/
│   ├── controller/information/configurator.php        — frontend controller
│   ├── model/catalog/configurator.php                 — frontend model
│   ├── view/theme/chameleon/template/information/configurator.twig
│   ├── view/javascript/configurator/configurator.js
│   ├── view/javascript/configurator/configurator.css
│   ├── view/javascript/configurator/icons/            — SVG icons
│   └── language/{en-gb,ge-ka,ka-ge,ru-ru}/information/configurator.php
└── storage/
    └── modification/admin/controller/common/column_left.php  — sidebar menu
```

---

## Admin Panel

Access via **Extensions → PC Configurator** in the admin sidebar.

Tabs:
- **Orders** — view and manage configurator orders, change status, download PDF
- **Catalog** — preview which products appear per category
- **Categories** — edit category names (EN/KA/RU) and icons
- **Manual** — add custom components not pulled from catalog
- **Rules** — add manual compatibility rules between components
- **Settings** — toggle Save/Load buttons, set quantity limits per category
- **Help** — usage documentation

---

## Category Mapping

The module maps configurator categories to OpenCart catalog categories:

| Configurator | OpenCart Category IDs |
|---|---|
| CPU | 271 |
| Motherboard | 272 |
| RAM | 274 |
| GPU | 273 |
| PSU | 277 |
| Air Cooler | 276 |
| Case | 278 |
| SSD | 275 |
| HDD | 275 |
| Case Fan | 276 |
| Monitor | 318 |
| Keyboard | 284, 426, 429 |
| Mouse | 285, 427, 428 |
| Headset | 287 |
| Speakers | 288 |
| Mousepad | 286 |
| Microphone | 289 |
| Water Cooler | 276 |

Update these mappings in `catalog/model/catalog/configurator.php` to match your store's category IDs.

---

## Frontend URL

```
index.php?route=information/configurator
```

---

## License

MIT — free to use, modify, and distribute.
