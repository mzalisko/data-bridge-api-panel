# Site Groups Page — Design Spec

**Date:** 2026-04-03
**Status:** Approved
**Route:** `/site-groups`

---

## Goal

Full-featured kanban board for managing site groups and sites: view, create, edit, delete groups and sites, drag & drop sites between groups, search and filter.

## Architecture

Single controller (`SiteGroupsController`) renders the full page via `Layout::start/end`. All CRUD operations use dedicated POST endpoints. Drag & drop is vanilla JS (no libraries). Modal forms opened via JS class toggle. Real DB queries replace mock data — joins `site_groups` → `sites` → `api_keys`.

**Tech Stack:** Pure PHP 8.x, Pure CSS (existing tokens), Vanilla JS. No frameworks, no CDN.

---

## Layout

### Global structure
- Sidebar (existing `Layout::start`) — global nav, "Site Groups" active
- Topbar (existing) — title + search + bell + theme toggle
- Page toolbar — **2 rows** (below topbar, above board)
- Kanban board — horizontally scrollable

### Page toolbar — Row 1
```
[Site Groups]  [18 груп · 142 сайти]   [spacer]   [+ Нова група btn]
```

### Page toolbar — Row 2
```
[🔍 Пошук сайтів або груп...]  [Всі статуси ▾]  [Сортування ▾]  [Фільтр групи ▾]
```

---

## Kanban Board

### Column (310px wide)
```
┌─────────────────────────────────────────┐
│ Group Alpha            [3]  [⋮]  [+]   │  ← header: name, count, menu, add
├─────────────────────────────────────────┤
│ [site card]                             │
│ [site card]                             │
│ ...                                     │
└─────────────────────────────────────────┘
```
- Width: **310px**
- `[⋮]` → dropdown: Редагувати групу / Видалити групу
- `[+]` → opens "Додати сайт" modal (accent blue button)
- Ghost column at end: `+ Нова група` (dashed border, click = new group modal)

### Drag & Drop
- Drag site card → ghost opacity 0.45, dashed accent border
- Drop zone column → accent border highlight + "↓ Перетягни сюди" hint
- On drop: POST `/api/v1/sites/{id}/move` with `{group_id: N}`
- Vanilla JS: `dragstart`, `dragover`, `drop`, `dragend` events

---

## Site Card

### Collapsed state (default)
```
┌─────────────────────────────────┐
│ site-name.com              [▼]  │
│ https://site-name.com           │
│ [● Підключений]                 │
│ dbapi_a1b2c3•••••••• [copy]     │
└─────────────────────────────────┘
```
- Padding: 13px
- Name: 12px/600
- Domain: 9px, muted
- Connection badge: `conn-badge--ok/pause/off` (existing CSS)
- API key: masked (first 10 chars + `••••••••`), `data-key` = full key
- Copy button: clipboard JS (already in `Layout::end()`)
- **Click card** = expand details (toggle `.is-open`)

### Expanded state (`.is-open`)
```
┌─────────────────────────────────┐
│ [collapsed content above]       │
├─────────────────────────────────┤
│ ТЕЛЕФОНИ          312           │
│ АДРЕСИ            891           │
│ ЦІНИ              2 100         │
│ СИНХРОНІЗАЦІЯ     2026-04-03... │
│ [Налаштування]  [→ Site Panel]  │
└─────────────────────────────────┘
```
- "→ Site Panel" → `/sites/{id}` (primary accent button)
- "Налаштування" → opens "Edit Site" modal

---

## Modal Forms

All modals: fixed overlay, 380px wide, dark bg `#2c313c`, border `--border`.

### New Group modal
Fields: Назва групи (required), Опис (optional)
POST → `/site-groups/create`

### Edit Group modal
Fields: Назва групи (pre-filled)
POST → `/site-groups/{id}/update`

### Delete Group confirmation
Text: "Видалити групу «{name}»? Всі сайти залишаться без групи."
POST → `/site-groups/{id}/delete`

