# WHMCS Plan & Tier Comparison Addon Module

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![WHMCS](https://img.shields.io/badge/WHMCS-8.0%2B-blue)](https://www.whmcs.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%20%7C%208.1%20%7C%208.2%20%7C%208.3%20%7C%208.4-purple)](https://php.net)

A high-converting, responsive **Hardware Specification & Category/Tier Comparison Matrix** addon module for WHMCS order forms and shopping carts.

Designed specifically for modern web hosts wanting to showcase their hosting tiers (e.g. **Budget vs. Pro vs. Turbo**) side-by-side with processor benchmarks, DDR speed, NVMe generations, resource isolation models, load balancing, and network uplinks.

---

## ✨ Features

- ⚡ **Interactive 4-Column Spec Matrix**: Clean, modern comparison table with microchip, pulse, drive, and network icons.
- 🎯 **Highlighted Tier Column**: Elevated card styling with glowing border, vibrant brand accent, and custom pill badges (`MOST POPULAR`, `ULTIMATE POWER`).
- 💡 **Interactive Tooltips (ⓘ)**: Built-in hover tooltips explaining technical specifications to prospective buyers.
- 🎨 **Status Badges**: Automatic conversion of `yes`/`1`/`✓` into soft green check circles and `no`/`—` into clean neutral dash circles.
- 📱 **100% Mobile Responsive**: Silky smooth touch horizontal scrolling with sticky feature titles on mobile viewports.
- 🛠️ **Visual Admin Spreadsheet Editor**: Add, edit, or reorder tiers and specification rows without editing any theme files or writing JSON.
- 🧩 **Zero-Touch Storefront Injection**: Automatically attaches directly below product cards or advanced features on **Lagom 2**, **Standard Cart**, and custom WHMCS templates.
- 🔒 **Safe & Production-Grade**: Follows official WHMCS Capsule database practices and includes directory security guards.

---

## 🚀 Installation

1. Clone or download this repository:
   ```bash
   git clone https://github.com/samsusiyam/WHMCS-PlanComparison.git
   ```
2. Copy the `modules/addons/plancomparison` folder to your WHMCS root directory:
   ```text
   /path/to/whmcs/modules/addons/plancomparison/
   ├── plancomparison.php
   ├── hooks.php
   ├── index.php
   ├── logo.png
   └── whmcs.json
   ```
3. In your WHMCS Admin Area, navigate to:
   **Configuration (Wrench) ➔ System Settings ➔ Addon Modules**
4. Locate **Bahari Plan & Tier Comparison**, click **Activate**, and set administrator access permissions.
5. Access the module from **Addons ➔ Bahari Plan & Tier Comparison** to configure your matrices and display preferences.

---

## ⚙️ Configuration & Live Preview

- **Matrices Tab**: Create multiple comparison tables tailored to different product categories (Shared Hosting, Reseller Hosting, VPS, Dedicated Servers).
- **Visual Editor**: Customize tier names, pill badges, feature rows, hover tooltips, and CTA button links.
- **Display Settings**: Choose injection placement (under pricing cards or under advanced features), brand colors, and custom CSS overrides.

---

## 📄 License

Open-source under the [MIT License](LICENSE). Developed with ❤️ for **Bahari Host**.
