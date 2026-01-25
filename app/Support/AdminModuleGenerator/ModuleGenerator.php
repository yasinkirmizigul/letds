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

        // 3) Requests
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

        // 4) Policy
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/policy.stub",
            app_path("Policies/{$context['policy']}.php"),
            $context,
            $force
        );

        // 5) Controller
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/controller.stub",
            app_path("Http/Controllers/Admin/{$context['controller']}.php"),
            $context,
            $force
        );

        // 6) Permission seeder
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/seeder.permissions.stub",
            database_path("seeders/{$context['permission_seeder']}.php"),
            $context,
            $force
        );

        // 7) Routes module file
        $routesModuleFile = rtrim($cfg['routes_modules_path'], '/')."/{$context['route_name_plural']}.php";
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/routes.module.stub",
            $routesModuleFile,
            $context,
            $force
        );

        // 8) Menu module file
        $menuModuleFile = rtrim($cfg['menu_modules_path'], '/')."/{$context['route_name_plural']}.php";
        $created[] = $this->writeFromStub(
            "{$stubsRoot}/menu.module.stub",
            $menuModuleFile,
            $context,
            $force
        );

        // 9) Views
        $viewsBase = resource_path("views/admin/pages/{$context['module_kebab_plural']}");
        $created[] = $this->writeFromStub("{$stubsRoot}/views/index.stub", "{$viewsBase}/index.blade.php", $context, $force);
        $created[] = $this->writeFromStub("{$stubsRoot}/views/create.stub", "{$viewsBase}/create.blade.php", $context, $force);
        $created[] = $this->writeFromStub("{$stubsRoot}/views/edit.stub", "{$viewsBase}/edit.blade.php", $context, $force);
        $created[] = $this->writeFromStub("{$stubsRoot}/views/trash.stub", "{$viewsBase}/trash.blade.php", $context, $force);
        $created[] = $this->writeFromStub("{$stubsRoot}/views/partials/_form.stub", "{$viewsBase}/partials/_form.blade.php", $context, $force);
        $created[] = $this->writeFromStub("{$stubsRoot}/views/partials/_status_featured.stub", "{$viewsBase}/partials/_status_featured.blade.php", $context, $force);
        $created[] = $this->writeFromStub("{$stubsRoot}/views/partials/_meta.stub", "{$viewsBase}/partials/_meta.blade.php", $context, $force);

        // 10) JS
        $jsBase = resource_path("js/admin/pages/{$context['module_kebab_plural']}");
        $created[] = $this->writeFromStub("{$stubsRoot}/js/index.stub", "{$jsBase}/index.js", $context, $force);
        $created[] = $this->writeFromStub("{$stubsRoot}/js/create.stub", "{$jsBase}/create.js", $context, $force);
        $created[] = $this->writeFromStub("{$stubsRoot}/js/edit.stub", "{$jsBase}/edit.js", $context, $force);
        $created[] = $this->writeFromStub("{$stubsRoot}/js/trash.stub", "{$jsBase}/trash.js", $context, $force);

        // Summarize created / skipped
        $created = array_values(array_filter($created));
        $skipped = array_filter($created, fn($x) => ($x['status'] ?? '') === 'skipped');

        // Optional patching
        if ($patch) {
            // Routes patch
            $routesWeb = $cfg['patching']['routes_web_file'];
            $routesMarker = $cfg['patching']['routes_marker'];
            $includeLine = "require __DIR__ . '/admin/modules/{$context['route_name_plural']}.php';";

            [$ok, $msg] = $this->patcher->patchAfterMarker($routesWeb, $routesMarker, $includeLine);
            $notes[] = $msg;

            // Menu patch
            $menuFile = $cfg['patching']['menu_file'];
            $menuMarker = $cfg['patching']['menu_marker'];
            $menuInclude = "\$__moduleMenuFiles[] = __DIR__ . '/admin_menu/modules/{$context['route_name_plural']}.php';";

            [$ok2, $msg2] = $this->patcher->patchAfterMarker($menuFile, $menuMarker, $menuInclude);
            $notes[] = $msg2;

            // If menu marker patched, ensure loader exists: we do NOT auto-inject a loader block (too risky).
            $notes[] = "If your config/admin_menu.php does not already load \$__moduleMenuFiles, add the loader block from docs below once.";
        } else {
            $notes[] = "Patching disabled. Routes/menu module files were generated, but no existing files were modified.";
        }

        $message = "Admin module generated: {$context['model']} (table: {$context['table']})";

        return (object)[
            'ok' => true,
            'message' => $message,
            'notes' => $notes,
        ];
    }

    protected function buildContext(ModuleNamer $n, array $cfg): array
    {
        $model = $n->model();
        $table = $n->table();

        $routeNamePlural = $n->routeNamePlural();   // portfolios
        $moduleKebabPlural = $n->kebabPlural();     // portfolios

        $permissionKey = $n->permissionKey();       // portfolios
        $permPrefix = $cfg['permission']['name_prefix'] ?? 'admin';

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
            'permission_key' => $permissionKey, // admin.portfolios.view

            // Defaults
            'featured_limit' => (int)($cfg['defaults']['featured_limit'] ?? 6),
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
            @mkdir($path, 0775, true);
        }
    }
}
