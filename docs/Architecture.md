# Architecture

## Goal

The application stores Elite Dangerous `.binds` files and turns them into compact reference cards. It must run on ordinary PHP shared hosting. That constraint drives the architecture:

- no framework bootstrap cost;
- no Composer dependency requirement;
- no queue or background worker;
- no command line requirement after upload;
- no writable public directory requirement.

## Runtime model

`index.php` loads `bootstrap.php`, which loads `config/config.php`, starts the session and registers a PSR-4 style autoloader for the `App\` namespace.

Routing is query based:

```text
index.php?page=home
index.php?page=login
index.php?page=dashboard
index.php?page=upload
index.php?page=view&id=1
index.php?page=admin
```

This avoids reliance on Apache rewrite rules. `.htaccess` is used only for directory protection and index listing prevention.

## Main layers

### Core

`App\Core` contains infrastructure:

- `Config`: dot notation access to the generated configuration.
- `Database`: shared PDO connection.
- `Session`: secure session start and flash messages.
- `Security`: HTML escaping, CSRF, password hashing and password verification.
- `Response`: redirects and simple error responses.
- `View`: PHP template rendering.
- `App`: query parameter router.

### Controllers

`App\Controllers` contains thin HTTP controllers:

- `HomeController`: public binding list.
- `AuthController`: login, logout and registration.
- `BindingController`: dashboard, upload, view and XML download.
- `AdminController`: admin overview and visibility updates.

Controllers should not parse `.binds` XML or build SQL. They orchestrate services and repositories.

### Repositories

`App\Repositories` contains database access:

- `UserRepository`: user lookup, creation and login timestamp updates.
- `BindingRepository`: binding set and version queries and writes.

The version table is append-only from the UI. A new version never modifies an older version row.

### Services

`App\Services` contains domain behavior:

- `AuthService`: current user resolution, login, logout and access guards.
- `BindingUploadService`: upload validation and version payload preparation.
- `BindParser`: XML parsing and normalized model creation.
- `ControlDictionary`: command labels and categories.

## Database model

### users

Stores login identities. Passwords are hashed with `password_hash()` plus an application pepper generated during installation.

### binding_sets

A binding set is the logical profile. It has an owner, title, description, visibility and active version pointer.

### binding_versions

A binding version stores the original XML, the parsed JSON model, a SHA-256 file hash and a SHA-256 normalized hash. Version numbers are unique per binding set.

## Access rules

Anonymous users can see public binding sets only.

Authenticated users can see public binding sets and their own private binding sets.

Administrators can see all binding sets and can change visibility.

## Parser model

`BindParser` uses a constrained Elite `.binds` scanner rather than loading external resources. It validates the `Root` element and extracts only usable bindings:

- `Primary`
- `Secondary`
- `Binding`

A binding is usable when the device is not `{NoDevice}` and the key is not empty. Optional `Modifier` nodes are included in the combo string.

The resulting JSON model contains:

```text
metadata
stats
categories
commands
conflicts
```

The card view renders the server-side categories and exposes the same model to JavaScript for the keyboard heat map.

## Themes

The themes are CSS variable sets applied to `.binding-graphic`:

- `theme-elite`: dark cockpit style with orange HUD accent.
- `theme-light`: print-friendly light reference sheet.
- `theme-grey`: neutral low-saturation cockpit sheet.

The design is deliberately DOM based rather than generated image based. It remains responsive, printable, accessible and editable by CSS.

## Security decisions

- Uploaded XML is scanned locally and no external resource is loaded.
- File extension is restricted to `.binds` and `.xml`.
- Upload size is limited by `app.upload_max_bytes`.
- All state-changing forms use CSRF tokens.
- All templates escape dynamic output with `Security::e()`.
- Sensitive directories are blocked by `.htaccess`.
- `config/config.php` is ignored by Git.

## Extension points

### Adding better labels

Add command mappings in `App\Services\ControlDictionary::LABELS` and category overrides in `CATEGORY_OVERRIDES`.

### Adding a theme

Add a `.theme-name` CSS variable block in `assets/css/app.css`, then add a toolbar button in `app/Views/bindings/view.php`.

### Adding exports

Use the parsed JSON stored in `binding_versions.parsed_json`. A future export controller can generate SVG, PDF or PNG without reparsing XML.
