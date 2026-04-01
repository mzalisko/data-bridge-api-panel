# Next — Immediate Tasks

## Priority Queue

### [TASK-001] Initialize Git repository
- Status: pending
- Agent: repo-manager
- Steps:
  1. Create remote repo (GitHub/GitLab)
  2. Init local repo in M:\Projects\CC\data-bridge-api
  3. Set up .gitignore (PHP, env files, logs)
  4. First commit: project structure
  5. Push to remote

### [TASK-002] Set up Obsidian MCP connection
- Status: pending
- Agent: docs-engineer
- Steps:
  1. Configure Claude Code MCP for Obsidian (port 22360)
  2. Test read/write to vault
  3. Verify agent access to documentation

### [TASK-003] Design database schema (MVP)
- Status: pending
- Agent: architect + security-engineer
- Depends on: TASK-001
- Tables needed: users, sites, site_groups, phones, addresses, prices, social_networks, api_keys, logs

### [TASK-004] Create project file structure
- Status: pending
- Agent: architect + backend-engineer
- Steps:
  1. Create /public, /src, /api, /config, /database, /logs, /tests
  2. Set up autoloader
  3. Create .env.example
  4. Create index.php entry point

### [TASK-005] Design login page UI
- Status: pending
- Agent: designer
- Depends on: TASK-004
- Deliverable: HTML/CSS login page with role-based flow

---
_Update after each sprint planning session._
