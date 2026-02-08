<?php

namespace App\Support\AdminModuleGenerator;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ModuleGenerator
{
    public function __construct(
        protected FilePatcher $patcher,
    ) {}

    public function generate(string $rawName, bool $force, bool $patch, ?string $preset = null, ?Command $output = null): object
    {
        $notes = [];

        try {
            $n = ModuleNamer::from($rawName);
        } catch (\Throwable $e) {
            return (object)[
                'ok' => false,
                'message' => $e->getMessage(),
                'notes' => [],
            ];
        }

        $cfg = config('admin_generator', []);

        $cfg['routes_modules_path'] ??= base_path('routes/admin/modules');
        $cfg['menu_modules_path']   ??= config_path('admin_menu/modules');

        $preset = $preset ?: ($cfg['default_preset'] ?? 'content');

        $stubsRoot = base_path("stubs/admin-module/presets/{$preset}");
        if (!is_dir($stubsRoot)) {
            $stubsRoot = base_path("stubs/admin-module/presets/content");
        }

        $context = $this->buildContext($n, $cfg);

        // Ensure output directories
        $viewsBase = resource_path("views/admin/pages/{$context['module_kebab_plural']}");
        $jsBase = resource_path("js/admin/pages/{$context['module_kebab_plural']}");
        $this->ensureDir($viewsBase . '/partials');
        $this->ensureDir($jsBase);

        $routesModulesPath = $cfg['routes_modules_path'] ?? base_path('routes/admin/modules');
        $menuModulesPath   = $cfg['menu_modules_path']   ?? config_path('admin_menu/modules');

        $this->ensureDir($routesModulesPath);
        $this->ensureDir($menuModulesPath);

        // 1) Migration
        $migrationName = date('Y_m_d_His') . "_create_{$context['table']}_table.php";
        $this->writeFromStub(
            "{$stubsRoot}/migration.create.stub",
            database_path("migrations/{$migrationName}"),
            $context,
            $force
        );

        // 2) Model
        $this->writeFromStub("{$stubsRoot}/model.stub", $context['model_path'], $context, $force);

        // 3) Requests
        $this->writeFromStub("{$stubsRoot}/request.store.stub", $context['store_request_path'], $context, $force);
        $this->writeFromStub("{$stubsRoot}/request.update.stub", $context['update_request_path'], $context, $force);

        // 4) Policy
        $this->writeFromStub("{$stubsRoot}/policy.stub", $context['policy_path'], $context, $force);

        // 5) Controller
        $this->writeFromStub("{$stubsRoot}/controller.stub", $context['controller_path'], $context, $force);

        // 6) Permission Seeder (optional, supports two filenames)
        $permissionSeederStubA = "{$stubsRoot}/permission.seeder.stub";
        $permissionSeederStubB = "{$stubsRoot}/seeder.permissions.stub";
        $permissionSeederStub = is_file($permissionSeederStubA)
            ? $permissionSeederStubA
            : (is_file($permissionSeederStubB) ? $permissionSeederStubB : null);

        if ($permissionSeederStub) {
            $this->writeFromStub(
                $permissionSeederStub,
                database_path("seeders/{$context['permission_seeder']}.php"),
                $context,
                $force
            );
        } else {
            $notes[] = 'Permission seeder stub not found. Skipped generating permission seeder.';
        }

        // 7) Routes module file
        $routesModuleFile = rtrim($routesModulesPath, '/\\') . DIRECTORY_SEPARATOR . "{$context['route_module_file']}.php";
        $this->writeFromStub("{$stubsRoot}/routes.module.stub", $routesModuleFile, $context, $force);

        // 8) Menu module file
        $menuModuleFile = rtrim($menuModulesPath, '/\\') . DIRECTORY_SEPARATOR . "{$context['route_name_plural']}.php";
        $this->writeFromStub("{$stubsRoot}/menu.module.stub", $menuModuleFile, $context, $force);

        // 9) Views
        $this->writeFromStub("{$stubsRoot}/views/index.stub", "{$viewsBase}/index.blade.php", $context, $force);
        $this->writeFromStub("{$stubsRoot}/views/create.stub", "{$viewsBase}/create.blade.php", $context, $force);
        $this->writeFromStub("{$stubsRoot}/views/edit.stub", "{$viewsBase}/edit.blade.php", $context, $force);
        $this->writeFromStub("{$stubsRoot}/views/trash.stub", "{$viewsBase}/trash.blade.php", $context, $force);
        $this->writeFromStub("{$stubsRoot}/views/partials/_form.stub", "{$viewsBase}/partials/_form.blade.php", $context, $force);
        $this->writeFromStub("{$stubsRoot}/views/partials/_status_featured.stub", "{$viewsBase}/partials/_status_featured.blade.php", $context, $force);
        $this->writeFromStub("{$stubsRoot}/views/partials/_meta.stub", "{$viewsBase}/partials/_meta.blade.php", $context, $force);

        // 10) JS
        $this->writeFromStub("{$stubsRoot}/js/index.stub", "{$jsBase}/index.js", $context, $force);
        $this->writeFromStub("{$stubsRoot}/js/create.stub", "{$jsBase}/create.js", $context, $force);
        $this->writeFromStub("{$stubsRoot}/js/edit.stub", "{$jsBase}/edit.js", $context, $force);
        $this->writeFromStub("{$stubsRoot}/js/trash.stub", "{$jsBase}/trash.js", $context, $force);

        // Patching: routes only (menu auto-load already handled by config/admin_menu.php)
        if ($patch) {
            $routesWeb = $cfg['patching']['routes_web_file'] ?? base_path('routes/web.php');
            $routesMarker = $cfg['patching']['routes_marker'] ?? '// [ADMIN_MODULE_ROUTES]';

            $includeLine = "require __DIR__ . '/admin/modules/{$context['route_module_file']}.php';";

            [$ok, $msg] = $this->patcher->patchAfterMarker($routesWeb, $routesMarker, $includeLine);
            $notes[] = $msg;

            $notes[] = 'Menu auto-load already active via config/admin_menu.php → admin_menu/modules/*.php';
        } else {
            $notes[] = 'Patching disabled (--no-patch).';
        }

        return (object)[
            'ok' => true,
            'message' => "Admin module generated: {$n->studlySingular()} (table: {$context['table']})",
            'module' => $n->studlySingular(),
            'table' => $context['table'],
            'notes' => $notes,
        ];
    }

