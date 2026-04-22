<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Support\Rbac;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $baseQuery = User::query();

        $users = (clone $baseQuery)
            ->select(['id', 'name', 'email', 'is_active', 'created_at'])
            ->with(['roles:id,name,slug,priority'])
            ->orderByDesc('id')
            ->get();

        $roles = Role::query()
            ->select(['id', 'name', 'slug', 'priority'])
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
            'admins' => (clone $baseQuery)->whereHas('roles', fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))->count(),
            'superadmins' => (clone $baseQuery)->whereHas('roles', fn ($query) => $query->where('slug', 'superadmin'))->count(),
        ];

        return view('admin.pages.users.index', [
            'pageTitle' => 'Kullanıcılar',
            'users' => $users,
            'roles' => $roles,
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.pages.users.create', [
            'pageTitle' => 'Kullanıcı Ekle',
        ], compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $user->roles()->sync($validated['roles'] ?? []);
        Rbac::bumpVersion();

        return redirect()->route('admin.users.index')->with('ok', 'Kullanıcı oluşturuldu.');
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $user->load('roles');

        return view('admin.pages.users.edit', [
            'pageTitle' => 'Kullanıcı Düzenle',
        ], compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_active = (bool) ($validated['is_active'] ?? false);

        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save();
        $user->roles()->sync($validated['roles'] ?? []);
        Rbac::bumpVersion();

        return redirect()->route('admin.users.index')->with('ok', 'Kullanıcı güncellendi.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->withErrors(['error' => 'Kendi hesabini silemezsin.']);
        }

        $user->roles()->detach();
        $user->delete();
        Rbac::bumpVersion();

        return redirect()->route('admin.users.index')->with('ok', 'Kullanıcı silindi.');
    }
}
