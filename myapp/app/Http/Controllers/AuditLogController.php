<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public const PER_PAGE_OPTIONS = [10, 20, 50, 100];
    private const DEFAULT_PER_PAGE = 20;

    /**
     * Display paginated audit log with filters (date range, user, action, keyword).
     */
    public function index(Request $request): View
    {
        // 只有有 settings.audit.view 权限的 user 才能看
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $user->hasPermissionTo('settings.audit.view')) {
            abort(403);
        }
        $today = now()->format('Y-m-d');
        $dateFrom = trim((string) $request->input('date_from', ''));
        $dateTo = trim((string) $request->input('date_to', ''));
        $dateAll = trim((string) $request->input('date_all', ''));
        $userId = (int) $request->input('user_id', 0);
        $action = trim((string) $request->input('action', ''));
        $q = trim((string) $request->input('q', ''));
        $perPage = (int) $request->input('per_page', self::DEFAULT_PER_PAGE);
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $useDateFilter = ($dateAll !== '1');
        if ($useDateFilter) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
                $dateFrom = now()->subDays(7)->format('Y-m-d');
            }
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                $dateTo = $today;
            }
        } else {
            $dateFrom = '';
            $dateTo = '';
        }

        $users = User::query()
            ->orderBy('username')
            ->get(['id', 'username', 'name']);

        $actions = AuditLog::query()
            ->select('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type')
            ->filter()
            ->values();

        $query = AuditLog::query()->with('user:id,name,username');

        if ($useDateFilter && $dateFrom !== '' && $dateTo !== '') {
            $query->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo);
        }

        if ($userId > 0) {
            $query->where('user_id', $userId);
        }
        if ($action !== '') {
            $query->where('event_type', $action);
        }
        if ($q !== '') {
            $query->where(function ($qb) use ($q) {
                $qb->where('event_type', 'like', '%' . $q . '%')
                    ->orWhere('details', 'like', '%' . $q . '%');
            });
        }

        $logs = $query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('setting.audit-log', [
            'logs' => $logs,
            'users' => $users,
            'actions' => $actions,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'date_all' => $dateAll === '1' ? '1' : '0',
                'user_id' => $userId,
                'action' => $action,
                'q' => $q,
                'per_page' => $perPage,
            ],
            'per_page_options' => self::PER_PAGE_OPTIONS,
        ]);
    }
}
