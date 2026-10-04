# InfinityFree upload manifest — 2026-10-04

## Current workflow: `.deploy/htdocs/`

The owner downloaded the live htdocs to this stable local mirror. Do not create
dated packages, overwrite the real `.env`, synchronize local storage over hosted
uploads, or delete remote-only files. This current workflow supersedes the original
fresh-package instructions below. Initial setup examples are not a reason to replace
an already configured environment.

Develop/build locally, then run `scripts/prepare-infinityfree-upload.ps1` with an
explicit `-Paths` array of changed project-relative paths (`-Preview` first).
The helper outputs the exact changed upload paths; copies only changed/new files;
skips oversized CDN bundles, generated caches, hot files and symlinks; and never
uploads remotely or deletes anything. `vendor` requires `-IncludeVendor` after a
reviewed production Composer install. PhpStorm local `.deploy/htdocs/` maps to
remote `htdocs/`. Build assets must be uploaded before their manifest.

Current comparison: deployed P/mobile fixes and both pinned CDN references already
match the development source. Only `resources/css/app.css` was synchronized to
bring the remote source copy up to date; existing compiled CSS was already correct.
Upload that source file only if keeping the hosted source tree synchronized.
The downloaded mirror's latest audit contains **15,023 files / 145,760,990 bytes**,
with no hosting file-limit violations. No uploaded data or remote-only files were deleted.

**Deployment readiness: READY (local upload package).** Target: `https://probablue.freedev.app`.
Owner-only environment configuration and hosting smoke tests remain manual steps.
This is a demo, not production infrastructure. MySQL export/import is already
completed according to the owner; it was not repeated or remotely verified.
No remote database connection, password request, remote HTTP deployment,
migration, seeder, commit or push was performed. Business logic is unchanged.

## Verified locally

- `composer.json`: PHP `^8.4`, Laravel `^13.0`. Lock: Laravel 13.32.0,
  Symfony 8.1 packages require PHP **>=8.4.1, <9**. Confirm hosting patch version.
- Production install succeeded. All **78 locked runtime packages** and their
  installation directories exist. No required runtime package was removed.
- `npm ci` and `npm run build` succeeded. Vite manifest has **23 entries**;
  all **24 referenced files** and imported manifest entries exist. Largest generated
  JS is approximately 591 kB: the Vite 500 kB warning is NOT a hosting blocker.
- `public/hot` does not exist. The snapshot explicitly excludes it and the local
  `public/storage` junction. Runtime directories listed below exist.
- `public/.htaccess` is unchanged, SHA256
  `B7E379C77639FD56144947DBAE84C84EB466D9C686EA81F2F013AE85421DA923`.
- Offline URL simulation passed 8 checks: base URL, `asset`, `url`, named `route`,
  public disk URL, Vite origin/build path, no `/public` prefix, no Vite dev server.
  No application kernel, real `.env`, DB or HTTP request was used by that test.
- Static URL/asset review covered 202 URL-using PHP/JS/CSS source files and 37 literal
  `asset()` paths. No protected-directory asset conflict was found. Dynamically
  computed paths, stored database URLs and every route's execution are not proven
  by this test; owner smoke checks remain necessary.

## Resolved size blocker: pinned jsDelivr bundle

Both required Blade script references now use this exact shared URL:

```text
https://cdn.jsdelivr.net/gh/yasinkirmizigul/letds@78ed8455ae58351e82cc6471afc8181785870eb3/public/assets/admin/plugins/global/plugins.bundle.js
```

```text
integrity="sha384-0XhE5yWVZ06NM+Ne65TKqFljEnvP0CxyX+wM1nrNw/94047GRXFhy1QK9ruhjw5M"
crossorigin="anonymous"
```

No async/defer was added; script order is unchanged. The pinned endpoint was
fetched read-only and returned HTTP 200, JavaScript MIME type, CORS `*`, and the
exact expected SHA-384. Response: 3,555,668 bytes, identical to the canonical
source after Git LF normalization. Local CRLF source is 3,558,077 bytes, so do
not calculate the production SRI from the unnormalized local file.

