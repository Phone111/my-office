<?php

namespace Modules\Executive\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Executive\Models\WorkLog;

/**
 * บันทึกปฏิบัติงานผู้บริหาร — สมุดบันทึกการปฏิบัติงานรายวันของตนเอง
 */
class WorkLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = WorkLog::where('user_id', $request->user()->id)
            ->orderByDesc('log_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (WorkLog $l) => [
                'id' => $l->id,
                'log_date' => $l->log_date->format('d/m/Y'),
                'log_thai' => $l->log_date->locale('th')->translatedFormat('j F').' '.($l->log_date->year + 543),
                'title' => $l->title,
                'detail' => $l->detail,
                'location' => $l->location,
            ]);

        return Inertia::render('Executive::WorkLog', [
            'logs' => $logs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        WorkLog::create([
            ...$this->validateData($request),
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'บันทึกการปฏิบัติงานเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, WorkLog $workLog): RedirectResponse
    {
        abort_unless($workLog->user_id === $request->user()->id, 403);

        $workLog->delete();

        return back()->with('success', 'ลบบันทึกเรียบร้อยแล้ว');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'log_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
