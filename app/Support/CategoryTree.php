<?php

namespace App\Support;

use App\Models\Admin\Category;

class CategoryTree
{
    public static function options(array $excludeIds = []): array
    {
        $all = Category::query()->orderBy('name')->get(['id','name','parent_id']);

        $byParent = [];
        foreach ($all as $c) $byParent[$c->parent_id ?? 0][] = $c;

        $out = [];
        $walk = function($parentId, $depth) use (&$walk, &$out, $byParent, $excludeIds) {
            foreach (($byParent[$parentId] ?? []) as $c) {
                if (in_array($c->id, $excludeIds, true)) continue;

                $out[] = ['id' => $c->id, 'label' => str_repeat('— ', $depth) . $c->name];
                $walk($c->id, $depth + 1);
            }
        };

        $walk(0, 0);
        return $out;
    }
}
