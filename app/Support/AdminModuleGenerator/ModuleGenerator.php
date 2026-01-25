<?php

namespace App\Support\AdminModuleGenerator;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ModuleGenerator
{
    public function __construct(
        protected FilePatcher $patcher,
    ) {}

    public function generate(string $rawName, bool $force, bool $patch, ?Command $output = null): object
    {
        try {
            $n = ModuleNamer::from($rawName);
        } catch (\Throwable $e) {
            return (object)['ok' => false, 'message' => $e->getMessage(), 'notes' => []];
        }

        $cfg = config('admin-module-generator');
        if (!$cfg) {
            return (object)['ok' => false, 'message' => 'Missing config: config/admin-module-generator.php', 'notes' => []];
        }

        $context = $this->buildContext($n, $cfg);

        $stubsRoot = base_path('stubs/admin-module');
        if (!is_dir($stubsRoot)) {
            return (object)['ok' => false, 'message' => "Missing stubs directory: {$stubsRoot}", 'notes' => []];
        }

        $created = [];
        $notes = [];

        // Ensure dirs
        $this->ensureDir(database_path('migrations'));
        $this->ensureDir(app_path('Models'));
        $this->ensureDir(app_path('Http/Controllers/Admin'));
        $this->ensureDir(app_path('Http/Requests/Admin'));
        $this->ensureDir(app_path('Policies'));
        $this->ensureDir(database_path('seeders'));
        $this->ensureDir(resource_path("views/admin/pages/{$context['module_kebab_plural']}"));
        $this->ensureDir(resource_path("views/admin/pages/{$context['module_kebab_plural']}/partials"));
        $this->ensureDir(resource_path("js/admin/pages/{$context['module_kebab_plural']}"));
        $this->ensureDir($cfg['routes_modules_path']);
        $this->ensureDir($cfg['menu_modules_path']);

        // 1) Migration
        $migrationName = date('Y_m_d_His')."_create_{$context['table']}_table.php";
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/migration.create.stub",
            database_path("migrations/{$migrationName}"),
            $context,
            $force
        );

