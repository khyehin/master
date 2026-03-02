<x-app-layout>
    <x-slot name="header">
        {{ $entry ? __('Edit Entry') : __('New Entry') }}
    </x-slot>
    <div class="user-edit-page max-w-xl mx-auto w-full pt-6 pb-12">
        @if(session('error'))
            <p class="user-edit-alert user-edit-alert--error">{{ session('error') }}</p>
        @endif

        <header class="user-edit-header">
            <h1 class="user-edit-title">
                {{ $entry ? __('Edit Entry') : __('New Entry') }}
            </h1>
        </header>

        <div class="user-edit-card">
            <form method="post" action="{{ $entry ? route('cashflow.update', $entry->id) : route('cashflow.store') }}" class="user-form">
                @csrf
                @if($entry)
                    @method('PATCH')
                @endif

                @if(!$entry)
                    <div class="user-form-field">
                        <label for="company_id" class="user-form-label">{{ __('Company') }} <span class="text-red-500">*</span></label>
                        <select id="company_id" name="company_id" required class="user-form-input">
                            <option value="" disabled {{ old('company_id', $presetCompanyId) ? '' : 'selected' }}>{{ __('Select company') }}</option>
                            @foreach($companies as $c)
                                <option value="{{ $c->id }}" data-base-currency="{{ strtoupper($c->base_currency ?? 'USD') }}" {{ old('company_id', $presetCompanyId) == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->base_currency ?? 'USD' }})</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1" id="display-base-currency-wrap" style="display: none;">{{ __('Base currency') }}: <strong id="display-base-currency"></strong></p>
                        @error('company_id')
                            <p class="user-form-error">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <div class="user-form-field">
                        <p class="text-sm text-gray-600">{{ __('Company') }}: <strong>{{ $entry->company->name }}</strong>. {{ __('Base currency') }}: <strong>{{ $baseCurrency ?? ($entry->company->base_currency ?? 'USD') }}</strong>.</p>
                    </div>
                @endif

                <div class="user-form-field">
                    <label for="entry_date" class="user-form-label">{{ __('Date') }} <span class="text-red-500">*</span></label>
                    <input type="date" id="entry_date" name="entry_date" required class="user-form-input"
                           value="{{ old('entry_date', $entry?->entry_date?->format('Y-m-d')) }}">
                    @error('entry_date')
                        <p class="user-form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="user-form-field">
                    <label for="category" class="user-form-label">{{ __('Category') }} <span class="text-red-500">*</span></label>
                    @if(($categories ?? collect())->isNotEmpty())
                        @php $selectedCategory = old('category', $entry?->category); $categoryNames = ($categories ?? collect())->pluck('name'); @endphp
                        <select id="category" name="category" required class="user-form-input">
                            <option value="" disabled>{{ __('Select category') }}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->name }}" {{ $selectedCategory === $cat->name ? 'selected' : '' }}>{{ $cat->name }} ({{ $cat->type === 'inflow' ? __('Inflow') : __('Outflow') }})</option>
                            @endforeach
                            @if($selectedCategory && !$categoryNames->contains($selectedCategory))
                                <option value="{{ $selectedCategory }}" selected>{{ $selectedCategory }}</option>
                            @endif
                        </select>
                    @else
                        <input type="text" id="category" name="category" required maxlength="255" class="user-form-input"
                               value="{{ old('category', $entry?->category) }}" placeholder="e.g. Deposit, Salary">
                    @endif
                    @error('category')
                        <p class="user-form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="user-form-field">
                    <label for="currency" class="user-form-label">{{ __('Currency') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="currency" name="currency" required maxlength="3" size="3" class="user-form-input" style="width: 5rem;"
                           value="{{ old('currency', $entry?->currency ?? ($entry ? $entry->company->base_currency : 'USD')) }}" placeholder="USD">
                    @error('currency')
                        <p class="user-form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="user-form-field">
                    <label for="amount" class="user-form-label">{{ __('Amount') }} <span class="text-red-500">*</span></label>
                    <p class="text-xs text-gray-500 mb-1">{{ __('Positive = Deposit (inflow), Negative = Withdrawal (outflow).') }}</p>
                    <input type="number" id="amount" name="amount" required step="0.01" min="-999999999.99" max="999999999.99" class="user-form-input"
                           value="{{ old('amount', $entry ? number_format($entry->amount_minor / 100, 2, '.', '') : '') }}" placeholder="0.00">
                    @error('amount')
                        <p class="user-form-error">{{ $message }}</p>
                    @enderror
                </div>

                @if($entry ?? null)
                    {{-- Edit: show rate only when base currency is not MYR --}}
                    @if($needFxRate ?? true)
                        <div class="user-form-field">
                            <label for="fx_rate_to_base" class="user-form-label">{{ __('FX rate to base') }} ({{ __('Base') }}: {{ $baseCurrency ?? 'USD' }}) <span class="text-red-500">*</span></label>
                            <input type="number" id="fx_rate_to_base" name="fx_rate_to_base" required step="0.000001" min="0" class="user-form-input"
                                   value="{{ old('fx_rate_to_base', $entry?->fx_rate_to_base ?? '1') }}" placeholder="1">
                            @error('fx_rate_to_base')
                                <p class="user-form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <div class="user-form-field">
                            <p class="text-sm text-gray-600">{{ __('Base currency is MYR; no rate needed.') }}</p>
                            <input type="hidden" name="fx_rate_to_base" value="1">
                        </div>
                    @endif
                @else
                    {{-- Create: show rate only when selected company base currency is not MYR (controlled by JS) --}}
                    <div class="user-form-field" id="fx-rate-wrap" style="display: none;">
                        <label for="fx_rate_to_base" class="user-form-label">{{ __('FX rate to base') }} <span class="text-red-500">*</span></label>
                        <input type="number" id="fx_rate_to_base" name="fx_rate_to_base" step="0.000001" min="0" class="user-form-input"
                               value="{{ old('fx_rate_to_base', '1') }}" placeholder="1">
                        @error('fx_rate_to_base')
                            <p class="user-form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="user-form-field" id="fx-rate-myr-wrap" style="display: none;">
                        <p class="text-sm text-gray-600">{{ __('Base currency is MYR; no rate needed.') }}</p>
                    </div>
                @endif

                <div class="user-form-field">
                    <label for="description" class="user-form-label">{{ __('Description') }}</label>
                    <textarea id="description" name="description" rows="3" maxlength="1000" class="user-form-input"
                              placeholder="{{ __('Optional notes') }}">{{ old('description', $entry?->description) }}</textarea>
                    @error('description')
                        <p class="user-form-error">{{ $message }}</p>
                    @enderror
                </div>

                @if($entry && ($extraColumns ?? collect())->isNotEmpty())
                    <div class="user-form-field">
                        <span class="user-form-label">{{ __('Extra columns') }}</span>
                        <div class="flex flex-wrap gap-4 mt-2">
                            @foreach($extraColumns as $col)
                                @php $val = $entry->getExtraValueMinor($col->id) / 100; @endphp
                                <div>
                                    <label for="extra-{{ $col->id }}" class="text-xs text-gray-600">{{ $col->name }}</label>
                                    <input type="number" id="extra-{{ $col->id }}" name="extra[{{ $col->id }}]" step="0.01" class="user-form-input w-28"
                                           value="{{ old("extra.{$col->id}", number_format($val, 2, '.', '')) }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="user-form-actions-wrap">
                    <button type="submit" class="user-btn user-btn--primary">
                        {{ $entry ? __('Update') : __('Create') }}
                    </button>
                    <a href="{{ route('cashflow.index') }}" class="user-btn user-btn--secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    @if(!$entry)
    <script>
    (function() {
        var sel = document.getElementById('company_id');
        var displayWrap = document.getElementById('display-base-currency-wrap');
        var displayBase = document.getElementById('display-base-currency');
        var fxRateWrap = document.getElementById('fx-rate-wrap');
        var fxRateMyrWrap = document.getElementById('fx-rate-myr-wrap');
        var fxRateInput = document.getElementById('fx_rate_to_base');
        if (!sel || !fxRateWrap) return;
        function updateForCompany() {
            var opt = sel.options[sel.selectedIndex];
            var base = opt && opt.getAttribute ? (opt.getAttribute('data-base-currency') || '').toUpperCase() : '';
            if (displayWrap) displayWrap.style.display = base ? '' : 'none';
            if (displayBase) displayBase.textContent = base || '—';
            if (!base) {
                fxRateWrap.style.display = 'none';
                if (fxRateMyrWrap) fxRateMyrWrap.style.display = 'none';
                if (fxRateInput) { fxRateInput.value = '1'; fxRateInput.removeAttribute('required'); }
            } else if (base === 'MYR') {
                fxRateWrap.style.display = 'none';
                if (fxRateMyrWrap) fxRateMyrWrap.style.display = '';
                if (fxRateInput) { fxRateInput.value = '1'; fxRateInput.removeAttribute('required'); }
            } else {
                fxRateWrap.style.display = '';
                if (fxRateMyrWrap) fxRateMyrWrap.style.display = 'none';
                if (fxRateInput) fxRateInput.setAttribute('required', 'required');
            }
        }
        sel.addEventListener('change', updateForCompany);
        updateForCompany();
    })();
    </script>
    @endif
</x-app-layout>
