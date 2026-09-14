# Hafez System Development Roadmap v1.1

## 1. Purpose and scope control

This roadmap is the execution plan for **Hafez System – Quran Memorization Competition Management Platform**. It is based solely on the approved requirements and uses PHP Laravel, MySQL, Blade Templates, HTML, Bootstrap, and Bootstrap RTL.

Scope is controlled as follows:

- The only account roles are **Platform Admin** and **User**.
- A student does not create an account; the student uses the public competition registration link and registration-status page.
- No database entities are permitted beyond the approved list in Phase 4.
- No feature, page, role, or workflow is introduced by this roadmap.

### Delivery sequence

`Documentation → Project setup → Architecture → Database design → Foundation and access → Models and layouts → Competition workflow → Evaluation and results → Certificates and reports → Testing → Release preparation → Deployment`

---

## Phase 1 — Project documentation preparation

### Objective

Create a single, approved project reference before development begins, so implementation decisions remain traceable to the approved requirements.

### Tasks

1. Prepare requirements documentation from the approved project reference.
   - Record the stated objective, technology stack, approved roles, required pages, required reports, and operational workflow.
   - Record approved competition, branch, registration, committee, evaluation, result, certificate, and audit-log requirements.
2. Prepare system-modules documentation.
   - Authentication and platform administration.
   - Competition and branch management.
   - Student registration.
   - Committees and evaluations.
   - Results, certificates, reports, and audit logging.
3. Prepare user-flow documentation.
   - Platform Admin flow.
   - User competition-management flow.
   - Assigned-user evaluation flow.
   - Student public registration and status-follow-up flow.
   - Certificate-verification flow.
4. Prepare screen-map documentation for the approved public, Platform Admin, and competition-management pages.
5. Prepare database documentation.
   - Approved entity list.
   - Preliminary relationship map.
   - Column dictionary from the approved reference.
6. Prepare a development-decisions register.
   - Record only decisions necessary to implement stated requirements.
   - Record approvals and unresolved points before their related development starts.

### Dependencies

- Approved project requirements and the original Hafez reference document.

### Deliverables

- Requirements baseline.
- Module map, user-flow map, and screen map.
- Preliminary database reference.
- Development-decisions register.

### Review and approval checkpoint

- Product and technical owners review that every documented item maps to an approved requirement.
- Development may proceed only after the documentation baseline is approved.

---

## Phase 2 — Project setup and development standards

### Objective

Prepare a reproducible Laravel development environment and consistent team conventions.

### Tasks

1. Install and validate the Laravel project.
   - Confirm PHP, Composer, Node.js, npm, and Laravel requirements are available.
   - Create or validate the Laravel application and run it locally.
2. Configure the environment.
   - Create the local environment configuration from the example file.
   - Set application name, URL, locale, timezone, debug setting, and application key for local development.
   - Keep environment secrets out of source control.
3. Configure MySQL.
   - Create the local development database.
   - Configure connection values in the environment file.
   - Verify Laravel can connect and run migrations.
4. Prepare authentication.
   - Select the approved authentication approach: session authentication or JWT.
   - Prepare the login route, authenticated session lifecycle, logout, and password handling within the selected approach.
5. Prepare frontend assets.
   - Configure Vite asset building.
   - Install and load Bootstrap and Bootstrap RTL.
   - Set Arabic direction and locale support at the shared layout level.
6. Prepare the project folder structure described in Phase 3.
7. Initialize and configure Git.
   - Confirm `.gitignore` excludes environment files, dependencies, generated caches, and generated runtime files.
   - Establish the working branch and review workflow used by the team.
8. Establish development standards.
   - Use Laravel naming conventions for migrations, models, controllers, requests, policies, services, routes, and Blade views.
   - Keep controllers focused on request coordination; keep validation in Form Requests; keep authorization in policies or middleware; keep reusable business operations in Services.
   - Use migrations for schema changes only; do not alter the database manually outside the migration process.
   - Require validation, authorization, and audit-log consideration for each state-changing operation.
   - Require Arabic RTL and responsive review for each new Blade page.

### Dependencies

- Phase 1 approval.

### Deliverables

- Runnable Laravel application.
- Verified local MySQL connection.
- Bootstrap RTL asset pipeline.
- Git repository configuration and documented development standards.
- Authentication foundation prepared for implementation.

### Review and approval checkpoint

- Team verifies a clean local setup from source control, successful database connection, asset build, and application boot.
- Proceed only after the setup checklist is approved.

---

## Phase 3 — System architecture and code organization

### Objective

