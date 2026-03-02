<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Cashflow Entry') }} #{{ $entry->id }}</title>
    <style>
        body { font-family: system-ui, sans-serif; font-size: 14px; color: #111; max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.25rem; margin-bottom: 1.5rem; border-bottom: 1px solid #d1d5db; padding-bottom: 0.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.5rem 0.75rem; border-bottom: 1px solid #e5e7eb; }
        th { font-weight: 600; color: #374151; width: 10rem; }
        .amount { text-align: right; font-variant-numeric: tabular-nums; }
        .footer { margin-top: 2rem; font-size: 0.75rem; color: #6b7280; }
        @media print { body { margin: 0; padding: 1rem; } .no-print { display: none; } }
    </style>
</head>
<body>
    <h1>{{ __('Cashflow Entry') }} #{{ $entry->id }}</h1>
    <table>
        <tr>
            <th>{{ __('Date') }}</th>
            <td>{{ $entry->entry_date->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <th>{{ __('Company') }}</th>
            <td>{{ $entry->company->name ?? '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('Category') }}</th>
            <td>{{ $entry->category }}</td>
        </tr>
        <tr>
            <th>{{ __('Currency') }}</th>
            <td>{{ $entry->currency }}</td>
        </tr>
        <tr>
            <th>{{ __('Amount') }}</th>
            <td class="amount">{{ number_format($entry->amount_minor / 100, 2) }}</td>
        </tr>
        <tr>
            <th>{{ __('FX rate to base') }}</th>
            <td>{{ $entry->fx_rate_to_base }}</td>
        </tr>
        <tr>
            <th>{{ __('Base amount') }}</th>
            <td class="amount">{{ number_format($entry->base_amount_minor / 100, 2) }}</td>
        </tr>
        <tr>
            <th>{{ __('Description') }}</th>
            <td>{{ $entry->description ?: '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('Created by') }}</th>
            <td>{{ $entry->user->name ?? $entry->user->username ?? '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('Created at') }}</th>
            <td>{{ $entry->created_at->format('Y-m-d H:i') }}</td>
        </tr>
    </table>
    <p class="footer">{{ __('Printed at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
    <p class="footer no-print"><a href="javascript:window.print()">{{ __('Print') }}</a></p>
</body>
</html>
