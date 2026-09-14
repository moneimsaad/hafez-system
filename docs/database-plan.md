# Hafez System — Approved Database Plan

## Approved entities (no additional tables)

`users`, `competitions`, `competition_branches`, `students`, `registrations`, `committees`, `committee_judges`, `committee_students`, `evaluations`, `results`, `certificates`, `audit_logs`.

## Migration dependency order

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

## Relationship plan

- Users create competitions through `competitions.created_by`.
- Competitions contain branches and committees.
- Registrations link competitions, branches, and students.
- Committees link to a competition and branch, with assigned users and registered students through the two committee link entities.
- Evaluations link the competition, branch, student, registration, and assigned user.
- Results link the competition, branch, student, and registration.
- Certificates link the student, competition, branch, and result.
- Audit logs link the acting user to the recorded operation.

## Required design controls

Prepare and approve the ERD and data dictionary before migrations. Verify foreign keys, required values, approved state values, relationship integrity, required uniqueness, and indexes supporting approved search/report filters.

## Business Decision Required Before Database Implementation

The requirements permit additional student-registration fields, but the approved schema has no dynamic-fields entity or defined value representation. Resolve and document this business decision before implementing that part. Do not add a table through this plan.
