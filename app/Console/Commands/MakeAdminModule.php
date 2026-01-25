<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\AdminModuleGenerator\ModuleGenerator;

class MakeAdminModule extends Command
{
    protected $signature = 'make:admin-module
                            {name : Module name (e.g. Portfolio)}
                            {--force : Overwrite existing generated files}
                            {--no-patch : Do not patch routes/menu even if markers exist}';

    protected $description = 'Generate a fully integrated Admin content module (Laravel + Metronic + page-registry)';

    public function handle(ModuleGenerator $generator): int
    {
        $name = (string) $this->argument('name');

        $result = $generator->generate(
            $name,
            force: (bool) $this->option('force'),
            patch: ! (bool) $this->option('no-patch'),
            output: $this
        );

        if (! $result->ok) {
            $this->error($result->message);
            return self::FAILURE;
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
}
