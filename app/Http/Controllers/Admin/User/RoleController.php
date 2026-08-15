<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\Permission;
use App\Models\Admin\User\Role;
use App\Support\Rbac;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::query()
            ->select(['id','name','slug','priority','created_at'])
            ->withCount('users')
            ->with(['permissions:id,name'])
            ->orderByDesc('priority')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.pages.roles.index', [
            'pageTitle' => 'Kullanıcı Roller',
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        $permissions = Permission::orderBy('slug')->get()
            ->groupBy(fn($p) => explode('.', $p->slug, 2)[0] ?? 'other');

        return view('admin.pages.roles.create', [
            'pageTitle' => 'Rol Ekle',
        ], compact('permissions'));
    }

    public function edit(Request $request, Role $role)
    {
        abort_unless($request->user()?->canManageRole($role), 403);

        $role->load('permissions:id');

        $permissions = Permission::orderBy('slug')->get()
            ->groupBy(fn($p) => explode('.', $p->slug, 2)[0] ?? 'other');

        return view('admin.pages.roles.edit', [
            'pageTitle' => 'Rol Düzenle',
        ], compact('role','permissions'));
    }

    public function store(Request $request)
    {
        $maximumPriority = max(0, $request->user()->topRolePriority() - 1);

        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'slug' => ['required','string','max:255','unique:roles,slug', Rule::notIn(['admin', 'superadmin'])],
            'priority' => ['nullable','integer','min:0','max:'.$maximumPriority],
            'permissions' => ['array'],
            'permissions.*' => ['integer','exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'priority' => (int)($validated['priority'] ?? 0),
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        Rbac::bumpVersion();
        return redirect()->route('admin.roles.index')->with('ok', 'Rol oluşturuldu.');
    }

    public function update(Request $request, Role $role)
    {
        $u = auth()->user();
        $myP = $u?->topRolePriority() ?? 0;
        $targetP = (int)($role->priority ?? 0);

        abort_unless($u?->canManageRole($role), 403);

        $slugRules = ['required', 'string', 'max:255', Rule::unique('roles', 'slug')->ignore($role->id)];
        $priorityRules = ['nullable', 'integer', 'min:0', 'max:'.max(0, $myP - 1)];

        if ($role->slug === 'admin') {
            $slugRules[] = Rule::in(['admin']);
            $priorityRules[] = Rule::in([$targetP]);
        }

        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'slug' => $slugRules,
            'priority' => $priorityRules,
            'permissions' => ['array'],
            'permissions.*' => ['integer','exists:permissions,id'],
        ]);

        $role->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'priority' => (int)($validated['priority'] ?? 0),
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        Rbac::bumpVersion();
        return redirect()->route('admin.roles.index')->with('ok', 'Rol güncellendi.');
    }

    public function destroy(Request $request, Role $role)
    {
        abort_unless($request->user()?->canManageRole($role), 403);

        if ($role->slug === 'admin') {
            return back()->withErrors(['error' => 'Admin sistem rolü silinemez.']);
        }

        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        Rbac::bumpVersion();
        return redirect()->route('admin.roles.index')->with('ok', 'Rol silindi.');
    }
}
