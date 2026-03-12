# Stock Management System

A multi-role PHP and MySQL stock platform for inventory control, supplier coordination, point-of-sale operations, customer ordering, guest bulk checkout, and SSLCommerz-based payment flows.

This codebase now includes five major user experiences:

- Admin operations
- Staff operations
- Supplier portal
- Registered customer portal
- Guest bulk-order flow from the landing page

## Overview

The project started as a classic stock management system for Admin, Staff, and Supplier users. It has now been expanded with a public landing page, customer registration, guest ordering, notification dots, AI-assisted customer support, membership payments, and customer-order processing.

Recommended entry points:

- `http://localhost/stock/home.php` for the public landing page
- `http://localhost/stock/index.php` for the role-based login page

## Main Modules

### 1. Public Landing Page

The public homepage is `home.php`.

It includes:

- Guest ordering flow with modal-based checkout
- Role selection cards for Admin, Staff, Supplier, and Customer
- Pro Customer registration option
- VIP Customer registration option
- Product search from the homepage
- Notification-dot indicators for admin, staff, supplier, and customer activity
- About/partnership section

### 2. Admin Panel

The admin module handles the full operational side of the business.

Key capabilities:

- Dashboard and analytics
- Product CRUD and product image support
- Supplier CRUD
- User management
- Customer overview and customer invoice handling
- Purchase order creation and status tracking
- Sell-product workflow with invoice generation
- Approve or cancel customer orders
- Grant customer order and status actions
- Payment success handling and supplier invoice generation

Relevant files include:

- `admin/dashboard.php`
- `admin/analytics.php`
- `admin/products.php`
- `admin/customers.php`
- `admin/customer_invoices.php`
- `admin/approve_customer_order.php`
- `admin/cancel_customer_order.php`
- `admin/generate_customer_invoice.php`

### 3. Staff Panel

The staff module focuses on operational execution.

Key capabilities:

- View dashboard metrics
- Sell products
- View suppliers and purchase orders
- Manage customer orders
- Approve, ship, and deliver customer orders
- Request product replenishment
- Acknowledge notification flows

Relevant files include:

- `staff/dashboard.php`
- `staff/customer_orders.php`
- `staff/approve_customer_order.php`
- `staff/ship_customer_order.php`
- `staff/deliver_customer_order.php`
- `staff/process_product_request.php`

### 4. Supplier Portal

The supplier module is used to track and respond to supply-side requests.

Key capabilities:

- View dashboard stats
- Monitor pending, delivered, and returned orders
- Mark deliveries complete
- Respond to supplier-side order requests
- Generate supplier invoices

Relevant files include:

- `supplier/dashboard.php`
- `supplier/respond_to_order.php`
- `supplier/pending_orders.php`
- `supplier/delivered_orders.php`
- `supplier/returned_orders.php`

### 5. Customer Portal

The customer module adds a full customer-facing experience.

Key capabilities:

- Customer login and dashboard
- Product browsing with customer-type discount logic
- Cart-based ordering
- Membership purchase and status tracking
- AI assistant chat
- Order tracking and invoice generation
- Profile management
- Support section
- Notification center

Relevant files include:

- `customer/dashboard.php`
- `customer/products.php`
- `customer/place_order.php`
- `customer/my_orders.php`
- `customer/membership.php`
- `customer/ai_assistant.php`
- `customer/profile.php`
- `customer/support.php`

### 6. Guest Ordering

Guests can place orders directly from `home.php` without creating a full account.

Guest-order workflow:

- Enter name and phone number
- Verify OTP
- Select products in the guest modal
- Meet guest minimum-order rules
- Pay through SSLCommerz
- Receive success/fail/cancel handling

Relevant files include:

- `guest_checkout.php`
- `guest_order_api.php`
- `guest_order_success.php`
- `guest_payment_success.php`
- `guest_payment_fail.php`
- `guest_payment_cancel.php`

## Roles and Access

The system now supports these authenticated roles in `users.role`:

- `admin`
- `staff`
- `supplier`
- `customer`

