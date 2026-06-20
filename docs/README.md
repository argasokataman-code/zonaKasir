# zonaKasir Documentation

Complete documentation for the zonaKasir POS application. All resources are organized by domain and purpose.

---

## 📚 Documentation Sections

### 🏗️ **Architecture & Visual Docs** (NEW ✅)
- [Architecture Overview](architecture/OVERVIEW.md) — System architecture, layer stack, request lifecycle, branch topology
- [DB Schema (ERD)](architecture/DB_SCHEMA.md) — 7 entity-relationship diagrams (sales, inventory, members, payments, auth)
- [Business Flowcharts](architecture/FLOWCHART.md) — POS transaction, auth, stock opname, purchasing, receivable, Midtrans flows

### 🔍 **Reports & Audits** (6-Phase Comprehensive Audit - COMPLETE ✅)

**Start Here:** [AUDIT_SYNTHESIS.md](reports/AUDIT_SYNTHESIS.md) ⭐ — Master summary of all 6 audit phases with production readiness plan (20 min read)

**Complete Audit Suite:**
- [AUDIT_SYNTHESIS.md](reports/AUDIT_SYNTHESIS.md) — FASE 6: Master synthesis + implementation timeline
- [AUDIT.md](reports/AUDIT.md) — Initial findings & recommendations
- [AUDIT_VERIFICATION.md](reports/AUDIT_VERIFICATION.md) — FASE 2: 100% verified with code evidence + line numbers
- [AUDIT_VERIFICATION_SUMMARY.md](reports/AUDIT_VERIFICATION_SUMMARY.md) — Executive tables & metrics
- [AUDIT_BUSINESS_IMPACT_ANALYSIS.md](reports/AUDIT_BUSINESS_IMPACT_ANALYSIS.md) — FASE 3-4: Role-based impact (Admin/Cashier/Manager/System)
- [AUDIT_VERIFICATION_TESTS.md](reports/AUDIT_VERIFICATION_TESTS.md) — FASE 5: Test suite + verification steps

### 🛠️ **Quick Reference Guides**
- [Quick Fixes Guide](guides/QUICK_FIXES.md) — Priority action list to production readiness
- Includes: Critical fixes (today), high priority (week 1), quick wins, checklists

### 📖 **API Documentation**
- [API Response Standard](API_RESPONSE_STANDARD.md) — Standardized JSON response format
- Coming soon: API endpoint documentation
- Resource: products, users, transactions, reports, etc.

### 🎯 **Feature Specifications**
- Coming soon: Feature documentation by domain
- Sections: Inventory, Sales, Reporting, Multi-tenancy, etc.

### 📋 **Planning & Roadmap**
- [Midtrans Payment Integration](planning/MIDTRANS_PAYMENT_INTEGRATION_PLAN.md) — Complete spec v1.3
- [Rebranding Plan](REBRANDING_PLAN.md) — Lakasir → zonaKasir migration tracking
- [Performance Optimization PRD](planning/PERFORMANCE_OPTIMIZATION_PRD.md) — Load times, SQL, caching
- [Single DB Architecture](planning/SINGLE_DB_ARCHITECTURE.md) — Multi-tenancy shared DB design
- [MySQL → PostgreSQL Migration](planning/MYSQL_TO_POSTGRESQL_MIGRATION.md) — Full migration plan & completion report
- [Repo Architecture Guide](planning/REPO_ARCHITECTURE.md) — Dual-branch strategy (main vs vercel), runtime, Vercel CLI

### 🚀 **Development Guides**
- [Server Access](guides/SERVER_ACCESS.md) — Staging SSH, commands, GitHub Actions
- Coming soon: Setup, deployment, troubleshooting guides

---

## 🔗 Quick Links

**Getting Started:**
- See [../../AGENTS.md](../../AGENTS.md) for code style and build commands
- See [../../README.md](../../README.md) for project overview
- See [.opencode/rules/00-task-framework.mdc](.opencode/rules/00-task-framework.mdc) for 6-phase task framework

**Agent Rules (modular):**
- [.opencode/rules/00-task-framework.mdc](.opencode/rules/00-task-framework.mdc) — 6-phase task execution
- [.opencode/rules/01-code-style.mdc](.opencode/rules/01-code-style.mdc) — Code style & naming
- [.opencode/rules/02-security.mdc](.opencode/rules/02-security.mdc) — Security, CI/CD, git conventions

**Current Status:**
- Test Suite: 🟢 63 test files (46 Feature, 7 Unit, 10+ helpers)
- Code Quality: 🟢 Good (high priority items resolved)
- Production Ready: 🟡 Medium (E2E tests, rate limiting, audit logging remain)

**Immediate Actions:**
1. Fix test database connection ([see Quick Fixes](guides/QUICK_FIXES.md#critical-do-first---today))
2. Remove debug code
3. Add permission checks

---

**Last Updated:** June 13, 2026  
**Maintained by:** Development Team  
**Related:** [AGENTS.md](../../AGENTS.md) | [QUICK_FIXES.md](guides/QUICK_FIXES.md) | [AUDIT_REPORT.md](reports/AUDIT.md)