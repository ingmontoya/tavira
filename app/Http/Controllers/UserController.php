<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view_users')->only(['index', 'show']);
        $this->middleware('can:create_users')->only(['create', 'store']);
        $this->middleware('can:edit_users')->only(['edit', 'update']);
        $this->middleware('can:delete_users')->only(['destroy']);
    }

    public function index(Request $request): Response
    {
        $query = User::with('roles')->administrative();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->get('role'));
            });
        }

        if ($request->filled('status')) {
            $active = $request->get('status') === 'active';
            $query->where('is_active', $active);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->get('department'));
        }

        $users = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::whereIn('name', ['superadmin', 'admin_conjunto', 'consejo'])
            ->pluck('name', 'name');

        $departments = User::administrative()
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department', 'department');

        $template = request()->routeIs('settings.users.*') ? 'settings/Users' : 'users/Index';
        
        return Inertia::render($template, [
            'users' => $users,
            'roles' => $roles,
            'departments' => $departments,
            'filters' => $request->only(['search', 'role', 'status', 'department']),
        ]);
    }

    public function create(): Response
    {
        $roles = Role::whereIn('name', ['superadmin', 'admin_conjunto', 'consejo'])
            ->get(['id', 'name']);

        $template = request()->routeIs('settings.users.*') ? 'settings/Users/Create' : 'users/Create';

        return Inertia::render($template, [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'role' => ['required', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'position' => $validated['position'],
            'department' => $validated['department'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->assignRole($validated['role']);

        // Check if this is a settings route
        if (request()->routeIs('settings.users.store')) {
            return redirect()->route('settings.users.index')->with('success', 'Usuario creado exitosamente.');
        }

        return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente.');
    }

    public function show(User $user): Response
    {
        $user->load('roles');

        $template = request()->routeIs('settings.users.*') ? 'settings/Users/Show' : 'users/Show';

        return Inertia::render($template, [
            'user' => $user,
        ]);
    }

    public function edit(User $user): Response
    {
        $user->load('roles');

        $roles = Role::whereIn('name', ['superadmin', 'admin_conjunto', 'consejo'])
            ->get(['id', 'name']);

        $template = request()->routeIs('settings.users.*') ? 'settings/Users/Edit' : 'users/Edit';

        return Inertia::render($template, [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'role' => ['required', 'exists:roles,name'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'position' => $validated['position'],
            'department' => $validated['department'],
            'is_active' => $validated['is_active'] ?? true,
        ];

        if ($validated['password']) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $user->syncRoles([$validated['role']]);

        // Check if this is a settings route
        if (request()->routeIs('settings.users.update')) {
            return redirect()->route('settings.users.index')->with('success', 'Usuario actualizado exitosamente.');
        }

        return redirect()->route('users.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'No puedes eliminar tu propia cuenta.']);
        }

        $user->delete();

        // Check if this is a settings route
        if (request()->routeIs('settings.users.destroy')) {
            return redirect()->route('settings.users.index')->with('success', 'Usuario eliminado exitosamente.');
        }

        return redirect()->route('users.index')->with('success', 'Usuario eliminado exitosamente.');
    }
}
