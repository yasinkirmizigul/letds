<?php

namespace App\Http\Controllers\Site\Member;

use App\Http\Controllers\Controller;
use App\Models\Admin\Project\Project;
use App\Models\Admin\Project\ProjectFile;
use App\Models\Member;
use App\Models\Admin\Project\ProjectAnalysisRequest;
use App\Services\Project\ProjectWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberProjectController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Member $member */
        $member = $request->user('member');
        $search = trim($request->string('q')->toString());
        $projects = $member->projects()
            ->with([
                'appointment.provider' => fn ($provider) => $provider
                    ->visibleTo(null)
                    ->select(['users.id', 'users.name', 'users.title']),
                'serviceReview',
            ])
            ->withCount(['files', 'workflowEvents as unread_events_count' => fn ($query) => $query->whereNull('member_seen_at')])
            ->search($search)
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        $reportedProjects = $member->projects()
            ->whereIn('status', [Project::STATUS_DELIVERED, Project::STATUS_APPROVED, Project::STATUS_CLOSED])
            ->whereHas('files', fn ($query) => $query->whereNull('member_id')->where('note', 'Analiz raporu'))
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return view('site.member-projects.index', [
            'pageTitle' => 'Analiz Sürecim',
            'metaDescription' => 'Analiz aşamalarınızı, belgelerinizi ve sonuçlarınızı tek alanda takip edin.',
            'projects' => $projects,
            'reportedProjects' => $reportedProjects,
            'search' => $search,
        ]);
    }

    public function show(Request $request, Project $project): View
    {
        $project = $this->ownedProject($request, $project);
        $project->workflowEvents()->whereNull('member_seen_at')->update(['member_seen_at' => now()]);
        $project->load([
            'appointment.provider' => fn ($provider) => $provider
                ->visibleTo(null)
                ->select(['users.id', 'users.name', 'users.title']),
            'files.member:id,name,surname',
            'analysisRequests.files',
            'workflowEvents',
            'serviceReview',
        ]);

        return view('site.member-projects.show', [
            'pageTitle' => $project->title,
            'metaDescription' => $project->excerptPreview(160),
            'project' => $project,
            'workflowSteps' => $project->memberWorkflowSteps(),
        ]);
    }

    public function storeFiles(Request $request, Project $project, ProjectWorkflowService $workflow): RedirectResponse
    {
        $project = $this->ownedProject($request, $project);
        abort_unless($project->allowsMemberUploads(), 422, 'Bu proje aşamasında yeni dosya yüklenemez.');

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:5'],
            'files.*' => [
                'required',
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,xls,xlsx,csv,zip,jpg,jpeg,png,webp,txt',
            ],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var Member $member */
        $member = $request->user('member');
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $project, $member, $validated, $workflow, &$storedPaths): void {
                $fileIds = [];
                foreach ($request->file('files', []) as $file) {
                    $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
                    $path = $file->storeAs(
                        'project-files/'.$project->id.'/'.$member->id,
                        Str::uuid().'.'.$extension,
                        'local'
                    );
                    $storedPaths[] = $path;

                    $projectFile = $project->files()->create([
                        'member_id' => $member->id,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => basename($file->getClientOriginalName()),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'note' => $validated['note'] ?? null,
                    ]);
                    $fileIds[] = $projectFile->id;
                }
                $workflow->record($project, 'documents_added', 'member', $member->id, $validated['note'] ?? null, ['file_ids' => $fileIds]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return back()->with('success', count($storedPaths).' dosya projeye güvenli biçimde eklendi.');
    }

    public function storeAnalysisRequest(Request $request, Project $project, ProjectWorkflowService $workflow): RedirectResponse
    {
        $project = $this->ownedProject($request, $project);
        abort_unless($project->allowsAdditionalAnalysisRequests(), 422, 'Bu çalışma için henüz ek analiz talebi açılamaz.');

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'documents' => ['nullable', 'array', 'max:5'],
            'documents.*' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,csv,zip,jpg,jpeg,png,webp,txt'],
        ]);

        /** @var Member $member */
        $member = $request->user('member');
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $project, $member, $validated, $workflow, &$storedPaths): void {
                $analysisRequest = $project->analysisRequests()->create([
                    'member_id' => $member->id,
                    'message' => trim($validated['message']),
                    'status' => 'pending',
                ]);

                $fileIds = [];

                foreach ($request->file('documents', []) as $file) {
                    $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
                    $path = $file->storeAs(
                        'project-files/'.$project->id.'/'.$member->id,
                        Str::uuid().'.'.$extension,
                        'local'
                    );
                    $storedPaths[] = $path;

                    $projectFile = $project->files()->create([
                        'member_id' => $member->id,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => basename($file->getClientOriginalName()),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'note' => 'Ek analiz talebi belgesi',
                    ]);
                    $analysisRequest->files()->attach($projectFile->id);
                    $fileIds[] = $projectFile->id;
                }

                $workflow->record($project, 'request_created', 'member', $member->id, trim($validated['message']), ['file_ids' => $fileIds], $analysisRequest->id);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return back()->with('success', 'Ek analiz talebiniz doğrudan uzmanınıza iletildi.');
    }

    public function reply(Request $request, Project $project, ProjectAnalysisRequest $analysisRequest, ProjectWorkflowService $workflow): RedirectResponse
    {
        $project = $this->ownedProject($request, $project);
        abort_unless((int) $analysisRequest->project_id === (int) $project->id && (int) $analysisRequest->member_id === (int) $request->user('member')->id, 404);

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'documents' => ['nullable', 'array', 'max:5'],
            'documents.*' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,csv,zip,jpg,jpeg,png,webp,txt'],
        ]);

        $storedPaths = [];
        try {
            DB::transaction(function () use ($request, $project, $analysisRequest, $validated, $workflow, &$storedPaths): void {
                $fileIds = [];
                foreach ($request->file('documents', []) as $file) {
                    $path = $file->storeAs('project-files/'.$project->id.'/'.$analysisRequest->member_id, Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: 'bin'), 'local');
                    $storedPaths[] = $path;
                    $projectFile = $project->files()->create([
                        'member_id' => $analysisRequest->member_id,
                        'disk' => 'local', 'path' => $path,
                        'original_name' => basename($file->getClientOriginalName()),
                        'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
                        'note' => 'Ek analiz görüşmesi belgesi',
                    ]);
                    $analysisRequest->files()->attach($projectFile->id);
                    $fileIds[] = $projectFile->id;
                }

                $analysisRequest->update(['status' => ProjectAnalysisRequest::STATUS_PENDING]);
                $workflow->record($project, 'member_message', 'member', $analysisRequest->member_id, trim($validated['message']), ['file_ids' => $fileIds], $analysisRequest->id);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return back()->with('success', 'Mesajınız uzmanınıza iletildi.');
    }

    public function download(Request $request, Project $project, ProjectFile $projectFile): StreamedResponse
    {
        $project = $this->ownedProject($request, $project);
        abort_unless((int) $projectFile->project_id === (int) $project->id, 404);
        abort_unless(Storage::disk($projectFile->disk)->exists($projectFile->path), 404);

        return Storage::disk($projectFile->disk)->download($projectFile->path, $projectFile->original_name);
    }

    public function destroyFile(Request $request, Project $project, ProjectFile $projectFile): RedirectResponse
    {
        $project = $this->ownedProject($request, $project);
        /** @var Member $member */
        $member = $request->user('member');

        abort_unless((int) $projectFile->project_id === (int) $project->id, 404);
        abort_unless((int) $projectFile->member_id === (int) $member->id, 403);
        abort_unless($project->allowsMemberUploads(), 422, 'Tamamlanan projede dosya silinemez.');
        abort_unless(! $project->analysisRequests()->where(function ($query) use ($projectFile): void {
            $query->where('project_file_id', $projectFile->id)
                ->orWhereHas('files', fn ($fileQuery) => $fileQuery->whereKey($projectFile->id));
        })->exists(), 422, 'Ek analiz talebine bağlı belge silinemez.');

        Storage::disk($projectFile->disk)->delete($projectFile->path);
        $projectFile->delete();

        return back()->with('success', 'Dosya projeden kaldırıldı.');
    }

    private function ownedProject(Request $request, Project $project): Project
    {
        /** @var Member $member */
        $member = $request->user('member');
        abort_unless((int) $project->member_id === (int) $member->id, 404);

        return $project;
    }
}
