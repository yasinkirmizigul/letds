<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\User\Role;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')->latest()->get();

        return view('admin.pages.users.index', [
            'pageTitle' => 'Kullanıcılar',
        ], compact('users'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        return view('admin.pages.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:190'],
            'email' => ['required','email','max:190', 'unique:users,email'],
            'password' => ['required','string','min:6'],
            'is_active' => ['nullable','boolean'],
            'roles' => ['nullable','array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'is_active' => (bool)($validated['is_active'] ?? false),
        ]);

        $user->roles()->sync($validated['roles'] ?? []);

        return redirect()->route('admin.users.index')->with('ok', 'Kullanıcı oluşturuldu.');
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $user->load('roles');
        return view('admin.pages.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:190'],
            'email' => ['required','email','max:190', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable','string','min:6'],
            'is_active' => ['nullable','boolean'],
            'roles' => ['nullable','array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_active = (bool)($validated['is_active'] ?? false);

        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save();
        $user->roles()->sync($validated['roles'] ?? []);

        return redirect()->route('admin.users.index')->with('ok', 'Kullanıcı güncellendi.');
    }

    public function destroy(User $user)
    {
        // Kendini silme saçmalığını engelle
        if (auth()->id() === $user->id) {
            return back()->withErrors(['error' => 'Kendi hesabını silemezsin.']);
        }

        $user->roles()->detach();
        $user->delete();

        return redirect()->route('admin.users.index')->with('ok', 'Kullanıcı silindi.');
    }
}
