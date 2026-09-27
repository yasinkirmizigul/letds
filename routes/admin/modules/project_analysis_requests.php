<?php

use App\Http\Controllers\Admin\Project\ProjectAnalysisRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/analysis-requests', [ProjectAnalysisRequestController::class, 'index'])
    ->name('analysis-requests.index');

Route::get('/analysis-requests/projects/{project}', [ProjectAnalysisRequestController::class, 'showProject'])
    ->name('analysis-requests.projects.show');
Route::get('/analysis-requests/projects/{project}/files/{projectFile}', [ProjectAnalysisRequestController::class, 'downloadProjectFile'])
    ->name('analysis-requests.projects.files.download');
Route::patch('/analysis-requests/projects/{project}/status', [ProjectAnalysisRequestController::class, 'updateProjectStatus'])
    ->name('analysis-requests.projects.status.update');
Route::post('/analysis-requests/projects/{project}/reports', [ProjectAnalysisRequestController::class, 'storeReport'])
    ->name('analysis-requests.projects.reports.store');
Route::post('/analysis-requests/{analysisRequest}/reply', [ProjectAnalysisRequestController::class, 'reply'])
    ->middleware('throttle:20,60')
    ->name('analysis-requests.reply');

Route::get('/analysis-requests/{analysisRequest}/files/{projectFile}', [ProjectAnalysisRequestController::class, 'download'])
    ->name('analysis-requests.files.download');
