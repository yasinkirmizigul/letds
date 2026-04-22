<?php

namespace App\Support\Site;

use App\Models\Site\SiteNavigationItem;
use Illuminate\Support\Collection;

class NavigationTree
{
    public static function forLocation(string $location, bool $activeOnly = false): Collection
    {
        $items = SiteNavigationItem::query()
            ->with('page:id,title,slug,is_active,published_at')
            ->forLocation($location)
            ->ordered()
            ->get();

        return static::fromItems($items, $activeOnly);
    }

    public static function fromItems(Collection $items, bool $activeOnly = false): Collection
    {
        $filtered = $items
            ->filter(function (SiteNavigationItem $item) use ($activeOnly) {
                if (!$activeOnly) {
                    return true;
                }

                if (!$item->is_active) {
                    return false;
                }

                if ($item->link_type === SiteNavigationItem::LINK_TYPE_PAGE) {
                    return $item->page?->isPublished() ?? false;
                }

                return filled($item->url);
            })
            ->values();

        $grouped = $filtered->groupBy(fn (SiteNavigationItem $item) => (int) ($item->parent_id ?? 0));

        $attachChildren = function (SiteNavigationItem $item) use (&$attachChildren, $grouped) {
            $children = ($grouped[(int) $item->id] ?? collect())
                ->sortBy([
                    ['sort_order', 'asc'],
                    ['id', 'asc'],
                ])
                ->values()
                ->map(function (SiteNavigationItem $child) use (&$attachChildren) {
                    $attachChildren($child);

                    return $child;
                });

            $item->setRelation('children', $children);
        };

        return ($grouped[0] ?? collect())
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->map(function (SiteNavigationItem $item) use (&$attachChildren) {
                $attachChildren($item);

                return $item;
            });
    }
}
