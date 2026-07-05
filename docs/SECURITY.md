# Security notes

## Threat model

This application stores user-uploaded XML and displays parsed values publicly when a binding set is published. The primary risks are credential leakage, XML parser abuse, cross-site scripting, unauthorized private binding access and accidental exposure of configuration files.

## Controls implemented

- `config/config.php` is not committed.
- Sensitive directories are blocked by `.htaccess`.
- XML processing is local only and does not dereference external entities.
- Templates escape dynamic values.
- State-changing POST requests require CSRF tokens.
- Passwords use PHP password hashing plus a generated pepper.
- Private binding access is checked in repository queries.

## Operational recommendations

- Use HTTPS only.
- Disable registration if the site is for a closed group. Set `app.allow_registration` to `false` in `config/config.php`.
- Rename or delete `install.php` after installation.
- Keep PHP updated in WEDOS administration.
- Back up MariaDB regularly. The version history lives in the database.
