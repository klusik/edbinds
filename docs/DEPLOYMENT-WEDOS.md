# WEDOS deployment checklist

## Before upload

- Confirm PHP 8.1 or newer is enabled for the subdomain.
- Create a MariaDB database and database user.
- Prepare FTP or file manager access.

## Upload

Upload all files to the subdomain directory. Keep the structure unchanged.

## Install

Open:

```text
https://subdomain.example.cz/install.php
```

Enter the database values exactly as shown in WEDOS administration. The installer creates tables and `config/config.php`.

## After install

- Log in with the administrator account.
- Upload a `.binds` file.
- Confirm that public and private visibility behave correctly in a private browser window.
- Keep `install.php` in place only if you accept the config-file lock. For maximum hardening, rename it after installation.

## Troubleshooting

### Installer says config is not writable

Set write permission on the `config` directory for the PHP process. On many shared hosts this means owner write permission is enough.

### Database connection fails

Check database host, database name, username, password and port. WEDOS often uses a host name different from `localhost` depending on hosting plan.

### CSS or JS does not load

The application expects `assets/` at the same URL level as `index.php`. Do not move `assets/` under `public/` unless you also update the layout paths.

### Direct access to app files works

Check whether `.htaccess` is enabled for the hosting plan. If not, move `app`, `config`, `database`, `docs`, `storage`, `tests` and `tools` outside the document root if your hosting layout allows it.
