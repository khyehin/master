<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Write one audit log entry.
     *
     * @param  string  $event   Short action key, e.g. 'cashflow.rows.saved'
     * @param  array   $details Free-form context (will be stored as JSON)
     * @param  int|null $companyId Optional company id related to the action
     */
    public static function log(string $event, array $details = [], ?int $companyId = null): void
    {
        try {
            $req = Request::instance();
        } catch (\Throwable $e) {
            $req = null;
        }

        $ip = $details['_ip'] ?? ($req ? $req->ip() : null);
        $ua = $details['_ua'] ?? ($req ? $req->userAgent() : null);

        AuditLog::create([
            'user_id' => Auth::id(),
            'company_id' => $companyId,
            'event_type' => $event,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'details' => $details,
        ]);
    }
}

