# Project Context: SKMS-Unimar

# Project Context: SKMS-Unimar

Single source of truth for any AI assistant working on this project. Read entirely before writing code, suggesting architecture, or making decisions.

## 1. Project Vision

**Name:** SKMS-Unimar (Scientific Knowledge Management System)
**Purpose:** A comprehensive web platform for ACTIVE management of scientific knowledge at the Decanato de Ingenieria y Afines, Universidad de Margarita (UNIMAR). This is NOT a passive file repository. It is a workflow-driven system that manages the full lifecycle of a research production: from initial upload as a draft, through tutor review, student corrections, jury evaluation, to final approval and publication.
**Architecture:** Pure Laravel monolith. NO separate API. NO SPA. Server-side rendered Blade views. Interactivity via Alpine.js. Session-based authentication via Laravel Breeze.
**Critical Context:** Venezuela. Variable connectivity, limited infrastructure costs. Must perform well on reduced bandwidth.

## 2. Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend Framework | Laravel | 12.x |
| Language | PHP | 8.2+ |
| Database | MySQL | 8.0+ |[GEMINI_SKMS (1).md](GEMINI_SKMS%20%281%29.md)
| Templating | Blade | 12.x |
| Frontend Interactivity | Alpine.js | 3.x |
| CSS Framework | Tailwind CSS | 3.4+ |
| Advanced Select | Select2 | 4.1+ |
| Build Tool | Vite | 6+ |
| HTTP Client | Axios | 1.7+ |
| Containerization | Docker / Laravel Sail | 27+ |
| Interoperability | OAI-PMH | 2.0 |
| Metadata Standard | Dublin Core Qualified | 1.1 |
| Local Environment | Laragon | - |

### Installed Laravel Packages
- **Laravel Breeze (Blade stack):** Authentication, login, registration, password recovery
- **spatie/laravel-permission:** Role-based access control (Users use `HasRoles` trait)
- **spatie/laravel-medialibrary:** File attachment management (PDFs of research works)
- **maatwebsite/excel:** Excel report exports
- **barryvdh/laravel-dompdf:** PDF report generation

### Strict Architecture Rules
- **NO separate REST API.** Everything goes through Laravel web routes with session auth.
- **NO React, Vue, or SPA frameworks.** Alpine.js only for interactivity.
- **NO Sanctum or token-based auth.** Session-based authentication from Breeze.
- **Server-side rendering first.** Blade renders everything. Alpine.js only for micro-interactions.
- **Pure Tailwind CSS.** Minimal custom CSS. Use Tailwind utility classes.
- **Premium academic design.** Modern, responsive, micro-animations, clean professional look.

## 3. The 10 System Modules

SKMS is NOT a passive repository. It is an ACTIVE management system with 10 interconnected modules.

### Module 1: Authentication and Access Control
**Roles:**
- **Administrator:** Full system control. Manages users, configures OAI-PMH, monitors logs, manages backups.
- **Research Coordinator:** Manages metadata, validates documents, generates reports, configures institutional catalogs, monitors student progress.
- **Student/Researcher:** Uploads works, browses catalog, views documents, receives feedback, monitors their own progress.
- **Tutor:** Reviews assigned works, provides structured feedback, approves/rejects with corrections.
- **Jury:** Evaluates works under review, issues approval/rejection verdict.

### Module 2: Cataloging and Document Management
**Dublin Core metadata (15 elements):** Title, Creator, Subject, Description, Publisher, Contributor, Date, Type, Format, Identifier, Source, Language, Relation, Coverage, Rights.
**Production types:** Thesis, Degree Project, Research Paper, Scientific Article, Conference Presentation, Book Chapter.
**Components:** Upload form with PDF validation, Dublin Core metadata fields with Select2 autocomplete, fulltext search (title, abstract, keywords), embedded PDF viewer (PDF.js), categorization by academic program and research line.

