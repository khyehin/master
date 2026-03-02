<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    private function canManage(): bool
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function index(): View
    {
        $companies = Company::query()->orderBy('name')->get();
        $canManage = $this->canManage();
        return view('companies.index', [
            'companies' => $companies,
            'canManage' => $canManage,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        if (! $this->canManage()) {
            return redirect()->route('companies.index')->with('error', __('You do not have permission.'));
        }
        return view('companies.edit', ['company' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->canManage()) {
            return redirect()->route('companies.index')->with('error', __('You do not have permission.'));
        }
        $valid = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', 'unique:companies,code'],
            'base_currency' => ['required', 'string', 'size:3'],
        ]);
        $valid['base_currency'] = strtoupper($valid['base_currency']);
        $company = Company::create($valid);

        AuditLogger::log('company.created', [
            'description' => 'Company created',
            'company_id' => $company->id,
            'name' => $company->name,
            'code' => $company->code,
            'base_currency' => $company->base_currency,
        ], $company->id);

        return redirect()->route('companies.index')->with('success', __('Company created.'));
    }

    public function edit(int $id): View|RedirectResponse
    {
        $company = Company::find($id);
        if (! $company) {
            return redirect()->route('companies.index')->with('error', __('Company not found.'));
        }
        if (! $this->canManage()) {
            return redirect()->route('companies.index')->with('error', __('You do not have permission.'));
        }
        return view('companies.edit', ['company' => $company]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $company = Company::find($id);
        if (! $company) {
            return redirect()->route('companies.index')->with('error', __('Company not found.'));
        }
        if (! $this->canManage()) {
            return redirect()->route('companies.index')->with('error', __('You do not have permission.'));
        }
        $valid = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', Rule::unique('companies', 'code')->ignore($company->id)],
            'base_currency' => ['required', 'string', 'size:3'],
        ]);
        $valid['base_currency'] = strtoupper($valid['base_currency']);
        $company->update($valid);

        AuditLogger::log('company.updated', [
            'description' => 'Company updated',
            'company_id' => $company->id,
            'name' => $company->name,
            'code' => $company->code,
            'base_currency' => $company->base_currency,
        ], $company->id);

        return redirect()->route('companies.index')->with('success', __('Company updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $company = Company::find($id);
        if (! $company) {
            return redirect()->route('companies.index')->with('error', __('Company not found.'));
        }
        if (! $this->canManage()) {
            return redirect()->route('companies.index')->with('error', __('You do not have permission.'));
        }
        $details = [
            'description' => 'Company deleted',
            'company_id' => $company->id,
            'name' => $company->name,
            'code' => $company->code,
            'base_currency' => $company->base_currency,
        ];
        $company->delete();

        AuditLogger::log('company.deleted', $details, $id);

        return redirect()->route('companies.index')->with('success', __('Company deleted.'));
    }
}
