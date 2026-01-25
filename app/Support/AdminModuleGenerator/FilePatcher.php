<?php

namespace App\Support\AdminModuleGenerator;

class FilePatcher
{
    /**
     * Insert $line right after $marker line (exact match as substring).
     * - Idempotent: if $line already exists, no-op.
     * - Safe: if marker not found, no-op with message.
     *
     * @return array{0:bool,1:string}
     */
    public function patchAfterMarker(string $file, string $marker, string $line): array
    {
        if (!is_file($file)) {
            return [false, "Patch skipped: file not found: {$file}"];
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return [false, "Patch skipped: cannot read: {$file}"];
        }

        if (str_contains($content, $line)) {
            return [true, "Patch OK: already present: {$file}"];
        }

        $pos = strpos($content, $marker);
        if ($pos === false) {
            return [false, "Patch skipped: marker not found in {$file} ({$marker})"];
        }

        // Insert after the marker line
        $before = substr($content, 0, $pos);
        $after  = substr($content, $pos);

        // find end of marker line
        $eolPos = strpos($after, "\n");
        if ($eolPos === false) {
            // marker is on last line
            $patched = $before . $after . "\n" . $line . "\n";
        } else {
            $eolPosAbs = $pos + $eolPos + 1;
            $patched =
                substr($content, 0, $eolPosAbs)
                . $line . "\n"
                . substr($content, $eolPosAbs);
        }

        $ok = file_put_contents($file, $patched) !== false;
        if (!$ok) {
            return [false, "Patch failed: cannot write: {$file}"];
        }

        return [true, "Patch OK: injected into {$file}"];
    }
}
