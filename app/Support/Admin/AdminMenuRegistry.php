<?php

namespace App\Support\Admin;

use Illuminate\Support\Str;
use LogicException;

final class AdminMenuRegistry
{
    public function all(): array
    {
        return $this->normalize((array) config('admin_menu', []));
    }

    public function normalize(array $menu): array
    {
        $usedKeys = [];
        $normalized = [];

        $position = 0;

        foreach ($menu as $item) {
            if (!is_array($item) || trim((string) ($item['title'] ?? '')) === '') {
                continue;
            }

            $normalized[] = $this->normalizeItem($item, $position, null, $usedKeys);
            $position++;
        }

        return $normalized;
    }

    public function availableKeys(?array $menu = null): array
    {
        $items = $menu === null ? $this->all() : $this->normalize($menu);
        $keys = [];

        foreach ($items as $item) {
            $keys[] = $item['key'];

            foreach ($item['children'] ?? [] as $child) {
                $keys[] = $child['key'];
            }
        }

        return $keys;
    }

    public function visibleMenu(array $hiddenKeys, ?array $menu = null): array
    {
        $items = $menu === null ? $this->all() : $this->normalize($menu);
        $hidden = array_fill_keys($hiddenKeys, true);
        $visible = [];

        foreach ($items as $item) {
            if (isset($hidden[$item['key']])) {
                continue;
            }

            if (($item['type'] ?? 'single') === 'accordion') {
                $item['children'] = array_values(array_filter(
                    $item['children'] ?? [],
                    fn (array $child) => !isset($hidden[$child['key']])
                ));

                if ($item['children'] === []) {
                    continue;
                }

                $item = $this->syncParentPermissions($item);
            }

            $visible[] = $item;
        }

        return $visible;
    }

    public function visibleKeys(array $hiddenKeys, ?array $menu = null): array
    {
        $keys = [];

        foreach ($this->visibleMenu($hiddenKeys, $menu) as $item) {
            $keys[] = $item['key'];

            foreach ($item['children'] ?? [] as $child) {
                $keys[] = $child['key'];
            }
        }

        return $keys;
    }

    private function normalizeItem(array $item, int $index, ?string $parentKey, array &$usedKeys): array
    {
        $key = trim((string) ($item['key'] ?? ''));

        if ($key === '') {
            $seed = (string) ($item['route'] ?? $item['title'] ?? 'menu_item_'.$index);
            $fallback = Str::slug($seed, '_') ?: 'menu_item_'.$index;
            $key = $parentKey ? $parentKey.'.'.$fallback : $fallback;
        }

        if (isset($usedKeys[$key])) {
            throw new LogicException("Admin menu key must be unique: {$key}");
        }

        $usedKeys[$key] = true;
        $item['key'] = $key;

        if (isset($item['children']) && is_array($item['children'])) {
            $children = [];

            foreach (array_values($item['children']) as $childIndex => $child) {
                if (!is_array($child)) {
                    continue;
                }

                $children[] = $this->normalizeItem($child, $childIndex, $key, $usedKeys);
            }

            $item['children'] = $children;
        }

        return $item;
    }

    private function syncParentPermissions(array $item): array
    {
        $permissions = [];

        foreach ($item['children'] as $child) {
            if (!empty($child['perm'])) {
                $permissions[] = (string) $child['perm'];
            }

            foreach ((array) ($child['permAny'] ?? []) as $permission) {
                $permissions[] = (string) $permission;
            }
        }

        $permissions = array_values(array_unique(array_filter($permissions)));

        if ($permissions !== []) {
            $item['permAny'] = $permissions;
        }

        return $item;
    }
}
