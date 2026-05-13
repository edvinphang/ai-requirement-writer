# AI Requirement Writer — Design Spec
**Date:** 2026-05-13
**Author:** Edvin
**Status:** Approved

---

## 1. Problem

Writing software requirements is slow, inconsistent, and depends on the skill of whoever writes them. Nexor's dev team — and the enterprises and GLCs they serve — need a faster, structured way to go from a raw idea to a complete, client-ready requirement set (BRD, user stories, technical spec) without starting from a blank page every time.

---

## 2. What We're Building

A web application (and headless API) that takes a raw project idea — via structured form, free-text, or meeting transcript — and generates a full requirement chain:

**Business Requirements Document (BRD) → User Stories → Technical Spec**

Each layer is reviewed and approved by the user before the next is generated. Output can be exported (PDF, Word, Markdown) or pushed directly to JIRA, Confluence, Notion, or GitHub.

Built internal-first at Nexor, then productized for enterprise/GLC clients.

---

## 3. Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | Next.js (React) |
| Backend | Laravel (PHP) |
| Database | MySQL |
| AI | Claude API (Anthropic) |
| Auth | Laravel Sanctum |

---

## 4. Architecture

```
┌─────────────────────────────────────────────────┐
│                  Next.js (Frontend)              │
│  Project Dashboard │ Intake Form │ Chat Mode     │
│  Review & Edit UI  │ Export Panel│ Integrations  │
└────────────────────┬────────────────────────────┘
                     │ REST API calls
┌────────────────────▼────────────────────────────┐
│                  Laravel (Backend)               │
│  Auth │ Projects │ AI Orchestration │ Exports    │
│  Integration Connectors │ REST API (headless)    │
└───────────┬────────────────┬────────────────────┘
            │                │
    ┌───────▼──────┐  ┌──────▼──────────────────┐
    │    MySQL     │  │     Claude API           │
    │  Projects    │  │  BRD → Stories → Spec    │
    │  Drafts      │  │  (prompt caching on)     │
    │  Templates   │  └─────────────────────────┘
    │  Users       │
    └──────────────┘
```

The Laravel API is the single backend — both the Next.js UI and external tools call the same endpoints (Laravel Sanctum token auth).

---

## 5. Core Generation Flow

### Template Mode (default)

1. User creates a new project and picks a template: `Web App | Mobile | API | Data Pipeline | Custom`
2. Fills intake form: project name, problem statement, target users, goals, constraints, stakeholders, timeline
3. Optionally pastes a meeting transcript or voice note — parsed and merged into the prompt context
4. **Generate BRD** — Claude API call #1, streams into UI, user reviews and edits inline, approves
5. **Generate User Stories** — Claude API call #2 (BRD as context), same review loop
6. **Generate Technical Spec** — Claude API call #3 (BRD + stories as context), same review loop
7. Final document assembled — Export or push to integrations

### Advanced Mode (conversational)

Toggle in UI opens a chat interface. The AI asks 8–12 targeted discovery questions (one at a time, BA interview style). Once context is built, the same 3-step generation chain runs. Suited for novel or complex projects that don't fit a template.

---

## 6. AI Orchestration

### Prompt Chain

```
Call 1 — BRD Generation
  System: Senior BA role, BRD output format, Nexor brand tone
  User:   Intake form data + parsed transcript
  Output: Structured BRD (objectives, scope, stakeholders, constraints)

Call 2 — User Stories
  System: Product manager role, Agile story format with acceptance criteria
  User:   Approved BRD
  Output: Prioritised user stories

Call 3 — Technical Spec
  System: Solutions architect role, spec format instructions
  User:   BRD + approved user stories
  Output: System design, data flow, API contracts, tech stack notes

Call 0 (conversational mode, repeated) — Discovery
  System: BA interviewer role, question strategy prompt
  User:   Running conversation history
  Output: Next question OR signal to proceed to Call 1
```

### Config

- **Streaming** enabled on all calls — tokens render in real-time in the UI
- **Prompt caching** on all system prompts — large, reused across every project; reduces cost materially
- **Model:** `claude-sonnet-4-6` default; `claude-opus-4-7` available as "deep analysis" toggle
- **Timeout:** 60s per call — partial output saved, user shown a "regenerate" option on timeout

---

## 7. Data Model

```sql
users
  id, name, email, password, role (admin|member), created_at

projects
  id, user_id, name, type (webapp|mobile|api|data|custom)
  mode (template|conversational), status (draft|in_progress|complete)
  template_id, created_at, updated_at

templates
  id, name, type, fields (JSON)

requirement_drafts
  id, project_id, type (brd|stories|spec)
  content (longtext), version (int), approved_at, created_at

project_intake
  id, project_id, form_data (JSON), transcript (text), created_at

exports
  id, project_id, format (pdf|docx|markdown), file_path, created_at

integrations
  id, user_id, provider (jira|confluence|notion|github)
  credentials (encrypted JSON), created_at

chat_messages
  id, project_id, role (user|assistant), content, created_at, order
```

**Key decisions:**
- `requirement_drafts` is versioned — every regeneration is a new row, nothing overwritten
- `credentials` encrypted at rest via Laravel's built-in encryption
- `form_data` and `fields` as JSON — templates evolve without schema migrations

---

## 8. Export & Integrations

### Export

| Format | Library | Notes |
|--------|---------|-------|
| PDF | Laravel Snappy (wkhtmltopdf) | Nexor-branded styling |
| Word (.docx) | PHPWord | Editable, client-ready |
| Markdown | Plain text | For GitHub/Notion paste |

### Integration Connectors

| Provider | What gets pushed |
|----------|-----------------|
| JIRA | User stories → Issues/Epics |
| Confluence | Full document → new page |
| Notion | Full document → new page |
| GitHub | User stories → Issues |

Each connector is an isolated Laravel service class. Adding a new provider does not touch existing ones.

### Headless API Endpoints

```
POST   /api/projects
POST   /api/projects/{id}/generate/{type}   — brd | stories | spec
GET    /api/projects/{id}/export/{format}   — pdf | docx | markdown
POST   /api/projects/{id}/push/{provider}   — jira | confluence | notion | github
```

---

## 9. Error Handling

- **Claude API failure** → retry once automatically, then surface "Regenerate" button with error message
- **Integration push failure** → queued Laravel job, retries 3x, user notified if all fail
- **Transcript parse failure** → fallback to raw text, user edits in form
- **Streaming timeout (60s)** → partial output saved, regenerate prompt shown

---

## 10. Testing Strategy

- **Laravel (PHPUnit):** API endpoints, AI orchestration, connector services — SQLite in-memory for CI
- **Next.js (Vitest + React Testing Library):** Form validation, review UI, state transitions
- **No mocking the Claude API** — fixture responses for unit tests; real API in staging for integration tests
- **E2E (Playwright):** Golden path — create project → generate BRD → stories → spec → export PDF

---

## 11. Out of Scope (v1)

- Real-time collaboration (multiple users editing simultaneously)
- Slack / Teams integration
- Voice recording (paste transcript only — no live mic input)
- Mobile app
- Fine-tuned or self-hosted AI model
