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

> Setup instructions are provided for both **Ubuntu (LAMP)** and **Windows (XAMPP / Laragon)** environments below.

---

## Installation & Setup

### Windows Setup (XAMPP) — Recommended for Beginners

#### Step 1: Install Prerequisites

1. **Install Git for Windows** (if not already installed)
   - Download from [https://git-scm.com/download/win](https://git-scm.com/download/win).
   - Run the installer. Accept all default options and click **Next** through each screen.
   - Once installed, you can open **Git Bash** or **Command Prompt** to use `git` commands.

2. **Download & Install XAMPP**
   - Go to [https://www.apachefriends.org/](https://www.apachefriends.org/).
   - Click **XAMPP for Windows** — make sure the version says **PHP 8.x** (e.g. PHP 8.2.12).
   - Run the downloaded `.exe` installer.
   - When the component selection screen appears, ensure **Apache**, **MySQL**, and **PHP** are checked (they should be by default).
   - Install to the default directory: `C:\xampp`.
   - Click **Finish** when the installation completes.

#### Step 2: Start Apache & MySQL

1. Open the **XAMPP Control Panel** (search for "XAMPP" in the Windows Start Menu, or run `C:\xampp\xampp-control.exe`).
2. Click the **Start** button next to **Apache** — the status should turn green.
3. Click the **Start** button next to **MySQL** — the status should turn green.
4. If Windows Firewall prompts you, click **Allow Access**.

> **Troubleshooting:** If Apache fails to start, port 80 may be in use. Open XAMPP → Config → Apache (httpd.conf) and change `Listen 80` to `Listen 8080`. Then access the app at `http://localhost:8080/...` instead.

#### Step 3: Clone the Repository

Open **Command Prompt** (press `Win + R`, type `cmd`, press Enter) and run:

```cmd
cd C:\xampp\htdocs
git clone https://github.com/forlabuse35-source/inventory.git inventory-management-system
cd inventory-management-system
git checkout UI
```

This places the project inside XAMPP's web root so Apache can serve it.

#### Step 4: Create the Database via phpMyAdmin

1. Open your browser and go to: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. In the left sidebar, click **New**.
3. In the **Database name** field, type: `inventory_db`
4. From the **Collation** dropdown, select: `utf8mb4_unicode_ci`
5. Click **Create**.
6. Now click on `inventory_db` in the left sidebar to select it.
7. Click the **Import** tab at the top.
8. Click **Choose File** and navigate to: `C:\xampp\htdocs\inventory-management-system\sql\schema.sql`
9. Scroll down and click **Go**. You should see a success message confirming the tables were created.

#### Step 5: Configure the Environment File

In Command Prompt, run:

```cmd
cd C:\xampp\htdocs\inventory-management-system
copy .env.example .env
notepad .env
```

Notepad will open. Update the file to look exactly like this:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=inventory_db
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

# Application Settings
APP_NAME="Inventory Management System"
APP_URL=http://localhost/inventory-management-system
APP_DEBUG=false
```

> **Note:** XAMPP's default MySQL user is `root` with **no password** (leave `DB_PASS=` empty). Save the file (`Ctrl + S`) and close Notepad.

#### Step 6: Open the Application

Open your browser and navigate to:

```
http://localhost/inventory-management-system/login.php
```

You should see the InvenTrack login page. Log in with the default credentials listed in the [Default Login Credentials](#default-login-credentials) section below.

---

### Windows Setup (Laragon) — Alternative

#### Step 1: Install Prerequisites

1. **Install Git for Windows** — Download from [https://git-scm.com/download/win](https://git-scm.com/download/win) and install with default options.

2. **Download & Install Laragon**
   - Go to [https://laragon.org/download/](https://laragon.org/download/).
   - Download the **Full** edition (includes Apache, MySQL, PHP 8.x, and more).
   - Run the installer and accept the default settings. Laragon installs to `C:\laragon` by default.

#### Step 2: Start Services

1. Open **Laragon** from the Start Menu or desktop shortcut.
2. Click the **Start All** button in the Laragon window.
3. Both Apache and MySQL indicators should turn green.

#### Step 3: Clone the Repository

Open **Laragon Terminal** (click the **Terminal** button in the Laragon window) and run:

```cmd
cd C:\laragon\www
git clone https://github.com/forlabuse35-source/inventory.git inventory-management-system
cd inventory-management-system
git checkout UI
```

#### Step 4: Create the Database

**Option A — Using Laragon's HeidiSQL:**
1. In the Laragon window, click **Menu** → **MySQL** → **HeidiSQL**.
2. Click **Open** to connect with the default root user (no password).
3. Right-click on the left panel → **Create new** → **Database**.
4. Name it `inventory_db`, set the collation to `utf8mb4_unicode_ci`, and click **OK**.
5. Select the `inventory_db` database, then go to **File** → **Run SQL file…**
6. Navigate to `C:\laragon\www\inventory-management-system\sql\schema.sql` and open it.

**Option B — Using the Terminal:**
```cmd
mysql -u root -e "CREATE DATABASE inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root inventory_db < C:\laragon\www\inventory-management-system\sql\schema.sql
```

#### Step 5: Configure the Environment File

In the terminal, run:

```cmd
cd C:\laragon\www\inventory-management-system
copy .env.example .env
notepad .env
```

Update the `.env` file:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=inventory_db
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

# Application Settings
APP_NAME="Inventory Management System"
APP_URL=http://localhost/inventory-management-system
APP_DEBUG=false
```

Save and close Notepad.

#### Step 6: Open the Application

Laragon auto-creates a virtual host. Navigate to either:

```
http://inventory-management-system.test/login.php
```

Or the standard URL:

```
http://localhost/inventory-management-system/login.php
```

Log in with the default credentials listed in the [Default Login Credentials](#default-login-credentials) section below.

---

### Windows Troubleshooting

| Issue | Solution |
|-------|----------|
| **Apache won't start (port conflict)** | Another program is using port 80. Change Apache's port in `httpd.conf` to 8080, or stop the conflicting program (Skype, IIS, etc.). |
| **"MySQL shutdown unexpectedly"** | Another MySQL instance may be running. Stop it via Task Manager or Services (`services.msc`), then retry. |
| **Blank page after login** | Edit `.env` and set `APP_DEBUG=true` to see PHP errors. Ensure the `php_pdo_mysql` extension is enabled in `php.ini`. |
| **"Class PDO not found" error** | Open `C:\xampp\php\php.ini`, find `;extension=pdo_mysql` and remove the semicolon (`;`) to enable it. Restart Apache. |
| **Git not recognized** | Restart Command Prompt after installing Git. If still not working, add `C:\Program Files\Git\bin` to your system PATH. |
| **Cannot connect to database** | Double-check that MySQL is running in the XAMPP/Laragon control panel, and that `.env` credentials match. |

---

### Ubuntu Setup (LAMP)

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
