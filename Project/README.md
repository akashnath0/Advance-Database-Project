# Bangladesh Railway Management System


---

## 🚀 How to Run

### Prerequisites
- **PHP 8.0+** with OCI8 extension enabled
- **Oracle Database** (XE 21c or ORCL)
- **Apache/Nginx** web server (XAMPP, WAMP, or standalone)

---

### Step 1 — Oracle Database Setup

Open **SQL*Plus** or **SQL Developer** and run:

```sql
-- 1. Run the full schema (creates all tables + sample data)
@db/schema.sql

-- 2. Run the provenance triggers
@db/triggers.sql
```

> 💡 Make sure your Oracle service name matches what's in `includes/db.php`

---

### Step 2 — Configure Database Connection

Edit **`includes/db.php`** and update:

```php
define('DB_HOST',    'localhost');
define('DB_PORT',    '1521');
define('DB_SERVICE', 'XE');         // Your Oracle service: XE or ORCL
define('DB_USER',    'railway');    // Your Oracle username
define('DB_PASS',    'railway123'); // Your Oracle password
```

---

### Step 3 — Deploy to Web Server

Copy this entire `Project/` folder into your web server root:
- **XAMPP**: `C:/xampp/htdocs/Project/`
- **WAMP**:  `C:/wamp64/www/Project/`

---

### Step 4 — Run Setup Page

Open your browser and go to:
```
http://localhost/Project/setup.php
```
This will hash all demo user passwords and verify tables.

---

### Step 5 — Login

```
http://localhost/Project/login.php
```

| Username | Password  | Role   |
|----------|-----------|--------|
| admin    | admin123  | Admin  |
| staff1   | staff123  | Staff  |
| viewer   | view123   | Viewer |

---

## 📁 Project Structure

```
Project/
├── db/
│   ├── schema.sql        ← Full Oracle schema + sample data
│   └── triggers.sql      ← All provenance triggers + views + stored procedures
├── includes/
│   ├── db.php            ← Oracle OCI8 connection
│   ├── auth.php          ← Session authentication
│   ├── functions.php     ← Helper utilities
│   ├── layout_head.php   ← Shared sidebar layout (header)
│   └── layout_foot.php   ← Shared layout (footer)
├── css/
│   └── style.css         ← Full dark premium stylesheet
├── js/
│   └── app.js            ← Frontend JS (modals, AJAX, toasts)
├── pages/
│   ├── dashboard.php     ← Stats overview + live audit feed
│   ├── passengers.php    ← Passenger CRUD
│   ├── trains.php        ← Train fleet management
│   ├── coaches.php       ← Coach management + occupancy bars
│   ├── bookings.php      ← Ticket booking with seat check
│   ├── payments.php      ← Payment records + revenue stats
│   ├── employees.php     ← Employee management
│   ├── routes.php        ← Routes & Schedules side-by-side
│   ├── maintenance.php   ← Maintenance logging
│   ├── technicians.php   ← Technician management
│   ├── audit.php         ← 🔍 Data Provenance Explorer
│   └── reports.php       ← Analytics & provenance coverage
├── api/
│   ├── passengers.php    ← REST API
│   ├── trains.php
│   ├── bookings.php      ← Atomic booking + payment creation
│   ├── employees.php
│   ├── routes.php        ← Routes + Schedules combined
│   ├── coaches.php
│   ├── maintenance.php   ← Auto-sets train to MAINTENANCE
│   ├── technicians.php
│   └── audit.php         ← Read-only provenance queries
├── login.php
├── logout.php
├── index.php
└── setup.php             ← One-click setup (delete after use!)
```

---

## 🧬 Data Provenance Features (Lab 6)

### What is Data Provenance?
The system tracks the **complete history** of every record — who changed it, when, from what to what.

### Implementation

| Feature | Implementation |
|---------|---------------|
| **Audit Tables** | 9 `*_Audit` shadow tables with OLD/NEW value pairs |
| **Triggers** | AFTER INSERT/UPDATE/DELETE on every core table |
| **Session Metadata** | `SYS_CONTEXT('USERENV','SESSION_USER')` and `IP_ADDRESS` |
| **Reverse Queries** | `audit.php?table=X&id=Y` traces full record lineage |
| **Views** | `v_passenger_lineage`, `v_booking_lineage`, `v_train_status_history`, `v_payment_changes` |
| **Stored Procedure** | `sp_get_passenger_history(id)` prints full text lineage |

### Grading Checklist

- ✅ **Triggers (25%)** — 9 triggers on INSERT/UPDATE/DELETE
- ✅ **Audit Structure (15%)** — 9 complete audit tables with OLD/NEW pairs
- ✅ **DML Execution (10%)** — Full CRUD UI that generates audit logs
- ✅ **Advanced Tasks (20%)** — IP address + Session ID captured via `SYS_CONTEXT`
- ✅ **Reverse Queries (20%)** — Timeline explorer per record with field-level diffs
- ✅ **Documentation (10%)** — This README + inline SQL comments

---

## 💎 Frontend Features

- **Dark premium UI** with glassmorphism and gradients
- **Animated stat counters** on dashboard
- **Live audit feed** on dashboard sidebar
- **Real-time table search** on all pages
- **Modal-based CRUD** (no page reloads)
- **Toast notifications** for all operations
- **Occupancy bars** on coaches (visual seat usage)
- **Provenance timeline** with field-level old → new diff view
- **Reports page** with horizontal bar charts for analytics
- **Responsive design** for mobile screens

---

*CSE464 — Database Management Systems | Spring 2026*
