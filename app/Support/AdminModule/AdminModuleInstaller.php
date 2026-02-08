<?php

namespace App\Support\AdminModule;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class AdminModuleInstaller
{
    public function __construct(private Filesystem $fs) {}

    public function injectIntoFile(string $path, string $markerStart, string $markerEnd, string $payload, bool $ensureNewline = true): void
    {
        if (!$this->fs->exists($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        $content = $this->fs->get($path);

        if (!str_contains($content, $markerStart) || !str_contains($content, $markerEnd)) {
            throw new RuntimeException("Markers not found in {$path}: {$markerStart} / {$markerEnd}");
        }

        // Idempotent: payload zaten varsa tekrar ekleme
        if (str_contains($content, $payload)) {
            return;
        }

        $parts = explode($markerEnd, $content, 2);
        $beforeEnd = $parts[0];
        $afterEnd  = $parts[1] ?? '';

        // markerStart'tan sonraki bölgeyi bul
        $startPos = strpos($beforeEnd, $markerStart);
        if ($startPos === false) {
            throw new RuntimeException("Start marker missing in {$path}: {$markerStart}");
        }

        $head = substr($beforeEnd, 0, $startPos + strlen($markerStart));
        $tail = substr($beforeEnd, $startPos + strlen($markerStart));

        $injected = $head
            . ($ensureNewline ? "\n" : '')
            . rtrim($tail, "\n")
            . ($tail === '' ? '' : "\n")
            . rtrim($payload, "\n")
            . "\n"
            . $markerEnd
            . $afterEnd;

        $this->fs->put($path, $injected);
    }
}