Define how the approved functionality is organized in Laravel before database and module implementation.

### Architecture approach

- Laravel is the application framework and the server-rendered UI uses Blade Templates.
- HTTP routes direct public and authenticated requests to controllers.
- Controllers coordinate the request, authorization, validation, service calls, and Blade response or redirect.
- Eloquent models represent only the approved database entities and their relationships.
- Form Requests centralize input validation.
- Policies and middleware protect authenticated operations and enforce the two approved roles.
- Services hold reusable business logic for evaluation calculation, result calculation, certificate generation, reporting, file handling, and audit-log writing.
- Blade layouts and components provide the Arabic RTL Bootstrap interface.

### Folder responsibilities

| Location | Responsibility |
|---|---|
| `app/` | Application domain code and shared Laravel application classes. |
| `app/Http/Controllers/` | Receives HTTP requests and coordinates approved use cases. |
| `app/Models/` | Eloquent models and relationships for the approved tables. |
| `app/Http/Requests/` | Validation and authorization rules for submitted forms. |
| `app/Policies/` | Resource-level authorization for Platform Admin and User access. |
| `app/Services/` | Reusable business operations without duplicating logic across controllers. |
| `resources/views/` | Blade layouts and approved public and authenticated pages. |
| `routes/` | Public, authentication, and protected web route definitions. |
| `database/` | Migrations, factories, and seed data used for development and tests. |

### Tasks

1. Approve the responsibility boundaries above.
2. Define route-group boundaries for public pages, authenticated pages, and Platform Admin-only pages.
3. Define the service boundaries for calculations, certificates, exports, storage handling, and audit logs.
4. Define the view-layout boundaries for authentication, public pages, Platform Admin pages, and competition-management pages.
5. Record the architecture decisions in the Phase 1 decisions register.

### Dependencies

- Phase 2 approval.

### Deliverables

- Approved Laravel architecture map.
- Folder responsibility reference.
- Route, authorization, service, and view organization rules.

### Review and approval checkpoint

- Technical lead confirms that the architecture supports every approved module without adding a new module, entity, or role.
- Database implementation starts only after approval.

---

## Phase 4 — Database design and implementation planning

### Objective

Approve a relational MySQL design that uses exactly the approved entities and supports all approved relationships.

### Approved entities

- `users`
- `competitions`
- `competition_branches`
- `students`
- `registrations`
- `committees`
- `committee_judges`
- `committee_students`
- `evaluations`
- `results`
- `certificates`
- `audit_logs`

### Tasks

1. Prepare the ERD.
   - Show primary keys, foreign keys, cardinality, and dependency direction.
   - Review the ERD before any migration is created.
2. Prepare the data dictionary.
   - Document each approved table, its approved columns, data type, nullability, key, relationship, and business purpose.
   - Document approved state values for competition, registration, evaluation, and result records.
3. Verify required relationships.
   - `competitions.created_by` links to `users`.
   - `competition_branches.competition_id` links to `competitions`.
   - `registrations` links a student, competition, and branch.
   - `committees` links to its competition and branch.
   - `committee_judges` links a committee and its assigned user.
   - `committee_students` links a committee, student, and registration.
   - `evaluations` links competition, branch, student, registration, and assigned user.
   - `results` links competition, branch, student, and registration.
   - `certificates` links student, competition, branch, and result.
   - `audit_logs.user_id` links to `users`.
4. Define required integrity constraints and indexes within the approved schema.
   - Foreign-key integrity.
   - Appropriate uniqueness checks where the approved workflow requires them.
   - Indexes supporting the approved student search and report filters.
5. Create migrations in dependency order.
   1. `users`
   2. `competitions`
   3. `competition_branches`
   4. `students`
   5. `registrations`
   6. `committees`
   7. `committee_judges`
   8. `committee_students`
   9. `evaluations`
   10. `results`
   11. `certificates`
   12. `audit_logs`
6. Create Eloquent models and relationship tests in the same dependency order.
7. Execute the database test plan.
   - Run migrations on an empty database.
   - Verify foreign keys, relationship reads, required values, and permitted state transitions.
   - Verify rollback and repeatable migration execution in the development environment.

### Business Decision Required Before Database Implementation

The approved requirements allow the user to define additional student-registration fields, but the approved database entities do not include a dynamic-fields entity or a defined storage representation for those values. This must be resolved and recorded as a business decision before implementing that part of registration. No additional table or entity is created by this roadmap.

### Dependencies

- Phase 3 approval.

### Deliverables

