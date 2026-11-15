# Sattva Botanical Manufacturing ERP

A production-grade, full-stack Enterprise Resource Planning (ERP) application engineered specifically for **herbal, phyto-chemical, and botanical extraction manufacturing plants**.

Built on **Laravel 12**, **MySQL 8.0**, and **Bootstrap 5**, following Good Manufacturing Practice (GMP) principles, complete electronic Batch Manufacturing Records (BMR), multi-stage mass-balance reconciliation, solvent recovery tracking, role-based access control (RBAC), and full audit logging.

---

## 1. Credentials & Quick Start

### Super Admin Credentials
- **URL**: `http://localhost:8000/login`
- **Username**: `admin`
- **Email**: `admin@sattvabotanical.local`
- **Password**: `Admin@12345`

### Prerequisites
- PHP 8.2 or higher (with `pdo_mysql`, `openssl`, `mbstring`, `curl`, `gd` extensions)
- Composer 2.x
- MySQL 8.0+ running on `127.0.0.1:3306`

### Installation Steps

```bash
# 1. Clone repository
git clone https://github.com/deshingesumit5-ctrl/satva_botanical.git
cd satva_botanical

# 2. Install PHP dependencies
composer install

# 3. Environment configuration
cp .env.example .env
# Edit .env and ensure MySQL credentials match your local setup:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=satva_botanical
# DB_USERNAME=root
# DB_PASSWORD=your_password_here

# 4. Generate app key & link storage
php artisan key:generate
php artisan storage:link

# 5. Run database migrations and seeders (loads all masters + Garcinia demo data)
php artisan migrate:fresh --seed

# 6. Verify view compilation
php artisan view:cache
php artisan view:clear

# 7. Run full test suite (35 tests, 495 assertions)
php artisan test

# 8. Start development server
php artisan serve
```

---

## 2. Core Modules & Capabilities

### 1. Security & RBAC (`/admin`)
- **Roles & Permissions**: 13 pre-configured functional roles with granular permission matrix (`view`, `create`, `edit`, `delete`, `approve`).
- **User Management**: Department mapping, plant assignments, password hashing, and active/inactive status toggles.
- **Audit Trail**: Immutable logs capturing user ID, module, action, IP address, and JSON diffs of changes.
- **Rate Limiting**: Throttled login protection (5 attempts per minute).
- **Confirmation Banner**: Standardized 380×380px square confirmation banner modal with dim backdrop and CSRF-protected POST/DELETE actions.

### 2. Business Masters (`/masters`)
- **Plants & Warehouses**: Multi-plant operational structure with warehouse zoning (`Raw Material`, `Solvent`, `WIP`, `Finished Goods`, `Quarantine`, `Rejection`).
- **Storage Locations**: Granular Bin / Rack / Pallet capacity tracking with visual occupancy charts.
- **Materials & Solvents**: Inward lot tracking, minimum safety thresholds, base & secondary UOM conversion factors.
- **Finished Goods**: Finished extract definitions with standardized assay specifications, shelf-life, and storage guidelines.
- **Process Flow Masters**: Stages (Extraction, Filtration, Concentration, Precipitation, Drying, Milling, Blending), Activities, Equipment with preventive maintenance schedules, and Quality Parameters.
- **Suppliers & Customers**: Vendor management, GSTIN validation, and customer credit limit tracking.

### 3. Master Recipe Builder (`/recipe`)
- **8-Step Interactive Wizard Flow**:
  1. Header & Standard Batch Size
  2. Sequential Stage Flow
  3. Bill of Materials (BOM) & Solvents
  4. Operational Activities & Time Limits
  5. Critical Quality Parameters & Tolerances
  6. Equipment Allocations
  7. Solvent Recovery & Waste Targets
  8. Final Review, Clone Version & Two-Person Approval Workflow.
- **Zero Product Hardcoding**: Any botanical manufacturing process can be configured entirely through the UI.

