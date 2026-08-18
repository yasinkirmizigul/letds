<?php

namespace App\Http\Controllers\Site\Cms;

use App\Http\Controllers\Controller;
use App\Services\Site\HomepageSectionService;
use Illuminate\Contracts\View\View;

class ServiceController extends Controller
{
    public function __invoke(HomepageSectionService $sectionService): View
    {
        $sections = $sectionService->resolvedForPlacement('services');

        return view('site.services.index', [
            'serviceSections' => collect($sections)->where('type', 'services')->values()->all(),
            'processSections' => collect($sections)->where('type', 'process')->values()->all(),
        ]);
    }
}