- Approved ERD.
- Approved data dictionary.
- Migration dependency plan.
- Relationship-verification checklist.
- Database test plan and verified migrations.

### Review and approval checkpoint

- Technical and business owners approve the ERD and data dictionary, including the documented unresolved decision above.
- Backend feature work starts only after the schema plan is approved.

---

## Phase 5 — Authentication and authorization

### Objective

Implement secure access using only Platform Admin and User accounts, while retaining public student registration and public certificate verification.

### Login and session flow

1. Platform Admin or User submits approved login credentials.
2. Credentials are validated against `users`.
3. The application creates an authenticated session using the selected approved authentication approach.
4. The user is redirected to the appropriate authorized dashboard area.
5. Logout invalidates the session and returns the user to the login page.

### Authorization plan

- Middleware protects all authenticated routes.
- Platform Admin-only middleware protects platform users, platform settings, platform statistics, and platform-wide reporting routes.
- Policies verify that a User operates only on records and competition data permitted to that user.
- Assigned evaluation access is limited to the user assignments recorded through `committee_judges` and `committee_students`; this is an assignment rule, not a new account role.
- Public routes are limited to the registration link, registration-status follow-up, and certificate verification.

### Tasks

1. Implement login, logout, session handling, and account-status checks.
2. Implement protected route groups and role middleware.
3. Implement policies for competitions, registrations, committees, evaluations, results, certificates, and reports.
4. Add audit-log recording for relevant authenticated state changes.
5. Test forbidden and permitted access paths for each approved role.

### Dependencies

- Phase 4 migration and model plan.

### Deliverables

- Secure login and logout flow.
- Protected routes and permission rules.
- Verified role and assignment access behavior.

### Review and approval checkpoint

- Security review confirms that a visitor, User, and Platform Admin cannot access data or actions outside their approved scope.
- Feature modules start only after authorization tests pass.

---

## Phase 6 — Development execution plan

### Objective

Implement the approved system in the required sequence, with each work item completed, tested, and approved before dependent work begins.

### 1. Project foundation

- **Goal:** Deliver the configured Laravel, MySQL, Git, asset, architecture, and standards foundation.
- **Dependencies:** Phases 1–3 approved.
- **Backend tasks:** Environment configuration, application configuration, service boundaries, storage configuration, and shared audit-log integration point.
- **Frontend tasks:** Vite, Bootstrap, Bootstrap RTL, Arabic base direction, shared assets.
- **Testing checkpoints:** Application boot, MySQL connection, migration command, asset build, RTL rendering.

### 2. Authentication and Platform Admin access

- **Goal:** Secure entry to the system and provide the approved Platform Admin access scope.
- **Dependencies:** Project foundation and `users` model/migration plan.
- **Backend tasks:** Login/logout, session management, middleware, role checks, user management, platform statistics and settings access control, audit logging.
- **Frontend tasks:** Login page; Platform Admin dashboard, users, platform reports/statistics, platform numbers, and settings pages.
- **Testing checkpoints:** Login and logout, role enforcement, unauthorized-route response, Platform Admin-only pages, audit logging.

### 3. Database models

- **Goal:** Implement the approved database schema and Eloquent relationships.
- **Dependencies:** Approved Phase 4 ERD and data dictionary.
- **Backend tasks:** Migrations, models, relationships, factories/seed data for tests, constraints, and indexes.
- **Frontend tasks:** None beyond development verification.
- **Testing checkpoints:** Fresh migration, relationship verification, foreign-key checks, rollback/re-run checks.

### 4. Core layouts

- **Goal:** Provide reusable, Arabic RTL Blade layouts for the approved page map.
- **Dependencies:** Project foundation and authentication route structure.
- **Backend tasks:** Shared view data and authorized navigation visibility.
- **Frontend tasks:** Authentication layout, public layout, dashboard layout, navigation, sidebar, header, footer, responsive Bootstrap structure.
- **Testing checkpoints:** RTL direction, authorized navigation, mobile/tablet/desktop display, public and protected layout separation.

### 5. Competition management

- **Goal:** Create and manage competitions with their approved details, dates, rules, status, and certificate-issuance setting.
- **Dependencies:** Authentication, `competitions`, core layouts, and authorization policies.
- **Backend tasks:** Competition model operations, validation, routes, controller, status handling, ownership authorization, audit-log writing.
- **Frontend tasks:** Competition dashboard, list, create, edit, detail, and management pages.
- **Testing checkpoints:** Required data validation, registration/exam date validation, permitted state changes, ownership restrictions, audit-log entries.

### 6. Branch management

