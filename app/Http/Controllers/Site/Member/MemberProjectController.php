<?php

namespace App\Http\Controllers\Site\Member;

use App\Http\Controllers\Controller;
use App\Models\Admin\Project\Project;
use App\Models\Admin\Project\ProjectFile;
use App\Models\Member;
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
            ->withCount('files')
            ->search($search)
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('site.member-projects.index', [
            'pageTitle' => 'Projelerim',
            'metaDescription' => 'Proje ilerlemelerinizi ve paylaşılan dosyaları yönetin.',
            'projects' => $projects,
            'search' => $search,
        ]);
    }

    public function show(Request $request, Project $project): View
    {
        $project = $this->ownedProject($request, $project);
        $project->load([
            'appointment.provider' => fn ($provider) => $provider
                ->visibleTo(null)
                ->select(['users.id', 'users.name', 'users.title']),
            'files.member:id,name,surname',
            'serviceReview',
        ]);

        return view('site.member-projects.show', [
            'pageTitle' => $project->title,
            'metaDescription' => $project->excerptPreview(160),
            'project' => $project,
            'workflowSteps' => $project->memberWorkflowSteps(),
        ]);
    }

    public function storeFiles(Request $request, Project $project): RedirectResponse
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
            DB::transaction(function () use ($request, $project, $member, $validated, &$storedPaths): void {
                foreach ($request->file('files', []) as $file) {
                    $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
                    $path = $file->storeAs(
                        'project-files/'.$project->id.'/'.$member->id,
                        Str::uuid().'.'.$extension,
                        'local'
                    );
                    $storedPaths[] = $path;

                    $project->files()->create([
                        'member_id' => $member->id,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => basename($file->getClientOriginalName()),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'note' => $validated['note'] ?? null,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return back()->with('success', count($storedPaths).' dosya projeye güvenli biçimde eklendi.');
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