Guest checkout exists outside the normal login flow.

### Admin

- Full CRUD over users, products, suppliers, and purchase orders
- Customer overview and customer-order administration
- Invoice generation and payment oversight
- Dashboard visibility across the platform

### Staff

- Product sales and day-to-day order handling
- Customer-order approval, shipment, and delivery actions
- Product-request workflow

### Supplier

- Supplier order visibility
- Delivery updates and response actions

### Customer

- Customer dashboard, ordering, membership, AI help, invoices, and profile

### Guest

- Public OTP-verified bulk ordering with checkout from the landing page

## Customer Types and Pricing Logic

The landing page and customer registration flow expose these customer options:

### Guest Customer

- Orders without full account login
- Minimum 10 units per product
- Minimum 100 total stocks per order
- Discount rule: `BDT 1000` off for every `100` stocks ordered
- Checkout through SSLCommerz

### Pro Customer

- Registration fee: `BDT 100`
- Base product discount: `5%`
- Bulk discount: `15%`
- Bulk threshold: `50` stocks
- Minimum per product: `20`

### VIP Customer

- Registration fee: `BDT 500`
- Base product discount: `10%`
- Bulk discount: `20%`
- Bulk threshold: `70` stocks
- Minimum per product: `10`

### Membership Layer

Registered customers also have a membership purchase flow in `customer/membership.php`.

Membership logic currently includes:

- Membership fee payments through SSLCommerz
- Member status tracking in the `customers` table
- Membership payment history in `membership_payments`
- Additional membership messaging and dashboard visibility

## Core Features

### Inventory and Sales

- Product management with stock counts
- Supplier linking to products
- Stock-aware selling for admin and staff
- Sales history and invoice generation
- Purchase order lifecycle management

### Customer Commerce

- Public landing page with login and registration paths
- Customer registration with payment-based onboarding
- Cart and checkout flows for customers
- Guest checkout for bulk orders
- Customer invoice generation
- Customer order status tracking

### Notifications and Workflow Automation

- Notification dots per user type
- Automated customer notifications
- AI chat message storage
- Staff product-request workflow
- Supplier-order workflow for admin-to-supplier communication

### Payment Integration

SSLCommerz is used in multiple places:

- Admin purchase-order payment flow
- Customer registration payment
- Customer membership payment
- Customer checkout/payment flow
- Guest checkout/payment flow

Payment configuration files:

- `includes/sslcommerz_config.php`
- `includes/sslcommerz_helper.php`
- `includes/sslcommerz_config_local.php.example`

## Tech Stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.2+ |
| Database | MariaDB / MySQL |
| DB access | MySQLi |
| Frontend | HTML, CSS, Vanilla JavaScript |
| Fonts | Google Fonts (Poppins) |
| Icons | Font Awesome 6.5.1 |
| Payments | SSLCommerz |
| Local environment | XAMPP |

## Database Structure

Base schema is stored in `stock_management_system.sql`.

Additional schema changes are split into migration-style SQL files:

- `customer_schema_updates.sql`
- `guest_membership_schema.sql`
- `notification_dots_schema.sql`
- `add_product_images.sql`
- `update_customer_role.sql`
- `update_charger_image.sql`

### Core Tables

- `users`
- `products`
- `suppliers`
- `purchase_orders`
- `sell_product`
- `staff_orders`
- `admin_payments`

### Customer and Guest Tables

- `customers`
- `customer_orders`
- `customer_cart`
- `membership_payments`
- `guest_customers`
- `guest_orders`
- `guest_order_items`

### Automation and Communication Tables

- `ai_chat_messages`
- `automated_notifications`
- `notification_dots`
- `product_requests`
- `supplier_orders`

## Demo Credentials

