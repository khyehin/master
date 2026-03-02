<x-app-layout>
    <x-slot name="header">
        {{ __('Roles & Permissions') }}
    </x-slot>

    <div class="max-w-7xl mx-auto w-full pb-10 space-y-6">
        @if(session('success'))
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        @endif
        @if(session('error'))
            <p class="text-sm text-red-700">{{ session('error') }}</p>
        @endif

        <div class="bg-white border border-gray-300 rounded-lg shadow-sm p-6">
            <div class="mb-4">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Security') }}</div>
                <h1 class="text-lg font-semibold text-gray-900 mt-1">{{ __('Roles & Permissions') }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ __('Set what each role is allowed to do (view, add, edit, delete, export).') }}
                </p>
            </div>

            <form method="post" action="{{ route('setting.roles.save') }}" class="space-y-6">
                @csrf

                {{-- Add new role --}}
                {{-- 上方工具列（与下方表格之间大约 1cm 空白） --}}
                <div class="flex flex-wrap items-center gap-3 mb-12">
                    <div class="flex flex-col gap-1">
                        {{-- label 仅给屏幕阅读器用，不占高度 --}}
                        <label class="sr-only text-xs font-medium text-gray-600">{{ __('New role name') }}</label>
                        <input type="text"
                               name="new_role_name"
                               class="rounded border border-gray-300 px-2.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-400 w-56 h-[40px]"
                               placeholder="{{ __('e.g. Cashier, Viewer') }}">
                    </div>
                    <button type="submit"
                            class="user-btn user-btn--secondary"
                            style="min-width:auto; height:40px;">
                        {{ __('+ Add role') }}
                    </button>
                    <p class="text-xs text-gray-500 whitespace-nowrap">
                        {{ __('super_admin / staff roles are reserved and cannot be deleted.') }}
                    </p>
                </div>

                {{-- Roles x Permissions matrix --}}
                <div class="overflow-x-auto mt-10">
                    <table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3 border-b border-gray-200 text-left font-semibold text-gray-700 w-40">
                                    {{ __('Role') }}
                                </th>
                                @foreach($groupedPermissions as $group => $perms)
                                    <th class="px-3 py-3 border-b border-gray-200 text-left font-semibold text-gray-700">
                                        {{ __($group) }}
                                    </th>
                                @endforeach
                                <th class="px-3 py-3 border-b border-gray-200 text-left font-semibold text-gray-700 w-20">
                                    {{ __('Delete') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $role)
                                <tr class="border-b border-gray-200 align-top">
                                    <td class="px-3 py-4 border-r border-gray-100 font-medium text-gray-900">
                                        {{ $role->name }}
                                    </td>
                                    @foreach($groupedPermissions as $group => $perms)
                                        <td class="px-3 py-4 border-r border-gray-100">
                                            <div class="flex flex-col gap-2">
                                                @foreach($perms as $permName => $cfg)
                                                    @php
                                                        $checked = isset($rolePermissions[$role->id][$permName]);
                                                        // super_admin: 全锁定；其他 role（包括 staff）可以自由勾选
                                                        $isDeletePerm = in_array($permName, \App\Http\Controllers\RoleController::DELETE_PERMISSIONS ?? []);
                                                        $lockAll = $role->name === 'super_admin';
                                                        $disabled = $lockAll;
                                                    @endphp
                                                    <label class="inline-flex items-center gap-1 text-xs text-gray-700 {{ $disabled ? 'opacity-60' : '' }}">
                                                        <input type="checkbox"
                                                               name="roles[{{ $role->id }}][permissions][]"
                                                               value="{{ $permName }}"
                                                               @checked($checked)
                                                               class="rounded border-gray-300 text-gray-700 focus:ring-gray-400"
                                                               @disabled($disabled)>
                                                        <span>{{ __($cfg['label']) }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-4">
                                        @php
                                            $locked = in_array($role->name, ['super_admin', 'staff']);
                                        @endphp
                                        @if($locked)
                                            <span class="text-xs text-gray-400">{{ __('Locked') }}</span>
                                        @else
                                            <button type="submit"
                                                    name="delete_role_id"
                                                    value="{{ $role->id }}"
                                                    onclick="return confirm('{{ __('Delete this role?') }}');"
                                                    class="text-xs text-red-600 hover:text-red-800 underline bg-transparent border-none p-0">
                                                {{ __('Delete') }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($groupedPermissions) + 2 }}" class="px-4 py-8 text-center text-gray-500">
                                        {{ __('No roles defined.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pt-4 border-t border-gray-200 flex justify-end">
                    <button type="submit"
                            class="user-btn user-btn--primary"
                            style="min-width:auto;">
                        {{ __('Save changes') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
