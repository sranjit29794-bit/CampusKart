# CampusKart — Student E-Commerce Marketplace

> A complete, production-grade, full-stack e-commerce web application built for a college Web Technology (WT) Laboratory Mini-Project.

---

## 1. Project Overview

### Abstract
College students frequently encounter challenges obtaining semester essentials—ranging from lecture apparel and ergonomic bags to specialized stationery, scientific calculators, and lab peripherals—due to off-campus transit times, mismatched pricing, and delivery delays. **CampusKart** solves this by establishing a campus-centric e-commerce marketplace offering localized hostel delivery within 2 hours, Cash on Delivery (COD) support, and fair student pricing.

### Core Objectives
1. **Curated Marketplace:** Provide an intuitive catalog of apparel, sneakers, accessories, backpacks, and study tech matching the visual aesthetic of the reference designs.
2. **Dynamic Shopping Operations:** Real-time search, category filtering, price bounds, session-based shopping cart, and automatic server-side price recalculation.
3. **Transactional Integrity:** Safe database transactions guaranteeing that orders, order items, and inventory decrements execute atomically.
4. **Role-Based Security:** Strict separation between student customer accounts and the administrative management portal.
5. **Academic Rigor:** Built purely with semantic HTML5, Vanilla CSS3 (Grid & Flexbox), Vanilla JavaScript, PHP 8 (PDO), and MySQL without bulky frontend frameworks.

---

## 2. Technology Stack

* **Frontend:** Semantic HTML5, Vanilla CSS3 (CSS Grid, Flexbox, Custom Properties), Vanilla JavaScript (ES6+).
* **Backend:** PHP 8.2+ with PDO (PHP Data Objects), prepared statements, and session-based state management.
* **Database:** MySQL 8.0 / MariaDB (relational 3NF schema with foreign keys and indexes) + automatic portable SQLite fallback.
* **Development Environment:** XAMPP-compatible (Apache/MySQL) and zero-dependency standalone PHP CLI server.

---

## 3. Project Directory Structure

```text
campuskart/
├── index.php                 # Homepage (Hero, Trust Badges, Featured Products)
├── shop.php                  # Product Catalog (Search, Category Filter, Price Filter, Grid)
├── product.php               # Dedicated Product Details & Stock Status
├── cart.php                  # Session-based Shopping Cart & Totals
├── cart-action.php           # Cart Controller (Add, Update, Remove, Clear)
├── checkout.php              # Delivery Details & Transactional Order Placement
├── order-success.php         # Order Confirmation & Dispatch Summary
├── orders.php                # Customer Order History (My Orders)
├── order-details.php         # Customer Order Details & Itemized Receipt
├── login.php                 # User & Admin Authentication
├── register.php              # Customer Account Registration
├── logout.php                # Session Termination & Logout
├── profile.php               # User Account Management & Password Change
├── about.php                 # CampusKart Mission & Lab Project Attribution
├── contact.php               # Student Support Desk & Contact Inquiries
├── admin/
│   ├── index.php             # Admin Dashboard Overview & Real Database KPIs
│   ├── products.php          # Product Management (List & Delete)
│   ├── product-create.php    # Add Product with Safe Image Upload Handling
│   ├── product-edit.php      # Product Details & Stock Adjuster
│   ├── categories.php        # Category Management & Department Organization
│   ├── orders.php            # Order Management & Status Lifecycle Changer
│   ├── order-details.php     # Order Breakdown & Printable Dispatch Receipt
│   └── customers.php         # Registered Customer Directory & Spend Metrics
├── config/
│   ├── database.php          # Database Connection (PDO Prepared Statements)
│   └── config.example.php    # Environment Configuration Template
├── includes/
│   ├── header.php            # Reusable Header, Navigation & Search Modal
│   ├── footer.php            # Reusable Footer & Campus Information
│   ├── functions.php         # Helper Functions, Pricing, Cart & Flash Messages
│   ├── auth.php              # Authentication & Session Fixation Protection
│   ├── csrf.php              # Cryptographic CSRF Token Protection
│   └── admin-auth.php        # Server-side 403 Administrator Access Gatekeeper
├── assets/
│   ├── css/
│   │   └── style.css         # Master Stylesheet (Reference Design Reproduction)
│   ├── js/
│   │   └── script.js         # Interactive Modals, Quantities & Toast Alerts
│   └── images/               # High-Resolution Product & Hero Photography
├── database/
│   ├── campuskart.sql        # MySQL 8.0 / MariaDB Schema & Seed Data
│   ├── setup.php             # Browser & CLI Database Migrator
│   └── test_runner.php       # Automated Test Suite (10/10 Verification)
├── docs/                     # Design Reference Screenshots & Project Report
│   ├── Screenshot 2026-10-26 013834.png
│   ├── Screenshot 2026-10-26 013841.png
│   ├── Screenshot 2026-10-26 013848.png
│   ├── Screenshot 2026-10-26 013853.png
│   └── PROJECT_REPORT.md     # Comprehensive Mini-Project Academic Report
└── README.md
```

---

## 4. Database Architecture

The relational database is named `campuskart` and consists of five core tables:

