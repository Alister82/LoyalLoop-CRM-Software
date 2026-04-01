<div align="center">
  <img src="https://via.placeholder.com/150/1e1b4b/c7d2fe?text=LoyalLoop" height="120" alt="LoyalLoop Logo" style="border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);" />
  <h1>LoyalLoop Smart CRM</h1>
  <p><strong>Next-Generation AI-Powered Replenishment & CRM System for Retailers</strong></p>

  <p>
    <a href="#features"><img src="https://img.shields.io/badge/Features-Rich-4ade80.svg?style=for-the-badge&logo=appveyor" alt="Features"></a>
    <a href="#tech-stack"><img src="https://img.shields.io/badge/Stack-PHP%20%7C%20MySQL-3b82f6.svg?style=for-the-badge&logo=php" alt="Tech Stack"></a>
    <a href="#license"><img src="https://img.shields.io/badge/License-MIT-8b5cf6.svg?style=for-the-badge" alt="License"></a>
  </p>
</div>

<br/>

## 📖 Overview

**LoyalLoop** is a comprehensive, visually stunning Customer Relationship Management (CRM) and Inventory System designed specifically for modern retail storefronts. It streamlines daily operations, automates stock replenishment using an intelligent prediction engine, and empowers store owners with actionable insights to maximize revenue and minimize stockouts or waste.

From blazing-fast billing to automated expiry alerts and personalized WhatsApp/Email marketing campaigns, LoyalLoop acts as a 24/7 digital assistant for your retail business.

---

## ✨ Key Features

### 🛍️ Smart Point of Sale (POS) & Billing
- **Fast Checkout:** Quick and intuitive interface for generating bills and invoices.
- **Customer Flexibility:** Seamlessly switch between anonymous walk-ins or loyal registered customers during checkout.
- **Real-time Deduction:** Automatically adjusts inventory levels post-transaction.

### 🧠 AI-Powered Replenishment
- **Smart Forecasting:** Utilizes historical sales data and the `prediction_engine` module to accurately forecast future demand.
- **Two-Tier Reorder Logic:** Accurately determines both *what* to order and *who* to order it from based on lead times and dynamic reorder levels.
- **Silent Fallback Mechanism:** Gracefully handles new products seamlessly, without crashing the AI for items lacking extensive historical data.

### 📦 Inventory & Expiry Management
- **Visual Insights:** Monitor stock levels with color-coded, visual low-stock indicators.
- **Proactive Expiry Alerts:** Automatically tracks items nearing expiry (45 days) and surfaces them directly on the dashboard.
- **Clearance Campaigns:** One-click integration to send exclusive tailored discount blasts (e.g., "20% Off") for expiring products to clear inventory efficiently.

### 📢 Targeted Marketing Ecosystem
- **Broadcast Blasts:** Send promotional offers via WhatsApp or Email straight from the LoyalLoop dashboard.
- **Customer Segmentation:** Target your shopper base easily to maintain high customer retention and loyalty.

### 📊 Dynamic, Aesthetic Dashboard
- **Modern UI/UX:** A beautiful, slightly dark-themed, glassmorphic, responsive CSS dashboard inspired by top-tier modern web design.
- **Live Metrics:** Instantly track Today's Sales, Monthly Revenue, Active Products, and Supplier metrics.
- **Quick Action Bar:** Direct access to your most frequently used tasks (Billing, Inventory, Campaigns).

---

## 🛠️ Technology Stack

- **Frontend Core:** HTML5, Modern Vanilla CSS3 (Custom Design System, CSS Variables, Flexbox/Grid Layouts, Micro-animations)
- **Backend Flow:** PHP 8+ (Session-based Auth, Modular Architecture)
- **Database Engine:** MySQL / MariaDB (Relational schema with optimized constraints for rapid data retrieval)
- **Icons & Typography:** FontAwesome & Modern Web Fonts (Inter/Roboto)
- **Data Science:** Custom PHP-based AI Forecasting & Prediction Engine

---

## 🚀 Getting Started

### Prerequisites

To run this application locally, you will need:
- A local web server like Apache or Nginx (e.g., **XAMPP**, **WAMP**, or **MAMP**).
- **PHP** version 8.0 or higher.
- **MySQL** or MariaDB database server.

### Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/LoyalLoop.git
   cd LoyalLoop
   ```

2. **Deploy to local server:**
   Move the project files to your server's root directory. For example, if using XAMPP on Windows:
   `C:\xampp\htdocs\LoyalLooop`

3. **Database Configuration:**
   - Open phpMyAdmin or your MySQL CLI.
   - Create a new empty database named `loyalloop`.
   - Import the provided schema files in the root folder in this order:
     ```bash
     mysql -u root -p loyalloop < database.sql
     mysql -u root -p loyalloop < migration_v2.sql
     ```

4. **Environment Settings:**
   - Update your database credentials in `db_connect.php` if you have a custom MySQL password or username:
     ```php
     $host = "localhost";
     $user = "root";
     $pass = ""; // Your DB password
     $db   = "loyalloop";
     ```

5. **Launch Application:**
   Open your browser and navigate to `http://localhost/LoyalLooop`.
   *You can now register a new admin account or login with existing demo credentials.*

---

## 📂 Project Structure

```text
LoyalLoop/
├── index.php                 # Main analytics & alerts dashboard
├── login.php / register.php  # Secure authentication layer
├── auth_session.php          # Session management middleware
├── billing.php               # POS and invoice generation flow
├── inventory.php             # Comprehensive stock management
├── replenishment.php         # AI Reorder Plan & Suggestion interface
├── prediction_engine.php     # Core ML/Forecasting algorithms
├── customers.php             # Customer CRM & communication portal
├── send_offer.php            # Promotional / Expiry campaign manager
├── suppliers.php             # Vendor management directory
├── style.css                 # Master stylesheet (UI design system)
├── database.sql              # Base database schema
└── migration_v2.sql          # Advanced features schema updates
```

---

## 🤝 Contributing

Contributions are what make the open-source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

1. **Fork** the Project.
2. Create your Feature Branch: `git checkout -b feature/AmazingFeature`
3. Commit your Changes: `git commit -m 'Add some AmazingFeature'`
4. Push to the Branch: `git push origin feature/AmazingFeature`
5. Open a **Pull Request**.

---

## 🛡️ License

Distributed under the **MIT License**. You are free to use, modify, and distribute this software.

---

<div align="center">
  <p>Built with ❤️ for modern retailers.</p>
</div>
