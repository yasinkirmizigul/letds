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
            ->select(['id','name','slug','created_at']) // ihtiyacın kadar
            ->withCount('users')
            ->with(['permissions:id,name']) // blade'de name basıyorsun
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
        Rbac::bumpVersion();
        return view('admin.pages.roles.create', compact('permissions'));
    }

    public function edit(Role $role)
    {
        $role->load('permissions:id');
        $permissions = Permission::orderBy('slug')->get()
            ->groupBy(fn($p) => explode('.', $p->slug, 2)[0] ?? 'other');

        return view('admin.pages.roles.edit', compact('role','permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:190'],
            'slug' => ['required','string','max:190','unique:roles,slug'],
            'permissions' => ['nullable','array'],
            'permissions.*' => ['integer','exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        Rbac::bumpVersion();
        return redirect()->route('admin.roles.index')->with('ok', 'Rol oluşturuldu.');
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:190'],
            'slug' => ['required','string','max:190', Rule::unique('roles','slug')->ignore($role->id)],
            'permissions' => ['nullable','array'],
            'permissions.*' => ['integer','exists:permissions,id'],
        ]);

        $role->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);
        Rbac::bumpVersion();

        return redirect()->route('admin.roles.index')->with('ok', 'Rol güncellendi.');
    }

    public function destroy(Role $role)
    {
        // Superadmin rolünü silme (kendini sabote etme)
        if ($role->slug === 'superadmin') {
            return back()->withErrors(['error' => 'Superadmin rolü silinemez.']);
        }

        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();
        Rbac::bumpVersion();
        return redirect()->route('admin.roles.index')->with('ok', 'Rol silindi.');
    }
}
