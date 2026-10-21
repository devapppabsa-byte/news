<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $action = $request->string('action')->toString();
        $search = trim($request->string('q')->toString());
        $from = $request->date('from');
        $to = $request->date('to');

        $logs = AdminAuditLog::query()
            ->with(['actor', 'subject'])
            ->when(in_array($action, AdminAuditLog::actions(), true), fn ($query) => $query->where('action', $action))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('actor', fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));

                if (is_numeric($search)) {
                    $query->orWhere('subject_id', (int) $search);
                }
            }))
            ->when($from !== null, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== null, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit.index', [
            'logs' => $logs,
            'actions' => AdminAuditLog::actions(),
            'filters' => [
                'action' => $action,
                'q' => $search,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
        ]);
    }
}
