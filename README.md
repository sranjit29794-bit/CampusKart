# CampusKart — Student E-Commerce Marketplace

> A production-grade, full-stack e-commerce web application with **Supabase Cloud PostgreSQL** backend, Row Level Security (RLS), atomic transactional checkout, and full student marketplace functionality.

---

## 1. Project Overview

### Abstract
College students frequently encounter challenges obtaining semester essentials—ranging from lecture apparel and ergonomic bags to specialized stationery, scientific calculators, and lab peripherals—due to off-campus transit times, mismatched pricing, and delivery delays. **CampusKart** solves this by establishing a campus-centric e-commerce marketplace offering localized hostel delivery, Cash on Delivery (COD) support, and fair student pricing.

### Core Objectives
1. **Curated Marketplace:** Provide an intuitive catalog of apparel, sneakers, accessories, backpacks, and study tech matching the visual aesthetic of the reference designs.
2. **Cloud Database Architecture:** Powered by **Supabase PostgreSQL** with Row Level Security (RLS) policies, indexes, and automated triggers.
3. **Transactional Checkout:** Atomic order placement via PostgreSQL stored procedure (`place_order_transaction`), locking inventory rows (`FOR UPDATE`) to prevent overselling.
4. **Role-Based Security:** Strict separation between student customer accounts and the administrative management portal (`/admin/`).
5. **Universal Portability:** Seamless deployment with native HTTPS PostgREST / RPC client, plus local MySQL/SQLite fallback for offline demonstration.

---

## 2. Technology Stack

* **Frontend:** Semantic HTML5, Modern Vanilla CSS (CSS Grid, Flexbox, Glassmorphism, CSS Custom Properties), Vanilla JavaScript (ES6+).
* **Backend:** PHP 8.2+ with dual-mode data access layer (Supabase Data API & PDO Prepared Statements).
* **Primary Database:** **Supabase PostgreSQL 17** (Cloud Database with RLS and Stored Procedures).
* **Fallback Database:** Local MySQL 8.0 or zero-dependency portable SQLite.
* **Hosting Platforms:** Netlify (static export pre-renderer via `build.php`) / Cloud PHP PaaS / Apache / Nginx.

---

## 3. Project Directory Structure

```text
campuskart/
├── index.php                 # Homepage (Hero, Trust Badges, Featured Products)
├── shop.php                  # Product Catalog (Search, Category Filter, Price Filter, Pagination)
├── product.php               # Dedicated Product Details & Stock Status
├── cart.php                  # Session-based Shopping Cart & Totals
├── cart-action.php           # Cart Controller (Add, Update, Remove, Clear)
├── checkout.php              # Delivery Details & Atomic RPC Order Placement
├── order-success.php         # Order Confirmation & Dispatch Summary
├── orders.php                # Customer Order History (My Orders)
├── order-details.php         # Customer Order Details & Itemized Receipt
├── login.php                 # User & Admin Authentication
├── register.php              # Customer Account Registration
├── logout.php                # Session Termination & Logout
├── profile.php               # User Account Management
├── about.php                 # CampusKart Mission & Lab Project Attribution
├── contact.php               # Student Support Desk & Inquiries
├── admin/
│   ├── index.php             # Admin Dashboard Overview & Real Supabase KPIs
│   ├── products.php          # Product Management (List & Delete)
│   ├── product-create.php    # Add Product with Image Handling
│   ├── product-edit.php      # Product Details & Stock Adjuster
│   ├── categories.php        # Category Management & Classification
│   ├── orders.php            # Order Management & Status Lifecycle Changer
│   ├── order-details.php     # Order Breakdown & Printable Dispatch Receipt
│   └── customers.php         # Registered Customer Directory & Spend Metrics
├── config/
│   ├── database.php          # Centralized Database Connection Adapter
│   └── supabase.php          # Reusable Supabase REST/RPC Client & PDO Bridge
├── supabase/
│   └── migrations/
│       └── 001_initial_schema.sql # PostgreSQL DDL, Indexes, RLS Policies & Stored Procedures
├── includes/
│   ├── header.php            # Reusable Header, Navigation & Search Modal
│   ├── footer.php            # Reusable Footer & Campus Information
│   ├── functions.php         # Helper Functions, Pricing, Cart & Flash Messages
│   ├── auth.php              # Authentication & Session Fixation Protection
│   ├── csrf.php              # Cryptographic CSRF Token Protection
│   └── admin-auth.php        # Server-side 403 Administrator Access Gatekeeper
├── assets/
│   ├── css/style.css         # Master Stylesheet (Modern Design System)
│   ├── js/script.js          # Interactive Modals, Quantities & Toast Alerts
│   └── images/               # High-Resolution Product & Hero Photography
├── database/
│   ├── campuskart.sql        # MySQL 8.0 DDL & Seed Data
│   ├── setup.php             # Database Verification & Status Inspector
│   └── test_runner.php       # Automated Test Suite (13/13 Verification)
├── build.php                 # Pre-renderer for Netlify / Static Deployment
├── netlify.toml              # Netlify Deployment Configuration
├── .env.example              # Environment Configuration Template
├── .gitignore                # Version Control Exclusions (Protects .env)
└── README.md
```