1. **`users`**: Customer and administrative accounts, telephone, role (`customer` | `admin`), and bcrypt `password_hash`.
2. **`categories`**: Product classifications (`slug`, `name`, `description`).
3. **`products`**: Inventory records with foreign key to `categories`, name, slug, description, selling price, optional original strike-through price, stock quantity, rating, rating count, and image path.
4. **`orders`**: Order records with foreign key to `users`, unique `order_number` (e.g., `CK-2026-XXXX`), shipping destination, item subtotal, delivery fee, final total amount, payment method (COD), and fulfillment status (`Pending`, `Processing`, `Shipped`, `Delivered`, `Cancelled`).
5. **`order_items`**: Line items linking `orders` and `products`, retaining historical locked unit prices and quantities.

---

## 5. Quick Start & Installation

### Option A: Running with XAMPP (Apache + MySQL)

1. **Place Project in `htdocs`**:
   Copy the `campuskart` folder to `C:\xampp\htdocs\campuskart`.
2. **Start Services**:
   Open XAMPP Control Panel and start **Apache** and **MySQL**.
3. **Import Database via phpMyAdmin**:
   * Open `http://localhost/phpmyadmin` in your web browser.
   * Click **New** on the left sidebar, enter database name `campuskart`, and click **Create**.
   * Select the `campuskart` database, click the **Import** tab at the top.
   * Click **Choose File**, select `database/campuskart.sql`, and click **Import** (or **Go**).
4. **Configure Database Credentials (if modified)**:
   * By default, XAMPP connects with `DB_USER = 'root'` and `DB_PASS = ''`.
   * If your local MySQL instance has a custom password, copy `config/config.example.php` to `config/config.php` and set `'password' => 'your_password'`.
5. **Access Application**:
   * Customer Storefront: `http://localhost/campuskart/`
   * Administrator Portal: `http://localhost/campuskart/admin/`

---

### Option B: Standalone Portable Run (Built-in PHP Server)

The application includes an automated self-migrating SQLite fallback that runs out of the box with zero configuration:

1. Open PowerShell or Command Prompt in the project root:
   ```powershell
   php -S 127.0.0.1:8000
   ```
2. Open your web browser and visit:
   `http://127.0.0.1:8000`

---

## 6. Default Login Credentials

The database includes pre-seeded demo accounts with bcrypt password hashes:

| Role | Email Address | Password | Permissions |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@campuskart.com` | `Admin@123` | Full access to Admin Portal (`admin/`), product CRUD, order transitions, customer metrics |
| **Student Customer** | `rohan@college.edu` | `Student@123` | Shop catalog, shopping cart, checkout, view order history (`orders.php`) |

> **Evaluator Tip:** The login page features quick one-click autofill buttons (`Student Demo` and `Admin Demo`) to allow rapid review during laboratory evaluation.

---

## 7. Security Implementation

1. **SQL Injection Defense:** All database queries utilize PDO prepared statements with bound parameters (`$stmt->prepare()` and `$stmt->execute()`). Untrusted data is never concatenated directly into SQL queries.
2. **Cross-Site Request Forgery (CSRF):** Cryptographic anti-CSRF tokens (`includes/csrf.php`) are generated using `random_bytes(32)` and validated on all state-changing POST requests (cart operations, checkout, login, product CRUD, status updates).
3. **Password Security:** Passwords are never stored in plaintext. Passwords are encrypted using PHP's industry-standard `password_hash(..., PASSWORD_DEFAULT)` and authenticated via `password_verify()`.
4. **Session Fixation Mitigation:** Session IDs are regenerated upon authentication using `session_regenerate_id(true)` to defeat session fixation attacks.
5. **Insecure Direct Object Reference (IDOR) Protection:** Order detail endpoints verify that the authenticated `user_id` matches the order's owner before rendering sensitive address and payment details.
6. **Server-Side Price & Inventory Verification:** Checkout recalculates all totals server-side directly against database records, preventing browser client manipulation of monetary prices or quantities.
7. **Safe Image Upload Validation:** The administrator product upload checks actual file content using PHP `finfo` MIME inspection (`image/jpeg`, `image/png`, `image/webp`), limits files to 3MB, and writes randomized filenames (`prod_xxxx.jpg`) to prevent executable file uploads.

---

## 8. Automated Verification Suite

Run the built-in test suite from PowerShell:
```powershell
php database/test_runner.php
```

Output:
```text
Running CampusKart Automated Verification Suite...

  [PASS] Database connection & tables exist
  [PASS] Catalog products seeded (Count >= 10)
  [PASS] Admin account exists
  [PASS] Student account exists
  [PASS] Cart subtotal calculation correct
  [PASS] Free delivery applied for order > 500
  [PASS] Order placement created order record
  [PASS] Inventory decremented atomically (-2)
  [PASS] Owner can access their order
  [PASS] Unauthorized user blocked from order

Verification Summary: 10 / 10 Tests Passed.
```

---

## 9. Academic Project Submission

Detailed Web Technology documentation is available in [`docs/PROJECT_REPORT.md`](docs/PROJECT_REPORT.md).
#   C a m p u s K a r t  
 