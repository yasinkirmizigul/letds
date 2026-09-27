<?php

use App\Http\Controllers\Site\Member\MemberProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['site.locale', 'auth:member', 'member.active'])
    ->prefix('member/projects')
    ->name('member.projects.')
    ->group(function (): void {
        Route::get('/', [MemberProjectController::class, 'index'])->name('index');
        Route::get('/{project}', [MemberProjectController::class, 'show'])->name('show');
        Route::post('/{project}/analysis-requests', [MemberProjectController::class, 'storeAnalysisRequest'])
            ->middleware('throttle:5,60')
            ->name('analysis-requests.store');
        Route::post('/{project}/analysis-requests/{analysisRequest}/reply', [MemberProjectController::class, 'reply'])
            ->middleware('throttle:20,60')
            ->name('analysis-requests.reply');
        Route::post('/{project}/files', [MemberProjectController::class, 'storeFiles'])
            ->middleware('throttle:20,10')
            ->name('files.store');
        Route::get('/{project}/files/{projectFile}', [MemberProjectController::class, 'download'])
            ->name('files.download');
        Route::delete('/{project}/files/{projectFile}', [MemberProjectController::class, 'destroyFile'])
            ->name('files.destroy');
    });