---

## 4. Supabase Configuration & Environment Variables

### Environment Setup
Create a `.env` file in the project root by copying `.env.example`:
```bash
cp .env.example .env
```

### Required Variables
```ini
# Application Configuration
NEXT_PUBLIC_APP_NAME="CampusKart"
NEXT_PUBLIC_APP_URL="http://localhost:8000"

# Supabase Project Configuration
# Obtain from: Supabase Dashboard -> Project Settings -> API
NEXT_PUBLIC_SUPABASE_URL="https://your-project-id.supabase.co"
NEXT_PUBLIC_SUPABASE_ANON_KEY="your-anon-publishable-key"

# Server-Side Privileged Key (Keep server-side only; NEVER expose to browser)
SUPABASE_SERVICE_ROLE_KEY="your-service-role-secret-key"
```

> **Security Note:** `.env` is explicitly listed in `.gitignore` and must never be committed to public repositories.

---

## 5. Database Schema & PostgreSQL Migration

The PostgreSQL migration is located at [`supabase/migrations/001_initial_schema.sql`](supabase/migrations/001_initial_schema.sql).

### Tables Overview
1. **`categories`**:
   - `id BIGINT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY`
   - `name VARCHAR(100) NOT NULL`
   - `slug VARCHAR(100) NOT NULL UNIQUE`
   - `description TEXT`
   - `created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now())`
2. **`products`**:
   - `id BIGINT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY`
   - `category_id BIGINT REFERENCES categories(id) ON DELETE CASCADE`
   - `name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description TEXT NOT NULL`
   - `price NUMERIC(10, 2) NOT NULL, original_price NUMERIC(10, 2)`
   - `stock INTEGER NOT NULL DEFAULT 0, rating NUMERIC(2, 1) DEFAULT 5.0, rating_count INTEGER DEFAULT 12`
   - `is_featured SMALLINT DEFAULT 0, image VARCHAR(255) NOT NULL`
   - `created_at TIMESTAMPTZ, updated_at TIMESTAMPTZ`
3. **`users`**:
   - `id BIGINT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY`
   - `name VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL UNIQUE, phone VARCHAR(20) NOT NULL`
   - `password_hash VARCHAR(255) NOT NULL, role VARCHAR(20) DEFAULT 'customer'`
   - `created_at TIMESTAMPTZ`
4. **`orders`**:
   - `id BIGINT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY`
   - `order_number VARCHAR(30) NOT NULL UNIQUE, user_id BIGINT REFERENCES users(id) ON DELETE CASCADE`
   - `customer_name, email, phone, address, city, state, pincode`
   - `subtotal NUMERIC(10, 2), delivery_charge NUMERIC(10, 2), total_amount NUMERIC(10, 2)`
   - `payment_method VARCHAR(50) DEFAULT 'Cash on Delivery', status VARCHAR(30) DEFAULT 'Pending'`
   - `created_at TIMESTAMPTZ`
5. **`order_items`**:
   - `id BIGINT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY`
   - `order_id BIGINT REFERENCES orders(id) ON DELETE CASCADE`
   - `product_id BIGINT REFERENCES products(id) ON DELETE SET NULL`
   - `product_name VARCHAR(255), quantity INTEGER, unit_price NUMERIC(10, 2), subtotal NUMERIC(10, 2)`

### Applying Migrations
Apply the initial schema and seed data directly through the Supabase CLI:
```bash
npx supabase db query --linked -f supabase/migrations/001_initial_schema.sql
```
Or execute the script in the **Supabase Dashboard -> SQL Editor**.

---

## 6. Row Level Security (RLS) & Security Policies

