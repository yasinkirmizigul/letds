<?php

namespace App\Console\Commands;

use App\Support\AdminModuleGenerator\ModuleGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeAdminModule extends Command
{
    protected $signature = 'make:admin-module
                        {name : Module name (e.g. Product)}
                        {--preset= : Preset name}
                        {--force : Overwrite existing generated files}
                        {--no-patch : Do not patch supporting files}
                        {--migrate : Run migrations after module generation}';

    protected $description = 'Generate a fully integrated Admin module';

    public function handle(ModuleGenerator $generator): int
    {
        $name   = (string) $this->argument('name');
        $preset = (string) ($this->option('preset') ?: '');

        $rawName = Str::studly($name);
        $slugPlural = Str::kebab(Str::pluralStudly($rawName)); // Product -> products
        $singularStudly = Str::studly(Str::singular($rawName));

        // 1) Core scaffold
        $result = $generator->generate(
            rawName: $rawName,
            force: (bool) $this->option('force'),
            patch: ! (bool) $this->option('no-patch'),
            preset: $preset !== '' ? $preset : null,
            output: $this
        );

        if (! $result->ok) {
            $this->error($result->message);
            return self::FAILURE;
        }

        $force = (bool) $this->option('force');

        // 2) Drop-in integration files
        $this->ensureDropInRouteFile($slugPlural, $singularStudly, $force);
        $this->ensureDropInMenuFile($slugPlural, $force);
        $this->ensureDropInPermissionModuleFile($slugPlural, $force);

        // 3) Safe patches
        if (! (bool) $this->option('no-patch')) {
            $this->patchWebRoutesAdminModulesLoader();
            $this->patchAdminPagesRegistry($slugPlural);
        }

        $this->info($result->message);
        if ((bool) $this->option('migrate')) {
            $this->call('migrate', ['--force' => true]);
        }
        $this->newLine();
        $this->line('<comment>Generated integration files:</comment>');
        $this->line(" - routes/admin/modules/{$slugPlural}.php");
        $this->line(" - config/admin_menu/modules/{$slugPlural}.php");
        $this->line(" - database/seeders/permissions/modules/{$slugPlural}.php");

        return self::SUCCESS;
    }

    /* -----------------------------------------------------------------
     | Drop-in files
     |-----------------------------------------------------------------*/

    private function ensureDropInRouteFile(string $slugPlural, string $singularStudly, bool $force): void
    {
        $dir = base_path('routes/admin/modules');
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        }

        $path = $dir . DIRECTORY_SEPARATOR . "{$slugPlural}.php";
        if (file_exists($path) && !$force) {
            return;
        }

        // preset seçimi (yoksa content)
        $preset = (string) ($this->option('preset') ?: 'content');

        // ✅ Stub path: senin güncellediğin dosya buradaysa buradan okunacak
        $stub = base_path("stubs/admin-module/presets/{$preset}/routes.module.stub");
        if (!file_exists($stub)) {
            // fallback: bazı projelerde preset yok, direkt stubs altında olur
            $stub = base_path("stubs/admin-module/routes.module.stub");
        }

        if (!file_exists($stub)) {
            throw new \RuntimeException("routes.module.stub not found. Expected: {$stub}");
        }

        $routeParam = Str::camel(Str::singular($slugPlural)); // products -> product

        // Controller FQCN (senin standardın)
        $controllerFqn = "App\\Http\\Controllers\\Admin\\{$singularStudly}\\{$singularStudly}Controller";
        $controllerClass = "{$singularStudly}Controller";

        // Stub token mapping (senin routes.module.stub token’ları)
        $ctx = [
            '{{ controller_fqn }}'       => $controllerFqn,
            '{{ controller_class }}'     => $controllerClass,
            '{{ admin_prefix }}'         => 'admin',
            '{{ route_uri_plural }}'     => $slugPlural,
            '{{ route_name_plural }}'    => $slugPlural,
            '{{ module_label_plural }}'  => Str::headline($slugPlural),
            '{{ route_param }}'          => '{' . $routeParam . '}', // -> {product}
        ];

        $content = file_get_contents($stub);
        if ($content === false) {
            throw new \RuntimeException("Cannot read stub: {$stub}");
        }

        $content = str_replace(array_keys($ctx), array_values($ctx), $content);

        file_put_contents($path, $content);
    }


    private function ensureDropInMenuFile(string $slugPlural, bool $force): void
    {
        $dir = config_path('admin_menu/modules');
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        }

        $path = $dir . "/{$slugPlural}.php";
        if (file_exists($path) && !$force) {
            return;
        }

        $title = Str::headline($slugPlural);

        $content = <<<PHP
<?php

