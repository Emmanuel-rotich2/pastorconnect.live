# FGCK Joyland Security & Routing Notes

## Public routing
The application uses extensionless public routes such as:

- `/`
- `/auth/login`
- `/auth/register`
- `/member/dashboard`
- `/staff/login`
- `/staff/dashboard`
- `/api/availability?date=YYYY-MM-DD`

Apache internally maps these routes to the existing PHP handlers. Direct browser requests to application `.php` handlers are rejected.

## Private areas
The following directories are blocked from HTTP access:

- `config/`
- `includes/`
- `database/`
- `storage/`
- `files/`
- `private/`
- `backups/`

SQL, log, backup, temporary, environment, and hidden files are also blocked.

The delivered ZIP intentionally does not include the runtime `storage/` directory. Email/SMS logging recreates it when necessary; the root routing rules continue to deny browser access.

## Session protections
The bootstrap enables strict session mode, HTTP-only cookies, SameSite=Lax cookies, session ID regeneration on authentication, and active-account checks for staff sessions.

## CSRF
State-changing POST requests use the application's CSRF token verification.

## Email credentials
SMTP credentials are loaded from environment variables. No real SMTP password is stored in this project.

Because an SMTP app password was present in an earlier project copy, that credential should be revoked/rotated immediately and replaced through the server environment.

## Deployment
1. Copy `.env.example` values into the hosting environment (do not create a publicly accessible `.env` file).
2. Set a dedicated database user instead of MySQL `root`.
3. Use HTTPS and a valid TLS certificate.
4. Set `FGCK_EMAIL_ENABLED=true` and the SMTP variables when email OTP/reset delivery is required.
5. Ensure the web server permits `.htaccess` overrides (`AllowOverride FileInfo Limit` or `All`) and has `mod_rewrite` and `mod_headers` enabled.
6. Keep the database server inaccessible from the public internet.
7. Back up the database outside the public web root.
