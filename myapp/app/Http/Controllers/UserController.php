<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * List users with optional search (username, name, email).
     */
    public function index(Request $request): View
    {
        /** @var User|null $current */
        $current = Auth::user();
        if (! $current || ! $current->hasPermissionTo('settings.users.manage')) {
            abort(403);
        }
        $search = trim((string) $request->input('q', ''));

        $query = User::query()->orderBy('username');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $users = $query->with(['roles', 'companies'])->get();

        return view('setting.users', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Show form to create a new user.
     */
    public function create(): View
    {
        /** @var User|null $current */
        $current = Auth::user();
        if (! $current || ! $current->hasPermissionTo('settings.users.manage')) {
            abort(403);
        }
        $roles = Role::query()->orderBy('name')->get();
        $companies = Company::query()->orderBy('name')->get();

        return view('setting.user-edit', [
            'user' => null,
            'roles' => $roles,
            'companies' => $companies,
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User|null $current */
        $current = Auth::user();
        if (! $current || ! $current->hasPermissionTo('settings.users.manage')) {
            abort(403);
        }
        $valid = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
            'all_companies' => ['boolean'],
            'companies' => ['array'],
            'companies.*' => ['integer', Rule::exists('companies', 'id')],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $user = new User();
        $user->username = $valid['username'];
        $user->name = $valid['name'];
        $user->email = $valid['email'] ?: null;
        $user->password = Hash::make($valid['password']);
        $user->is_active = (bool) ($valid['is_active'] ?? true);
        $user->all_companies = (bool) ($valid['all_companies'] ?? false);
        $user->save();

        if (! empty($valid['roles'])) {
            $user->syncRoles($valid['roles']);
        }
        $user->companies()->sync($valid['companies'] ?? []);

        return redirect()
            ->route('setting.users')
            ->with('success', __('User created successfully.'));
    }

    /**
     * Show form to edit an existing user.
     */
    public function edit(int $id): View|RedirectResponse
    {
        /** @var User|null $current */
        $current = Auth::user();
        if (! $current || ! $current->hasPermissionTo('settings.users.manage')) {
            abort(403);
        }
        $user = User::find($id);
        if (! $user) {
            return redirect()->route('setting.users')->with('error', __('User not found.'));
        }

        $roles = Role::query()->orderBy('name')->get();
        $companies = Company::query()->orderBy('name')->get();

        return view('setting.user-edit', [
            'user' => $user,
            'roles' => $roles,
            'companies' => $companies,
        ]);
    }

    /**
     * Update an existing user.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var User|null $current */
        $current = Auth::user();
        if (! $current || ! $current->hasPermissionTo('settings.users.manage')) {
            abort(403);
        }
        $user = User::find($id);
        if (! $user) {
            return redirect()->route('setting.users')->with('error', __('User not found.'));
        }

        $valid = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
            'all_companies' => ['boolean'],
            'companies' => ['array'],
            'companies.*' => ['integer', Rule::exists('companies', 'id')],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $user->username = $valid['username'];
        $user->name = $valid['name'];
        $user->email = $valid['email'] ?: null;
        $user->is_active = (bool) ($valid['is_active'] ?? true);
        $user->all_companies = (bool) ($valid['all_companies'] ?? false);

        if (! empty($valid['password'])) {
            $user->password = Hash::make($valid['password']);
        }

        $user->save();

        $user->syncRoles($valid['roles'] ?? []);
        $user->companies()->sync($valid['companies'] ?? []);

        return redirect()
            ->route('setting.users')
            ->with('success', __('User updated successfully.'));
    }
}
