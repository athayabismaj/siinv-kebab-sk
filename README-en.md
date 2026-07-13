<div align="center">
  <img src="public/favicon.svg" alt="Kebab SK Logo" width="100" />
  <h1>Kebab SK - SIINV</h1>
  <p><b>Cloud-Based Point of Sales & Supply Chain Management System</b></p>
  
  [![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
  [![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
  [![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=flat-square&logo=alpine.js&logoColor=white)](https://alpinejs.dev/)
  [![PostgreSQL](https://img.shields.io/badge/PostgreSQL-316192?style=flat-square&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
  
  <br />
  <br />
  
  **[🇮🇩 ID](README.md)** &nbsp;&middot;&nbsp; **[🇬🇧 ENG](README-en.md)**
</div>

---

## 📖 About The Project
**SIINV (Inventory System)** is a unified management platform designed specifically for the operational needs of Kebab SK. This system bridges the complexity of the supply chain (raw material stock) with daily cashier transaction recording (Point of Sales). SIINV separates the web-based management panel for owners and admins from the high-performance API backend designed for the mobile cashier application.

---

## ✨ Key Features

### 👑 Owner Panel
- 📊 **Financial Dashboard:** Monitor daily and monthly revenues, alongside real-time sales trends.
- 🔒 **Closing Book:** Validate and archive transaction data per branch into permanent historical records.
- 👥 **HR Management:** Role-Based Access Control (RBAC), employee account creation, and deactivated account archiving.

### 💼 Admin Panel (Operations)
- 📦 **Supply Chain:** Manage raw material data, track stock adjustments, and monitor restock history.
- 🧾 **Product Catalog:** Detailed recipe standardization (BoM - *Bill of Materials*) for automated stock deduction.
- 📋 **Daily Audits:** Track material usage reports, record operational expenses, and process stock transfers between branches.

### 📱 Cashier API (Mobile POS)
- 🚀 **Fast Transactions:** Lightweight and secure JSON checkout processing.
- 🔑 **Layered Authentication:** Bearer Token system featuring OTP-based password recovery.
- 📈 **Shift History:** Cashiers can independently track their daily generated revenue at the end of their shifts.

---

## 🛠️ Architecture & Technology
This project is built on a robust modern web architecture.
- **Backend:** PHP 8.2+, Laravel 11
- **Database:** PostgreSQL (Supabase integration ready)
- **Frontend (Web):** Blade Templates, Tailwind CSS v3, Alpine.js, SweetAlert2
- **Mobile Integration:** RESTful API (JSON Response)

---

## 🚀 Getting Started (Local Installation)

To run this project on your local machine, follow these steps:

1. **Clone the Repository**
   ```bash
   git clone https://github.com/athayabismaj/siinv-kebab-sk.git
   cd siinv-kebab-sk
   ```
2. **Install Dependencies**
   ```bash
   composer install && npm install
   ```
3. **Environment Configuration**
   Copy the environment file, then configure your database credentials (ensure it is set for PostgreSQL).
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. **Database Migration & Seeding**
   *This step is mandatory to initialize basic access roles, the primary admin account, and default payment options.*
   ```bash
   php artisan migrate --seed
   ```
5. **Run the Application**
   Open two terminal windows to run both the frontend compiler and backend server simultaneously.
   ```bash
   npm run dev
   php artisan serve
   ```
   The application will now be accessible in your browser at `http://127.0.0.1:8000`.

---

## 📚 API Endpoint Summary

All API routes are prefixed with `/api/` and require the `Accept: application/json` header.
- `POST /auth/login` — Authentication and session token retrieval.
- `GET /menus` — List of active products available for order.
- `POST /transactions` — Store customer purchase transaction data.
- `GET /revenue/summary` — Automated daily revenue calculation for the cashier.

---
<br />
<div align="center">
  <sub>All rights reserved. Built for <b>Kebab SK</b> operations &copy; 2026.</sub>
</div>
