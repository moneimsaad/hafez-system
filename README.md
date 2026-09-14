# Hafez System

Hafez System is a Laravel-based platform for managing educational competitions from initial setup through public certificate verification.

## Project overview

The platform centralizes competition administration, participant registration, committee assignment, evaluation, results, and certificates in one workflow. It reduces manual coordination, improves data consistency, and gives participants a clear way to review registration information and verify issued certificates.

### Target users

- Platform administrators
- Competition organizers
- Students and participants
- Members of evaluation committees
- Public users verifying certificates

## Main features

- Secure authentication with account verification and OTP-based email flows
- Organizer account and profile management
- Competition creation and lifecycle management
- Public participant registration
- Registration review and participant management
- Competition levels and categories
- Committee assignment
- Evaluation and score entry
- Results and rankings
- Certificate generation with QR-code support
- Public certificate verification
- Reports and organizer dashboards

## Technology stack

| Area | Technology |
| --- | --- |
| Backend | PHP 8.2+ and Laravel 12 |
| Database | SQLite for local development; MySQL-compatible database for production |
| Frontend | Blade, Bootstrap 5, Tailwind CSS tooling, Alpine.js, and Flatpickr |
| Asset tooling | Vite, npm, PostCSS, and Autoprefixer |
| Email | Laravel Mail with SMTP support |
| Background jobs | Laravel database queue with a worker on supported hosting |
| Documents and verification | DOMPDF and QR Code generation |

## Requirements

- PHP 8.2 or later
- Composer
- Node.js and npm
- SQLite for a lightweight local setup, or a MySQL-compatible database
- A configured SMTP service for account and password-reset OTP emails

## Installation

1. Clone the repository and enter the project directory.
2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Copy `.env.example` to `.env`.
4. Configure the application URL, database connection, mail settings, and other environment variables. Never commit the resulting `.env` file.
5. Generate the application key:

   ```bash
   php artisan key:generate
   ```

6. Run the database migrations:

   ```bash
   php artisan migrate
   ```

7. If an initial administrator is required, set the `HAFEZ_ADMIN_*` environment variables locally and run:

   ```bash
   php artisan db:seed --class=AdminUserSeeder
   ```

8. Install and build frontend assets:

   ```bash
   npm install
   npm run build
   ```

9. Start the local development environment:

   ```bash
   composer run dev
   ```

   The development command starts the Laravel server, Vite, and the database queue worker together.

## Demo accounts

Use placeholders for instructor or demonstration credentials and replace them only in the local environment:

- Platform administrator email: `<demo-admin-email>`
- Organizer email: `<demo-organizer-email>`
- Password: `<set-locally>`

Do not publish real passwords, OTPs, app passwords, or other credentials.

## Testing

Run the application test suite:

```bash
composer test
```

The current verification baseline is 183 tests with 1,059 assertions. The production frontend build has also been verified with:

```bash
npm run build
```

## Deployment notes

- Configure production values through environment variables only, including `APP_KEY`, database credentials, application URL, session settings, and SMTP credentials.
- Set `APP_ENV=production` and `APP_DEBUG=false` in production.
- Build assets with `npm run build` and deploy the generated `public/build` assets. `node_modules` is not required on the web server.
- On hosting with a persistent worker, use `QUEUE_CONNECTION=database` and keep a queue worker running for OTP email delivery.
- For an InfinityFree demonstration deployment, use `QUEUE_CONNECTION=sync` in that deployment's environment because persistent workers are unavailable. Local development remains configured for the database queue.
- Run migrations against the deployment database and ensure the application can write to the required storage directories.
- Run `php artisan storage:link` where supported so public certificate files can be served correctly.
- Certificate generation requires writable public storage and a correctly configured application URL.

## Security notes

- OTP codes are hashed in storage and protected by expiration, single-use invalidation, maximum attempts, cooldowns, and request rate limits.
- Authentication, role checks, middleware, policies, and resource ownership checks protect organizer and administrative operations.
- Database constraints help prevent duplicate registrations and evaluations.
- SMTP credentials, application keys, passwords, and other secrets must be provided through environment variables and must not be committed to GitHub.
- Production deployments should use HTTPS, disable debug output, restrict access to logs, and keep dependencies up to date.
