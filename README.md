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
