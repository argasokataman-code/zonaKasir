# zonaKasir Documentation

Complete documentation for the zonaKasir POS application. All resources are organized by domain and purpose.

---

## 📚 Documentation Sections

### 🏗️ **Architecture & Visual Docs** (NEW ✅)
- [Architecture Overview](architecture/OVERVIEW.md) — System architecture, layer stack, request lifecycle, branch topology
- [DB Schema (ERD)](architecture/DB_SCHEMA.md) — 7 entity-relationship diagrams (sales, inventory, members, payments, auth)
- [Business Flowcharts](architecture/FLOWCHART.md) — POS transaction, auth, stock opname, purchasing, receivable, Midtrans flows

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
- [Performance Optimization PRD](planning/PERFORMANCE_OPTIMIZATION_PRD.md) — Load times, SQL, caching
- [Repo Architecture Guide](planning/REPO_ARCHITECTURE.md) — Dual-branch strategy (main vs vercel), runtime, Vercel CLI
- [On-Prem Deploy Plan](planning/ONPREM_DEPLOYMENT_PLAN.md) — On-prem single-tenant deployment
- [On-Prem License Plan](planning/ONPREM_LICENSE_PLAN.md) — License + heartbeat monitoring

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
**Related:** [AGENTS.md](../../AGENTS.md) | [QUICK_FIXES.md](guides/QUICK_FIXES.md) | [On-Prem Runbook](onprem/RUNBOOK.md)