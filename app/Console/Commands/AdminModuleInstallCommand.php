<?php

namespace App\Console\Commands;

use App\Support\AdminModule\AdminModuleInstaller;
use Illuminate\Console\Command;
use RuntimeException;

class AdminModuleInstallCommand extends Command
{
    protected $signature = 'admin:module-install
        {slug : module slug (kebab plural), e.g. products}
        {--title= : menu label, e.g. Products}
        {--controller= : full controller class, e.g. App\\Http\\Controllers\\Admin\\ProductController}
        {--model= : full model class, e.g. App\\Models\\Product}
        {--policy= : full policy class, e.g. App\\Policies\\ProductPolicy}
        {--routeFile= : module route file path, e.g. routes/admin/products.php}
        {--menuIcon=ki-outline ki-basket : keenicon class}
        {--permPrefix=admin : permission prefix}
        {--permKey= : permission key, default = slug}
        {--adminPrefix=admin : route name prefix (admin.)}
        {--dry : show what would change without writing}';

    public function handle(AdminModuleInstaller $installer): int
    {
        $slug = trim((string) $this->argument('slug'));
        if ($slug === '') return $this->failWith('slug boş');

        $title      = (string) ($this->option('title') ?: ucfirst($slug));
        $controller = (string) ($this->option('controller') ?: '');
        $model      = (string) ($this->option('model') ?: '');
        $policy     = (string) ($this->option('policy') ?: '');
        $routeFile  = (string) ($this->option('routeFile') ?: "routes/admin/{$slug}.php");

        $menuIcon   = (string) $this->option('menuIcon');
        $permPrefix = (string) $this->option('permPrefix');
        $permKey    = (string) ($this->option('permKey') ?: $slug);
        $adminPrefix= (string) $this->option('adminPrefix');

        $dry = (bool) $this->option('dry');

        // 1) routes/admin.php include
        $routesAdminPath = base_path('routes/admin.php');
        $routesPayload = "require base_path('{$routeFile}'); // {$slug}";

        // 2) admin_menu item
        $menuPath = config_path('admin_menu.php');
        $menuPayload = $this->menuPayload($slug, $title, $menuIcon, $adminPrefix, $permPrefix, $permKey);

        // 3) policies map
        $authProviderPath = app_path('Providers/AuthServiceProvider.php');
        $policyPayload = $this->policyPayload($model, $policy);

        // 4) permissions registry (opsiyonel)
        $permConfigPath = config_path('admin_permissions.php'); // yoksa marker koymazsın, patch de yapmaz
        $permPayload = $this->permissionsPayload($slug, $title, $permPrefix, $permKey);

        if ($dry) {
            $this->line("[DRY] routes/admin.php +={$routesPayload}");
            $this->line("[DRY] config/admin_menu.php +=" . trim($menuPayload));
            $this->line("[DRY] AuthServiceProvider +=" . trim($policyPayload));
            if (file_exists($permConfigPath)) $this->line("[DRY] config/admin_permissions.php +=" . trim($permPayload));
            return self::SUCCESS;
        }

        $installer->injectIntoFile($routesAdminPath, '// [ADMIN_MODULE_ROUTES:START]', '// [ADMIN_MODULE_ROUTES:END]', $routesPayload);

        $installer->injectIntoFile($menuPath, '// [ADMIN_MODULE_MENU:START]', '// [ADMIN_MODULE_MENU:END]', $menuPayload);

        if ($model && $policy) {
            $installer->injectIntoFile($authProviderPath, '// [ADMIN_MODULE_POLICIES:START]', '// [ADMIN_MODULE_POLICIES:END]', $policyPayload);
        } else {
            $this->warn('model/policy boş: policy register patch atlandı.');
        }

        if (file_exists($permConfigPath)) {
            $installer->injectIntoFile($permConfigPath, '// [ADMIN_MODULE_PERMISSIONS:START]', '// [ADMIN_MODULE_PERMISSIONS:END]', $permPayload);
        } else {
            $this->warn('config/admin_permissions.php yok: permission registry patch atlandı.');
        }

        $this->info("OK: {$slug} installed (routes/menu/policy/permissions).");
        return self::SUCCESS;
    }

    private function menuPayload(string $slug, string $title, string $icon, string $adminPrefix, string $permPrefix, string $permKey): string
    {
        // route_name_plural = slug varsayımı (products gibi)
        $routeBase = "{$adminPrefix}.{$slug}";

        return <<<PHP
[
    'key'   => '{$slug}',
    'label' => '{$title}',
    'icon'  => '{$icon}',
    'route' => '{$routeBase}.index',
    'can'   => '{$permPrefix}.{$permKey}.view',
    'children' => [
        [
            'key' => '{$slug}.list',
            'label' => 'Liste',
            'route' => '{$routeBase}.index',
            'can'   => '{$permPrefix}.{$permKey}.view',
        ],
        [
            'key' => '{$slug}.trash',
            'label' => 'Çöp',
            'route' => '{$routeBase}.trash',
            'can'   => '{$permPrefix}.{$permKey}.view',
        ],
    ],
],
PHP;
    }

    private function policyPayload(string $model, string $policy): string
    {
        if (!$model || !$policy) return '';
        return "{$model}::class => {$policy}::class,";
    }

    private function permissionsPayload(string $slug, string $title, string $permPrefix, string $permKey): string
    {
        // burada permissions.stub mantığını inline yazıyoruz ki registry dosyası tek yerden include etmeye devam etsin
        $groupKey = "{$permPrefix}.{$permKey}";

        return <<<PHP
'{$groupKey}' => [
    'label' => '{$title}',
    'permissions' => [
        ['name' => '{$groupKey}.view',    'label' => 'Görüntüle'],
        ['name' => '{$groupKey}.create',  'label' => 'Oluştur'],
        ['name' => '{$groupKey}.update',  'label' => 'Güncelle'],
        ['name' => '{$groupKey}.delete',  'label' => 'Sil (Çöp)'],
        ['name' => '{$groupKey}.restore', 'label' => 'Geri Yükle'],
        ['name' => '{$groupKey}.force',   'label' => 'Kalıcı Sil'],
    ],
],
PHP;
    }

    private function failWith(string $msg): int
    {
        $this->error($msg);
        return self::FAILURE;
    }
}