The admin bundle remains the canonical repository source. The duplicate site
source is retained too: neither was deleted or altered. Neither oversized file
is uploaded to InfinityFree. The other eight oversized, unreferenced vendor/demo
files below are also explicitly excluded from the package, not deleted from the
repository. Searches covered application/configuration and public asset code;
no external/custom database HTML usage can be guaranteed by static inspection.
The active TinyMCE and DataTables assets remain in the package.

CDN availability is now a demo dependency; a local oversized fallback cannot
work on InfinityFree. Do not change this pinned URL to a moving branch or
raw.githubusercontent.com. Bundle licenses (including commercial FormValidation)
remain the owner's responsibility. No repository CSP/SRI policy blocks the tag;
any host-added CSP must allow the chosen CDN. Hosted browser tests remain manual.

InfinityFree documents a **1 MB ceiling for PHP/HTML/JS**, **10 kB for .htaccess**,
and **10 MB for other files**. Oversized files are automatically removed regardless
of upload method; FTP does **not** bypass this file-system policy.
[Official file-size policy](https://forum.infinityfree.com/t/why-are-my-files-deleted-after-uploading-them/49310/1).
This is a documented limit, not an observed upload failure on this account: no
test upload was authorized or performed. All source JS files below exceed even
1 MiB; **all ten are absent from the new upload package**.

| File under htdocs | Original bytes | Conservative minification bytes | Application reference |
| --- | ---: | ---: | --- |
| `public/assets/admin/js/datatables/allowed-ip-addresses.js` | 1,097,747 | 1,065,493 | No exact source reference found |
| `public/assets/admin/plugins/custom/ckeditor/ckeditor-balloon-block.bundle.js` | 1,389,497 | 1,370,605 | No exact source reference found |
| `public/assets/admin/plugins/custom/ckeditor/ckeditor-balloon.bundle.js` | 1,382,432 | 1,363,550 | No exact source reference found |
| `public/assets/admin/plugins/custom/ckeditor/ckeditor-classic.bundle.js` | 1,388,775 | 1,369,723 | No exact source reference found |
| `public/assets/admin/plugins/custom/ckeditor/ckeditor-document.bundle.js` | 1,489,541 | 1,470,616 | No exact source reference found |
| `public/assets/admin/plugins/custom/ckeditor/ckeditor-inline.bundle.js` | 1,380,801 | 1,361,908 | No exact source reference found |
| `public/assets/admin/plugins/custom/datatables/datatables.bundle.js` | 3,607,823 | 3,325,705 | No exact source reference found |
| `public/assets/admin/plugins/custom/tinymce/tinymce.bundle.js` | 2,443,722 | 1,829,657 | No exact source reference found |
| `public/assets/admin/plugins/global/plugins.bundle.js` | 3,558,077 | 2,776,740 | Canonical CDN source; both Blade tags use it externally |
| `public/assets/site/plugins/global/plugins.bundle.js` | 3,558,077 | 2,776,740 | Retained duplicate; no deployment reference |

The two required global bundles are served externally; the eight other files have
no current application references and are not required by this demo package.
The working editor uses the smaller
`public/assets/admin/vendors/tinymce/tinymce.min.js`, not the oversized custom bundle.

The local audit parsed/minified in memory with compression and name mangling OFF.
Names and script structure were preserved; all parses succeeded. Output was NOT
written and no original asset was changed. This low-risk size reduction alone
does not meet the limit. Splitting/rebuilding libraries requires load-order and
browser regression checks; it is not proven safe or performed automatically.
Transfer compression/gzip does not shrink the JS file stored on the hosting disk.

## MUST UPLOAD

Use the reviewed **`.deploy/htdocs/`** mirror. For updates upload only the changed
paths reported by the synchronization helper, not the entire downloaded directory.
The mirror passes the checked hosting limits with zero JS files >=1 MB, both CDN
references intact, and all 24 Vite-referenced files present. The real downloaded
environment and storage/cache data are preserved locally, not recreated or uploaded.
The earlier generated snapshots are obsolete. Do not create `htdocs/htdocs` remotely.

```text
htdocs/
  .htaccess                    # root protection and public/storage rewrites
  .env                         # downloaded hosting configuration: preserve, do not overwrite
  artisan
  composer.json
  composer.lock
  app/
  bootstrap/                   # cache directory present, generated cache absent
  config/
  database/                    # migrations/seeders source, not SQL dumps
  public/                      # original .htaccess, index.php, assets, build, SEO files
  resources/                   # includes all Blade views
  routes/
  vendor/                      # all production runtime packages + autoload files
  storage/
    app/public/                # reviewed public uploads and original paths
    app/private/               # empty unless approved demo documents added manually
    framework/cache/data/
    framework/sessions/
    framework/views/
    logs/
  lang/                        # only if the project has this directory
```

Storage and uploaded documents were downloaded by the owner and are left untouched.
Do not resynchronize primary-project storage or automatically re-upload private
documents. Any deliberate document update requires separate owner review.

## MUST NOT UPLOAD

- Real development `.env` or any local environment backup, secrets, `auth.json`,
  signing keys, local SMTP/payment/API credentials. Do not upload the existing
  Git-index version of `.env.infinityfree.example`: it differs from the reviewed
  working template. Keep the mirror's existing hosting `.env`; a blank template
  is for a new environment's manual setup only, never for an existing-site sync.
- `.git` anywhere (including vendor packages), `.github`, `.svn`, `.hg`, `.codex`,
  `.agents`, `.aws`, `graphify-out`, IDE settings, `.deploy` itself, deployment docs,
  audit/packaging scripts and screenshots.
- `node_modules`, tests, test reports/coverage, package tooling and development
  server files; `public/hot`; `public/storage` symlink/junction; source maps.
- Local SQL exports, SQLite databases/WAL files, database backups; keep the manual
  phpMyAdmin import outside the web root. Do not upload `package.json`, npm lock,
  Vite configuration or development-only root configuration; the built output is sufficient.
- `bootstrap/cache/*.php` generated caches, compiled views, old sessions, cached
  data, logs and unreviewed private documents. Keep their runtime directories.
- All ten oversized source assets listed above. Both global bundles are supplied
  by the verified pinned CDN references instead. Do not prune required `vendor`
  runtime packages or remove the smaller active editor/DataTables assets.

## GENERATED LOCALLY

Executed from `C:\Users\yasn\Desktop\letds`:

```powershell
composer install --no-dev --prefer-dist
composer check-platform-reqs --no-dev
composer validate --no-check-publish
npm.cmd ci
npm.cmd run build
node scripts/audit-infinityfree-assets.mjs
php scripts/check-infinityfree-urls.php
& ./scripts/prepare-infinityfree-upload.ps1 -Paths @('public/build', 'resources/views/admin/layouts/partials/scripts.blade.php', 'resources/views/site/appointments/index.blade.php')
node scripts/audit-infinityfree-assets.mjs --deployment .deploy/htdocs
```

Windows `npm.cmd` is the same npm CLI as `npm`. Composer's install scripts run
local package discovery only; no remote Artisan execution is needed. Installer
did not update dependency versions. The helper now updates selected changed files
in the stable downloaded mirror; it does not accept dated `-OutputName` snapshots
or overwrite `.env`. The ten named oversized assets remain excluded.
The CDN-only change reused the prior complete Composer/Vite output; no dependency
install, dependency upgrade, Vite reconfiguration or build rewrite was needed.

Repository-maintenance commands also executed (AST-only update, zero LLM/API cost):

```powershell
graphify update .
graphify save-result --question 'InfinityFree production upload: Composer and Vite completeness, oversized assets, rewrite and storage URL compatibility' --answer-file INFINITYFREE_UPLOAD_MANIFEST.md --type query --nodes 'SendProjectStageWhatsAppJob' 'SeoFileGenerator' --outcome useful
git restore --worktree -- graphify-out/cache/ast/v0.9.7
```

The targeted restore only recovered unchanged old graph caches removed by Graphify;
no application/user edit was reverted. Graph update warned that `hooks.json`
produced no nodes and some community labels are stale; semantic relabeling was
not run. These are graph-maintenance findings, not hosting failures.

`vendor/`, `public/build/manifest.json`, hashed build assets and the reviewed
snapshot were generated locally. Do not upload local config/route/view caches.
Do not generate or share secrets through these audit scripts.

## WRITABLE DIRECTORIES

Ensure actual directories survive FTP transfer (empty directories can be skipped):

- `storage/framework/cache` and `storage/framework/cache/data`
- `storage/framework/sessions`
- `storage/framework/views`
- `storage/logs`
- `storage/app/public` and `storage/app/private`
- `bootstrap/cache`

SEO management additionally writes `public/robots.txt` and `public/sitemap.xml`;
those files must be writable when that admin feature is used. Use host-appropriate
ownership/permissions (commonly directories 755, files 644); do not default to 777.

## MANUAL STEPS AFTER UPLOAD

1. Use only the stable `.deploy/htdocs/` mirror and retain both pinned script tags with their SRI.
   Verify hosting PHP >=8.4.1, PDO MySQL, required Composer extensions, GD/WebP,
   writable storage and memory/upload limits. Local checks do not verify host capabilities.
2. Database import is already completed according to the owner. Do not rerun it
   blindly or execute migrations/seeders. Check expected demo accounts/content in
   phpMyAdmin yourself; no remote verification was made by this preparation.
3. Preserve existing mirror/host `.env`. For a NEW installation only, configure it privately using `.env.infinityfree.example`.
   Enter the DB password yourself on the hosting account, never in tracked files
   or this conversation. Supply an APP_KEY privately; preserve the original key
   if encrypted imported settings are retained, or sanitize those encrypted values.
   Supply demo gate credentials privately: blank values intentionally fail closed
   (503). No key or password was generated/requested/stored by these preparations.
4. Use root URLs, not `https://probablue.freedev.app/public`. Review imported
   `site_settings.seo_base_url`, menu/CTA/media/payment callback URLs for old domain
   values. SEO base URL can override APP_URL. Confirm media uses the public disk
   for public files; records pointing to private/local storage need their existing
   authorized download routes, not a public storage link.
5. Keep `public/.htaccess`. Root storage rule ends rewriting with `[END]`;
   `^public(?:/|$)` guard prevents `/public/public/...` loops. Existing child rules
   serve files or forward to Laravel. `/storage/x` maps directly to
   `storage/app/public/x` with no symlink. Hidden/application/private/script upload
   guards are retained. Do NOT use the two unguarded rewrite rules alone.
   Apache was unavailable locally; this is static review, not a live Apache test.
   [Official Laravel hosting layout](https://forum.infinityfree.com/t/how-to-install-a-laravel-site-on-infinityfree/118578).
6. Manually test HTTPS login, admin, appointments, `/build/...`, `/assets/...`, a
   public `/storage/...` file and authorized private download. Check `.env`, vendor,
   private storage and executable uploads cannot be fetched. No target-domain
   HTTP request or upload was performed here.
7. Do not configure database/Redis queues. `QUEUE_CONNECTION=sync` runs the four
   queued notification jobs in web requests; no background retry/worker exists.
   Old queued jobs will not drain. Daily trash cleanup and service-review scheduler
   tasks will NOT run; do them manually when needed. No explicit runtime shell
   dependency was found in app/routes. File cache/sessions avoid Redis/Memcached.
8. Disable imported database-backed SMTP/notification settings (`mail_notifications_enabled`,
   `notify_contact_messages`, `notify_appointments`); they can override `MAIL_MAILER=log`.
   Keep WhatsApp off and sanitize payment/API integration secrets. Upload controllers
   accepting 12/20 MB exceed the host's 10 MB other-file ceiling and host PHP limits;
   use smaller demo files. No business validation was rewritten.
9. For no-SSH cache clearing, enable local file-based maintenance if needed before
   update and upload the maintenance marker; remove generated `bootstrap/cache/*.php`,
   `storage/framework/views/*`, and `storage/framework/cache/data/*` through FTP.
   Preserve directories, `.gitignore`, uploaded data and logs; remove the maintenance
   marker when done. Delete sessions only if a deliberate logout is wanted. Do not
   add public web Artisan/cache-clear endpoints. See DEPLOY_INFINITYFREE.md for details.
10. On HTTP 500 inspect `storage/logs/laravel.log` and hosting error logs privately;
    check PHP/extensions, missing vendor files, APP_KEY, DB credentials, cached old
    configuration and write permissions. On 503 check demo gate values. On missing
    JS check hosting auto-deletion before blaming Laravel/Vite. Keep APP_DEBUG=false.

### Largest JavaScript files in the final package

| Path under htdocs | Bytes |
| --- | ---: |
| `public/assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.js` | 744,515 |
| `public/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.js` | 657,605 |
| `public/build/assets/app-BcHHjYgn.js` | 591,068 |
| `public/assets/admin/vendors/apexcharts/apexcharts.min.js` | 576,420 |
| `public/assets/admin/vendors/tinymce/tinymce.min.js` | 477,296 |

The deployment audit passed both CDN tags (exact URL/SRI/CORS, one reference,
no async/defer, no old local reference), all 24 Vite manifest file checks, byte
preservation of all build output, zero newly missing literal assets and zero
file-size violations. Eight pre-existing missing images below remain visual
issues, not regressions caused by packaging; they were not silently counted as
present. No live hosted application/browser check was performed.
Both modified Blade templates also compiled in memory and passed PHP syntax
parsing without bootstrapping the application or connecting to a database.
Comparison against the previous snapshot confirmed exactly ten excluded files
and exactly two changed remaining files (the Blade tags); all other packaged
files, including vendor and build assets, were byte-for-byte preserved.

### Existing non-layout issues and review findings

Eight literal image assets are missing locally: `assets/admin/media/app/mini-logo.svg`,
`assets/admin/media/app/mini-logo-circle.svg`, `assets/admin/media/app/mini-logo-circle-dark.svg`,
`assets/admin/media/images/2600x1200/bg-1.png`, `assets/admin/media/images/2600x1200/bg-1-dark.png`,
`assets/admin/media/illustrations/1.svg`, `assets/admin/media/illustrations/1-dark.svg`,
`assets/admin/media/avatars/300-2.png`. These cause existing visual 404s, independent
of htdocs rewriting; no placeholder or unrelated UI change was made.

Existing Composer Flysystem class-map ambiguity and PHP 8.5 Composer deprecation
warnings did not prevent installation/checks. npm install reports 7 high audit
findings in development tooling. Earlier runtime CommonMark advisories remain
documented in DEPLOY_INFINITYFREE.md; no dependency upgrade was authorized/performed.
This preparation is not a security certification.

### Files changed by upload preparation

`.env.infinityfree.example`, `.gitignore`, `DEPLOY_INFINITYFREE.md`,
`INFINITYFREE_UPLOAD_MANIFEST.md`, `scripts/audit-infinityfree-assets.mjs`,
`scripts/check-infinityfree-urls.php`, `scripts/prepare-infinityfree-upload.ps1`;
production `vendor/`, `public/build/` and ignored `.deploy/` snapshots regenerated.
The root `.htaccess` was prepared earlier and reverified unchanged in this pass.
Graphify metadata is refreshed under the project instructions. No application
business logic, original asset, dependency lock, real development `.env`,
`public/.htaccess`, commit or push was changed.

Latest CDN workaround changed the two Blade references, packaging/audit scripts,
this manifest and DEPLOY_INFINITYFREE.md only (plus generated package/Graphify
metadata). Source bundles, Vite configuration, lockfiles, business logic,
public/.htaccess and the InfinityFree environment example were preserved.
