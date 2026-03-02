<?php

namespace App\Http\Controllers;

use App\Models\CashflowCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CashflowCategoryController extends Controller
{
    private function canManage(): bool
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function index(Request $request): View
    {
        $categories = CashflowCategory::ordered()->get();
        $canManage = $this->canManage();
        $editId = $request->integer('edit');
        $editingCategory = $editId > 0 ? CashflowCategory::find($editId) : null;
        return view('companies.categories', [
            'categories' => $categories,
            'canManage' => $canManage,
            'editingCategory' => $editingCategory,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->canManage()) {
            return redirect()->route('companies.categories')->with('error', __('You do not have permission.'));
        }
        $valid = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:64', 'unique:cashflow_categories,code'],
            'type' => ['required', 'string', Rule::in(['inflow', 'outflow'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $valid['sort_order'] = (int) ($valid['sort_order'] ?? 0);
        CashflowCategory::create($valid);
        return redirect()->route('companies.categories')->with('success', __('Category added.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $category = CashflowCategory::find($id);
        if (! $category) {
            return redirect()->route('companies.categories')->with('error', __('Category not found.'));
        }
        if (! $this->canManage()) {
            return redirect()->route('companies.categories')->with('error', __('You do not have permission.'));
        }
        $valid = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:64', Rule::unique('cashflow_categories', 'code')->ignore($category->id)],
            'type' => ['required', 'string', Rule::in(['inflow', 'outflow'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $valid['sort_order'] = (int) ($valid['sort_order'] ?? $category->sort_order);
        $category->update($valid);
        return redirect()->route('companies.categories')->with('success', __('Category updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $category = CashflowCategory::find($id);
        if (! $category) {
            return redirect()->route('companies.categories')->with('error', __('Category not found.'));
        }
        if (! $this->canManage()) {
            return redirect()->route('companies.categories')->with('error', __('You do not have permission.'));
        }
        $category->delete();
        return redirect()->route('companies.categories')->with('success', __('Category deleted.'));
    }
}
