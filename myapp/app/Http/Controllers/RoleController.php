<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * 固定权限清单：Key = permission name, value = [label, group].
     * 你以后要加新的功能，只要在这里多加一行就可以在 UI 勾选。
     */
    private const PERMISSIONS = [
        // Cashflow
        'cashflow.view' => ['label' => 'View cashflow', 'group' => 'Cashflow'],
        'cashflow.add_row' => ['label' => 'Add row', 'group' => 'Cashflow'],
        'cashflow.edit_row' => ['label' => 'Edit row', 'group' => 'Cashflow'],
        'cashflow.delete_row' => ['label' => 'Delete row', 'group' => 'Cashflow'],
        'cashflow.export' => ['label' => 'Export', 'group' => 'Cashflow'],
        'cashflow.manage_columns' => ['label' => 'Manage columns', 'group' => 'Cashflow'],

        // Companies
        'company.manage' => ['label' => 'Create / edit / delete company', 'group' => 'Companies'],
        'company.report.view' => ['label' => 'View monthly report', 'group' => 'Companies'],
        'company.report.edit' => ['label' => 'Edit monthly report', 'group' => 'Companies'],
        'company.report.export' => ['label' => 'Export monthly report', 'group' => 'Companies'],

        // Settings
        'settings.users.manage' => ['label' => 'Manage users', 'group' => 'Settings'],
        'settings.audit.view' => ['label' => 'View audit log', 'group' => 'Settings'],
    ];

    /**
     * 删除相关的权限 key（staff 不可以有这些）。
     * 需要在 Blade 中读取，所以设为 public。
     */
    public const DELETE_PERMISSIONS = [
        'cashflow.delete_row',
        // 以后如果拆分 company.delete / user.delete，可以加在这里
    ];

    private function ensurePermissionsExist(): void
    {
        // 确保基础角色存在（只保留 super_admin 和 staff）
        Role::findOrCreate('super_admin');
        Role::findOrCreate('staff');

        foreach (array_keys(self::PERMISSIONS) as $name) {
            Permission::findOrCreate($name);
        }
    }

    public function index(Request $request): View
    {
        /** @var User|null $current */
        $current = Auth::user();
        if (! $current || (! $current->hasRole('super_admin') && ! $current->hasPermissionTo('settings.users.manage'))) {
            abort(403);
        }
        $this->ensurePermissionsExist();

        $roles = Role::query()
            ->orderBy('name')
            ->get();

        // role_id => [permission_name => true]
        $rolePermissions = [];
        foreach ($roles as $role) {
            $names = $role->permissions->pluck('name')->all();
            $rolePermissions[$role->id] = array_fill_keys($names, true);
        }

        // 分组权限：group => [name => config]
        $grouped = [];
        foreach (self::PERMISSIONS as $name => $cfg) {
            $grouped[$cfg['group']][$name] = $cfg;
        }

        return view('setting.roles', [
            'roles' => $roles,
            'groupedPermissions' => $grouped,
            'rolePermissions' => $rolePermissions,
        ]);
    }

    /**
     * 保存角色与权限（以及新增角色名字）。
     */
    public function save(Request $request): RedirectResponse
    {
        /** @var User|null $current */
        $current = Auth::user();
        if (! $current || (! $current->hasRole('super_admin') && ! $current->hasPermissionTo('settings.users.manage'))) {
            abort(403);
        }
        $this->ensurePermissionsExist();

        // 如果点击的是 Delete 按钮，优先处理删除并直接返回
        $deleteRoleId = (int) $request->input('delete_role_id', 0);
        if ($deleteRoleId > 0) {
            $role = Role::find($deleteRoleId);
            if ($role && ! in_array($role->name, ['super_admin', 'staff'], true)) {
                $details = [
                    'description' => 'Role deleted via roles.save',
                    'role_id' => $role->id,
                    'name' => $role->name,
                ];
                $role->delete();
                AuditLogger::log('role.deleted', $details);
                return redirect()->route('setting.roles')->with('success', __('Role deleted.'));
            }
            return redirect()->route('setting.roles')->with('error', __('This role cannot be deleted.'));
        }

        // 1) 新增角色（可选）
        $newName = trim((string) $request->input('new_role_name', ''));
        if ($newName !== '') {
            $role = Role::firstOrCreate(['name' => $newName]);
            AuditLogger::log('role.created', [
                'description' => 'Role created',
                'role_id' => $role->id,
                'name' => $role->name,
            ]);
        }

        // 2) 更新每个角色的权限
        $rolesInput = $request->input('roles', []);
        if (is_array($rolesInput)) {
            foreach ($rolesInput as $roleId => $data) {
                $role = Role::find((int) $roleId);
                if (! $role) {
                    continue;
                }
                $perms = $data['permissions'] ?? [];
                if (! is_array($perms)) {
                    $perms = [];
                }
                // 只保留我们定义过的权限 key
                $perms = array_values(array_intersect($perms, array_keys(self::PERMISSIONS)));
                $role->syncPermissions($perms);

                AuditLogger::log('role.permissions.updated', [
                    'description' => 'Role permissions updated',
                    'role_id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $perms,
                ]);
            }
        }

        // super_admin 一律全部权限（其他角色包括 staff 可自由配置）
        $allPerms = array_keys(self::PERMISSIONS);
        $super = Role::where('name', 'super_admin')->first();
        if ($super) {
            $super->syncPermissions($allPerms);
        }

        return redirect()
            ->route('setting.roles')
            ->with('success', __('Roles updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        /** @var User|null $current */
        $current = Auth::user();
        if (! $current || (! $current->hasRole('super_admin') && ! $current->hasPermissionTo('settings.users.manage'))) {
            abort(403);
        }
        $role = Role::find($id);
        if (! $role) {
            return redirect()->route('setting.roles')->with('error', __('Role not found.'));
        }

        if (in_array($role->name, ['super_admin', 'staff'], true)) {
            return redirect()->route('setting.roles')->with('error', __('This role cannot be deleted.'));
        }

        $details = [
            'description' => 'Role deleted',
            'role_id' => $role->id,
            'name' => $role->name,
        ];

        $role->delete();
        AuditLogger::log('role.deleted', $details);

        return redirect()->route('setting.roles')->with('success', __('Role deleted.'));
    }
}

