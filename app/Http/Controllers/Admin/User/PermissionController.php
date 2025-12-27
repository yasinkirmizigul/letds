<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\Permission;
use App\Support\Rbac;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('slug')->paginate(20);
        return view('admin.pages.permissions.index', [
            'pageTitle' => 'Role İzinler',
        ], compact('permissions'));
    }

    public function create()
    {
        Rbac::bumpVersion();
        return view('admin.pages.permissions.create', [
            'pageTitle' => 'Role İzin Ekle',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:190'],
            'slug' => ['required','string','max:190','unique:permissions,slug'],
        ]);

        Permission::create($validated);
        Rbac::bumpVersion();
        return redirect()->route('admin.permissions.index')->with('ok', 'Yetki oluşturuldu.');
    }

    public function edit(Permission $permission)
    {
        return view('admin.pages.permissions.edit', [
            'pageTitle' => 'Role İzin Düzenle',
        ], compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:190'],
            'slug' => ['required','string','max:190', Rule::unique('permissions','slug')->ignore($permission->id)],
        ]);

        $permission->update($validated);
        Rbac::bumpVersion();
        return redirect()->route('admin.permissions.index')->with('ok', 'Yetki güncellendi.');
    }

    public function destroy(Permission $permission)
    {
        $permission->roles()->detach();
        $permission->delete();
        Rbac::bumpVersion();
        return redirect()->route('admin.permissions.index')->with('ok', 'Yetki silindi.');
    }
}
