# Architecture Decisions & Assumptions

This document lists all system assumptions, configurable defaults, and architectural decisions made for the **Sattva Botanical Manufacturing ERP**, as mandated by Section 0 of the development specification.

---

## 1. Zero Hardcoding Policy

- **Botanical / Chemical Agnosticism**:
  - The application logic contains zero hardcoded references to specific botanical products, chemicals, extracts, solvents, or stage names.
  - Garcinia Cambogia (Water/Ethanol extraction, HCA 60%) is loaded strictly as demo seed data via `DemoGarciniaSeeder.php` through standard master records (`ProductCategory`, `Product`, `Material`, `Uom`, `Stage`, `Activity`, `Parameter`, `Recipe`, `RecipeStage`, etc.).
  - The system dynamically supports any botanical extraction process (Curcumin, Ashwagandha, Green Tea, Boswellia, Piperine, etc.) defined dynamically through the UI.

- **Process Loss Tolerance (Document Step 25 & Rule 6)**:
  - Numeric process loss percentages are **never hardcoded** in application logic.
  - Tolerances are maintained dynamically in the `process_loss_tolerances` table by Product Category and Stage Type (Extraction, Filtration, Concentration, Precipitation, Drying, Milling, Blending).
  - The mass balance reconciliation engine computes:
    $$\text{Total Input Qty} - (\text{Main Product Qty} + \text{By-Product Qty} + \text{Recovered Solvent Qty} + \text{Waste Qty}) = \text{Unaccounted Difference}$$
  - The calculated unaccounted difference is displayed transparently alongside the category-configured tolerance without arbitrary hardcoded rejection.

---

## 2. Inventory & Stock Valuation

- **Valuation Method**:
  - The default stock valuation method is **FIFO (First-In, First-Out)**, matching standard pharmaceutical and herbal manufacturing GMP guidelines.
  - Stock lots are ordered by inward receipt date (`created_at` / `receipt_date`) and lot ID.
  - Stock ledger entries (`stock_ledgers`) record both physical quantity and valuation rate (`unit_cost`) for every inward, issue, transfer, return, and adjustment.

- **Storage Hierarchy**:
  - `Plant` $\rightarrow$ `Warehouse` $\rightarrow$ `StorageLocation` (Bin / Rack / Pallet).
  - Warehouses are segmented by functional types: `Raw Material`, `Chemical & Solvent`, `Work in Progress`, `Finished Goods`, `Quarantine`, `Rejection`.

- **QC Gate on Stock Lots**:
  - When raw materials or intermediate products require QC inspection, the created `StockLot` is marked as `Quarantine` / `QC Pending`.
  - Only lots with `qc_status = 'QC Approved'` are eligible for production allocation or customer dispatch.

---

## 3. Production & Batch Execution

- **Standard Batch Size Scaling**:
  - Standard recipes are defined for a canonical batch size (e.g. 1,000 kg).
  - When launching a production batch with a planned output $Q$, all recipe stage input quantities and parameter targets are scaled proportionately:
    $$\text{Required Quantity} = \text{Recipe Input Qty} \times \left(\frac{Q}{\text{Recipe Standard Batch Size}}\right)$$

- **Batch Numbering**:
  - Default batch format is generated dynamically via `DocumentNumberService`: `BAT-YYYYMM-XXXX` (e.g. `BAT-202611-0001`), configurable in System Settings $\rightarrow$ Document Numbering.

- **Stage Execution Lifecycle**:
  - Stages progress sequentially: `Pending` $\rightarrow$ `In Progress` $\rightarrow$ `QC Inspection` $\rightarrow$ `Completed`.
  - Operators record start/end timestamps, actual input lots consumed, environmental/operational parameter readings, output lots generated, recovered solvents, and solid/liquid wastes.

---

## 4. Security, Access Control & Sessions

- **Custom RBAC Matrix**:
  - Standard Laravel session authentication without heavy third-party packages (Spatie).
  - 13 pre-seeded functional roles: Super Admin, Plant Admin, Master Admin, Recipe Admin, Recipe Approver, Production Planner, Production Supervisor, Store Manager, Purchase Manager, QC Manager, Sales Manager, Accounts User, Viewer.
  - Granular permissions mapped across modules: `view`, `create`, `edit`, `delete`, `approve`.
  - Super Admin (`role_code = 'super_admin'`) possesses unrestricted access across all routes and actions.

- **Session Timeout**:
  - Default session inactivity timeout is configured at **120 minutes** in `system_settings` (`session_timeout_minutes`).

- **Rate Limiting**:
  - Login attempts are throttled to **5 attempts per minute** per IP address/username to prevent brute-force attacks.

- **Destructive Actions Safety**:
  - All edit, delete, cancel, approve, reject, hold, and close operations require explicit user confirmation through the standardized 380×380px square confirmation banner modal with CSRF-protected POST/DELETE forms.
  - Foreign key safety: Records referenced in existing transactions cannot be hard-deleted; users are offered safe deactivation instead.

---

## 5. Commercial, Costing & Currency

- **Currency**:
  - Indian Rupee (`INR` / `₹`) with standard 2-decimal financial formatting (`d-m-Y` date format).

- **Cost Allocation Engine**:
  - Monthly overhead expenses (Electricity, Fuel & Biomass, Direct Labor, Maintenance, QC Testing, Depreciation, Water, Rent) are pooled and allocated across completed batches based on batch production weight or operating hours.
  - Batch Cost Summary aggregates:
    $$\text{Total Batch Cost} = \text{Raw Material Cost} + \text{Solvent Net Cost} + \text{Overhead Allocation}$$
    $$\text{Unit Cost per KG} = \frac{\text{Total Batch Cost}}{\text{Final Output Quantity}}$$
