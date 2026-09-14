# Hafez System — Development Decisions

## Confirmed decisions

1. The project uses Laravel, MySQL, Blade Templates, HTML, Bootstrap, and Bootstrap RTL.
2. Account roles are limited to Platform Admin and User.
3. Student registration is public; a student does not create an account.
4. Only the twelve approved database entities may be implemented.
5. Authentication uses the selected Laravel-compatible session or JWT approach recorded by the technical owner before implementation.
6. Controllers coordinate requests, Form Requests validate input, Policies/Middleware authorize access, and Services hold reusable approved business logic.
7. Every phase requires review, approval, and deliverable evidence before dependent work begins.

## Unresolved decision

**Business Decision Required Before Database Implementation:** the requirement for user-defined additional student-registration fields is not represented by the approved database entities. The business/technical owners must decide how to handle it within the approved schema before registration implementation. This roadmap does not add a table or feature.
