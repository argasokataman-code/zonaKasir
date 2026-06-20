# zonaKasir — Architecture Overview

> System architecture, layer stack, and deployment topology.

---

## 1. System Architecture

```mermaid
graph TB
    subgraph Client["Client Layer"]
        B[Browser POS UI]
        M[Mobile App<br/>Firebase FCM]
    end

    subgraph Web["Web Layer"]
        LB[Load Balancer<br/>VPS / Vercel]
        A[Laravel App<br/>Filament 3 / Livewire 3]
    end

    subgraph Services["Service Layer"]
        MP[Midtrans<br/>Payment Gateway]
        FB[Firebase<br/>Cloud Messaging]
        SMTP[SMTP Mail]
        S3[Storage<br/>Local / S3]
    end

    subgraph DB["Data Layer"]
        DB_P[PostgreSQL 15+]
        RD[Redis<br/>Cache / Queue]
    end

    B -->|HTTPS| A
    M -->|FCM| FB
    A -->|REST| MP
    A -->|HTTP| FB
    A -->|SMTP| SMTP
    A -->|SQL| DB_P
    A -->|TCP| RD
    A -->|File| S3
```

---

## 2. Branch Architecture

```mermaid
graph LR
    subgraph VERCEL["vercel (default)"]
        direction TB
        V1[PHP 8.4]
        V2[PostgreSQL 15]
        V3[Vercel Serverless / Local]
        V1 --- V2 --- V3
    end

    subgraph MAIN["main (legacy/archived)"]
        direction TB
        M1[PHP 8.4]
        M2[PostgreSQL 15]
        M3[VPS Docker]
        M1 --- M2 --- M3
    end

    VERCEL -->|Manual deploy| VERCEL_DEPLOY[.vercel.app]

    style MAIN fill:#e1f5fe,stroke:#01579b
    style VERCEL fill:#fff3e0,stroke:#e65100
```

---

## 3. Laravel Stack Layers

```mermaid
graph TB
    subgraph HTTP["HTTP / CLI"]
        RT[Routes<br/>api.php / tenant.php / web.php]
        MD[Middleware<br/>Auth / RBAC / Tenant]
    end

    subgraph Controller["Controller Layer"]
        API[API Controllers<br/>App\\Http\\Controllers\\Api\\]
        FL[Filament Resources<br/>App\\Filament\\Tenant\\Resources\\]
        LP[Livewire Pages<br/>App\\Filament\\Tenant\\Pages\\]
    end

    subgraph Business["Business Layer"]
        SRV[Services<br/>App\\Services\\]
        PL[Policies<br/>App\\Policies\\]
        EV[Events / Listeners]
    end

    subgraph Data["Data Layer"]
        MDL[Models<br/>App\\Models\\Tenants\\]
        RBAC[Spatie Permission<br/>Roles / Permissions]
        AUDIT[Activitylog<br/>14 models]
    end

    subgraph External["External Integrations"]
        MID[Midtrans Payment]
        FCM[Firebase FCM]
        SSO[Socialite Login]
        PDF[DomPDF Export]
        EXL[Excel Export]
    end

    RT --> MD --> API
    RT --> MD --> FL
    FL --> LP
    API --> SRV
    SRV --> MDL
    SRV --> MID
    SRV --> FCM
    MDL --> RBAC
    MDL --> AUDIT
    PL --> MDL
```

---

## 4. Multi-Tenant Data Model

```mermaid
graph TB
    subgraph CENTRAL["Central DB (shared)"]
        T[tenants<br/>id, name, domain]
        A[admins<br/>super admin users]
        PLANS[plans / subscriptions]
        CP[coupons]
    end

    subgraph TENANT["Tenant DB (per-school)"]
        U[users]
        P[products]
        CT[categories]
        S[sellings]
        M[members]
        V[vouchers]
        ST[stock]
        PR[purchasing]
        R[receivables]
    end

    CENTRAL -->|has many| TENANT
    T -->|scopes| TENANT
```

---

## 5. Request Lifecycle

```mermaid
sequenceDiagram
    actor C as Client
    participant N as Nginx/Vercel
    participant L as Laravel
    participant A as Auth (Sanctum)
    participant R as RBAC (Spatie)
    participant S as Service
    participant DB as Database

    C->>N: HTTP Request
    N->>L: FastCGI / Serverless
    L->>A: Validate Token
    A-->>L: User Identity
    L->>R: Check Permission
    R-->>L: Granted/Denied
    L->>S: Business Logic
    S->>DB: Query
    DB-->>S: Result
    S-->>L: Response Data
    L-->>N: JSON / View
    N-->>C: HTTP Response
```

---

> **Last Updated:** June 20, 2026  
> **Related:** [DB Schema](./DB_SCHEMA.md) | [Flowcharts](./FLOWCHART.md) | [Repo Architecture](../planning/REPO_ARCHITECTURE.md)
