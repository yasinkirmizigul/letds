<?php

namespace App\Console\Commands;

use App\Support\AdminModuleGenerator\ModuleGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeAdminModule extends Command
{
    protected $signature = 'make:admin-module
                            {name : Module name (e.g. Product)}
                            {--preset= : Preset name (content|product). Default is config(admin-module-generator.default_preset)}
                            {--force : Overwrite existing generated files}
                            {--no-patch : Do not patch supporting files (page registry etc.)}';

    protected $description = 'Generate a fully integrated Admin module (Laravel + Metronic + page-registry)';

    public function handle(ModuleGenerator $generator): int
    {
        $name = (string) $this->argument('name');
        $preset = (string) ($this->option('preset') ?: '');

        $result = $generator->generate(
            rawName: $name,
            force: (bool) $this->option('force'),
            patch: ! (bool) $this->option('no-patch'),
            preset: $preset !== '' ? $preset : null,
            output: $this
        );

        if (! $result->ok) {
            $this->error($result->message);
            return self::FAILURE;
        }

        // ✅ Page-registry patch (projendeki gerçek key formatı: "blog.index", "projects.trash", ...)
        if (! (bool) $this->option('no-patch')) {
            $modulePlural = Str::kebab(Str::pluralStudly($name)); // Product -> products
            $this->patchAdminPagesRegistry($modulePlural);
        }

        $this->info($result->message);

        if (!empty($result->notes)) {
            $this->newLine();
            $this->line('<comment>Notes:</comment>');
            foreach ($result->notes as $n) {
                $this->line(" - {$n}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Patch: resources/js/admin/pages/index.js
     * - import ./{module}/index|create|edit|trash
     * - register('{module}.index|create|edit|trash', ...)
     *
     * Proje standardı: register key "admin." prefix YOK (ör: projects.trash).
     */
    private function patchAdminPagesRegistry(string $modulePlural): void
    {
        $registryPath = base_path('resources/js/admin/pages/index.js');

        if (!file_exists($registryPath)) {
            throw new \RuntimeException("Page registry not found: {$registryPath}");
        }

        $content = file_get_contents($registryPath);

        $base = Str::studly(Str::singular($modulePlural));

        $varIndex  = "{$base}Index";
        $varCreate = "{$base}Create";
        $varEdit   = "{$base}Edit";
        $varTrash  = "{$base}Trash";

        $importLines = [
            "import {$varCreate} from './{$modulePlural}/create';",
            "import {$varEdit} from './{$modulePlural}/edit';",
            "import {$varIndex} from './{$modulePlural}/index';",
            "import {$varTrash} from './{$modulePlural}/trash';",
        ];

        $registerLines = [
            "    register('{$modulePlural}.create', {$varCreate});",
            "    register('{$modulePlural}.edit', {$varEdit});",
            "    register('{$modulePlural}.index', {$varIndex});",
            "    register('{$modulePlural}.trash', {$varTrash});",
        ];

        // 1) Imports (idempotent)
        $needsImport = !str_contains($content, "./{$modulePlural}/index'");
        if ($needsImport) {
            $content = $this->insertAfterLastImport($content, implode("\n", $importLines) . "\n");
        }

        // 2) registerPages() (idempotent)
        $needsRegister = !str_contains($content, "register('{$modulePlural}.index'");
        if ($needsRegister) {
            $content = $this->insertIntoRegisterPages($content, implode("\n", $registerLines) . "\n");
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

        array_splice($lines, $lastImportIdx + 1, 0, [rtrim($toInsert)]);
        return implode("\n", $lines);
    }

    private function insertIntoRegisterPages(string $content, string $toInsert): string
    {
        $needle = 'export function registerPages()';
        $pos = strpos($content, $needle);

        if ($pos === false) {
            throw new \RuntimeException("registerPages() function not found in resources/js/admin/pages/index.js");
        }

        $openBrace = strpos($content, '{', $pos);
        if ($openBrace === false) {
            throw new \RuntimeException("registerPages() opening brace not found");
        }

        $len = strlen($content);
        $depth = 0;

        for ($i = $openBrace; $i < $len; $i++) {
            $ch = $content[$i];
            if ($ch === '{') $depth++;
            if ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    $insertPos = $i;
                    $prefix = (substr($content, $insertPos - 1, 1) === "\n") ? '' : "\n";
                    return substr($content, 0, $insertPos) . $prefix . rtrim($toInsert) . "\n" . substr($content, $insertPos);
                }
            }
        }

        throw new \RuntimeException("registerPages() block parse failed");
    }
}