return [
    [
        'type'     => 'accordion',
        'title'    => '{$title}',
        'icon'     => 'ki-outline ki-abstract-26',
        'perm'     => '{$slugPlural}.view',
        'children' => [
            [
                'title'  => 'Liste',
                'route'  => 'admin.{$slugPlural}.index',
                'active' => ['admin.{$slugPlural}.*'],
                'perm'   => '{$slugPlural}.view',
            ],
            [
                'title'  => 'Çöp',
                'route'  => 'admin.{$slugPlural}.trash',
                'active' => ['admin.{$slugPlural}.trash'],
                'perm'   => '{$slugPlural}.trash',
            ],
        ],
    ],
];
PHP;

        file_put_contents($path, $content);
    }

    private function ensureDropInPermissionModuleFile(string $slugPlural, bool $force): void
    {
        $dir = database_path('seeders/permissions/modules');
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        }

        $path = $dir . "/{$slugPlural}.php";
        if (file_exists($path) && !$force) {
            return;
        }

        $content = <<<PHP
<?php

return [
    '{$slugPlural}.view',
    '{$slugPlural}.create',
    '{$slugPlural}.update',
    '{$slugPlural}.delete',
    '{$slugPlural}.trash',
    '{$slugPlural}.restore',
    '{$slugPlural}.force_delete',
];
PHP;

        file_put_contents($path, $content);
    }

    /* -----------------------------------------------------------------
     | Patches
     |-----------------------------------------------------------------*/

    private function patchWebRoutesAdminModulesLoader(): void
    {
        $path = base_path('routes/web.php');
        if (!file_exists($path)) {
            return;
        }

        $content = file_get_contents($path);

        $start = '// [ADMIN_MODULE_ROUTES:START]';
        $end   = '// [ADMIN_MODULE_ROUTES:END]';

        if (!str_contains($content, $start) || !str_contains($content, $end)) {
            return;
        }

        $loader = <<<PHP
// [ADMIN_MODULE_ROUTES:START]
\$__adminModuleDir = __DIR__ . '/admin/modules';
if (is_dir(\$__adminModuleDir)) {
    foreach (glob(\$__adminModuleDir . '/*.php') as \$__f) {
        require \$__f;
    }
}
// [ADMIN_MODULE_ROUTES:END]
PHP;

        $pattern = '/\/\/ \[ADMIN_MODULE_ROUTES:START][\s\S]*?\/\/ \[ADMIN_MODULE_ROUTES:END]/m';
        $new = preg_replace($pattern, $loader, $content, 1);

        if ($new !== null && $new !== $content) {
            file_put_contents($path, $new);
        }
    }

    /* -----------------------------------------------------------------
     | Page Registry Patch (CLEAN)
     |-----------------------------------------------------------------*/

    private function patchAdminPagesRegistry(string $modulePlural): void
    {
        $registryPath = base_path('resources/js/admin/pages/index.js');
        if (!file_exists($registryPath)) {
            return;
        }

        $content = file_get_contents($registryPath);
        $base = Str::studly(Str::singular($modulePlural));

        $importLines = [
            "import {$base}Create from './{$modulePlural}/create';",
            "import {$base}Edit from './{$modulePlural}/edit';",
            "import {$base}Index from './{$modulePlural}/index';",
            "import {$base}Trash from './{$modulePlural}/trash';",
        ];

        $registerLines = [
            "    register('{$modulePlural}.create', {$base}Create);",
            "    register('{$modulePlural}.edit', {$base}Edit);",
            "    register('{$modulePlural}.index', {$base}Index);",
            "    register('{$modulePlural}.trash', {$base}Trash);",
        ];

        if (!str_contains($content, "./{$modulePlural}/index")) {
            $content = $this->insertAfterLastImport(
                $content,
                implode("\n", $importLines) . "\n"
            );
        }

        if (!str_contains($content, "register('{$modulePlural}.index'")) {
            $content = $this->insertIntoRegisterPages(
                $content,
                implode("\n", $registerLines) . "\n"
            );
        }

        file_put_contents($registryPath, $content);
    }

    private function insertAfterLastImport(string $content, string $toInsert): string
    {
        $lines = preg_split("/(\r\n|\n|\r)/", $content);
        $lastImportIdx = -1;

        foreach ($lines as $i => $line) {
            if (preg_match('/^\s*import\s.+;\s*$/', $line)) {
                $lastImportIdx = $i;
            }
        }

        if ($lastImportIdx === -1) {
            return rtrim($toInsert) . "\n\n" . ltrim($content);
        }

        array_splice($lines, $lastImportIdx + 1, 0, [rtrim($toInsert, "\n")]);
        return implode("\n", $lines);
    }

    private function insertIntoRegisterPages(string $content, string $toInsert): string
    {
        $needle = 'export function registerPages()';
        $pos = strpos($content, $needle);
        if ($pos === false) {
            return $content;
        }

        $openBrace = strpos($content, '{', $pos);
        if ($openBrace === false) {
            return $content;
        }

        $len = strlen($content);
        $depth = 0;

        for ($i = $openBrace; $i < $len; $i++) {
            if ($content[$i] === '{') $depth++;
            if ($content[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($content, 0, $i)
                        . "\n"
                        . rtrim($toInsert, "\n")
                        . "\n"
                        . substr($content, $i);
                }
            }
        }

        return $content;
    }
}
