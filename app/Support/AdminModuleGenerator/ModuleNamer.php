<?php

namespace App\Support\AdminModuleGenerator;

use Illuminate\Support\Str;

class ModuleNamer
{
    public function __construct(public string $rawName) {}

    public static function from(string $rawName): self
    {
        $rawName = trim($rawName);
        if ($rawName === '') {
            throw new \InvalidArgumentException('Module name cannot be empty.');
        }
        return new self($rawName);
    }

    public function studlySingular(): string
    {
        return Str::studly(Str::singular($this->rawName));
    }

    public function studlyPlural(): string
    {
        return Str::studly(Str::plural($this->rawName));
    }

    public function snakeSingular(): string
    {
        return Str::snake(Str::singular($this->rawName));
    }

    public function snakePlural(): string
    {
        return Str::snake(Str::plural($this->rawName));
    }

    public function kebabPlural(): string
    {
        return Str::kebab(Str::plural($this->rawName));
    }

    public function routeNamePlural(): string
    {
        // Keep route names stable: portfolios.*
        return Str::snake(Str::plural($this->rawName));
    }

    public function table(): string
    {
        return $this->snakePlural();
    }

    public function model(): string
    {
        return $this->studlySingular();
    }

    public function controller(): string
    {
        return $this->studlySingular().'Controller';
    }

    public function policy(): string
    {
        return $this->studlySingular().'Policy';
    }

    public function requestsStore(): string
    {
        return 'Store'.$this->studlySingular().'Request';
    }

    public function requestsUpdate(): string
    {
        return 'Update'.$this->studlySingular().'Request';
    }

    public function permissionKey(): string
    {
        // portfolios -> admin.portfolios.view
        return $this->snakePlural();
    }
}
