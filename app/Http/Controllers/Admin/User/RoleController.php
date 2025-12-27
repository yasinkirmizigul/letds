<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\Permission;
use App\Models\Admin\User\Role;
use App\Support\Rbac;
use Illuminate\Http\Request;

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

    public function edit(Role $role)
    {
        $role->load('permissions:id');

        $permissions = Permission::orderBy('slug')->get()
            ->groupBy(fn($p) => explode('.', $p->slug, 2)[0] ?? 'other');

        return view('admin.pages.roles.edit', [
            'pageTitle' => 'Rol Düzenle',
        ], compact('role','permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'slug' => ['required','string','max:255','unique:roles,slug'],
            'priority' => ['nullable','integer','min:0','max:100000'],
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

        if ($role->slug === 'superadmin') {
            return back()->withErrors(['error' => 'Superadmin rolü düzenlenemez.']);
        }

        // Kendinden yüksek/eşit rolü düzenleyemez (yetki olsa bile)
        if ($myP <= $targetP) {
            return back()->withErrors(['error' => 'Kendinle aynı veya daha yüksek öncelikli rolü düzenleyemezsin.']);
        }
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'slug' => ['required','string','max:255',"unique:roles,slug,{$role->id}"],
            'priority' => ['nullable','integer','min:0','max:100000'],
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

    public function destroy(Role $role)
    {
        if ($role->slug === 'superadmin') {
            return back()->withErrors(['error' => 'Superadmin rolü silinemez.']);
        }
        $u = auth()->user();
        $myP = $u?->topRolePriority() ?? 0;
        $targetP = (int)($role->priority ?? 0);

        // Kendinden yüksek ya da eşit priority rolü silemez
        if ($myP <= $targetP) {
            return back()->withErrors(['error' => 'Kendinle aynı veya daha yüksek öncelikli rolü silemezsin.']);
        }

        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        Rbac::bumpVersion();
        return redirect()->route('admin.roles.index')->with('ok', 'Rol silindi.');
    }
}
