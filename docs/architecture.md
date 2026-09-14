# Hafez System — Laravel Architecture Decisions

## Application structure

Laravel is the application framework. HTTP routes lead to controllers; controllers coordinate validation, authorization, service operations, and Blade responses. Eloquent models represent only the approved database entities. The UI is server-rendered Blade using Bootstrap RTL.

## Folder responsibilities

- `app/`: shared application and domain code.
- `app/Http/Controllers/`: request coordination and responses.
- `app/Models/`: Eloquent models and relationships for approved tables only.
- `app/Http/Requests/`: request validation and request-level authorization.
- `app/Policies/`: resource authorization for Platform Admin and User.
- `app/Services/`: reusable approved business operations, including score/result calculation, certificates, reports, file handling, and audit logging.
- `resources/views/`: Blade layouts and approved screens.
- `routes/`: public, authentication, and protected route definitions.
- `database/`: migrations, factories, and seeders.
- `storage/`: runtime and approved uploaded/generated files.

## Access boundary

Authentication protects private routes. Middleware and policies enforce only Platform Admin and User access. Student registration, status follow-up, and certificate verification remain public flows; students do not authenticate.

## Frontend foundation

The shared Blade layout uses Bootstrap RTL and Arabic right-to-left direction. The approved visual direction is green, gold, white, and light gray. Responsive behavior applies to approved screens only.
