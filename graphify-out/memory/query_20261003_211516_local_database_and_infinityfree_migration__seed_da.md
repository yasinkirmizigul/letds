---
type: "query"
date: "2026-10-03T21:15:16.892868+00:00"
question: "Local database and InfinityFree migration, seed data, authentication and filesystem compatibility"
contributor: "graphify"
outcome: "useful"
source_nodes: ["DatabaseSeeder", "Role", "Permission", "SiteLocalization"]
---

# Q: Local database and InfinityFree migration, seed data, authentication and filesystem compatibility

## Answer

Expanded vocabulary: database seeder role permission menu setting queue storage schedule sqlite. Source inspection: current local env selects mysql on loopback database letds, without DB_URL or generated config cache. No database connection or password use; live schema/data not verified. DatabaseSeeder initializes permissions, admin/superadmin roles and optional users. SiteLocalization requires initial site_languages data; SiteSetting and HomepageConfigurationService auto-create defaults. Admin menus are config-backed; admin_menu_settings is optional. No incompatible raw SQL found; sync queue and file cache/session are the demo strategy, scheduler and symlinks remain unsupported.

## Outcome

- Signal: useful

## Source Nodes

- DatabaseSeeder
- Role
- Permission
- SiteLocalization