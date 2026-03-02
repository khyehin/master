<x-app-layout>
    <x-slot name="header">
        {{ $company ? __('Edit Company') : __('Add new company') }}
    </x-slot>
    <div class="user-edit-page max-w-xl mx-auto w-full pt-6 pb-12">
        @if(session('error'))
            <p class="user-edit-alert user-edit-alert--error">{{ session('error') }}</p>
        @endif

        <header class="user-edit-header">
            <h1 class="user-edit-title">
                {{ $company ? __('Edit Company') : __('Add new company') }}
            </h1>
        </header>

        <div class="user-edit-card">
            <form method="post" action="{{ $company ? route('companies.update', $company->id) : route('companies.store') }}" class="user-form">
                @csrf
                @if($company)
                    @method('PATCH')
                @endif

                <div class="user-form-field">
                    <label for="name" class="user-form-label">{{ __('Name') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" required maxlength="255" class="user-form-input"
                           value="{{ old('name', $company?->name) }}" placeholder="e.g. ABC Sdn Bhd">
                    @error('name')
                        <p class="user-form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="user-form-field">
                    <label for="code" class="user-form-label">{{ __('Code') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="code" name="code" required maxlength="64" class="user-form-input"
                           value="{{ old('code', $company?->code) }}" placeholder="e.g. ABC">
                    @error('code')
                        <p class="user-form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="user-form-field">
                    <label for="base_currency" class="user-form-label">{{ __('Base currency') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="base_currency" name="base_currency" required maxlength="3" class="user-form-input" style="width: 6rem; text-transform: uppercase;"
                           value="{{ old('base_currency', strtoupper($company?->base_currency ?? 'MYR')) }}" placeholder="e.g. MYR, USD" pattern="[A-Za-z]{3}" title="{{ __('3-letter currency code (e.g. MYR, USD)') }}">
                    @error('base_currency')
                        <p class="user-form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="user-form-actions-wrap">
                    <button type="submit" class="user-btn user-btn--primary">
                        {{ $company ? __('Update') : __('Create') }}
                    </button>
                    <a href="{{ route('companies.index') }}" class="user-btn user-btn--secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
