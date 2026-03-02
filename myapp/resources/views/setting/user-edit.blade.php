<x-app-layout>
    <x-slot name="header">
        {{ $user ? __('Edit User') : __('New User') }}
    </x-slot>
    <div class="user-edit-page max-w-xl mx-auto w-full pt-6 pb-12">
        @if(session('error'))
            <p class="user-edit-alert user-edit-alert--error">{{ session('error') }}</p>
        @endif

        <header class="user-edit-header">
            <div class="user-edit-header-eyebrow">{{ __('Security') }}</div>
            <h1 class="user-edit-title">
                {{ $user ? __('Edit User') : __('New User') }}
            </h1>
            <p class="user-edit-subtitle">
                {{ $user ? __('Update user details and roles.') : __('Create a new admin user.') }}
            </p>
        </header>

        <div class="user-edit-card">
        <form method="post" action="{{ $user ? route('setting.users.update', $user->id) : route('setting.users.store') }}" class="user-form">
            @csrf
            @if($user)
                @method('PATCH')
            @endif

            <div class="user-form-field">
                <label for="username" class="user-form-label">{{ __('Username') }} <span class="text-red-500">*</span></label>
                <input type="text" id="username" name="username" required class="user-form-input"
                       value="{{ old('username', $user?->username) }}">
                @error('username')
                    <p class="user-form-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="user-form-field">
                <label for="name" class="user-form-label">{{ __('Full name') }} <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" required class="user-form-input"
                       value="{{ old('name', $user?->name) }}">
                @error('name')
                    <p class="user-form-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="user-form-field">
                <label for="email" class="user-form-label">{{ __('Email') }}</label>
                <input type="email" id="email" name="email" class="user-form-input"
                       value="{{ old('email', $user?->email) }}">
                @error('email')
                    <p class="user-form-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="user-form-field">
                <label for="password" class="user-form-label">
                    {{ __('Password') }}
                    @if($user)
                        <span class="text-gray-400 font-normal">({{ __('leave blank to keep current') }})</span>
                    @else
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input type="password" id="password" name="password" {{ $user ? '' : 'required' }}
                       class="user-form-input" autocomplete="new-password">
                @error('password')
                    <p class="user-form-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="user-form-field">
                <label for="password_confirmation" class="user-form-label">
                    {{ __('Confirm Password') }}{{ $user ? '' : ' *' }}
                </label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       {{ $user ? '' : 'required' }} class="user-form-input" autocomplete="new-password">
            </div>
            <div class="user-form-field user-form-field--checkbox">
                <label class="user-form-checkbox-wrap">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $user?->is_active ?? true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-gray-800 focus:ring-gray-400">
                    <span class="text-sm text-gray-700">{{ __('Active') }}</span>
                </label>
                @error('is_active')
                    <p class="user-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="user-form-block">
                <h3 class="user-form-block-title">{{ __('Roles') }}</h3>
                <div class="user-form-checkbox-list">
                    @foreach($roles as $role)
                        <label class="user-form-checkbox-wrap">
                            <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                   {{ in_array($role->name, old('roles', $user ? $user->getRoleNames()->toArray() : []), true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-gray-800 focus:ring-gray-400">
                            <span class="text-sm text-gray-700">{{ $role->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('roles')
                    <p class="user-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="user-form-block">
                <h3 class="user-form-block-title">{{ __('Company access') }}</h3>
                <label class="user-form-checkbox-wrap user-form-checkbox-wrap--mb">
                    <input type="hidden" name="all_companies" value="0">
                    <input type="checkbox" name="all_companies" value="1"
                           {{ old('all_companies', $user?->all_companies ?? false) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-gray-800 focus:ring-gray-400">
                    <span class="text-sm text-gray-700">{{ __('Access all companies') }}</span>
                </label>
                <p class="user-form-block-desc user-form-block-desc--sm">{{ __('Or select specific companies:') }}</p>
                <div class="user-form-companies">
                    @forelse($companies as $company)
                        <label class="user-form-checkbox-wrap">
                            <input type="checkbox" name="companies[]" value="{{ $company->id }}"
                                   {{ in_array((string)$company->id, old('companies', $user ? $user->companies->pluck('id')->map(fn ($id) => (string)$id)->toArray() : []), true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-gray-800 focus:ring-gray-400">
                            <span class="text-sm text-gray-700">{{ $company->name }}</span>
                            <span class="text-xs text-gray-400">({{ $company->code }})</span>
                        </label>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('No companies yet.') }}</p>
                    @endforelse
                </div>
                @error('companies')
                    <p class="user-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="user-form-actions-wrap">
                <button type="submit" class="user-btn user-btn--primary">
                    {{ $user ? __('Update') : __('Create') }}
                </button>
                <a href="{{ route('setting.users') }}" class="user-btn user-btn--secondary">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
        </div>
    </div>
</x-app-layout>
