# DataBridgeApi — Operational Context

## Stack
- PHP pure (no frameworks)
- CSS pure (no CDN, no external libs)
- JS minimal (only where unavoidable)
- MySQL + migrations only

## Critical Rules
- NEVER alter DB schema without migration files
- NEVER modify production data directly
- NEVER use external CDN or third-party libraries
- NEVER push directly to `main`
- ALL API changes require versioning (`/api/v1/`)
- ALL inputs must be sanitized and validated
- ALL errors must be logged

## Team Roles
| Role | Responsibility |
|------|----------------|
| architect | System design, module boundaries, scaling |
| backend-engineer | PHP, API endpoints, DB, migrations |
| planner | Roadmap, milestones, sprint tasks |
| tester | Unit/integration tests, QA checklists |
| designer | UI/UX, CSS components, style consistency |
| security-engineer | Auth, CSRF, XSS, session, input validation |
| docs-engineer | Obsidian docs, API docs, changelogs |
| performance-engineer | Query optimization, caching, load |
| repo-manager | Git flow, branches, merges, sync |

## Memory Files
- `memory/Done.md` — completed work log
- `memory/Next.md` — immediate next steps
- `memory/Owner.md` — status summary for project owner

## Architecture Docs
All architecture lives in Obsidian:
`C:\Users\zalis\OneDrive\Documents\DataBridgeApi`
Access via MCP Obsidian. Always check before making architectural decisions.

## Git Flow
```
main ← protected
dev  ← integration branch
feature/xxx ← all work happens here
hotfix/xxx  ← urgent production fixes
```

## DB Migrations
Path: `/database/migrations/`
Format: `YYYY_MM_DD_HHMMSS_description.php`
Run: before any schema-related code goes live.

## Project Vision
MVP v1 → central data management panel for site groups.
Architecture must support incremental growth without rewrites.