- **Goal:** Manage approved competition branches and their participation, age, memorization, score, success, and winner-count rules.
- **Dependencies:** Competition management and `competition_branches`.
- **Backend tasks:** Branch operations, competition relationship validation, age/score range validation, authorized routes, audit logs.
- **Frontend tasks:** Branch list, add, edit, and branch details within competition management.
- **Testing checkpoints:** Competition association, minimum/maximum age checks, total/passing score checks, winner-count persistence, authorization.

### 7. Student registration

- **Goal:** Receive student registrations through a public competition link and allow authorized follow-up, approval, rejection, and status tracking.
- **Dependencies:** Competition and branch management, `students`, `registrations`, storage configuration, and the business decision in Phase 4.
- **Backend tasks:** Public registration route, registration-period check, student and registration validation, file handling for approved student data, status follow-up, approval/rejection, audit logs.
- **Frontend tasks:** Registration form, registration-status page, authorized registration/student lists, search and approved filters.
- **Testing checkpoints:** Open/closed registration periods, branch and age validation, required/optional approved fields, rejection reason, status follow-up, file handling, authorization.

### 8. Committees

- **Goal:** Create committees, assign users as judges, assign accepted students, and record the approved schedule and location.
- **Dependencies:** Competition, branches, accepted registrations, `committees`, `committee_judges`, and `committee_students`.
- **Backend tasks:** Committee operations, judge assignments, manual or automatic student distribution, assignment checks, audit logs.
- **Frontend tasks:** Committee list, create/edit, committee details, judge assignment, and student-assignment screens.
- **Testing checkpoints:** Valid competition/branch links, accepted registration only, assignment integrity, assigned-user visibility restriction, audit logs.

### 9. Evaluation

- **Goal:** Let assigned users enter detailed student scores and notes, then calculate evaluation totals and percentages.
- **Dependencies:** Committees, `evaluations`, branch score settings, and assignment authorization.
- **Backend tasks:** Evaluation validation, memorization/tajweed/performance/discipline score handling, total and percentage calculation, multiple-evaluator calculation according to the approved competition setting, locked-result restriction, audit logs.
- **Frontend tasks:** Assigned-students list, score-entry form, and evaluation follow-up screens.
- **Testing checkpoints:** Score limits, total/percentage accuracy, permitted assigned-student access, multiple-evaluator calculation, prohibition after result approval.

### 10. Results

- **Goal:** Calculate final scores, success status, branch ranking, winners, and approval of results.
- **Dependencies:** Completed evaluations, `results`, branch passing score, and branch winner count.
- **Backend tasks:** Result calculation service, ranking by branch, winner determination, result approval, post-approval protection, audit logs.
- **Frontend tasks:** Score sheet, results list, successful students, winners, and result-approval pages.
- **Testing checkpoints:** Final-score accuracy, percentage, success/failure status, rank, winner count, approval permissions, edit protection after approval.

### 11. Certificates

- **Goal:** Generate approved PDF certificate types with their stated content and QR verification.
- **Dependencies:** Approved results, `certificates`, storage configuration, PDF generation, and QR generation.
- **Backend tasks:** Certificate-number generation, PDF generation using the available template, QR generation, file storage, certificate verification lookup, issuance according to the competition setting, audit logs.
- **Frontend tasks:** Certificate management/issuance screens and the public QR verification page.
- **Testing checkpoints:** Required certificate data, certificate type, unique certificate number, generated file, QR verification, authorized issuance, result linkage.

### 12. Reports

- **Goal:** Produce the approved dashboards, administrative reports, attendance printing, filters, and Excel/PDF exports.
- **Dependencies:** Competition, registration, committee, evaluation, result, and certificate data.
- **Backend tasks:** Report queries, aggregate counts, filters, attendance-print data, Excel export, PDF export, authorization.
- **Frontend tasks:** Dashboard cards, report screens, filters, export controls, and print-ready attendance views.
- **Testing checkpoints:** Accuracy of totals, branch/age/city/center/committee/status filters, judge-performance report, score/success/winner reports, Excel/PDF output, access control.

### Phase dependencies

- Items 1–4 establish the reusable foundation.
- Items 5 and 6 must complete before item 7.
- Item 7 must complete before item 8.
- Item 8 must complete before item 9.
- Item 9 must complete before item 10.
- Item 10 must complete before item 11.
- Item 12 is completed after the corresponding operational data sources are available and finalized after item 11.

### Deliverables

- Implemented, reviewed, and tested approved modules in the stated order.
- Updated decision register, user flows, screen map, and test evidence.

