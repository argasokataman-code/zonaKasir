# zonaKasir — Business Flowcharts

> Key business processes visualized with Mermaid.

---

## 1. POS Transaction Flow

```mermaid
flowchart TD
    START([Customer arrives]) --> SCAN[Scan Products<br/>Barcode / Search]
    SCAN --> CART[Add to Cart]
    CART --> MEMBER{Member?}
    MEMBER -->|Yes| SELECT_MEMBER[Select / Search Member]
    MEMBER -->|No| SKIP_MEMBER[Skip]
    SELECT_MEMBER --> DISCOUNT{Apply Discount?}
    SKIP_MEMBER --> DISCOUNT
    DISCOUNT -->|Per Item| ITEM_DISC[Set item discount]
    DISCOUNT -->|Global| GLOBAL_DISC[Set global discount]
    DISCOUNT -->|None| NO_DISC[No discount]
    ITEM_DISC --> VOUCHER{Voucher?}
    GLOBAL_DISC --> VOUCHER
    NO_DISC --> VOUCHER
    VOUCHER -->|Yes| APPLY_VOUCHER[Apply voucher code]
    VOUCHER -->|No| NO_VOUCHER[No voucher]
    APPLY_VOUCHER --> PAYMENT[Select Payment Method]
    NO_VOUCHER --> PAYMENT
    PAYMENT --> PROC_PAYMENT{Payment Type}
    PROC_PAYMENT -->|Cash| CASH[Enter Cash Amount]
    PROC_PAYMENT -->|Transfer| TRANSFER[Show Transfer Info]
    PROC_PAYMENT -->|E-Wallet| EWALLET[QR or Input]
    PROC_PAYMENT -->|Midtrans| MIDTRANS[Redirect to Gateway]
    CASH --> CHANGE[Calculate Change]
    CHANGE --> COMPLETE[Transaction Complete]
    TRANSFER --> COMPLETE
    EWALLET --> COMPLETE
    MIDTRANS --> CALLBACK[Payment Callback]
    CALLBACK --> COMPLETE
    COMPLETE --> PRINT{Print Receipt?}
    PRINT -->|Yes| THERMAL[Print via Web USB<br/>Thermal Printer]
    PRINT -->|No| SKIP_PRINT[Skip]
    THERMAL --> DONE([Done])
    SKIP_PRINT --> DONE
```

---

## 2. Auth & Login Flow

```mermaid
flowchart TD
    START([User visits app]) --> LOGIN{Has Session?}
    LOGIN -->|No| SHOW_LOGIN[Show Login Page]
    LOGIN -->|Yes| DASHBOARD[Redirect to Dashboard]
    SHOW_LOGIN --> CREDENTIALS[Enter Email + Password]
    CREDENTIALS --> VALIDATE{Valid?}
    VALIDATE -->|Invalid| ERROR[Show Error]
    ERROR --> CREDENTIALS
    VALIDATE -->|Valid| CHECK_ROLE{Check Role}
    CHECK_ROLE -->|Admin| ADMIN_DASH[Admin Dashboard<br/>Central]
    CHECK_ROLE -->|Tenant| TENANT_DASH[Tenant Dashboard<br/>School]
    CHECK_ROLE -->|Cashier| CASHIER_POS[Cashier POS Screen]
    ADMIN_DASH --> SESSION([Session Active])
    TENANT_DASH --> SESSION
    CASHIER_POS --> SESSION
    SESSION -->|Logout| LOGOUT[Clear Session]
    LOGOUT --> SHOW_LOGIN
```

---

## 3. Stock Opname Flow

```mermaid
flowchart TD
    START([Start Stock Opname]) --> CREATE[Create Opname Session]
    CREATE --> SET_NAME[Set name + date]
    SET_NAME --> SCAN{Scan Method}
    SCAN -->|Barcode| SCAN_BARCODE[Scan Product Barcode]
    SCAN -->|Manual| SEARCH[Search Products]
    SCAN_BARCODE --> INPUT_QTY[Input Actual Quantity]
    SEARCH --> INPUT_QTY
    INPUT_QTY --> MORE{More Items?}
    MORE -->|Yes| SCAN
    MORE -->|No| REVIEW[Review All Items]
    REVIEW --> VERIFY[System: Calculate Difference]
    VERIFY --> STATUS{All Good?}
    STATUS -->|Yes| COMPLETE[Complete Opname]
    STATUS -->|Has Discrepancy| ADJUST[Adjust Stock]
    ADJUST --> COMPLETE
    COMPLETE --> REPORT([Generate Report])
```

