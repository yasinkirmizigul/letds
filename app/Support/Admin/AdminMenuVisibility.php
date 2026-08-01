<?php

namespace App\Support\Admin;

use App\Models\Admin\AdminMenuSetting;
use App\Models\Admin\User\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class AdminMenuVisibility
{
    private const CACHE_KEY = 'admin_menu:hidden_items:v1';

    public function __construct(private readonly AdminMenuRegistry $registry) {}

    public function hiddenKeys(): array
    {
        $availableKeys = $this->registry->availableKeys();
        $available = array_fill_keys($availableKeys, true);

        $stored = Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function (): array {
            try {
                if (!Schema::hasTable('admin_menu_settings')) {
                    return [];
                }

                $value = AdminMenuSetting::query()
                    ->whereKey(AdminMenuSetting::SINGLETON_ID)
                    ->value('hidden_items');

                if (is_string($value)) {
                    $value = json_decode($value, true);
                }

                return is_array($value) ? $value : [];
            } catch (Throwable) {
                return [];
            }
        });

        return collect($stored)
            ->map(fn ($key) => trim((string) $key))
            ->filter(fn (string $key) => isset($available[$key]))
            ->unique()
            ->values()
            ->all();
    }

    public function visibleMenu(): array
    {
        return $this->registry->visibleMenu($this->hiddenKeys());
    }

    public function replaceHiddenKeys(array $hiddenKeys, User $user): void
    {
        $selected = array_fill_keys(
            collect($hiddenKeys)->map(fn ($key) => (string) $key)->all(),
            true
        );

        $normalized = collect($this->registry->availableKeys())
            ->filter(fn (string $key) => isset($selected[$key]))
            ->values()
            ->all();

        DB::transaction(function () use ($normalized, $user): void {
            if ($normalized === []) {
                AdminMenuSetting::query()
                    ->whereKey(AdminMenuSetting::SINGLETON_ID)
                    ->delete();

                return;
            }

            AdminMenuSetting::query()->updateOrCreate(
                ['id' => AdminMenuSetting::SINGLETON_ID],
                [
                    'hidden_items' => $normalized,
                    'updated_by' => $user->id,
                ]
            );
        });

        Cache::forget(self::CACHE_KEY);
    }

    public function reset(): void
    {
        AdminMenuSetting::query()
            ->whereKey(AdminMenuSetting::SINGLETON_ID)
            ->delete();

        Cache::forget(self::CACHE_KEY);
    }
}
