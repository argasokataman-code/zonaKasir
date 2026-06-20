# zonaKasir — Database Schema (ERD)

> Entity-Relationship Diagram for tenant database.  
> All tables use `tenant_id` column for multi-tenant isolation.

---

## 1. Core Business Entities

```mermaid
erDiagram
    Category ||--o{ Product : "has many"
    Product ||--o{ SellingDetail : "appears in"
    Product ||--o{ Stock : "has stock"
    Product ||--o{ CartItem : "in cart"
    Product ||--o{ StockOpnameItem : "counted in"
    Product ||--o{ PriceUnit : "has pricing"
    Product ||--o{ ProductImage : "has images"
    Product ||--o{ Barcode : "has barcode"

    Category {
        int id PK
        string name
        string code UK
    }

    Product {
        int id PK
        int category_id FK
        string name
        string sku UK
        string barcode UK
        decimal initial_price
        decimal selling_price
        string unit
        text hero_images
        boolean is_active
        datetime expired_at
        datetime deleted_at
    }

    PriceUnit {
        int id PK
        int product_id FK
        string unit
        decimal price
        decimal initial_price
        decimal stock
    }

    ProductImage {
        int id PK
        int product_id FK
        string url
        boolean is_primary
    }
```

---

## 2. Sales & Transaction

```mermaid
erDiagram
    Selling ||--o{ SellingDetail : "contains"
    Selling ||--o| PaymentMethod : "uses"
    Selling ||--o| Member : "by member"
    Selling ||--o| User : "by cashier"
    Selling ||--o| CashDrawer : "from drawer"
    Selling ||--o| Voucher : "applied"
    SellingDetail ||--o| Product : "references"

    Selling {
        int id PK
        string invoice_number UK
        int user_id FK
        int member_id FK "nullable"
        int payment_method_id FK
        int cash_drawer_id FK "nullable"
        int voucher_id FK "nullable"
        decimal total_price
        decimal total_discount
        decimal total_paid
        decimal total_qty
        decimal tax "nullable"
        decimal change
        boolean is_paid
        string note
        datetime date
    }

    SellingDetail {
        int id PK
        int selling_id FK
        int product_id FK
        decimal qty
        decimal price
        decimal discount_price
        decimal total
    }

    CartItem {
        int id PK
        int product_id FK
        int user_id FK
        decimal qty
        decimal price
    }

    CashDrawer {
        int id PK
        string name
        decimal initial_balance
        decimal current_balance
    }
```

---

## 3. Inventory & Purchasing

```mermaid
erDiagram
    Purchasing ||--o{ Stock : "receives"
    Purchasing ||--o| Supplier : "from supplier"
    Stock ||--o| Product : "for product"
    StockOpname ||--o{ StockOpnameItem : "has items"
    StockOpnameItem ||--o| Product : "counts product"

    Purchasing {
        int id PK
        int supplier_id FK "nullable"
        string invoice_number UK
        decimal total_price
        decimal total_qty
        string note
        date date
    }

    Stock {
        int id PK
        int product_id FK
        int purchasing_id FK "nullable"
        decimal stock
        decimal initial_price
        decimal selling_price
        boolean is_ready
        date expired_date "nullable"
    }

    Supplier {
        int id PK
        string name
        string email
        string phone
        string address
        string code UK
    }

    StockOpname {
        int id PK
        string name
        date date
        string note
        string status "draft|completed"
    }

    StockOpnameItem {
        int id PK
        int stock_opname_id FK
        int product_id FK
        decimal system_qty
        decimal actual_qty
        decimal difference
    }
```

---

## 4. Members & Receivables

```mermaid
erDiagram
    Member ||--o{ Receivable : "has"
    Receivable ||--o{ ReceivableItem : "has items"
    Receivable ||--o{ ReceivablePayment : "has payments"
    Receivable ||--o| Selling : "from sale"

    Member {
        int id PK
        string code UK
        string name
        string email
        string phone
        string address
        string identity_number
        decimal point
        decimal total_transaction
    }

    Receivable {
        int id PK
        int member_id FK
        int selling_id FK "nullable"
        decimal total_price
        decimal remaining_debt
        string status "active|paid|overdue"
        date due_date
    }

    ReceivableItem {
        int id PK
        int receivable_id FK
        string description
        decimal price
        date date
    }

    ReceivablePayment {
        int id PK
        int receivable_id FK
        decimal amount
        string payment_method
        date date
        string note
    }
```

---

## 5. Vouchers & Discounts

```mermaid
erDiagram
    Voucher ||--o{ Selling : "applied to"
    Voucher {
        int id PK
        string code UK
        string name
        string type "nominal|percentage"
        decimal value
        decimal min_purchase "nullable"
        decimal max_discount "nullable"
        datetime start_date
        datetime end_date
        int quota "nullable"
        int used_count
        boolean is_active
    }
```

---

## 6. Payments & Settlements

```mermaid
erDiagram
    PaymentMethod ||--o{ Selling : "used in"
    MidtransPayment ||--o| Selling : "for sale"
    Settlement ||--o| MidtransPayment : "settles"
    LedgerEntry ||--o| Settlement : "records"

    PaymentMethod {
        int id PK
        string name
        string code UK
        boolean is_cash
        boolean is_active
        string icon "nullable"
    }

    MidtransPayment {
        int id PK
        int selling_id FK
        string order_id UK
        string transaction_id
        string transaction_status
        decimal gross_amount
        string payment_type
        datetime transaction_time
        json raw_response
    }

    Settlement {
        int id PK
        int midtrans_payment_id FK
        string settlement_id UK
        string status
        decimal amount
        date settlement_date
    }

    LedgerEntry {
        int id PK
        int settlement_id FK "nullable"
        string type "income|expense"
        decimal amount
        string description
        string reference_type
        int reference_id
    }
```

---

## 7. Auth & Users

```mermaid
erDiagram
    User ||--o{ Selling : "creates"
    User ||--o{ Role : "has"
    Role ||--o{ Permission : "has"
    User ||--o| Profile : "has profile"

    User {
        int id PK
        string name
        string email UK
        string password
        string phone "nullable"
        string photo "nullable"
        boolean is_active
        datetime deleted_at
    }

    Role {
        int id PK
        string name UK
        string guard_name
    }

    Permission {
        int id PK
        string name UK
        string guard_name
    }

    Profile {
        int id PK
        int user_id FK
        string address
        string phone
        string avatar
    }
```

---

> **Last Updated:** June 20, 2026  
> **Related:** [Architecture Overview](./OVERVIEW.md) | [Flowcharts](./FLOWCHART.md)
