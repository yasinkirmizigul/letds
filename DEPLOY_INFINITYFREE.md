# PROBABLUE demo deployment on InfinityFree

## Stable downloaded deployment mirror

The active upload directory is now **`.deploy/htdocs/`**, downloaded by the owner
from InfinityFree. Dated snapshots are no longer created or used. This directory
preserves the real hosting `.env`, uploaded documents, runtime data and remote-only
files. Do not replace it with a fresh blank environment or a whole development tree.

Develop and build in the primary project, then copy only changed deployable paths:

```powershell
& ./scripts/prepare-infinityfree-upload.ps1 -Paths @('public/build', 'public/assets/site/home/css/home.css', 'public/assets/site/home/js/home.js') -Preview
& ./scripts/prepare-infinityfree-upload.ps1 -Paths @('public/build', 'public/assets/site/home/css/home.css', 'public/assets/site/home/js/home.js')
node scripts/audit-infinityfree-assets.mjs --deployment .deploy/htdocs
```

The helper hashes managed files and copies only changed/new files. It does not
delete files or connect to FTP/DB, and refuses `.env`, `storage`, user uploads,
runtime caches, hot files, symlinks and excluded oversized bundles. `vendor` needs
explicit `-IncludeVendor` after a reviewed local production dependency install.
The primary project's AGENTS.md records this workflow for future changes.

Map local `.deploy/htdocs/` to remote `htdocs/` in PhpStorm. Upload only the paths
listed at the end of each change. Upload new `public/build/assets/` first and the
manifest last; do not delete old assets while cached pages may still reference them.
Keep `.env`, `storage/`, caches and unreviewed documents out of automatic uploads.

## Original preparation audit — 2026-10-04

Use [INFINITYFREE_UPLOAD_MANIFEST.md](INFINITYFREE_UPLOAD_MANIFEST.md) as the
authoritative upload checklist for `https://probablue.freedev.app`. The owner has
already completed the manual MySQL export/import; it was not repeated or remotely
verified. The original CDN preparation contained 15,076 files and 146,824,725
bytes with zero checked file-size violations. Those generated snapshots have
been replaced by the owner's downloaded `.deploy/htdocs/` mirror above.

Production Composer installation and Vite build succeeded again. All 78 runtime
packages and all 24 manifest-referenced build files were checked locally.
The Vite size warning is not a blocker. The two global bundle references now use
one pinned jsDelivr URL with verified SRI/CORS and unchanged script ordering.
Both local bundle files and eight other oversized, unreferenced vendor/demo JS
files are excluded from the upload only; all source copies are retained. No
original asset, business logic, Vite configuration or dependency version changed.
See the manifest for exact exclusions and the remaining manual checks.

## Readiness and scope

Prepared on 2026-10-04. This is a **demo deployment**, not production infrastructure.
The application/business logic, dependency versions, Vite configuration and
`public/.htaccess` have not been changed. No external database migration was run.

**READY as a local demo upload mirror**, with the CDN workaround below. Preserve
the downloaded hosting environment; blank examples are for initial setup only. Server PHP/extensions,
credentials, CDN/browser behavior and HTTP routing still need verification on
the hosting account. The owner reports database import complete; no hosting
account or remote database was accessed here.

## 1. Requirements and local checks