        // 2) Model
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/model.stub",
            app_path("Models/{$context['model']}.php"),
            $context,
            $force
        );

        // 3) Controller
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/controller.stub",
            app_path("Http/Controllers/Admin/{$context['controller']}.php"),
            $context,
            $force
        );

        // 4) Requests
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/request.store.stub",
            app_path("Http/Requests/Admin/{$context['store_request']}.php"),
            $context,
            $force
        );

        $created[] = $this->writeFromStub(
            "{$stubsRoot}/request.update.stub",
            app_path("Http/Requests/Admin/{$context['update_request']}.php"),
            $context,
            $force
        );

        // 5) Policy
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/policy.stub",
            app_path("Policies/{$context['policy']}.php"),
            $context,
            $force
        );

        // 6) Permission seeder
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/permission.seeder.stub",
            database_path("seeders/{$context['permission_seeder']}.php"),
            $context,
            $force
        );

        // 7) Views
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/views/index.stub",
            resource_path("views/admin/pages/{$context['module_kebab_plural']}/index.blade.php"),
            $context,
            $force
        );

        $created[] = $this->writeFromStub(
            "{$stubsRoot}/views/create.stub",
            resource_path("views/admin/pages/{$context['module_kebab_plural']}/create.blade.php"),
            $context,
            $force
        );

        $created[] = $this->writeFromStub(
            "{$stubsRoot}/views/edit.stub",
            resource_path("views/admin/pages/{$context['module_kebab_plural']}/edit.blade.php"),
            $context,
            $force
        );

        $created[] = $this->writeFromStub(
            "{$stubsRoot}/views/trash.stub",
            resource_path("views/admin/pages/{$context['module_kebab_plural']}/trash.blade.php"),
            $context,
            $force
        );

        $created[] = $this->writeFromStub(
            "{$stubsRoot}/views/partials/form.stub",
            resource_path("views/admin/pages/{$context['module_kebab_plural']}/partials/_form.blade.php"),
            $context,
            $force
        );

        $created[] = $this->writeFromStub(
            "{$stubsRoot}/views/partials/status-featured.stub",
            resource_path("views/admin/pages/{$context['module_kebab_plural']}/partials/_status_featured.blade.php"),
            $context,
            $force
        );

        $created[] = $this->writeFromStub(
            "{$stubsRoot}/views/partials/meta.stub",
            resource_path("views/admin/pages/{$context['module_kebab_plural']}/partials/_meta.blade.php"),
            $context,
            $force
        );

        // 8) JS
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/js/index.stub",
            resource_path("js/admin/pages/{$context['module_kebab_plural']}/index.js"),
            $context,
            $force
        );

        $created[] = $this->writeFromStub(
            "{$stubsRoot}/js/create.stub",
            resource_path("js/admin/pages/{$context['module_kebab_plural']}/create.js"),
            $context,
            $force
        );

        $created[] = $this->writeFromStub(
            "{$stubsRoot}/js/edit.stub",
            resource_path("js/admin/pages/{$context['module_kebab_plural']}/edit.js"),
            $context,
            $force
        );

        $created[] = $this->writeFromStub(
            "{$stubsRoot}/js/trash.stub",
            resource_path("js/admin/pages/{$context['module_kebab_plural']}/trash.js"),
            $context,
            $force
        );

        // 9) Routes module file
        $routesModuleFile = rtrim($cfg['routes_modules_path'], '/\\').DIRECTORY_SEPARATOR."{$context['module_kebab_plural']}.php";
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/routes.module.stub",
            $routesModuleFile,
            $context,
            $force
        );

        // 10) Menu module file
        $menuModuleFile = rtrim($cfg['menu_modules_path'], '/\\').DIRECTORY_SEPARATOR."{$context['module_kebab_plural']}.php";
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/menu.module.stub",
            $menuModuleFile,
            $context,
            $force
        );

        // Optional patching
        if ($patch) {
            $patchResult = $this->patcher->patch($context, $cfg);
            $notes = array_merge($notes, $patchResult['notes'] ?? []);
        } else {
            $notes[] = 'Patching disabled (--no-patch). Routes/menu markers not touched.';
        }

        $countCreated = count(array_filter($created, fn($c) => ($c['status'] ?? '') === 'created'));
        $countSkipped = count(array_filter($created, fn($c) => ($c['status'] ?? '') === 'skipped'));

        return (object)[
            'ok' => true,
            'message' => "Admin module generated: {$context['module_label_plural']} (created: {$countCreated}, skipped: {$countSkipped})",
            'notes' => $notes,
            'created' => $created,
        ];
    }

    function buildContext(ModuleNamer $n, array $cfg): array
    {
        $model = $n->model();
        $table = $n->table();

        $routeNamePlural = $n->routeNamePlural();   // portfolios
        $moduleKebabPlural = $n->kebabPlural();     // portfolios

        $permissionKey = $n->permissionKey();       // portfolios
        $permPrefix = $cfg['permission']['name_prefix'] ?? 'admin';

        $ajaxSave = (bool)($cfg['defaults']['ajax_save'] ?? false);
        $statusOptions = $cfg['defaults']['statuses'] ?? [
            'draft' => ['label' => 'Taslak', 'badge' => 'kt-badge kt-badge-sm kt-badge-light'],
            'published' => ['label' => 'Yayınlandı', 'badge' => 'kt-badge kt-badge-sm kt-badge-success'],
        ];
        return [
            // Names
            'model' => $model,
            'model_var' => Str::camel($model),
            'model_var_plural' => Str::camel($n->studlyPlural()),
            'table' => $table,
            'controller' => $n->controller(),
            'store_request' => $n->requestsStore(),
            'update_request' => $n->requestsUpdate(),
            'policy' => $n->policy(),
            'permission_seeder' => $model.'PermissionsSeeder',

            // Module slug
            'module_label_singular' => Str::headline($model),
            'module_label_plural' => Str::headline($n->studlyPlural()),
            'module_kebab_plural' => $moduleKebabPlural,
            'route_name_plural' => $routeNamePlural,

            // Routes
            'admin_prefix' => $cfg['admin_route_prefix'] ?? 'admin',

            // Permissions
            'permission_guard' => $cfg['permission']['guard_name'] ?? 'web',
            'permission_driver' => $cfg['permission']['driver'] ?? 'spatie',
            'permission_prefix' => $permPrefix,
            'permission_key' => $permissionKey,

            // Defaults
            'featured_limit' => (int)($cfg['defaults']['featured_limit'] ?? 6),
            'statuses' => $cfg['defaults']['statuses'] ?? [],
            'ajax_save' => (bool)($cfg['defaults']['ajax_save'] ?? false),
            'ajax_save_attr' => ((bool)($cfg['defaults']['ajax_save'] ?? false)) ? 'data-ajax-save' : '',
            'status_options_php' => var_export($statusOptions, true),
        ];
    }

    protected function renderStub(string $stub, array $ctx): string
    {
        $content = file_get_contents($stub);
        if ($content === false) {
            throw new \RuntimeException("Cannot read stub: {$stub}");
        }

        foreach ($ctx as $k => $v) {
            $content = str_replace('{{ '.$k.' }}', (string)$v, $content);
        }

        return $content;
    }

    protected function writeFromStub(string $stub, string $target, array $ctx, bool $force): array
    {
        if (is_file($target) && !$force) {
            return ['status' => 'skipped', 'path' => $target];
        }

        $content = $this->renderStub($stub, $ctx);
        $dir = dirname($target);
        $this->ensureDir($dir);

        $ok = file_put_contents($target, $content) !== false;
        if (!$ok) {
            throw new \RuntimeException("Cannot write: {$target}");
        }

        return ['status' => 'created', 'path' => $target];
    }

    protected function ensureDir(string $path): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0775, true) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
        }
    }
}