### Module 3: Document Approval Workflow (Workflow Engine) - THE HEART
**State machine (5 states):**
```
DRAFT --> UNDER_REVIEW --> NEEDS_CORRECTIONS --> APPROVED --> PUBLISHED
              |                   ^
              v                   |
          REJECTED <--------------|
```
**Role-conditioned transitions:**
- Student: creates Draft, submits to Under Review, corrects and resubmits
- Tutor: Under Review -> Needs Corrections / Approved / Rejected
- Jury: Under Review -> Approved / Needs Corrections / Rejected
- Coordinator: Approved -> Published
- Each transition records: timestamp, actor (user), role, mandatory comment, document version

### Module 4: Structured Feedback and Comments
Nested comments per document with:
- Observation text + reference to document section (page, paragraph)
- Correction status: Pending | In Progress | Addressed
- Author (tutor/jury) + timestamp
- Student reply
  **Flow:** Tutor posts comment -> Student receives notification -> marks In Progress -> corrects -> marks Addressed -> Tutor verifies and closes.

### Module 5: Student Progress Tracking
Individual student dashboard with:
- Quarterly progress percentage
- Scheduled vs actual delivery dates
- Aggregated correction status (how many observations pending, in progress, addressed)
- Complete document version history
- Deadline expiration alerts (configurable by coordinator)
- Visual timeline of the approval workflow
- **For Coordinator:** Consolidated view of ALL active students with filters by program, research line, status

### Module 6: OAI-PMH Interoperability
**Endpoint:** `/oai` with verbs: Identify, ListSets, ListMetadataFormats, ListRecords, GetRecord
**Response format:** Dublin Core XML (oai_dc)
**Configurable sets:** By academic program, by research line, by year, by production type
**Target repositories:** Google Scholar, Scopus, Latindex, Redalyc, SciELO

### Module 7: Bibliometric Analysis
Metrics: Productivity by period, program, research line, tutor. Tutor rankings. Research line rankings. Temporal evolution charts (Chart.js).

### Module 8: Institutional Reports
Export PDF (dompdf) and Excel (maatwebsite/excel). Institutional cover page, table of contents, methodological notes.

### Module 9: Real-time Notifications
Notification bell in UI (Alpine.js) + automatic emails (Laravel Mailables).
**Triggering events:** state change, new comment from tutor, upcoming deadline, approval, publication.

### Module 10: Administrative Panel
CRUDs: Academic programs, Research lines, Production types, Academic periods, Deadline configuration, OAI-PMH configuration, Audit logs, User and role management.

## 4. Data Model

### users
id, name, email, email_verified_at, password, remember_token, timestamps
Relationship: roles (via spatie), productions (as author), comments, revisions, notifications

### roles (spatie/laravel-permission)
id, name, guard_name, timestamps
Roles: admin, coordinador_investigacion, tutor, jurado, estudiante

### productions (central table)
id, user_id (student), titulo, resumen, palabras_clave, programa_id, linea_investigacion_id, tipo_produccion_id, estado_flujo (enum: borrador, en_revision, requiere_correcciones, aprobado, publicado, rechazado), tutor_id, jurado_id, periodo_academico_id, fecha_envio, fecha_aprobacion, doi, uri_pdf, created_at, updated_at

### academic_programs
id, nombre, codigo, descripcion, activo, timestamps

### research_lines
id, nombre, programa_id, descripcion, activo, timestamps

### production_types
id, nombre, descripcion, timestamps

### academic_periods
id, nombre, fecha_inicio, fecha_fin, activo, timestamps

### revisions (approval workflow)
id, produccion_id, estado_anterior, estado_nuevo, user_id (who made the change), rol, comentario, created_at

### comments (feedback)
id, produccion_id, user_id (author), contenido, seccion_referencia, estado_correccion (pending, in_progress, addressed), parent_id (for nested replies), created_at, updated_at

### notifications
id, user_id (recipient), tipo, titulo, mensaje, data (json), leida (boolean), created_at

### document_versions
id, produccion_id, numero_version, uri_pdf, cambios_descritos, user_id, created_at

### audit_logs
id, user_id, accion, entidad_tipo, entidad_id, datos_anteriores (json), datos_nuevos (json), ip_address, created_at

## 5. Main Workflows

