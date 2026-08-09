<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\AuditLog;

/**
 * บันทึกการใช้งาน (Audit Log) — ดูประวัติว่าใครทำอะไร (เฉพาะ admin)
 */
class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $q = AuditLog::with('user:id,name')->latest('created_at');

        if ($a = $request->input('action')) {
            $q->where('action', $a);
        }
        if ($kw = trim((string) $request->input('q'))) {
            $q->where(fn ($w) => $w->where('user_name', 'like', "%{$kw}%")->orWhere('description', 'like', "%{$kw}%"));
        }
        if ($d = $request->input('date')) {
            $q->whereDate('created_at', $d);
        }

        $logs = $q->limit(500)->get()->map(fn (AuditLog $l) => [
            'id' => $l->id,
            'user' => $l->user_name,
            'action' => $l->action,
            'action_label' => AuditLog::actionLabel($l->action),
            'type' => AuditLog::typeLabel($l->auditable_type),
            'description' => $l->description,
            'ip' => $l->ip,
            'at' => $l->created_at?->format('d/m/Y H:i:s'),
        ]);

        return Inertia::render('Core::Admin/AuditLog', [
            'logs' => $logs,
            'actions' => collect(AuditLog::ACTION_LABELS)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'filters' => $request->only(['action', 'q', 'date']),
            'total' => AuditLog::count(),
        ]);
    }
}
