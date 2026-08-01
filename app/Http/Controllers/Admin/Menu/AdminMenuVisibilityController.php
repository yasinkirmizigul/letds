<?php

namespace App\Http\Controllers\Admin\Menu;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\User;
use App\Support\Admin\AdminMenuRegistry;
use App\Support\Admin\AdminMenuVisibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminMenuVisibilityController extends Controller
{
    public function edit(AdminMenuRegistry $registry, AdminMenuVisibility $visibility): View
    {
        $menuItems = $registry->all();
        $hiddenKeys = $visibility->hiddenKeys();
        $availableKeys = $registry->availableKeys($menuItems);
        $visibleKeys = $registry->visibleKeys($hiddenKeys, $menuItems);

        return view('admin.pages.menu-visibility.edit', [
            'pageTitle' => 'Menü Yönetimi',
            'pageDescription' => 'Sol menü görünürlüğünü yönet',
            'menuItems' => $menuItems,
            'hiddenKeys' => $hiddenKeys,
            'availableItemCount' => count($availableKeys),
            'visibleItemCount' => count($visibleKeys),
        ]);
    }

    public function update(
        Request $request,
        AdminMenuRegistry $registry,
        AdminMenuVisibility $visibility
    ): RedirectResponse {
        $availableKeys = $registry->availableKeys();

        $validated = $request->validate([
            'visible_items' => ['nullable', 'array'],
            'visible_items.*' => ['string', Rule::in($availableKeys)],
        ]);

        $visibleKeys = collect($validated['visible_items'] ?? [])
            ->map(fn ($key) => (string) $key)
            ->unique()
            ->values()
            ->all();

        $hiddenKeys = array_values(array_diff($availableKeys, $visibleKeys));

        /** @var User $user */
        $user = $request->user();
        $visibility->replaceHiddenKeys($hiddenKeys, $user);

        return redirect()
            ->route('admin.menu-visibility.edit')
            ->with('success', 'Sol menü görünürlüğü güncellendi.');
    }

    public function reset(AdminMenuVisibility $visibility): RedirectResponse
    {
        $visibility->reset();

        return redirect()
            ->route('admin.menu-visibility.edit')
            ->with('success', 'Sol menüdeki tüm öğeler yeniden görünür yapıldı.');
    }
}