### Workflow 1: Upload and Approval
1. Student logs in -> "My Works" -> "New Work"
2. Completes Dublin Core metadata form + uploads PDF
3. Initial state: DRAFT. Can edit metadata as many times as needed.
4. When ready, clicks "Submit for Review" -> UNDER_REVIEW
5. Tutor receives notification -> reviews document in PDF viewer
6. Tutor can: (a) Approve directly, (b) Reject, (c) "Needs Corrections" + comments
7. If "Needs Corrections": Student receives notification + comments -> corrects -> resubmits
8. Tutor/Jury approves -> APPROVED
9. Coordinator publishes -> PUBLISHED (visible in catalog + OAI-PMH)

### Workflow 2: Feedback
1. Tutor opens work under review -> views PDF -> posts comment on a section
2. Comment in PENDING status -> Student receives notification
3. Student marks IN_PROGRESS -> corrects -> marks ADDRESSED
4. Tutor verifies and closes comment

### Workflow 3: Progress Tracking
1. Coordinator opens student dashboard
2. Sees list with: name, program, research line, current state, progress %, pending observations, days until deadline
3. Filters: by program, research line, state, assigned tutor
4. Clicks on student -> complete timeline view

## 6. Validated Requirements (from questionnaire, population = 10, unanimous consensus)

**Functional:** RF-01 Dublin Core metadata management, RF-02 PDF upload/validation, RF-03 Search and retrieval, RF-04 PDF viewing/download, RF-05 Productivity reports, RF-06 Security/role-based access, RF-07 Responsive design, RF-08 Audit logging, RF-09 Document approval workflow, RF-10 Structured feedback.

**Non-functional:** RNF-01 Long-term digital preservation, RNF-02 Standardized Dublin Core, RNF-03 OAI-PMH interoperability, RNF-04 Creative Commons licensing, RNF-05 International standards compliance, RNF-06 Response time < 2 seconds, RNF-07 99.5% availability, RNF-08 LOCTI compliance (Ley Organica de Ciencia, Tecnologia e Innovacion).

## 7. Development Context

### Local Environment
- OS: Windows (developer), Laragon (Apache/Nginx, MySQL 8.4, PHP 8.2+)
- Database: `skms_unimar`, username `root`, password empty
- URL: `http://skms-unimar.test`
- Commands: `php artisan serve`, `php artisan migrate`, `php artisan db:seed`, `npm run dev`

### Production (future)
- Cloud VPS (DigitalOcean/Linode/Hetzner): 2 vCPU, 4 GB RAM, 80 GB SSD + 200 GB volume block, ~$24/month
- OS: Ubuntu Server 22.04 LTS, Nginx, PHP-FPM 8.2
- SSL: Let's Encrypt (Certbot), auto-renewal every 90 days
- Backup: Daily cron mysqldump + rsync to Google Drive

### Venezuela Considerations
- Variable connectivity: Server-side rendering minimizes JS payload
- Limited costs: Entire stack is open source (zero licenses)
- Accessible hosting: VPS at ~$24/month vs expensive dedicated servers
- LOCTI: Law mandates visibility and access to scientific production

## 8. Directory Structure

```
skms-unimar/
├── app/
│   ├── Http/Controllers/          # Auth/, DashboardController,
│   │                               # ProductionController, CatalogController,
│   │                               # WorkflowController, CommentController,
│   │                               # ProgressController, BibliometricController,
│   │                               # ReportController, NotificationController,
│   │                               # Admin/ (all CRUDs)
│   ├── Http/Requests/             # Form request validation classes
│   ├── Models/                    # User, Production, AcademicProgram,
│   │                               # ResearchLine, ProductionType,
│   │                               # AcademicPeriod, Revision, Comment,
│   │                               # Notification, DocumentVersion, AuditLog
│   ├── Services/                  # WorkflowService, NotificationService,
│   │                               # BibliometricService, OaiPmhService
│   ├── Events/                    # StateChanged, CommentCreated, DeadlineApproaching
│   └── Listeners/                 # SendStateNotification, SendCommentNotification
├── database/
│   ├── migrations/
│   └── seeders/                   # RoleSeeder, ProgramSeeder,
│                                   # ResearchLineSeeder, ProductionTypeSeeder
├── resources/
│   ├── views/                     # layouts/, dashboard, productions/,
│   │                               # catalog/, workflow/, comments/,
│   │                               # progress/, bibliometrics/, reports/,
│   │                               # notifications/, admin/
│   ├── js/app.js
│   └── css/app.css
├── routes/web.php                 # ONLY web routes (NO API routes)
├── storage/app/public/productions/  # PDF files of research works
└── tests/Feature/
```