`composer.json` requires PHP `^8.4` and Laravel `^13.0`. `composer.lock` currently
installs Laravel **13.32.0**, Intervention Image **4.3.2**, and Symfony 8.1 packages
requiring PHP **>=8.4.1**. Therefore the locked application needs PHP **8.4.1 or
newer within PHP 8.x**. Do not downgrade Laravel or ignore platform requirements.
InfinityFree currently advertises PHP 8.4, but confirm the account's patch version
and extensions in its PHP information/control panel before uploading.
[InfinityFree features](https://www.infinityfree.com/)

Required PHP capabilities include PDO with `pdo_mysql`, fileinfo, DOM/libxml,
filter, hash, iconv, JSON, OpenSSL, PCRE, session and tokenizer. Laravel also uses
ctype and mbstring (Composer includes polyfills). The application's media service
explicitly uses **GD and WebP encoding**; check `gd`/WebP support and image-processing
memory limits even though Composer's platform check alone does not test this.
cURL is needed for enabled outbound HTTP integrations. Verify all this on the host,
not just on the local machine.

The locked Vite toolchain needs Node `^20.19.0 || >=22.12.0`; Node 24 is suitable.
Run these commands **locally in the project/upload copy**, never on InfinityFree:

```powershell
npm ci
npm run build
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
php artisan --version
php artisan route:list --except-vendor
```

On Windows, use `npm.cmd` if PowerShell prevents execution of `npm.ps1`.
Do not install npm with `--omit=dev` before building: Vite and the Laravel plugin
are development dependencies needed at build time. Keep both lockfiles; use
`composer install`, not `composer update`. Keep all required runtime packages,
including Intervention Image, Laravel and Tinker.

Local verification succeeded with PHP 8.5.1, Composer 2.8.9, Node 24.12.0 and
npm 11.6.2. `npm ci`, Vite build (Vite 8.3.0), production Composer install,
Composer validation/platform checks and Laravel route registration passed.
The build generated `public/build/manifest.json` plus hashed assets. The largest
generated JavaScript chunk is about 591 kB, below the hosting ceiling; Vite's
500 kB chunk warning is not itself a deployment failure.

Composer reported ambiguous Flysystem local adapter classes in the existing
locked packages; package discovery and route registration still passed. Composer
2.8.9 also emits PHP 8.5 deprecation warnings. Neither was fixed by changing
dependencies. `npm audit` reported **7 high-severity findings**, in the formatter/
glob dependency tree. Review separately; no `npm audit fix` or forced upgrade was
performed. `node_modules` is not uploaded.

`composer audit --no-dev` also reported **two runtime advisories in
league/commonmark** (one medium, one high):
[raw-HTML filtering bypass](https://github.com/advisories/GHSA-97jj-33gv-5xf9)
and [Markdown table parsing denial of service](https://github.com/advisories/GHSA-3q6v-r5mr-hxv8).
This dependency finding is not proof that a particular application route is
exploitable, but it needs review before accepting untrusted demo input. Package
versions were kept unchanged as requested; dependency remediation is separate work.

The current local `vendor` directory now contains production dependencies only.
For subsequent development/testing, run `composer install` locally to restore
development packages. Re-run `composer install --no-dev` in the upload copy before
the next deployment. Factories use Faker, so prepare any factory-based demo data
with development dependencies installed first.

## 2. Pinned CDN workaround and upload limits

InfinityFree's current documentation limits **PHP, HTML and JS files to 1 MB**,
`.htaccess` to **10 kB**, and other files to **10 MB**. Oversized files can disappear
after FTP upload; FTP does not bypass these limits.
[File limits](https://forum.infinityfree.com/t/why-are-my-files-deleted-after-uploading-them/49310/1)

These existing public assets exceed 1 MB (byte sizes measured locally):

| Path relative to project root | Bytes |
| --- | ---: |
| `public/assets/admin/js/datatables/allowed-ip-addresses.js` | 1,097,747 |
| `public/assets/admin/plugins/custom/ckeditor/ckeditor-balloon-block.bundle.js` | 1,389,497 |
| `public/assets/admin/plugins/custom/ckeditor/ckeditor-balloon.bundle.js` | 1,382,432 |
| `public/assets/admin/plugins/custom/ckeditor/ckeditor-classic.bundle.js` | 1,388,775 |
| `public/assets/admin/plugins/custom/ckeditor/ckeditor-document.bundle.js` | 1,489,541 |
| `public/assets/admin/plugins/custom/ckeditor/ckeditor-inline.bundle.js` | 1,380,801 |
| `public/assets/admin/plugins/custom/datatables/datatables.bundle.js` | 3,607,823 |
| `public/assets/admin/plugins/custom/tinymce/tinymce.bundle.js` | 2,443,722 |
| `public/assets/admin/plugins/global/plugins.bundle.js` | 3,558,077 |
| `public/assets/site/plugins/global/plugins.bundle.js` | 3,558,077 |

All ten files above are now explicitly excluded from the upload package. The
eight non-global files have no current application/configuration/asset references;
the active editor uses `assets/admin/vendors/tinymce/tinymce.min.js`, and the
active admin table library remains `assets/admin/js/datatables.min.js`.

Both `resources/views/admin/layouts/partials/scripts.blade.php` and
`resources/views/site/appointments/index.blade.php` load the same pinned script:

```html
<script
    src="https://cdn.jsdelivr.net/gh/yasinkirmizigul/letds@78ed8455ae58351e82cc6471afc8181785870eb3/public/assets/admin/plugins/global/plugins.bundle.js"
    integrity="sha384-0XhE5yWVZ06NM+Ne65TKqFljEnvP0CxyX+wM1nrNw/94047GRXFhy1QK9ruhjw5M"
    crossorigin="anonymous"
></script>
```

No async/defer or load-order change was introduced. A read-only CDN fetch returned
HTTP 200, CORS `*` and matching SHA-384. The CDN response uses Git LF line endings;
do not substitute the SRI calculated from the local CRLF copy. Keep the admin
bundle as the canonical source; the duplicate site source is retained too.
Neither source file is required in htdocs. Do not use raw.githubusercontent.com,
a moving branch or automatic CDN minification. CDN outages affect this demo;
bundle licensing, including commercial FormValidation, remains the owner's
responsibility. Hosted browser smoke tests must check login/admin/appointments
and any host-added CSP. Vite output and CSS/fonts remain local and intact.

```powershell
& ./scripts/prepare-infinityfree-upload.ps1 -Paths @('public/build', 'resources/views/admin/layouts/partials/scripts.blade.php', 'resources/views/site/appointments/index.blade.php')
node scripts/audit-infinityfree-assets.mjs --deployment .deploy/htdocs
```

The production `vendor/composer/autoload_classmap.php` is about 728 kB and
`autoload_static.php` about 797 kB; both currently fit. Recheck future builds.
If an optimized autoloader eventually exceeds the limit, regenerate it locally
without optimization, preserving runtime packages:

```powershell
composer dump-autoload --no-dev --optimize=false
```

This alternative is documented by
[InfinityFree's Laravel guide](https://forum.infinityfree.com/t/how-to-install-a-laravel-site-on-infinityfree/118578).
About 15,300 files were counted in the inspected application/vendor/public tree,
excluding the local public-storage junction; compare the final upload's file and
directory count with your account's inode allowance. Sanitize sample uploads and
scan their sizes too; the current storage files checked had none >=10,000,000 bytes.

## 3. Prepare a separate upload copy and demo database

Use a separate local release folder, e.g. `C:\Users\yasn\Desktop\infinityfree-demo\htdocs`.
Do not overwrite the development `.env` or export its database blindly. The host
does not need a `.git` checkout, SSH, Composer, npm or Artisan.

For a fresh demo, use an isolated **local MySQL** database; the project's default
SQLite example is not the database to upload. Before running any database command,
check that the upload copy's `.env` points at the isolated local server
(`DB_HOST=127.0.0.1`) and a new demo database, not InfinityFree or another external
database. Install development dependencies if demo factories need Faker.
Choose strong credentials for the seeders using `SEED_CREATE_USERS=true`,
`SEED_SUPERADMIN_EMAIL`, `SEED_SUPERADMIN_NAME`, `SEED_SUPERADMIN_PASS`,
`SEED_ADMIN_EMAIL`, `SEED_ADMIN_NAME`, `SEED_ADMIN_PASS`; never use the default
`123456` accounts. Then, **only for that isolated local database**:

```powershell
php artisan migrate --seed
```

Alternatively, make a sanitized local snapshot of existing demo data. Keep its
`migrations` table so future schema versions are identifiable. In the local demo
admin panel, remove real members/documents/contact details, change weak account
passwords, disable real payment integrations, disable mail notifications and
WhatsApp, and remove SMTP/API credentials before exporting.

`SiteSetting.smtp_password` is encrypted with `APP_KEY`: changing the key without
clearing/re-encrypting encrypted values breaks decryption. For a new sanitized
demo, clear those secrets first and generate a new demo-only key. For later updates,
retain the deployed demo key. Do not rotate the development key indiscriminately.

Export the **local MySQL demo database's structure and data** using local
phpMyAdmin's Export tab: SQL format, all application tables, UTF-8/utf8mb4; include
the `migrations` table. Omit `CREATE DATABASE`, `USE` directives naming your local
database, server-specific `DEFINER` clauses, and unnecessary privileged statements.
Keep the SQL dump outside `htdocs`. Empty local sessions, jobs, failed-job records
and transient logs/diagnostics from the demo snapshot as appropriate.

Create a MySQL database in InfinityFree's panel. Open its phpMyAdmin, select that
database, and import the SQL dump there. If the import exceeds panel limits, use
smaller SQL exports split at complete SQL statement/table boundaries. Do not upload
a database installer or an Artisan-over-HTTP endpoint. Do not run migrations
against any external database. For later schema changes, prepare and verify an
explicit SQL upgrade offline, back up the hosted database, then import it through
phpMyAdmin; don't overwrite hosted demo data with an old snapshot by accident.

## 4. Configure the real deployment .env

Copy `.env.infinityfree.example` to `.env` **in the upload copy only**. Fill in:

- `APP_URL`: actual HTTPS domain, no `/public` suffix or trailing slash.
- `APP_KEY`: fresh demo-only key generated locally, or the existing deployed demo
  key when retaining encrypted demo data.
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: the exact values from the
  InfinityFree MySQL panel. Its host is not `localhost`.
- `DEMO_ACCESS_USERNAME` and a strong `DEMO_ACCESS_PASSWORD`: blank password
  intentionally makes Laravel web pages return HTTP 503. Set before upload.

Generate a new key locally without modifying your development `.env`:

```powershell
php artisan key:generate --show
```

Paste the result privately into the deployment `.env`. Treat it as a secret; do not
put it in the example, a committed file, screenshots, logs or a public SQL dump.
Quote environment values containing spaces or `#`. Keep `APP_ENV=production`,
`APP_DEBUG=false`, `LOG_CHANNEL=single`, `SESSION_DRIVER=file`, `CACHE_STORE=file`,
`QUEUE_CONNECTION=sync`, and `FILESYSTEM_DISK=public`.

Enable HTTPS before using `SESSION_SECURE_COOKIE=true`; otherwise cookies will not
be sent over HTTP. Leave `SESSION_DOMAIN=null` for host-only cookies. `MAIL_MAILER=log`
and `WHATSAPP_STAGE_ENABLED=false` are demo defaults. Database-backed site SMTP
settings can override `MAIL_MAILER=log`: disable `mail_notifications_enabled`,
`notify_contact_messages` and `notify_appointments` in the sanitized demo settings.
Do not enable live payments or reuse real external-service tokens.

The existing `.env.infinityfree.example` was found to contain real secrets in both
the working file and Git's staged snapshot. The working template is now sanitized;
the Git index was not changed. **Rotate the exposed DB password and review key
exposure before deploying. Before any future commit, unstage the old example and
stage only the sanitized version**; sanitizing the working file alone does not
sanitize a previously staged snapshot. No commit or push was made here.

## 5. Upload layout and exclusions

Upload the **contents** of the release folder into the domain's `htdocs`, not an
extra `letds` subfolder and not only Laravel's `public` directory:

```text
htdocs/
  .htaccess                 # new root rules
  .env                      # private deployment credentials
  artisan
  composer.json
  composer.lock
  app/
  bootstrap/
    app.php
    providers.php
    cache/                  # writable; no local generated PHP caches
  config/
  database/                 # migrations/factories/seeders; NOT DB dumps/sqlite
  resources/
  routes/
  vendor/                   # production Composer dependencies
  public/
    .htaccess               # original, unchanged
    index.php
    build/                  # manifest.json AND all generated assets
    assets/                 # local assets, excluding the ten oversized JS files
    ...                     # favicons/fonts/robots/sitemap/other public files
  storage/
    app/public/             # sanitized public uploads
    app/private/            # only approved demo private documents
    framework/cache/data/   # empty, writable
    framework/sessions/     # empty, writable
    framework/views/        # empty, writable
    logs/                   # empty, writable
```

Also include any real runtime `lang` directory if present in a later release.
Show hidden files in FTP so both `.htaccess` files and `.env` transfer correctly.
The root rules forward requests to `/public` internally and map `/storage/...`
to `storage/app/public/...` with `[END]`, without a symlink. Additional deny rules
protect hidden files, private application directories, storage internals and
executable uploads; a `/public` guard prevents rewrite loops. No `public/index.php`
path edits are needed.

**Do not upload:**

- `.git`, `.github`, `.codex`, `.agents`, `.aws`, `graphify-out`, IDE settings,
  node_modules, tests, development scripts/docs, this guide, or environment examples.
- Your development `.env`, backups, `auth.json`, credentials, SQL exports,
  `database/database.sqlite` or SQLite WAL/SHM files, test database files or archives.
- `public/hot` (it points Vite at your development server), source maps containing
  unwanted source details, or `public/storage` (currently a Windows junction).
- Generated local `bootstrap/cache/*.php` including config/routes/events/packages/
  services manifests, compiled Blade files, file-cache entries, development sessions,
  log files, `storage/pail`, framework test files or local maintenance-state files
  (`storage/framework/down`, `maintenance.php`). Keep the empty directories.
- Unreviewed user uploads/private documents or signing keys from `storage/*.key`.

Do not prune individual runtime Composer packages to reduce size. Keep the
production `vendor` contents and check FTP transfer results/host file scanning.
Upload files individually with FTP rather than a large application ZIP that exceeds
the file limit. Verify the transferred files actually remain on the server.

## 6. Storage and writable directories

All required runtime directories already exist locally: `storage/framework/cache`
(including `data`), `sessions`, `views`, and `storage/logs`. `storage/app/public`,
`storage/app/private`, and `bootstrap/cache` also exist. FTP clients/archives may
omit empty directories, so explicitly recreate each on the host.

Make `storage` and its runtime subdirectories, plus `bootstrap/cache`, writable
by PHP. Use the account's supported ownership/permissions; typically directories
755 and files 644 work, with 775 directories only if required by group ownership.
Do not default to world-writable 777. Site SEO management writes
`public/robots.txt`, `public/sitemap.xml` and `public/llms.txt`; these files/their
parent must be writable if those features are exercised.

Public media remains in `storage/app/public`. Its URL is `/storage/<relative-path>`
(e.g. `/storage/media/example.webp`), not `/public/storage/...` or
`/storage/app/public/...`. **Do not run `storage:link`**, upload a junction, or copy
private storage to public. Authorized controllers already serve private member
documents/project files from `storage/app/private`; preserve that separation.
Direct public storage is not protected by the application's Basic Auth demo gate,
so never put confidential content on the public disk.

## 7. Compatibility risks accepted for this demo

- **Queues/workers:** `SendContactMessageReceivedMailJob`,
  `SendAppointmentUpdatedMailJob`, `SendAppointmentAdminNotificationMailJob`, and
  `SendProjectStageWhatsAppJob` implement `ShouldQueue`. The template's `sync`
  driver executes dispatched jobs in the request; no background worker exists,
  queued database jobs won't drain, and worker retry/backoff semantics are lost.
  External mail/WhatsApp latency can hit shared-host request timeouts. Jobs and
  application dispatch logic were not rewritten.
- **Scheduler:** `routes/console.php` runs `trash:purge` daily at 03:30 and
  `service-reviews:sync` daily at 02:45 with overlap prevention. These will NOT run
  automatically here. Trash retention and service-review synchronization will
  therefore not behave as on a server with Artisan scheduling. No cron/HTTP-shell
  substitute was added.
- **Redis/Memcached/WebSockets:** optional configured stores are not supplied by
  this hosting setup. File sessions/cache and log broadcasting avoid them. No
  explicit Redis-dependent application code or required persistent WebSocket
  worker was found during inspection; do not enable those optional backends.
- **Shell/CLI:** no runtime `exec`, `shell_exec`, `proc_open` or `Process::` calls
  were found in `app`/`routes`. Composer's dev script starts serve/queue/pail/Vite
  processes, and custom console/scaffolding commands still require a local CLI.
  They cannot be invoked on this server. Composer package discovery ran locally.
- **Uploads/GD:** several controllers accept 12 MB or 20 MB files, exceeding the
  host's 10 MB filesystem ceiling. Use comfortably smaller demo uploads, respecting
  `upload_max_filesize`, `post_max_size`, execution/memory limits and GD/WebP support.
  Controller validation was not reduced automatically.
- **Mail/integrations:** SMTP in database settings overrides environment defaults;
  disable it for this demo or separately test a demo-only external SMTP account.
  Payment/webhook functionality is unsuitable for a live demonstration with real
  transactions: the host's browser-verification security can block non-browser
  callbacks/API consumers, and the demo gate also requires authorization.
  [InfinityFree browser security](https://forum.infinityfree.com/t/browser-security-system-features-and-limitations/49353)
- **Resource limits:** CPU, memory, file counts and request limits must be checked
  on the account. A successful local build is not proof every module will fit.

No automatic system rewrites, business changes or infrastructure integrations were
performed to hide these risks.

## 8. Cache clearing without SSH (first upload and updates)

Do not run `config:cache`, `route:cache`, `view:cache` or `optimize` on Windows and
upload their output: cached configuration can embed local paths and credentials.
For the upload copy, simply omit generated caches. Optional local cleanup:

```powershell
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
```

Use those only in the isolated upload copy. Avoid broad `optimize:clear` against
the development `.env`, since its cache driver may point to a database/external
store. No server Artisan command is necessary.

After an update, use FTP/File Manager to delete **only generated** PHP files under
`htdocs/bootstrap/cache`, contents of `htdocs/storage/framework/views`, and cache
entries under `htdocs/storage/framework/cache/data`. Keep directories and their
`.gitignore` placeholders; do not delete `bootstrap/app.php`, uploaded files or
private documents. Package/service discovery manifests and views will regenerate
on requests, so those directories must be writable. File config/route caches remain
absent unless deliberately generated. Delete session files only when intentionally
logging everyone out. Preserve the hosted `.env`, `APP_KEY`, user uploads and DB on
ordinary code updates. Back up before replacement; coordinate updates during a
quiet demo period and upload matching assets plus manifest together.

Never create a public `Artisan::call`, migration, shell-command, cache-clearing or
unzipper endpoint. Do not upload Windows generated path caches as a workaround.

## 9. Hosted smoke checks and HTTP 500 troubleshooting

Use a real browser with JavaScript/cookies enabled (InfinityFree may insert a browser
verification challenge). After configuring the demo credentials, test `/`, `/login`,
the admin dashboard, `/randevu-al`, a Vite asset listed in the manifest, a small public
image at `/storage/<path>`, and an authorized private-document download.
Verify incorrect demo credentials are rejected, and `/.env`, `/public/.env`,
`/vendor/autoload.php`, `/storage/app/private/<known-file>` and
`/storage/logs/laravel.log` return 403/404 and never disclose content. Requests for
executable uploads must be denied. Do not share sensitive response bodies.

For an HTTP 500, work through these checks:

1. Read `storage/logs/laravel.log` privately through FTP and the host's error logs.
   Keep `APP_DEBUG=false`; do not display secrets to visitors. If no Laravel log
   exists, the error may precede Laravel (PHP platform, missing vendor, or Apache).
2. Confirm PHP >=8.4.1, all required extensions, GD/WebP and `pdo_mysql`. Do not
   edit `vendor/composer/platform_check.php` or use `--ignore-platform-reqs`.
3. Check `vendor/autoload.php`, `vendor/composer/*`, `public/index.php`, both
   `.htaccess` files, and the full `public/build` tree survived the upload. Check
   FTP failed transfers and silently deleted oversized JS/PHP files.
4. Check directory write permissions and clear only the generated caches described
   above. Look for old maintenance flags, stale dev Debugbar providers/manifests,
   `public/hot`, or config cached with Windows paths.
5. Confirm `.env` filename, a valid APP_KEY, MySQL panel hostname/credentials,
   imported tables and matching schema. A blank/malformed key causes encryption
   failures; changing the key breaks previously encrypted SMTP settings.
6. Diagnose rewrite-only failures separately: root `.htaccess` must live directly
   in the domain's `htdocs`, Apache rewrite support must be active, and the public
   rules must be unchanged. Keep `[END]` on the public-storage rule to avoid its
   physical target being rewritten recursively. Investigate any host-rejected
   directive through the server error log without disabling private-file guards.
7. HTTP 503 with the demo-access message means a missing demo username/password,
   not a Laravel crash; HTTP 401 is the expected Basic Auth challenge. HTTP 419
   usually means sessions/cookies/HTTPS configuration. Broken JavaScript with a
   working page can indicate stale oversized uploads or a blocked CDN/SRI request;
   check the browser Network/Console panels and ensure the pinned tag is intact.

There is no local Apache installation available to validate `.htaccess` execution;
PHP's built-in server/`artisan serve` does not exercise Apache rewrite rules.
Actual hosted routing/security smoke checks remain mandatory. The static-file
blocker is resolved in the CDN mirror; retain the existing private environment
and complete the host checks above. This prepares a deployment; it does not
publish or certify the demo.
