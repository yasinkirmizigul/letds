---
type: "query"
date: "2026-10-03T21:38:45.108135+00:00"
question: "InfinityFree production upload: Composer and Vite completeness, oversized assets, rewrite and storage URL compatibility"
contributor: "graphify"
outcome: "useful"
source_nodes: ["SendProjectStageWhatsAppJob", "SeoFileGenerator"]
---

# Q: InfinityFree production upload: Composer and Vite completeness, oversized assets, rewrite and storage URL compatibility

## Answer

# InfinityFree upload manifest — 2026-10-04

**Deployment readiness: BLOCKED.** Target: `https://probablue.freedev.app`.
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
- Static URL/asset review covered 201 PHP/JS/CSS source files and 39 literal
  `asset()` paths. No protected-directory asset conflict was found. Dynamically
  computed paths, stored database URLs and every route's execution are not proven
  by this test; owner smoke checks remain necessary.

## Actual blocker: static vendor JavaScript, not the Vite warning

InfinityFree documents a **1 MB ceiling for PHP/HTML/JS**, **10 kB for .htaccess**,
and **10 MB for other files**. Oversized files are automatically removed regardless
of upload method; FTP does **not** bypass this file-system policy.
[Official file-size policy](https://forum.infinityfree.com/t/why-are-my-files-deleted-after-uploading-them/49310/1).
This is a documented limit, not an observed upload failure on this account: no
test upload was authorized or performed. All JS files below exceed even 1 MiB.

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
| `public/assets/admin/plugins/global/plugins.bundle.js` | 3,558,077 | 2,776,740 | `resources/views/admin/layouts/partials/scripts.blade.php` |
| `public/assets/site/plugins/global/plugins.bundle.js` | 3,558,077 | 2,776,740 | `resources/views/site/appointments/index.blade.php` |

The two referenced global bundles are functional blockers. The eight unreferenced
files also prevent an exact, complete upload; they may be omitted only after
confirming no dynamic/external usage. The working editor uses the smaller
`public/assets/admin/vendors/tinymce/tinymce.min.js`, not the oversized custom bundle.

The local audit parsed/minified in memory with compression and name mangling OFF.
Names and script structure were preserved; all parses succeeded. Output was NOT
written and no original asset was changed. This low-risk size reduction alone
does not meet the limit. Splitting/rebuilding libraries requires load-order and
browser regression checks; it is not proven safe or performed automatically.
Transfer compression/gzip does not shrink the JS file stored on the hosting disk.

## MUST UPLOAD

Use only the reviewed snapshot contents from
`.deploy/infinityfree-20261004-final/htdocs`, **after resolving the asset blocker**.
This snapshot contains **15,086 files / 168,120,792 bytes**; its only ten
over-limit files are the JavaScript files listed above. It has 78 runtime
packages, no vendor Git directories, no generated bootstrap PHP caches, no hot
file, and identical root/public `.htaccess` files and blank environment template.
Copy its CONTENTS into the account's existing `htdocs`, not into `htdocs/htdocs`.
The earlier `.deploy/infinityfree-20261004` snapshot is superseded; do not upload it.

```text
htdocs/
  .htaccess                    # root protection and public/storage rewrites
  .env                         # owner privately configures blank template
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

Private member/project documents were deliberately NOT copied. If imported rows
refer to required demo documents, review/sanitize those files and manually place
them at their original relative paths under `storage/app/private`, never public.
Review public uploads for sensitive data as well; copying is not content sanitization.

## MUST NOT UPLOAD

- Real development `.env` or any local environment backup, secrets, `auth.json`,
  signing keys, local SMTP/payment/API credentials. Do not upload the existing
  Git-index version of `.env.infinityfree.example`: it differs from the reviewed
  working template. Use the snapshot's blank template, configured privately.
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
- Oversized assets until resolved. Do not silently remove the two required global
  bundles and call the site ready. Do not prune required `vendor` runtime packages.

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
powershell -NoProfile -ExecutionPolicy Bypass -File scripts/prepare-infinityfree-upload.ps1
```

Windows `npm.cmd` is the same npm CLI as `npm`. Composer's install scripts run
local package discovery only; no remote Artisan execution is needed. Installer
did not update dependency versions. For another snapshot use
`-OutputName infinityfree-reviewed-02`; the packager refuses to overwrite existing
snapshots. The first packager execution created a superseded snapshot containing
vendor Git metadata; the second, corrected execution excludes it.

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

1. First resolve the oversized required JS issue and regenerate the snapshot.
   Verify hosting PHP >=8.4.1, PDO MySQL, required Composer extensions, GD/WebP,
   writable storage and memory/upload limits. Local checks do not verify host capabilities.
2. Database import is already completed according to the owner. Do not rerun it
   blindly or execute migrations/seeders. Check expected demo accounts/content in
   phpMyAdmin yourself; no remote verification was made by this preparation.
3. Privately configure snapshot/host `.env` using `.env.infinityfree.example`.
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

## Outcome

- Signal: useful

## Source Nodes

- SendProjectStageWhatsAppJob
- SeoFileGenerator