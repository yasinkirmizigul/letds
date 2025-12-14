<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\User\Permission;
use App\Models\User\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('name')->paginate(15);
        return view('admin.pages.roles.index', [
            'pageTitle' => 'Kullanıcı Roller',
        ], compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('slug')->get();
        return view('admin.pages.roles.create', compact('permissions'));
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

        return redirect()->route('admin.roles.index')->with('ok', 'Rol oluşturuldu.');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('slug')->get();
        $role->load('permissions');
        return view('admin.pages.roles.edit', compact('role','permissions'));
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

        return redirect()->route('admin.roles.index')->with('ok', 'Rol silindi.');
    }
}
