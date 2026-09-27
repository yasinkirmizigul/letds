<?php

namespace App\Http\Controllers\Admin\Project;

use App\Http\Controllers\Controller;
use App\Models\Admin\Project\ProjectAnalysisRequest;
use App\Models\Admin\Project\Project;
use App\Models\Admin\Project\ProjectFile;
use App\Models\Admin\User\User;
use App\Services\Project\ProjectWorkflowService;
use App\Services\Review\ServiceReviewAssignmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectAnalysisRequestController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $this->authorizeActor($actor);

        $requests = ProjectAnalysisRequest::query()
            ->with(['project.appointment', 'member:id,name,surname', 'files'])
            ->when(! $actor->isAdmin(), fn ($query) => $query->whereHas('project.appointment',
                fn ($appointment) => $appointment->where('provider_id', $actor->id)))
            ->latest()
            ->paginate(20);

        $projects = Project::query()
            ->whereNotNull('member_id')
            ->when(! $actor->isAdmin(), fn ($query) => $query->whereHas('appointment',
                fn ($appointment) => $appointment->where('provider_id', $actor->id)))
            ->with(['member:id,name,surname', 'appointment.provider:id,name'])
            ->withCount(['workflowEvents as unread_events_count' => fn ($query) => $query->whereNull('provider_seen_at')])
            ->latest('updated_at')
            ->paginate(20, ['*'], 'projects_page');

        return view('admin.pages.projects.analysis-requests', [
            'analysisRequests' => $requests,
            'projects' => $projects,
            'pageTitle' => 'Ek Analiz Talepleri',
        ]);
    }

    public function showProject(Request $request, Project $project): View
    {
        $actor = $request->user();
        $this->authorizeProject($actor, $project);
        if (! $actor->isAdmin()) {
            $project->workflowEvents()->whereNull('provider_seen_at')->update(['provider_seen_at' => now()]);
        }
        $project->load(['member:id,name,surname', 'appointment.provider:id,name,title', 'files.member:id,name,surname', 'analysisRequests.files', 'workflowEvents']);

        return view('admin.pages.projects.analysis-project', [
            'project' => $project,
            'workflowSteps' => $project->memberWorkflowSteps(),
            'pageTitle' => $project->title.' · Analiz süreci',
        ]);
    }

    public function downloadProjectFile(Request $request, Project $project, ProjectFile $projectFile): StreamedResponse
    {
        $this->authorizeProject($request->user(), $project);
        abort_unless((int) $projectFile->project_id === (int) $project->id, 404);
        abort_unless(Storage::disk($projectFile->disk)->exists($projectFile->path), 404);

        return Storage::disk($projectFile->disk)->download($projectFile->path, $projectFile->original_name);
    }

    public function updateProjectStatus(Request $request, Project $project, ProjectWorkflowService $workflow, ServiceReviewAssignmentService $reviews): RedirectResponse
    {
        $actor = $request->user();
        $this->authorizeExpertProject($actor, $project);
        $validated = $request->validate(['status' => ['required', 'in:dev_pending,dev_in_progress,approved']]);

        DB::transaction(function () use ($project, $actor, $validated, $workflow): void {
            $locked = Project::query()->lockForUpdate()->findOrFail($project->id);
            $transitions = [
                Project::STATUS_APPOINTMENT_DONE => Project::STATUS_DEV_PENDING,
                Project::STATUS_DEV_PENDING => Project::STATUS_DEV_IN_PROGRESS,
                Project::STATUS_DELIVERED => Project::STATUS_APPROVED,
            ];
            abort_unless(($transitions[$locked->status] ?? null) === $validated['status'], 422, 'Bu aşama geçişi kullanılamaz.');
            $from = $locked->status;
            $locked->update(['status' => $validated['status']]);
            $workflow->record($locked, 'project_status_changed', 'provider', $actor->id, Project::statusLabel($validated['status']), ['from' => $from, 'to' => $validated['status']]);
        });

        $reviews->assignForProject($project->fresh());
        return back()->with('success', 'Analiz aşaması güncellendi.');
    }

    public function storeReport(Request $request, Project $project, ProjectWorkflowService $workflow, ServiceReviewAssignmentService $reviews): RedirectResponse
    {
        $actor = $request->user();
        $this->authorizeExpertProject($actor, $project);
        abort_unless($project->member_id, 422, 'Bu çalışmanın üyesi bulunamadı.');
        $request->validate(['report' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,zip']]);

        $file = $request->file('report');
        $path = $file->storeAs('project-files/'.$project->id.'/reports', Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: 'bin'), 'local');
        try {
            DB::transaction(function () use ($project, $actor, $file, $path, $workflow): void {
                $locked = Project::query()->lockForUpdate()->findOrFail($project->id);
                abort_unless(in_array($locked->status, [Project::STATUS_APPOINTMENT_DONE, Project::STATUS_DEV_PENDING, Project::STATUS_DEV_IN_PROGRESS, Project::STATUS_DELIVERED, Project::STATUS_APPROVED], true), 422);
                $stageChanged = $locked->status !== Project::STATUS_DELIVERED && $locked->status !== Project::STATUS_APPROVED;
                $report = $locked->files()->create([
                    'member_id' => null, 'disk' => 'local', 'path' => $path,
                    'original_name' => basename($file->getClientOriginalName()),
                    'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
                    'note' => 'Analiz raporu',
                ]);
                if ($locked->status !== Project::STATUS_APPROVED) {
                    $locked->update(['status' => Project::STATUS_DELIVERED]);
                }
                $workflow->record($locked, 'report_delivered', 'provider', $actor->id, null, ['file_ids' => [$report->id], 'stage_changed' => $stageChanged]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        $reviews->assignForProject($project->fresh());
        return back()->with('success', 'Rapor doğrudan üyeye teslim edildi.');
    }

    public function reply(Request $request, ProjectAnalysisRequest $analysisRequest, ProjectWorkflowService $workflow): RedirectResponse
    {
        $actor = $request->user();
        $analysisRequest->loadMissing('project.appointment');
        $project = $analysisRequest->project;
        abort_unless($project, 404);
        $this->authorizeExpertProject($actor, $project);
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'documents' => ['nullable', 'array', 'max:5'],
            'documents.*' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,csv,zip,jpg,jpeg,png,webp,txt'],
            'complete' => ['nullable', 'boolean'],
        ]);

        $storedPaths = [];
        try {
            DB::transaction(function () use ($request, $project, $analysisRequest, $actor, $validated, $workflow, &$storedPaths): void {
                $fileIds = [];
                foreach ($request->file('documents', []) as $file) {
                    $path = $file->storeAs('project-files/'.$project->id.'/replies', Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: 'bin'), 'local');
                    $storedPaths[] = $path;
                    $projectFile = $project->files()->create([
                        'member_id' => null, 'disk' => 'local', 'path' => $path,
                        'original_name' => basename($file->getClientOriginalName()),
                        'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
                        'note' => 'Uzman yanıt eki',
                    ]);
                    $analysisRequest->files()->attach($projectFile->id);
                    $fileIds[] = $projectFile->id;
                }
                $analysisRequest->update(['status' => ($validated['complete'] ?? false) ? ProjectAnalysisRequest::STATUS_COMPLETED : ProjectAnalysisRequest::STATUS_REVIEWING]);
                $workflow->record($project, 'provider_message', 'provider', $actor->id, trim($validated['message']), ['file_ids' => $fileIds, 'completed' => (bool) ($validated['complete'] ?? false)], $analysisRequest->id);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return back()->with('success', 'Yanıtınız doğrudan üyeye iletildi.');
    }

    public function download(Request $request, ProjectAnalysisRequest $analysisRequest, ProjectFile $projectFile): StreamedResponse
    {
        $actor = $request->user();
        $this->authorizeActor($actor);

        $analysisRequest->loadMissing('project.appointment');
        abort_unless($actor->isAdmin() || (int) $analysisRequest->project?->appointment?->provider_id === (int) $actor->id, 404);
        abort_unless((int) $projectFile->project_id === (int) $analysisRequest->project_id, 404);
        abort_unless($analysisRequest->files()->whereKey($projectFile->id)->exists(), 404);
        abort_unless(Storage::disk($projectFile->disk)->exists($projectFile->path), 404);

        return Storage::disk($projectFile->disk)->download($projectFile->path, $projectFile->original_name);
    }

    private function authorizeActor(?User $actor): void
    {
        abort_unless($actor && ($actor->isAdmin() || $actor->hasRole('provider')), 403);
    }

    private function authorizeProject(?User $actor, Project $project): void
    {
        $this->authorizeActor($actor);
        abort_unless($actor->isAdmin() || ($actor->hasRole('provider') && (int) $project->appointment?->provider_id === (int) $actor->id), 404);
    }

    private function authorizeExpertProject(?User $actor, Project $project): void
    {
        $this->authorizeProject($actor, $project);
        abort_unless(! $actor->isAdmin() && $actor->hasRole('provider'), 403, 'Bu alanda yalnızca atanmış uzman işlem yapabilir.');
    }
}
