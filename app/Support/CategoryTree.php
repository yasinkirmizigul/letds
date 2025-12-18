<?php

namespace App\Support;

use App\Models\Admin\Category;
use Illuminate\Support\Collection;

class CategoryTree
{
    /**
     * Tek query ile tüm kategorileri al.
     */
    public static function all(): Collection
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
    }

    /**
     * parent_id => [Category, Category, ...] indexi oluştur.
     */
    public static function indexByParent(Collection $all): array
    {
        $byParent = [];

        foreach ($all as $c) {
            $pid = $c->parent_id ?? 0;
            $byParent[$pid][] = $c;
        }

        return $byParent;
    }

    /**
     * RAM'deki index üzerinden $categoryId'nin tüm altlarının ID'lerini döndür.
     * DB yok, recursion query yok.
     */
    public static function descendantIdsFromAll(int $categoryId, array $byParent): array
    {
        $ids = [];

        // Iteratif DFS: recursion bile yok (stack kullanıyoruz)
        $stack = [$categoryId];

        while (!empty($stack)) {
            $current = array_pop($stack);

            foreach (($byParent[$current] ?? []) as $child) {
                $ids[] = $child->id;
                $stack[] = $child->id;
            }
        }

        // unique (nadiren lazım ama garanti)
        return array_values(array_unique($ids));
    }

    /**
     * RAM'deki index üzerinden tree options üret.
     */
    public static function optionsFromIndex(array $byParent, array $excludeIds = []): array
    {
        $out = [];
        $excludeMap = array_flip($excludeIds);

        $walk = function (int $parentId, int $depth) use (&$walk, &$out, $byParent, $excludeMap) {
            foreach (($byParent[$parentId] ?? []) as $c) {
                if (isset($excludeMap[$c->id])) {
                    continue;
                }

                $out[] = [
                    'id'    => $c->id,
                    'label' => str_repeat('— ', $depth) . $c->name,
                ];

                $walk($c->id, $depth + 1);
            }
        };

        $walk(0, 0);

        return $out;
    }

    /**
     * (Opsiyonel) Eski çağrılar bozulmasın diye:
     * CategoryTree::options($excludeIds) halen çalışsın.
     */
    public static function options(array $excludeIds = []): array
    {
        $all = self::all();
        $byParent = self::indexByParent($all);

        return self::optionsFromIndex($byParent, $excludeIds);
    }
}