---

## 4. Purchasing Flow

```mermaid
flowchart TD
    START([Need Stock]) --> PO[Create Purchase Order]
    PO --> SELECT_SUPP{Select Supplier}
    SELECT_SUPP -->|Existing| CHOOSE_SUPP[Choose from list]
    SELECT_SUPP -->|New| CREATE_SUPP[Create Supplier]
    CREATE_SUPP --> CHOOSE_SUPP
    CHOOSE_SUPP --> ADD_ITEMS[Add Products + Qty]
    ADD_ITEMS --> SAVE_PO[Save PO]
    SAVE_PO --> RECEIVE[Receive Stock]
    RECEIVE --> CHECK{Match PO?}
    CHECK -->|Yes| CONFIRM[Confirm Receipt]
    CHECK -->|Partial| PARTIAL[Receive Partial]
    CHECK -->|Discrepancy| NOTE[Note Discrepancy]
    CONFIRM --> UPDATE_STOCK[Auto-update Stock<br/>Initialize Prices]
    PARTIAL --> UPDATE_STOCK
    NOTE --> UPDATE_STOCK
    UPDATE_STOCK --> DONE([Purchasing Complete])
```

---

## 5. Receivable Lifecycle

```mermaid
flowchart TD
    START([Sale with Credit]) --> CREATE_RCV[Create Receivable]
    CREATE_RCV --> NOTIFY[Notify Member<br/>Due date set]
    NOTIFY --> STATUS{Payment Status}
    STATUS -->|On Time| PAY[Member Pays]
    STATUS -->|Overdue| REMINDER[Send Reminder]
    REMINDER --> PAY
    PAY --> RECORD[Record Payment]
    RECORD --> CHECK_BAL{Remaining?}
    CHECK_BAL -->|Yes| STATUS
    CHECK_BAL -->|Zero| CLOSE([Receivable Closed])
```

---

## 6. Midtrans Payment Flow

```mermaid
sequenceDiagram
    actor C as Customer
    participant POS as POS System
    participant MT as Midtrans API
    participant Bank as Bank/E-Wallet

    C->>POS: Select Midtrans Payment
    POS->>MT: Create Transaction
    MT-->>POS: Redirect URL / QR
    POS-->>C: Show Payment Page / QR
    C->>Bank: Pay via selected method
    Bank->>MT: Payment Success
    MT->>POS: HTTP Callback (POST)
    POS->>POS: Update Selling Status
    POS-->>C: Receipt / Confirmation

    alt Callback Missed
        POS->>MT: Status Check (GET)
        MT-->>POS: Transaction Status
        POS->>POS: Sync Status
    end
```

---

## 7. Multi-Tenant Request Flow

```mermaid
flowchart LR
    REQ([HTTP Request]) --> DOMAIN{Has Tenant?}
    DOMAIN -->|Central| ROUTE_C[Central Routes<br/>api.php]
    DOMAIN -->|Tenant| ROUTE_T[Tenant Routes<br/>tenant.php]
    ROUTE_C --> AUTH_C[Sanctum Auth]
    ROUTE_T --> AUTH_T[Sanctum Auth]
    AUTH_C --> RBAC_C[RBAC Check]
    AUTH_T --> RBAC_T[RBAC Check]
    RBAC_C --> SCOPED_C[Central Controller]
    RBAC_T --> SCOPE_T[Tenant Scoped<br/>Controller]
    SCOPED_C --> RESPONSE[JSON Response]
    SCOPE_T --> RESPONSE
```

---

> **Last Updated:** June 20, 2026  
> **Related:** [Architecture Overview](./OVERVIEW.md) | [DB Schema](./DB_SCHEMA.md)
