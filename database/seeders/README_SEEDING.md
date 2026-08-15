# Seeding notes (safe defaults)

## Default behavior
- Roles/permissions are always seeded.
- Demo users are created **only in non-production** environments by default.
- Existing user passwords are never overwritten by the role seeders.

## Built-in role profiles
- `superadmin`: developer/system owner; every permission plus the Super Admin middleware areas.
- `admin`: global operational owner for content, commerce, appointments, messages, members and site management.
- `admin` manages operational modules together with users, lower-priority roles, permissions, audit logs, webhook diagnostics and permanent deletion.
- `superadmin` additionally manages Admin accounts and the global dashboard menu visibility. Admin cannot modify equal/higher roles or Super Admin accounts.

## Control demo user creation
Set this in `.env`:

SEED_CREATE_USERS=true|false

Defaults:
- production: false
- non-production: true

## Customize credentials
SEED_SUPERADMIN_EMAIL=admin@admin.com
SEED_SUPERADMIN_NAME="Super Admin"
SEED_SUPERADMIN_PASS=123456

SEED_ADMIN_EMAIL=admin2@admin.com
SEED_ADMIN_NAME="Admin"
SEED_ADMIN_PASS=123456

## Add module permissions
Put files under:
database/seeders/permissions/modules/*.php

Each file should `return` an array:
- list of slugs: ['blog.view', ...]  -> name auto-generated
- or mapping: ['blog.view' => 'Blog View', ...]
