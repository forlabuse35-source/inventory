# InvenTrack — Inventory Management System

A production-ready, full-featured Inventory Management System built with **PHP**, **MySQL**, and **Vanilla JavaScript**. Features a modern SaaS-style dashboard, role-based access control, product catalog management, real-time stock tracking, and a Point of Sale (POS) interface.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

---

## Features

- **Dashboard** — Overview metrics (total products, stock count, revenue, low-stock alerts) with recent sales and alert tables.
- **Product Management** — Full CRUD for product catalog with SKU, category, pricing, and descriptions.
- **Stock Management** — Add/remove stock quantities, set low-stock thresholds, auto-status updates (In Stock / Low Stock / Out of Stock).
- **Sales & Billing (POS)** — Point of Sale interface with dynamic price calculation, stock validation, automatic inventory deduction, and sales history.
- **User Management** — Secure authentication with bcrypt hashing, Admin/Staff roles, admin account limit (max 4).
- **Modern UI** — Clean, responsive SaaS dashboard with sidebar navigation, modal forms, real-time validation, and card-based metrics.

---

## Prerequisites

| Software | Version | Purpose |
|----------|---------|---------|
| **PHP**  | 8.0+    | Backend runtime |
| **MySQL**| 8.0+ (or MariaDB 10.4+) | Database |
| **Apache** | 2.4+ | Web server (with `mod_rewrite`) |
| **Git**  | 2.x     | Version control |

> This guide is tailored for an **Ubuntu LAMP stack** environment. Adjust commands for your OS as needed.

---

## Installation & Setup

### 1. Install LAMP Stack (Ubuntu)

```bash
# Update packages
sudo apt update && sudo apt upgrade -y

# Install Apache
sudo apt install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2

# Install MySQL
sudo apt install mysql-server -y
sudo systemctl enable mysql
sudo systemctl start mysql

# Install PHP and required extensions
sudo apt install php libapache2-mod-php php-mysql php-mbstring php-xml php-curl -y

# Enable Apache mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 2. Clone the Repository

```bash
cd /var/www/html
sudo git clone <YOUR_REPOSITORY_URL> inventory-management-system
cd inventory-management-system
```

### 3. Configure File Permissions

```bash
# Set ownership to the web server user
sudo chown -R www-data:www-data /var/www/html/inventory-management-system

# Set directory permissions
sudo find /var/www/html/inventory-management-system -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/html/inventory-management-system -type f -exec chmod 644 {} \;
```

### 4. Create the Database

```bash
# Log in to MySQL
sudo mysql -u root

# Inside MySQL shell, run:
CREATE DATABASE inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'inventory_user'@'localhost' IDENTIFIED BY 'YourStrongPassword123!';
GRANT ALL PRIVILEGES ON inventory_db.* TO 'inventory_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5. Import the SQL Schema

```bash
sudo mysql -u root inventory_db < /var/www/html/inventory-management-system/sql/schema.sql
```

This creates all required tables (`users`, `products`, `inventory_stock`, `sales_transactions`) and inserts the default admin user.

### 6. Configure Environment Variables

```bash
cd /var/www/html/inventory-management-system

# Copy the example environment file
cp .env.example .env

# Edit the .env file with your database credentials
nano .env
```

Update the following values in `.env`:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=inventory_db
DB_USER=inventory_user
DB_PASS=YourStrongPassword123!
```

**Security:** The `.env` file is listed in `.gitignore` and will never be committed to version control.

### 7. Configure Apache Virtual Host (Optional)

For a dedicated domain or subdomain, create a virtual host:

```bash
sudo nano /etc/apache2/sites-available/inventory.conf
```

Add the following configuration:

```apache
<VirtualHost *:80>
    ServerName inventory.local
    DocumentRoot /var/www/html/inventory-management-system

    <Directory /var/www/html/inventory-management-system>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/inventory-error.log
    CustomLog ${APACHE_LOG_DIR}/inventory-access.log combined
</VirtualHost>
```

Enable the site and restart Apache:

```bash
sudo a2ensite inventory.conf
sudo systemctl restart apache2
```

### 8. Access the Application

Open your browser and navigate to:

```
http://localhost/inventory-management-system/login.php
```

Or, if you configured a virtual host:

```
http://inventory.local/login.php
```

---

## Default Login Credentials

| Field    | Value        |
|----------|------------- |
| Username | `admin`      |
| Password | `Admin@123`  |

> **Important:** Change the default admin password immediately after your first login by editing the user via the Users management page.

---

## Project Structure

```
inventory-management-system/
├── css/
│   └── style.css              # Complete stylesheet (responsive)
├── js/
│   └── app.js                 # Core JavaScript (sidebar, modals, validation, POS)
├── includes/
│   ├── config.php             # Environment loader & app constants
│   ├── db.php                 # PDO database connection
│   ├── auth.php               # Authentication & session management
│   ├── helpers.php            # Utility functions (sanitize, format, badges)
│   ├── header.php             # HTML header, sidebar, top navigation
│   └── footer.php             # HTML footer & script loader
├── sql/
│   └── schema.sql             # Complete MySQL schema & seed data
├── index.php                  # Dashboard (homepage)
├── login.php                  # Login page
├── logout.php                 # Logout handler
├── products.php               # Product catalog management
├── stock.php                  # Stock/inventory management
├── sales.php                  # Sales/billing & POS interface
├── users.php                  # User management (admin only)
├── .env                       # Environment config (git-ignored)
├── .env.example               # Environment template
├── .gitignore                 # Git ignore rules
└── README.md                  # This file
```

---

## User Roles & Permissions

| Feature               | Admin | Staff |
|-----------------------|-------|-------|
| View Dashboard        | Yes   | Yes   |
| Manage Products       | Full CRUD | Add, Edit, View |
| Delete Products       | Yes   | No    |
| Manage Stock          | Yes   | Yes   |
| Create Sales          | Yes   | Yes   |
| View Sales History    | Yes   | Yes   |
| Manage Users          | Yes   | No    |

**Admin Limit:** A maximum of **4 admin accounts** can exist at any time. The system enforces this when creating or promoting users.

---

## Database Schema

### Tables

- **`users`** — User accounts with bcrypt-hashed passwords, roles (admin/staff), and status.
- **`products`** — Product catalog with SKU, name, category, description, and base price.
- **`inventory_stock`** — Stock levels per product with quantity, low-stock threshold, and auto-calculated status.
- **`sales_transactions`** — Sales records with product, quantity, unit price, total, and processing staff.

### Relationships

- `products.created_by` → `users.id` (FK, RESTRICT)
- `inventory_stock.product_id` → `products.id` (FK, CASCADE)
- `sales_transactions.product_id` → `products.id` (FK, RESTRICT)
- `sales_transactions.processed_by` → `users.id` (FK, RESTRICT)

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| **Blank page** | Enable `APP_DEBUG=true` in `.env` to see error details |
| **Database connection failed** | Verify `.env` credentials match your MySQL setup |
| **Permission denied** | Re-run the `chown` and `chmod` commands from step 3 |
| **PHP errors** | Ensure `php-mysql` and `php-mbstring` extensions are installed |
| **Apache 403 Forbidden** | Check `AllowOverride All` is set in your Apache config |

---

## License

This project is open-source and available under the [MIT License](https://opensource.org/licenses/MIT).
