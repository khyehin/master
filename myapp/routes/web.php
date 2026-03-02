<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CashflowCategoryController;
use App\Http\Controllers\CashflowController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/locale/{locale}', function (string $locale) {
    if (! in_array($locale, ['en', 'zh', 'ms'], true)) {
        return redirect()->back();
    }
    session()->put('locale', $locale);
    session()->save();
    $user = Auth::user();
    if ($user instanceof User) {
        $user->update(['locale' => $locale]);
    }
    app()->setLocale($locale);
    return redirect()->back();
})->name('locale.switch')->where('locale', 'en|zh|ms');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'pin.verified'])->name('dashboard');

Route::middleware(['auth', 'pin.verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('/companies/setting/categories', [CashflowCategoryController::class, 'index'])->name('companies.categories');
    Route::post('/companies/setting/categories', [CashflowCategoryController::class, 'store'])->name('companies.categories.store');
    Route::patch('/companies/setting/categories/{id}', [CashflowCategoryController::class, 'update'])->name('companies.categories.update');
    Route::delete('/companies/setting/categories/{id}', [CashflowCategoryController::class, 'destroy'])->name('companies.categories.destroy');
    Route::get('/companies/{id}/report', [CompanyReportController::class, 'show'])->name('companies.report');
    Route::post('/companies/{id}/report', [CompanyReportController::class, 'store'])->name('companies.report.store');
    Route::get('/companies/{id}/report/export', [CompanyReportController::class, 'export'])->name('companies.report.export');
    Route::get('/companies/{id}/report/all-years', [CompanyReportController::class, 'totalAllYears'])->name('companies.report.all-years');
    Route::post('/companies/{id}/report/all-years/delete-row', [CompanyReportController::class, 'deleteAllYearsRow'])->name('companies.report.all-years.delete-row');
    Route::get('/companies/{id}/report/delete-section', [CompanyReportController::class, 'deleteSection'])->name('companies.report.delete-section');
    Route::get('/companies/{id}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::patch('/companies/{id}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy'])->name('companies.destroy');

    Route::prefix('cashflow')->name('cashflow.')->group(function () {
        Route::get('/', [CashflowController::class, 'index'])->name('index');
        Route::get('/export', [CashflowController::class, 'export'])->name('export');
        Route::post('/columns', [CashflowController::class, 'storeColumn'])->name('columns.store');
        Route::delete('/columns/{id}', [CashflowController::class, 'destroyColumn'])->name('columns.destroy');
        Route::post('/rows', [CashflowController::class, 'storeRows'])->name('rows.store');
        Route::get('/create', [CashflowController::class, 'create'])->name('create');
        Route::post('/', [CashflowController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [CashflowController::class, 'edit'])->name('edit');
        Route::patch('/{id}', [CashflowController::class, 'update'])->name('update');
        Route::delete('/{id}', [CashflowController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/print', [CashflowController::class, 'print'])->name('print');
    });

    Route::prefix('setting')->name('setting.')->group(function () {
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log');

        Route::get('/roles', [RoleController::class, 'index'])->name('roles');
        Route::post('/roles', [RoleController::class, 'save'])->name('roles.save');
        // 使用 POST /roles/{id}/delete，避免 DELETE 方法不被浏览器/中间件支持
        Route::post('/roles/{id}/delete', [RoleController::class, 'destroy'])->name('roles.destroy');

        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{id}', [UserController::class, 'update'])->name('users.update');
    });
});

require __DIR__.'/auth.php';