### 4. Purchase & Inward Management (`/purchase`)
- **Purchase Requisitions (PR)**: Multi-level departmental requisition and approval workflows.
- **Purchase Orders (PO)**: Automated numbering, supplier terms, GST split, and printable PO documents.
- **Goods Receipt Notes (GRN)**: Vehicle tracking, invoice verification, inward lot creation, and automated QC quarantine tagging.

### 5. Multi-Warehouse Inventory (`/inventory`)
- **Stock Balance & Lots**: Real-time batch-level inventory tracking with expiration date alerts and FIFO allocation.
- **Stock Ledger**: Immutable audit of inward, outward, transfer, and adjustment transactions.
- **Stock Transfers & Adjustments**: Safe multi-warehouse stock relocations with discrepancy approvals.
- **Registers**: Work-in-Progress (WIP), Solvent Recovery, and Solid/Liquid Waste logs.

### 6. Production & Batch Execution (`/production`)
- **Production Planning (MRP)**: Planned production schedules linked to active recipes with automated material shortage analysis.
- **Batch Initiation & Material Allocation**: Direct batch launch from approved plans with strict lot reservation (QC Approved & non-expired lots only).
- **Stage-by-Stage Execution**: Real-time recording of operator signatures, parameter readings (temperature, vacuum, pH), solvent charges, and actual output yields.
- **Mass Balance Reconciliation**: Dynamic mass-balance calculations displaying unaccounted difference against configurable category tolerances without hardcoded limits.
- **Batch Manufacturing Record (BMR)**: Compliant BMR summary with one-click PDF generation via DomPDF.
- **Rework Processing**: Traceable rework batch initiation for non-conforming batches.

### 7. Quality Control Gate (`/qc`)
- **3-Tier QC Architecture**: Raw Material Quarantine, In-Process QC testing between stages, and Finished Goods Release.
- **Test Result Entry**: Quantitative (numeric limits) and Qualitative (Pass/Fail) result evaluations.
- **Approval / Rejection / Rework Decisions**: Immediate status synchronization with inventory lots.

### 8. Sales & Commercial (`/sales`)
- **Sales Orders**: Customer ordering with automated credit limit checks.
- **Finished Goods Dispatch**: Physical dispatch validation restricted to QC Approved lots.
- **Tax Invoices**: GST compliance (CGST, SGST, IGST) with PDF print templates.
- **Payments & Receipts**: Vendor payments and customer receipts updating document balances dynamically.

### 9. Accounts & Costing (`/costing`)
- **Monthly Overhead Expenses**: Pooling of direct labor, electricity, biomass fuel, maintenance, and testing expenses.
- **Cost Allocation Engine**: Proportionate distribution of overhead costs across completed manufacturing batches.
- **Comprehensive Batch Costing**: Breakdown of raw materials, net solvent losses, and allocated overheads into total batch cost and net cost per kilogram.

### 10. Management Reports (`/reports`)
- Comprehensive MIS reporting for Production, Material Consumption, Quality Control, Solvent Recovery, Commercial GST, and Product Costing with CSV and PDF export options.

---

## 3. Technology Stack

- **Backend Framework**: Laravel 12.12.2
- **Language**: PHP 8.2.12
- **Database**: MySQL 8.0 (InnoDB, foreign keys, utf8mb4)
- **Frontend**: Blade templates, Bootstrap 5.3, Bootstrap Icons, Chart.js, SortableJS
- **PDF Engine**: `barryvdh/laravel-dompdf` (DomPDF 3.1)
- **Testing**: PHPUnit 11.5 (Feature and Unit tests)

---

## 4. Documentation References

- `docs/ASSUMPTIONS.md` — Detailed documentation of configurable defaults, mass balance engine rules, and design choices.
- `docs/TRACEABILITY.md` — Step-by-step checklist matching all Steps 1–33 and Business Validations 1–25 against the codebase.
- `docs/Sattva_Botanical_DEVELOPMENT_IMPLEMENTATION_DOCUMENT.docx` — Original specification and functional requirements document.