    protected function buildContext(ModuleNamer $n, array $cfg): array
    {
        $model = $n->model();                 // Product
        $table = $n->table();                 // products

        $routeNamePlural = $n->routeNamePlural();
        $moduleKebabPlural = $n->kebabPlural();

        // ✅ module folder/namespace (singular) => Product
        $moduleStudly = $model;

        // Route param (route-model binding): {product}
        $routeParam = Str::camel($model);

        // Namespaces
        $modelBaseNs = rtrim($cfg['model_namespace'] ?? 'App\\Models\\Admin', '\\');
        $controllerBaseNs = rtrim($cfg['controller_namespace'] ?? 'App\\Http\\Controllers\\Admin', '\\');
        $requestBaseNs = rtrim($cfg['request_namespace'] ?? 'App\\Http\\Requests\\Admin', '\\');
        $policyBaseNs = rtrim($cfg['policy_namespace'] ?? 'App\\Policies\\Admin', '\\');

        $modelNs = $modelBaseNs . '\\' . $moduleStudly;
        $controllerNs = $controllerBaseNs . '\\' . $moduleStudly;
        $requestNs = $requestBaseNs . '\\' . $moduleStudly;
        $policyNs = $policyBaseNs . '\\' . $moduleStudly;

        // Paths (your requested layout)
        $modelDir = app_path("Models/Admin/{$moduleStudly}");
        $controllerDir = app_path("Http/Controllers/Admin/{$moduleStudly}");
        $requestDir = app_path("Http/Requests/Admin/{$moduleStudly}");
        $policyDir = app_path("Policies/Admin/{$moduleStudly}");

        $controllerClass = $n->controller();
        $storeRequest = $n->requestsStore();
        $updateRequest = $n->requestsUpdate();
        $policyClass = $n->policy();

        // Permissions
        $permissionKey = $n->permissionKey();
        $permPrefix = $cfg['permission']['name_prefix'] ?? 'admin';

        return [
            // Names
            'model' => $model,
            'model_var' => Str::camel($model),
            'model_var_plural' => Str::camel($n->studlyPlural()),
            'table' => $table,

            'controller' => $controllerClass,
            'store_request' => $storeRequest,
            'update_request' => $updateRequest,
            'policy' => $policyClass,

            'permission_seeder' => $model . 'PermissionsSeeder',

            // Module labels
            'module_label_singular' => Str::headline($model),
            'module_label_plural' => Str::headline($n->studlyPlural()),
            'module_kebab_plural' => $moduleKebabPlural,
            'route_name_plural' => $routeNamePlural,

            // Routes
            'admin_prefix' => $cfg['admin_route_prefix'] ?? 'admin',
            'route_param' => $routeParam,
            'route_param_braced' => '{' . $routeParam . '}',

            // Permissions
            'permission_guard' => $cfg['permission']['guard_name'] ?? 'web',
            'permission_driver' => $cfg['permission']['driver'] ?? 'spatie',
            'permission_prefix' => $permPrefix,
            'permission_key' => $permissionKey,

            // Defaults
            'featured_limit' => (int)($cfg['defaults']['featured_limit'] ?? 6),
            'ajax_save_default' => (bool)($cfg['defaults']['ajax_save'] ?? false),

            // View/UI contract helpers
            // - Some Blade stubs include "{{ ajax_save_attr }}" on <form> to enable global AJAX save behavior.
            // - Keep both boolean and attribute forms for backward compatibility across stub versions.
            'ajax_save' => (bool)($cfg['defaults']['ajax_save'] ?? false),
            'ajax_save_attr' => ((bool)($cfg['defaults']['ajax_save'] ?? false)) ? 'data-ajax-save' : '',

            // Derived controller tokens (useful for newer stubs)
            'controller_class' => $controllerClass,
            'controller_fqn' => $controllerNs . '\\' . $controllerClass,

            // Namespaces
            'model_namespace' => $modelNs,
            'controller_namespace' => $controllerNs,
            'request_namespace' => $requestNs,
            'policy_namespace' => $policyNs,

            // Paths
            'model_dir' => $modelDir,
            'controller_dir' => $controllerDir,
            'request_dir' => $requestDir,
            'policy_dir' => $policyDir,

            'model_path' => "{$modelDir}/{$model}.php",
            'controller_path' => "{$controllerDir}/{$controllerClass}.php",
            'store_request_path' => "{$requestDir}/{$storeRequest}.php",
            'update_request_path' => "{$requestDir}/{$updateRequest}.php",
            'policy_path' => "{$policyDir}/{$policyClass}.php",
            'route_module_file' => Str::snake($n->studlyPlural()),

            'status_options_php' => "[]",
            'status_default' => "'draft'",
        ];
    }

    protected function renderStub(string $stub, array $ctx): string
    {
        $content = file_get_contents($stub);
        if ($content === false) {
            throw new \RuntimeException("Cannot read stub: {$stub}");
        }

        foreach ($ctx as $k => $v) {
            $v = (string) $v;

            // tolerate common stub token styles:
            $content = str_replace('{{ '.$k.' }}', $v, $content);
            $content = str_replace('{{'.$k.'}}', $v, $content);

            // also tolerate accidental "$" in token names used in blade stubs
            $content = str_replace('{{ $'.$k.' }}', $v, $content);
            $content = str_replace('{{ $'.$k.'}}', $v, $content);
            $content = str_replace('{{$'.$k.'}}', $v, $content);
            $content = str_replace('{{$'.$k.' }}', $v, $content);
        }

        return $content;
    }

    protected function writeFromStub(string $stub, string $target, array $ctx, bool $force): array
    {
        if (is_file($target) && !$force) {
            return ['status' => 'skipped', 'path' => $target];
        }

        $content = $this->renderStub($stub, $ctx);
        if ($content === '') {
            throw new \RuntimeException("Rendered empty stub: {$stub}");
        }

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
