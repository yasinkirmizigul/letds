<?php

namespace App\Http\Controllers\Admin\Site;

use App\Http\Controllers\Controller;
use App\Services\Admin\Media\MediaService;
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
        $mediaPreviews = collect(['header_logo_media_id', 'background_media_id'])
            ->mapWithKeys(fn (string $key) => [$key => $configurationService->mediaAsset($settings, $key)])
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
            'mediaPreviews' => $mediaPreviews,
        ]);
    }

    public function update(
        Request $request,
        HomepageConfigurationService $configurationService,
        MediaService $mediaService,
    ): RedirectResponse {
        $validator = Validator::make($request->all(), array_merge(
            $configurationService->validationRules(),
            [
                'background_image' => ['nullable', 'file', 'max:12288', 'mimes:jpg,jpeg,png,webp'],
                'clear_background_image' => ['nullable', 'boolean'],
            ]
        ));

        $validator->after(function ($validator) use ($request, $configurationService): void {
            foreach ($configurationService->unsafeLinks($request->all()) as $key) {
                $validator->errors()->add(
                    $key,
                    'Bağlantı /, #, ?, http, https, mailto veya tel ile başlamalıdır.'
                );
            }
        });

        $validated = $validator->validate();

        if ($request->hasFile('background_image')) {
            $media = $mediaService->store($request->file('background_image'), [
                'title' => 'Ana sayfa arka planı',
                'alt' => 'Ana sayfa arka planı',
            ]);
            $validated['settings']['background_media_id'] = $media->id;
        } elseif ($request->boolean('clear_background_image')) {
            $validated['settings']['background_media_id'] = null;
        }

        $configuration = $configurationService->persist($validated);

        AuditEvent::log('site.homepage.update', [
            'site_homepage_config_id' => $configuration->id,
            'schema_key' => $configuration->key,
        ]);

        return back()->with('success', 'Ana sayfa ayarları güncellendi.');
    }
}
