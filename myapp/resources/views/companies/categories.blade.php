<x-app-layout>
    <x-slot name="header">
        {{ __('Design accounts') }}
    </x-slot>
    <div class="max-w-4xl mx-auto w-full pb-12">
        @if(session('success'))
            <p class="text-sm text-green-700 mb-4">{{ session('success') }}</p>
        @endif
        @if(session('error'))
            <p class="text-sm text-red-700 mb-4">{{ session('error') }}</p>
        @endif

        <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
            <div>
                <a href="{{ route('companies.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-block">← {{ __('Companies') }}</a>
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Accounts') }}</div>
                <h1 class="text-lg font-semibold text-gray-900 mt-1">{{ __('Cashflow categories (accounts)') }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('Same for all companies. Add categories like Deposit, Withdrawal, Salary for keying data.') }}</p>
            </div>
        </div>

        @if($canManage)
            <div class="user-edit-card mb-8">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ __('Add category') }}</h2>
                <form method="post" action="{{ route('companies.categories.store') }}" class="user-form">
                    @csrf
                    <div class="flex flex-wrap gap-4 items-end">
                        <div class="user-form-field flex-1 min-w-[10rem]">
                            <label for="name" class="user-form-label">{{ __('Name') }} <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" required maxlength="255" class="user-form-input"
                                   value="{{ old('name') }}" placeholder="e.g. Deposit, Salary">
                        </div>
                        <div class="user-form-field w-32">
                            <label for="code" class="user-form-label">{{ __('Code') }}</label>
                            <input type="text" id="code" name="code" maxlength="64" class="user-form-input"
                                   value="{{ old('code') }}" placeholder="optional">
                        </div>
                        <div class="user-form-field w-36">
                            <label for="type" class="user-form-label">{{ __('Type') }} <span class="text-red-500">*</span></label>
                            <select id="type" name="type" required class="user-form-input">
                                <option value="inflow" {{ old('type') === 'inflow' ? 'selected' : '' }}>{{ __('Inflow') }}</option>
                                <option value="outflow" {{ old('type', 'outflow') === 'outflow' ? 'selected' : '' }}>{{ __('Outflow') }}</option>
                            </select>
                        </div>
                        <div class="user-form-field w-24">
                            <label for="sort_order" class="user-form-label">{{ __('Order') }}</label>
                            <input type="number" id="sort_order" name="sort_order" min="0" class="user-form-input" value="{{ old('sort_order', 0) }}">
                        </div>
                        <button type="submit" class="user-btn user-btn--primary">{{ __('Add') }}</button>
                    </div>
                    @error('name')
                        <p class="user-form-error mt-1">{{ $message }}</p>
                    @enderror
                    @error('code')
                        <p class="user-form-error mt-1">{{ $message }}</p>
                    @enderror
                </form>
            </div>
        @endif

        <div class="overflow-x-auto rounded-xl">
            <table class="user-list-table w-full">
                <thead>
                    <tr>
                        <th class="user-th">{{ __('Order') }}</th>
                        <th class="user-th">{{ __('Name') }}</th>
                        <th class="user-th">{{ __('Code') }}</th>
                        <th class="user-th">{{ __('Type') }}</th>
                        @if($canManage)
                            <th class="user-th" style="width: 10rem;">{{ __('Actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $cat)
                        <tr class="{{ $editingCategory && $editingCategory->id === $cat->id ? 'bg-amber-50' : '' }}">
                            <td class="user-td">{{ $cat->sort_order }}</td>
                            @if($canManage && $editingCategory && $editingCategory->id === $cat->id)
                                <td colspan="3" class="user-td text-left">
                                    <form method="post" action="{{ route('companies.categories.update', $cat->id) }}" class="flex flex-wrap gap-3 items-center">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="name" required maxlength="255" class="user-form-input w-48" value="{{ old('name', $cat->name) }}">
                                        <input type="text" name="code" maxlength="64" class="user-form-input w-28" value="{{ old('code', $cat->code) }}" placeholder="Code">
                                        <select name="type" required class="user-form-input w-28">
                                            <option value="inflow" {{ $cat->type === 'inflow' ? 'selected' : '' }}>{{ __('Inflow') }}</option>
                                            <option value="outflow" {{ $cat->type === 'outflow' ? 'selected' : '' }}>{{ __('Outflow') }}</option>
                                        </select>
                                        <input type="number" name="sort_order" min="0" class="user-form-input w-20" value="{{ old('sort_order', $cat->sort_order) }}">
                                        <button type="submit" class="user-btn user-btn--primary text-sm">{{ __('Save') }}</button>
                                        <a href="{{ route('companies.categories') }}" class="text-sm text-gray-500">{{ __('Cancel') }}</a>
                                    </form>
                                </td>
                            @else
                                <td class="user-td">{{ $cat->name }}</td>
                                <td class="user-td">{{ $cat->code ?? '—' }}</td>
                                <td class="user-td">{{ $cat->type === 'inflow' ? __('Inflow') : __('Outflow') }}</td>
                            @endif
                            @if($canManage)
                                <td class="user-td">
                                    @if(!$editingCategory || $editingCategory->id !== $cat->id)
                                        <a href="{{ route('companies.categories') }}?edit={{ $cat->id }}" class="user-edit-link">{{ __('Edit') }}</a>
                                        <form method="post" action="{{ route('companies.categories.destroy', $cat->id) }}" class="inline ml-2" onsubmit="return confirm('{{ __('Delete this category?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                        </form>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 5 : 4 }}" class="user-td" style="padding: 2rem; color: #6b7280;">
                                {{ __('No categories yet.') }} @if($canManage) {{ __('Add one above.') }} @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