## 9. Key Code Patterns

### WorkflowService (State Machine)
```php
class WorkflowService
{
    protected array $transitions = [
        'borrador' => ['en_revision'],
        'en_revision' => ['requiere_correcciones', 'aprobado', 'rechazado'],
        'requiere_correcciones' => ['en_revision'],
        'aprobado' => ['publicado'],
    ];

    public function canTransition(Production $p, string $newState, User $user): bool
    {
        // Check valid transition + role permissions
    }
}
```

### Events -> Notifications
Every state change fires a Laravel Event -> Listener creates DB notification + sends email if applicable -> Alpine.js updates the bell via polling.

### Notification Bell (Alpine.js)
```html
<div x-data="{ open: false, notifications: [], unreadCount: 0 }"
     x-init="fetchNotifications()">
    <button @click="open = !open">
        <span x-text="unreadCount" x-show="unreadCount > 0"></span>
    </button>
    <div x-show="open" @click.outside="open = false">
        <template x-for="n in notifications" :key="n.id">
            <div :class="{ 'bg-blue-50': !n.read }" @click="markRead(n.id)"
                 x-text="n.title"></div>
        </template>
    </div>
</div>
```

## 10. Development Checklist

### Phase 1: Foundation
- [ ] Laravel 12 + Breeze (Blade) + spatie/permission + spatie/medialibrary
- [ ] All database migrations + seeders for base data
- [ ] Docker/Sail configured for consistent development

### Phase 2: Authentication + Cataloging
- [ ] Login/registration with roles
- [ ] Production CRUD with Dublin Core metadata
- [ ] PDF upload with validation
- [ ] Fulltext search engine
- [ ] Embedded PDF viewer

### Phase 3: Active Workflows (THE HEART)
- [ ] Workflow state machine (WorkflowService)
- [ ] Comments system with correction statuses
- [ ] Student progress dashboard
- [ ] Notifications (bell + email)

### Phase 4: Analysis + Reports
- [ ] Bibliometric analysis with charts
- [ ] PDF and Excel report generator

### Phase 5: Interoperability + Admin
- [ ] Full OAI-PMH endpoint
- [ ] Admin panel with all CRUDs

### Phase 6: Testing + Deployment
- [ ] Unit tests for WorkflowService
- [ ] Integration tests for complete approval flows
- [ ] User acceptance testing with academic community
- [ ] VPS deployment
- [ ] Configured automated backups
- [ ] User training

## 11. AI Notes (MANDATORY)

- **CRITICAL / MANDATORY RULE:** Never write, modify, or implement any code changes without asking the user first. Do not make assumptions or guess design/logic. Always ask open-ended questions or present a list of at least 3 options/alternatives before making any edits.
- ALWAYS verify stack: Tailwind + Alpine.js + Laravel + Blade. NO Livewire, NO React, NO Vue.
- NEVER suggest separate REST API, Sanctum, or SPA frameworks.
- NEVER suggest paid libraries when an open source alternative exists.
- ALWAYS consider Venezuela's variable connectivity: minimize assets, server-side render.
- ALWAYS use spatie/laravel-permission for authorization. NO custom gates.
- ALWAYS fire Laravel Events on workflow state changes for notifications to work.
- NEVER hardcode UNIMAR-specific text. Use config files or settings table.
- PREFER solutions that work well on reduced bandwidth.
- The entire stack is open source (PHP, Laravel, MySQL, Alpine.js, Tailwind, Vite) = zero licenses.
- 10 interconnected functional modules. This is NOT a passive repository.
- ALWAYS consult the global skills bank in `C:\Users\regna\.gemini\skills` and utilize the necessary skills for ANY request sent by the user. These skills contain curated patterns and expert guidelines for almost all technologies and scenarios.

*Note to AI: Keep responses concise, prioritize this stack, and do not introduce unapproved third-party structural libraries.*

