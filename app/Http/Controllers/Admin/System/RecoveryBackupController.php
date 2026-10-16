<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Support\Recovery\RecoveryArchives;
use App\Support\Recovery\RecoveryConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class RecoveryBackupController extends Controller
{
    public function index(RecoveryArchives $archives, RecoveryConfiguration $configuration): View
    {
        $items = [];
        $listError = null;

        if ($configuration->isReady()) {
            try {
                $items = $archives->all();
            } catch (Throwable $exception) {
                report($exception);
                $listError = 'Bulut yedeklerine erişilemiyor. Bağlantı ve bucket ayarlarını kontrol edin.';
            }
        }

        return view('admin.pages.system.recovery-backups.index', [
            'pageTitle' => 'Kurtarma Yedekleri',
            'pageDescription' => 'Şifreli tam yedekleri yönet',
            'archives' => $items,
            'problems' => $configuration->problems(),
            'listError' => $listError,
        ]);
    }

    public function download(Request $request, RecoveryArchives $archives, RecoveryConfiguration $configuration)
    {
        abort_unless($configuration->isReady(), 503);

        try {
            $path = $archives->path((string) $request->query('file'));
        } catch (RuntimeException) {
            abort(404);
        }

        abort_unless(Storage::disk('recovery_backups')->exists($path), 404);

        return Storage::disk('recovery_backups')->download($path, basename($path), [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function import(Request $request, RecoveryArchives $archives, RecoveryConfiguration $configuration): RedirectResponse
    {
        $validated = $request->validate([
            'archive' => ['required', 'file', 'mimes:zip'],
        ]);

        try {
            $filename = $archives->import($validated['archive'], $configuration);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Arşiv yüklenemedi. Bulut bağlantısı ve sunucu yükleme sınırını kontrol edin.');
        }

        return back()->with('success', "{$filename} doğrulandı ve özel buluta alındı. Geri yükleme için konsol adımlarını uygulayın.");
    }
}
