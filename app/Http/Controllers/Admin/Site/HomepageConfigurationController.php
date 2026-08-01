<?php

namespace App\Http\Controllers\Admin\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\HomepageConfigurationService;
use App\Support\Audit\AuditEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HomepageConfigurationController extends Controller
{
    public function edit(HomepageConfigurationService $configurationService): View
    {
        $configuration = $configurationService->current()->loadMissing('translations');
        $schema = $configurationService->schema();
        $content = array_replace($configurationService->contentDefaults(), $configuration->content ?? []);
        $settings = array_replace($configurationService->settingDefaults(), $configuration->settings ?? []);
        $storedTranslations = $configuration->translations
            ->mapWithKeys(fn ($translation) => [$translation->locale => $translation->content ?? []])
            ->all();

        $localizedFields = collect($schema['content_fields'] ?? [])
            ->map(function (array $field): array {
                if (($field['type'] ?? null) === 'section') {
                    return $field;
                }

                $field['name'] = $field['key'];

                if (($field['type'] ?? 'text') === 'url') {
                    $field['type'] = 'text';
                    $field['input_type'] = 'text';
                }

                return $field;
            })
            ->values();
        $modes = collect($schema['modes'] ?? []);
        $modeLocalizedFields = $modes->mapWithKeys(fn (array $mode, string $key) => [
            $key => $localizedFields->where('mode', $key)->values()->all(),
        ])->all();
        $sharedLocalizedFields = $localizedFields
            ->filter(fn (array $field) => blank($field['mode'] ?? null))
            ->values()
            ->all();
        $settingGroups = collect($configurationService->settingGroups());
        $modeSettingGroups = $modes->mapWithKeys(fn (array $mode, string $key) => [
            $key => $settingGroups->where('mode', $key)->values()->all(),
        ])->all();
        $sharedSettingGroups = $settingGroups
            ->filter(fn (array $group) => blank($group['mode'] ?? null))
            ->values()
            ->all();

        return view('admin.pages.site.homepage.edit', [
            'schema' => $schema,
            'content' => $content,
            'settings' => $settings,
            'storedTranslations' => $storedTranslations,
            'modes' => $modes->all(),
            'modeLocalizedFields' => $modeLocalizedFields,
            'sharedLocalizedFields' => $sharedLocalizedFields,
            'modeSettingGroups' => $modeSettingGroups,
            'sharedSettingGroups' => $sharedSettingGroups,
            'headerLogo' => $configurationService->headerLogo($settings),
        ]);
    }

    public function update(
        Request $request,
        HomepageConfigurationService $configurationService
    ): RedirectResponse {
        $validator = Validator::make($request->all(), $configurationService->validationRules());

        $validator->after(function ($validator) use ($request, $configurationService): void {
            foreach ($configurationService->unsafeLinks($request->all()) as $key) {
                $validator->errors()->add(
                    $key,
                    'Bağlantı /, #, ?, http, https, mailto veya tel ile başlamalıdır.'
                );
            }
        });

        $configuration = $configurationService->persist($validator->validate());

        AuditEvent::log('site.homepage.update', [
            'site_homepage_config_id' => $configuration->id,
            'schema_key' => $configuration->key,
        ]);

        return back()->with('success', 'Ana sayfa ayarları güncellendi.');
    }
}
