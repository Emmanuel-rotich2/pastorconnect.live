# FGCK Joyland Routing

The public application is routed through extensionless URLs. Existing PHP files remain on disk as internal controllers so the application does not require a risky rewrite of its business logic.

Apache rewrites:

`/staff/dashboard` -> `staff/dashboard.php`

`/member/profile` -> `member/profile.php`

`/auth/login` -> `auth/login.php`

`/api/availability?date=...` -> `api/availability.php?date=...`

Direct requests for those `.php` URLs are blocked. Private application directories are blocked separately. Assets remain directly accessible because they are static public resources.
