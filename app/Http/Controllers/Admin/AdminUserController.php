<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): View
    {
        $query = User::with('roles');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('cedula', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for editing the specified user roles.
     */
    public function edit(User $user): View
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user roles in storage.
     */
    public function update(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $user->syncRoles($request->input('roles'));

        return redirect()->route('admin.users.index')
            ->with('success', "Roles del usuario {$user->name} actualizados correctamente.");
    }
}
