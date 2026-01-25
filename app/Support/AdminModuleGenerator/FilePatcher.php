<?php

namespace App\Support\AdminModuleGenerator;

class FilePatcher
{
    public function patchAfterMarker(string $file, string $marker, string $injectionLine): array
    {
        if (!is_file($file)) {
            return [false, "File not found: {$file}"];
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return [false, "Cannot read file: {$file}"];
        }

        if (strpos($content, $marker) === false) {
            return [false, "Marker not found in {$file}: {$marker}"];
        }

        if (strpos($content, $injectionLine) !== false) {
            return [true, "Already patched: {$file}"];
        }

        $patched = str_replace($marker, $marker.PHP_EOL.$injectionLine, $content);

        $ok = file_put_contents($file, $patched) !== false;
        return [$ok, $ok ? "Patched {$file}" : "Cannot write file: {$file}"];
    }
}
