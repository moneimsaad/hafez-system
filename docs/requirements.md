# Hafez System — Approved Requirements

## Scope

Hafez System is a Laravel/MySQL web platform for managing Quran memorization competitions from student registration through evaluation, results, certificates, and reports.

## Approved roles and access

- Platform Admin: manages platform users, platform statistics/reports, and basic platform settings.
- User: manages competitions and their operational stages, including registrations, committees, evaluations, results, certificates, and competition reports within authorized access.
- Students do not create accounts. They use the public competition registration link and registration-status page.

## Approved workflow

Competition creation → branches and rules → registration opening → student registration → registration review → committee assignment → evaluation → automatic score/result calculation → result approval → certificate issuance → reports and verification.

## Approved capabilities

- Competition details, dates, location, status, rules, and certificate-issuance setting.
- Competition branches with participation conditions, age range, memorization amount, scores, passing score, evaluation rules, and winner count.
- Student registration data and optional approved personal files.
- Committee creation, judge assignment, student assignment, exam date, and location.
- Detailed evaluation, automatic totals/percentages, pass status, ranking, and winners.
- Certificate PDF generation, approved certificate types, and QR verification.
- Dashboard indicators, student/result/judge reports, attendance printing, and Excel/PDF export.
- Secure authentication, role permissions, audit logging, protection after result approval, Arabic RTL, responsive support, backups, and competition archiving.

## Approved technology

- Backend: PHP Laravel
- Database: MySQL
- Frontend: Blade Templates, HTML, Bootstrap, Bootstrap RTL