### Review and approval checkpoint

- Each numbered item requires functional review, authorization review, RTL/responsive review, and test evidence before its dependents begin.
- The complete execution phase is approved only after all twelve work items pass their checkpoints.

---

## Phase 7 — Integrated testing and acceptance

### Objective

Verify that the completed system satisfies the approved workflow from registration through certificate verification.

### Tasks

1. Functional tests.
   - Create competition and branches.
   - Open registration, submit a student registration, and track its status.
   - Approve or reject registrations.
   - Assign students and users to committees.
   - Record evaluations, calculate results, approve results, and issue certificates.
2. Authentication and authorization tests.
   - Platform Admin and User permissions.
   - Protected routes and record-level access.
   - Public-only registration, registration-status, and certificate-verification routes.
3. Database tests.
   - Migration execution, relationship integrity, constraints, and data consistency.
4. Score-calculation tests.
   - Detailed score totals, percentages, pass/fail, multiple-evaluator handling, rank, and winners.
5. Certificate tests.
   - PDF generation, data accuracy, file path, QR code, and public verification.
6. Report tests.
   - Dashboard figures, all approved filters, attendance printout, Excel export, and PDF export.
7. Responsive and RTL tests.
   - Review approved pages on mobile, tablet, and desktop layouts.

### Dependencies

- Phase 6 completed and all module checkpoints passed.

### Deliverables

- Test plan and executed test evidence.
- Defect register and resolved-defect evidence.
- User-acceptance test checklist.

### Review and approval checkpoint

- Business and technical owners approve acceptance results.
- Release preparation begins only when all blocking defects are resolved and acceptance is approved.

---

## Phase 8 — Final release preparation

### Objective

Prepare the accepted system for a controlled production release.

### Tasks

1. Clean up code, configuration, unused development artifacts, and documentation references.
2. Conduct security review.
   - Input validation, authentication, authorization, protected routes, file access, result-approval protection, and audit-log coverage.
3. Conduct performance review.
   - Database indexes, reporting queries, list/filter queries, and asset build output.
4. Prepare database and file backup procedures.
5. Verify production configuration values are ready without exposing credentials.
6. Prepare the final release checklist and rollback procedure according to the project deployment process.

### Dependencies

- Phase 7 acceptance approval.

### Deliverables

- Production-ready release candidate.
- Security and performance review records.
- Backup preparation record.
- Final release and rollback checklists.

### Review and approval checkpoint

- Technical owner approves release readiness and backup readiness before deployment.

---

## Phase 9 — Production deployment

### Objective

Deploy the approved production release and verify every required operational path.

### Tasks

1. Prepare the production environment.
   - Configure the approved server, PHP/Laravel runtime requirements, MySQL, web server, and directory permissions.
2. Configure environment variables.
   - Application environment, application key, production URL, database connection, storage configuration, and approved mail configuration.
   - Keep production secrets outside source control.
3. Deploy the application release and install production dependencies.
4. Run database migrations in the approved order.
5. Configure storage.
   - Ensure storage permissions and public access paths required for approved files and certificates are correct.
6. Build and publish frontend assets.
7. Run post-deployment checks.
   - Application availability and login.
   - Platform Admin and User route protection.
   - Competition and branch management.
   - Public registration and status follow-up.
   - Committee assignment, evaluation, and result approval.
   - Certificate PDF generation and QR verification.
   - Reports, printing, Excel export, and PDF export.
8. Release the production version after the final checklist passes.

### Dependencies

- Phase 8 release-readiness approval.

### Deliverables

- Deployed production application.
- Completed migration and storage configuration record.
- Production verification evidence.
- Production release checklist.

### Review and approval checkpoint

- The release owner approves production release only after all post-deployment checks pass.

### Final production checklist

- [ ] Production environment variables are configured securely.
- [ ] MySQL connection and all migrations succeed.
- [ ] Storage and certificate files are accessible according to approved access rules.
- [ ] Built Bootstrap RTL assets load correctly.
- [ ] Platform Admin and User authentication and authorization work.
- [ ] Public registration, status follow-up, and certificate verification work.
- [ ] Evaluation, results approval, and post-approval protection work.
- [ ] Certificate PDF and QR verification work.
- [ ] Reports, attendance printing, Excel export, and PDF export work.
- [ ] Backup procedure is ready.

## Final outcome

An approved, Arabic RTL Laravel system ready for production use, covering the required Quran memorization competition workflow: competition creation, branches, student registration, committees, evaluations, results, certificates, reports, and audit logging.