Row Level Security is enabled on all tables:
* **`categories` & `products`:** Public read access (`SELECT true`) so all campus visitors can view catalog items. Modifications (`INSERT`, `UPDATE`, `DELETE`) are restricted to `service_role` and administrative users.
* **`users`:** Customers can only read and update their own profile (`auth.uid() = id`).
* **`orders`:** Customers can only read orders belonging to their account (`auth.uid() = user_id`).
* **`order_items`:** Customers can only view order items belonging to their own orders.

---

## 7. Atomic Transactional Checkout (`place_order_transaction`)

To prevent race conditions and concurrent overselling, checkout uses an atomic PostgreSQL stored procedure:
```sql
SELECT place_order_transaction(
    p_order_number := 'CK-2026-XXXX',
    p_user_id := 2,
    p_customer_name := 'Rohan Sharma',
    p_email := 'rohan@college.edu',
    p_phone := '+91 9812345678',
    p_address := 'Hostel B',
    p_city := 'Bangalore',
    p_state := 'Karnataka',
    p_pincode := '560064',
    p_delivery_charge := 0.00,
    p_payment_method := 'Cash on Delivery',
    p_items := '[{"product_id": 1, "quantity": 1}]'::jsonb
);
```

**Guarantees:**
1. **Row-level locking:** Employs `SELECT ... FOR UPDATE` on each product row before validation.
2. **Server-side price verification:** Unit prices and line subtotals are calculated from database records, preventing client tampering.
3. **Atomic rollback:** If any item lacks sufficient stock, the entire transaction rolls back; no order record is created, and inventory is untouched.

---

## 8. Quick Start & Local Development

### Option A: Running with Standalone PHP Server
1. Clone the repository and configure `.env`:
   ```bash
   cp .env.example .env
   ```
2. Start the built-in PHP development server:
   ```bash
   php -S 127.0.0.1:8000
   ```
3. Visit `http://127.0.0.1:8000` in your web browser.

### Option B: Database Status Inspection
Run the verification tool from your terminal:
```bash
php database/setup.php
```

Output:
```text
✓ Successfully connected to database [Engine: supabase-postgresql].
✓ Found 5 active tables: categories, order_items, orders, products, users
Database Content Summary:
- Categories: 5
- Products:   10
- Users:      2
- Orders:     2
✓ Setup and database integrity checks completed successfully!
```

---

## 9. Default Login Credentials

| Role | Email Address | Password | Permissions |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@campuskart.com` | `Admin@123` | Full access to Admin Portal (`/admin/`), product CRUD, order transitions |
| **Student Demo** | `rohan@college.edu` | `Student@123` | Browsing catalog, shopping cart, checkout, viewing order history |

> **Tip:** The login screen provides quick one-click autofill buttons (`Student Demo` and `Admin Demo`) for fast lab evaluation.

---

## 10. Automated Verification Suite

Execute the automated verification test suite:
```bash
php database/test_runner.php
```

Output:
```text
========================================================
   CampusKart - Automated Verification Suite           
   Active Backend: Supabase PostgreSQL (Cloud)
========================================================

  [PASS] Database connection & tables exist
  [PASS] Categories seeded (Count = 5)
  [PASS] Catalog products seeded (Count >= 10)
  [PASS] Admin account exists (admin@campuskart.com)
  [PASS] Admin password hash validates
  [PASS] Student account exists (rohan@college.edu)
  [PASS] Student password hash validates
  [PASS] Cart subtotal calculation correct (Rs 1347.00)
  [PASS] Free delivery applied for order > Rs 500
  [PASS] Atomic RPC checkout transaction succeeded
  [PASS] Inventory decremented atomically (-2)
  [PASS] Owner can access their order
  [PASS] Unauthorized user blocked from order

Verification Summary: 13 / 13 Tests Passed.
```

---

## 11. Troubleshooting & Common Errors

* **SSL certificate error (`unable to get local issuer certificate`):**
  - Occurs on Windows if PHP does not have a configured `curl.cainfo`.
  - `config/supabase.php` automatically scans and binds Git's system CA bundle (`ca-bundle.crt`). You can also specify `curl.cainfo = "C:\path\to\cacert.pem"` in your `php.ini`.
* **Missing Supabase environment variables:**
  - If `.env` is absent, the system gracefully falls back to local MySQL or self-migrating SQLite.
* **401 Unauthorized on PostgREST:**
  - Verify that `NEXT_PUBLIC_SUPABASE_ANON_KEY` and `SUPABASE_SERVICE_ROLE_KEY` are accurately copied without trailing spaces.

---

## 12. Academic Project Submission

Detailed Web Technology documentation and system architectural diagrams are available in [`docs/PROJECT_REPORT.md`](docs/PROJECT_REPORT.md).