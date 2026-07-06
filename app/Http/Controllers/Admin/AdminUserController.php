<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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

    /**
     * Search users by role and query term for autocomplete.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q');
        $role = $request->input('role');

        $usersQuery = User::query();

        if ($role) {
            $usersQuery->role($role);
        }

        if (! empty($query)) {
            $usersQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('cedula', 'like', "%{$query}%");
            });
        }

        $users = $usersQuery->limit(10)
            ->get(['id', 'name', 'email', 'cedula']);

        $results = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'text' => $user->name,
            ];
        });

        return response()->json($results);
    }
}
