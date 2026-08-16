<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\User;
use App\Services\Admin\DemoDataFactoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class DemoDataFactoryController extends Controller
{
    public function index(DemoDataFactoryService $factory): View
    {
        return view('admin.pages.system.demo-data.index', [
            'pageTitle' => 'Örnek Veri Fabrikası',
            'pageDescription' => 'Sunum ve geliştirme için ilişkili örnek veriler üret',
            'overview' => $factory->overview(),
            'demoPassword' => DemoDataFactoryService::DEMO_PASSWORD,
        ]);
    }

    public function generate(Request $request, DemoDataFactoryService $factory): RedirectResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();
            $result = $factory->generate($user);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Örnek veriler üretilemedi. Veritabanı ve dosya kayıtları geri alındı.');
        }

        return redirect()
            ->route('admin.demo-data.index')
            ->with('success', number_format((int) $result['created_total']).' ilişkili örnek kayıt kullanıma hazırlandı.');
    }

    public function reset(Request $request, DemoDataFactoryService $factory): RedirectResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();
            $result = $factory->reset($user);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Veriler sıfırlanamadı. Hiçbir korunan sistem kaydı değiştirilmedi.');
        }

        return redirect()
            ->route('admin.demo-data.index')
            ->with('success', number_format((int) $result['removed_total']).' kayıt temizlendi; yönetici hesapları ve sistem yapılandırması korundu.');
    }
}
