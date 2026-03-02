<x-app-layout>
    <x-slot name="header">
        {{ __('User') }}
    </x-slot>
    <div class="user-list-page max-w-7xl mx-auto w-full pb-12">
        @if(session('success'))
            <p class="text-sm text-green-700 mb-6">{{ session('success') }}</p>
        @endif
        @if(session('error'))
            <p class="text-sm text-red-700 mb-6">{{ session('error') }}</p>
        @endif

        <div class="user-list-header flex flex-wrap justify-between items-end gap-6">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Security') }}</div>
                <h1 class="text-lg font-semibold text-gray-900 mt-1">{{ __('Internal Users') }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('Manage admin accounts and their roles.') }}</p>
            </div>
            <div class="flex items-center gap-4">
                <form method="get" action="{{ route('setting.users') }}" class="flex gap-3 items-center">
                    <input type="text"
                           name="q"
                           class="rounded border border-gray-300 px-2.5 py-2 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-400 w-56"
                           placeholder="{{ __('Search username / name / email') }}"
                           value="{{ request('q', '') }}">
                    <button type="submit" class="text-sm text-gray-700 hover:text-black">{{ __('Search') }}</button>
                    @if(request('q'))
                        <a href="{{ route('setting.users') }}" class="text-sm text-gray-500 hover:text-black">{{ __('Reset') }}</a>
                    @endif
                </form>
                <span class="user-new-cell">
                    <a href="{{ route('setting.users.create') }}" class="user-new-link">
                        {{ __('+ New User') }}
                    </a>
                </span>
            </div>
        </div>

        <div class="overflow-x-auto mt-8">
            <table class="user-list-table w-full min-w-[720px]">
                <thead>
                    <tr>
                        <th class="user-th">{{ __('Username') }}</th>
                        <th class="user-th">{{ __('Full name') }}</th>
                        <th class="user-th">{{ __('Email') }}</th>
                        <th class="user-th">{{ __('Roles') }}</th>
                        <th class="user-th">{{ __('Companies') }}</th>
                        <th class="user-th">{{ __('Status') }}</th>
                        <th class="user-th">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr class="user-row">
                            <td class="user-td">{{ $u->username }}</td>
                            <td class="user-td">{{ $u->name }}</td>
                            <td class="user-td text-gray-600">{{ $u->email ?? '—' }}</td>
                            <td class="user-td text-gray-600 text-xs">
                                @if($u->roles->isNotEmpty())
                                    {{ $u->roles->pluck('name')->join(', ') }}
                                @else
                                    <span class="text-gray-400">{{ __('(no roles)') }}</span>
                                @endif
                            </td>
                            <td class="user-td text-gray-600 text-xs">
                                @if($u->all_companies)
                                    <span class="text-gray-500">{{ __('All') }}</span>
                                @elseif($u->companies->isNotEmpty())
                                    {{ $u->companies->pluck('name')->join(', ') }}
                                @else
                                    <span class="text-gray-400">{{ __('(none)') }}</span>
                                @endif
                            </td>
                            <td class="user-td">
                                @if($u->is_active)
                                    <span class="text-xs text-green-700">{{ __('Active') }}</span>
                                @else
                                    <span class="text-xs text-red-600">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td class="user-td user-td-actions">
                                <a href="{{ route('setting.users.edit', $u->id) }}" class="user-edit-link">{{ __('Edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr class="user-row user-row--empty">
                            <td colspan="7" class="user-td-empty">
                                {{ __('No users found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
