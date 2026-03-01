# Seeding notes (safe defaults)

## Default behavior
- Roles/permissions are always seeded.
- Demo users are created **only in non-production** environments by default.

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