Default role accounts from the base project:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@stock.com` | `123` |
| Staff | `staff@stock.com` | `123` |
| Supplier | `supplier@stock.com` | `123` |

Customer accounts should be created through the customer registration flow, or manually seeded after the customer schema has been applied.

Guest users do not need a password-based login.

## Project Structure

```text
stock/
+-- home.php
+-- index.php
+-- login.php
+-- logout.php
+-- config.php
+-- create_users.php
+-- guest_checkout.php
+-- guest_order_api.php
+-- stock_management_system.sql
+-- customer_schema_updates.sql
+-- guest_membership_schema.sql
+-- notification_dots_schema.sql
+-- add_product_images.sql
+-- update_customer_role.sql
+-- admin/
+-- customer/
+-- staff/
+-- supplier/
+-- includes/
+-- assets/
```

### Important Folders

- `admin/` contains admin dashboards, customer management, invoices, analytics, and order actions
- `customer/` contains registration, login, dashboard, products, orders, profile, support, AI assistant, and payment callbacks
- `staff/` contains operational selling and customer-order handling
- `supplier/` contains supplier-facing order and delivery pages
- `includes/` contains shared header/footer, notification helpers, SSLCommerz helpers, and utility logic
- `assets/images/` contains homepage and product images

## Installation and Setup

### Prerequisites

- XAMPP or another PHP + MySQL local stack
- PHP 8.2 or newer
- MySQL or MariaDB
- Browser access to `localhost`

### 1. Place the project in your web root

Example for XAMPP:

```bash
git clone https://github.com/your-username/stock-management-system.git
cd C:/xampp/htdocs/stock
```

Or copy the folder manually into `C:/xampp/htdocs/stock`.

### 2. Create the database

Open phpMyAdmin and create:

- Database name: `stock_management_system`

### 3. Import SQL files

Run these SQL files in order:

1. `stock_management_system.sql`
2. `customer_schema_updates.sql`
3. `guest_membership_schema.sql`
4. `notification_dots_schema.sql`
5. `add_product_images.sql`
6. `update_customer_role.sql`

If needed, apply any optional patch/update SQL files after that.

Important note:

- The Pro/VIP registration flow uses a `pending_registrations` table, but no dedicated SQL migration for that table is currently included in this repository.
- If you want paid customer registration to work end-to-end, create that table manually or add a migration for it before testing `customer/register_pro.php` and `customer/register_vip.php`.

### 4. Configure database connection

Edit `config.php` with your local MySQL settings.

Typical local values:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "stock_management_system";
```

### 5. Configure SSLCommerz

You have three supported options:

1. Edit `includes/sslcommerz_config.php` directly
2. Create `includes/sslcommerz_config_local.php` based on `includes/sslcommerz_config_local.php.example`
3. Set environment variables `SSLCOMMERZ_STORE_ID` and `SSLCOMMERZ_STORE_PASS`

By default the project is configured for sandbox mode.

### 6. Open the app

Recommended URLs:

- `http://localhost/stock/home.php`
- `http://localhost/stock/index.php`

## Suggested User Flow

### Public / Sales Entry

- Open `home.php`
- Choose Guest Order, Pro registration, VIP registration, or role login

### Internal Users

- Admin logs in from `login.php?role=admin`
- Staff logs in from `login.php?role=staff`
- Supplier logs in from `login.php?role=supplier`

### Customers

- Register from `customer/register_pro.php` or `customer/register_vip.php`
- Or log in through `login.php?role=customer`
- Use dashboard, products, orders, AI assistant, and membership pages

## Security Notes

This project is suitable for learning, demos, and local deployment. Before production use, review these issues:

- Legacy role accounts still use plain-text password comparison in `login.php`
- Customer registration code uses hashed passwords, so authentication logic should be standardized
- Multiple forms do not include CSRF protection
- Some credentials and local assumptions are still embedded in code
- Access control should be reviewed carefully after any schema changes
- Payment configuration should be moved to environment-specific secrets

## Notes for Developers

- `home.php` is now the public-facing homepage
- `index.php` is the role-based login screen
- The project contains both legacy inventory flows and newer customer-commerce flows
- Several SQL files are additive migrations and should not be skipped if you want the customer and guest modules to work

## License

No explicit license file is currently included in this repository. Add one before public distribution if needed.
