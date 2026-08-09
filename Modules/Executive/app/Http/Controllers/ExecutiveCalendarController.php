<?php

namespace Modules\Executive\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Executive\Models\ExecutiveEvent;

/**
 * ปฏิทินปฏิบัติงานของผู้บริหาร
 * เลขาฯ (secretary) เพิ่ม/แก้ไขได้ — ผอ./รองฯ ดูได้อย่างเดียว (read-only)
 */
class ExecutiveCalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $events = ExecutiveEvent::with(['creator:id,name', 'executive:id,name'])
            ->orderByDesc('start_at')
            ->get()
            ->map(fn (ExecutiveEvent $e) => [
                'id' => $e->id,
                'executive_id' => $e->executive_id,
                'executive' => $e->executive?->name,
                'title' => $e->title,
                'description' => $e->description,
                'location' => $e->location,
                'start_at' => $e->start_at->format('Y-m-d H:i'),
                'start_date' => $e->start_at->toDateString(),
                'end_at' => $e->end_at?->format('Y-m-d H:i'),
                'all_day' => $e->all_day,
                'audience' => $e->audience ?? [],
                'time_text' => $e->time_text,
                'days' => $e->days ?? 1,
                'creator' => $e->creator?->name,
            ]);

        // วาระล่าสุด (สำหรับปุ่ม "เรียกข้อมูลล่าสุด" แบบ AMSS)
        $last = ExecutiveEvent::latest('id')->first();

        return Inertia::render('Executive::Calendar', [
            'events' => $events,
            'audienceOptions' => self::AUDIENCE,
            // รายชื่อผู้บริหารรายคน (ผอ./รองผอ.) — เลือกผู้ปฏิบัติแบบ affair AMSS
            'executives' => User::whereHas('roles', fn ($q) => $q->whereIn('name', ['director', 'deputy_director']))
                ->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])->values(),
            'lastEntry' => ['title' => $last?->title, 'location' => $last?->location],
            // เฉพาะเลขาฯ/แอดมินเท่านั้นที่จัดการได้ (ผอ. = read-only)
            'canManage' => $request->user()->hasAnyRole(['secretary', 'admin']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = ExecutiveEvent::create([
            ...$this->validateData($request),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'เพิ่มวาระลงปฏิทินเรียบร้อยแล้ว');
    }

    public function update(Request $request, ExecutiveEvent $event): RedirectResponse
    {
        $this->guardOwner($request, $event);
        $event->update($this->validateData($request));

        return back()->with('success', 'แก้ไขวาระเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, ExecutiveEvent $event): RedirectResponse
    {
        $this->guardOwner($request, $event);
        $event->delete();

        return back()->with('success', 'ลบวาระเรียบร้อยแล้ว');
    }

    /** แก้ไข/ลบได้เฉพาะวาระที่ตนสร้าง (ยกเว้น admin) */
    private function guardOwner(Request $request, ExecutiveEvent $event): void
    {
        abort_unless(
            $request->user()->hasRole('admin') || $event->created_by === $request->user()->id,
            403,
            'แก้ไข/ลบได้เฉพาะวาระที่ท่านสร้าง'
        );
    }

    /** ผู้ปฏิบัติที่เลือกได้ */
    public const AUDIENCE = ['ผู้อำนวยการ', 'รองผู้อำนวยการ', 'ผู้อำนวยการกลุ่ม'];

    private function validateData(Request $request): array
    {
        $v = $request->validate([
            'executive_id' => ['nullable', 'integer', 'exists:users,id'], // ผู้ปฏิบัติ (รายคน)
            'title' => ['required', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],          // หมายเหตุ
            'date' => ['required', 'date'],                         // เลือกวันที่
            'time_text' => ['nullable', 'string', 'max:100'],       // เวลา (พิมพ์)
            'days' => ['required', 'integer', 'min:1', 'max:60'],   // จำนวนวัน
            'audience' => ['nullable', 'array'],                    // ผู้ปฏิบัติ (บทบาท — เก่า)
            'audience.*' => ['string', 'in:'.implode(',', self::AUDIENCE)],
        ]);

        $start = \Illuminate\Support\Carbon::parse($v['date'])->startOfDay();
        $end = (clone $start)->addDays(max($v['days'], 1) - 1)->endOfDay();

        return [
            'executive_id' => $v['executive_id'] ?? null,
            'title' => $v['title'],
            'location' => $v['location'] ?? null,
            'description' => $v['description'] ?? null,
            'time_text' => $v['time_text'] ?? null,
            'days' => $v['days'],
            'audience' => $v['audience'] ?? [],
            'start_at' => $start,
            'end_at' => $end,
            'all_day' => true,
        ];
    }
}