### Add Site modal (per group)
Fields: Назва сайту (required), URL (required), API Key (auto-generated, shown read-only)
POST → `/sites/create` with `group_id`

### Edit Site modal
Fields: Назва, URL, Група (select)
POST → `/sites/{id}/update`

---

## Data Layer

### DB queries (`SiteGroupsController`)
```sql
-- Groups with site counts
SELECT sg.*, COUNT(s.id) as site_count
FROM site_groups sg
LEFT JOIN sites s ON s.group_id = sg.id
GROUP BY sg.id
ORDER BY sg.name

-- Sites per group (separate query, keyed by group_id)
SELECT s.*, ak.key_hash, ak.is_active as key_active,
       ak.last_used_at
FROM sites s
LEFT JOIN api_keys ak ON ak.site_id = s.id AND ak.is_active = 1
WHERE s.group_id IN (...)
ORDER BY s.name
```

Connection status derived from `sites.is_active` + `api_keys.is_active`:
- Both active → `ok`
- Site active, key inactive → `pause`
- Site inactive → `off`

---

## Routes to add

```php
// View
GET  /site-groups                      → SiteGroupsController::index

// Group CRUD
POST /site-groups/create               → SiteGroupsController::createGroup
POST /site-groups/{id}/update          → SiteGroupsController::updateGroup
POST /site-groups/{id}/delete          → SiteGroupsController::deleteGroup

// Site CRUD
POST /sites/create                     → SiteGroupsController::createSite
POST /sites/{id}/update                → SiteGroupsController::updateSite
POST /sites/{id}/delete                → SiteGroupsController::deleteSite

// Drag & drop (AJAX)
POST /api/v1/sites/{id}/move           → SiteGroupsController::moveSite
```

**Note:** Router currently has no `{id}` param support → add simple regex param extraction to `Router::dispatch()`.

---

## New Files

| File | Action | Responsibility |
|---|---|---|
| `src/Admin/SiteGroupsController.php` | Create | All CRUD + render |
| `src/Core/Router.php` | Modify | Add `{id}` param extraction |
| `routes.php` | Modify | Register 8 new routes |
| `public/assets/css/app.css` | Append | Page toolbar, modal, drag states |
| `public/assets/js/site-groups.js` | Create | Drag & drop + modal open/close |

---

## JS Architecture (`public/assets/js/site-groups.js`)

```
initModals()       — open/close all modals, ESC key, overlay click
initDragDrop()     — dragstart/dragover/drop/dragend on cards + columns
initSearch()       — live filter cards/columns by search input text
initFilters()      — status filter dropdown
```

All functions called on `DOMContentLoaded`. No global state — use `data-*` attributes on DOM elements for IDs, group IDs, etc.

---

## CSS additions

```
.page-toolbar            — 2-row toolbar container
.page-toolbar__row1/2    — individual rows
.modal-overlay           — fixed fullscreen overlay
.modal                   — 380px dialog
.modal__head/body/footer — sections
.field-label / .field-input — form fields
.card--dragging          — opacity 0.45, dashed accent border
.col--drag-over          — accent border highlight on column
.drop-hint               — "↓ Перетягни сюди" dashed zone
.col-new                 — ghost "+ Нова група" column
```

---

## Self-Review

**Placeholder scan:** No TBD/TODO. Route `/sites/{id}` requires router param support — explicitly called out in Routes section.

**Consistency:** CSS class names match existing `.site-card`, `.conn-badge`, `.kanban-col` patterns. Modal structure reuses `--bg-card`, `--border` tokens.

**Scope:** Appropriate for one implementation plan. Router change is minimal (one method update).

**Ambiguity resolved:**
- "Click card" = expand/collapse, NOT navigate. Navigate = "→ Site Panel" button.
- Delete group does NOT cascade-delete sites (FK is RESTRICT in schema).
- API key on add site = auto-generated server-side, shown read-only in modal.
