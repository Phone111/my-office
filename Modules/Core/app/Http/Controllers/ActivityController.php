<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\MeetingRoom;
use Modules\Booking\Models\Vehicle;
use Modules\Core\Models\Activity;
use Modules\Core\Models\Group;
use Modules\Executive\Models\ExecutiveEvent;

/**
 * ปฏิทินกิจกรรม (รวม) — กิจกรรมทั่วไป + จองห้อง/รถ + วาระผู้บริหาร บนปฏิทินเดียว
 */
class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        // วาระผู้บริหารเห็นเฉพาะผู้บริหาร/เลขาฯ/admin (ตรงกับสิทธิ์หน้าปฏิทินผู้บริหารเดิม)
        $canSeeExec = $user->hasAnyRole(['admin', 'area_admin', 'secretary', 'director', 'deputy_director']);
        $month = (int) ($request->query('month') ?: Carbon::now()->month);
        $year = (int) ($request->query('year') ?: Carbon::now()->year);
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth();

        $events = collect();

        // 1) กิจกรรมทั่วไป (ตามสิทธิ์การมองเห็น)
        Activity::with(['creator:id,name', 'group:id,name'])
            ->whereBetween('start_at', [$start, $end])
            ->where(function ($w) use ($user) {
                $w->where('visibility', 'all')
                    ->orWhere('created_by', $user->id)
                    ->orWhere(fn ($x) => $x->where('visibility', 'group')->where('group_id', $user->group_id));
            })
            ->get()
            ->each(function (Activity $a) use ($events, $user) {
                $events->push([
                    'key' => 'a'.$a->id,
                    'id' => $a->id,
                    'type' => 'activity',
                    'type_label' => 'กิจกรรม',
                    'title' => $a->title,
                    'date' => $a->start_at->toDateString(),
                    'end_date' => ($a->end_at ?? $a->start_at)->toDateString(),
                    'time_label' => $this->timeLabel($a->all_day, $a->start_at, $a->end_at),
                    'location' => $a->location,
                    'owner' => $a->creator?->name,
                    'group' => $a->group?->name,
                    'detail' => $a->detail,
                    'can_edit' => $a->created_by === $user->id || $user->hasAnyRole(['admin', 'area_admin']),
                    // ฟิลด์สำหรับแก้ไข
                    'event_date' => $a->start_at->toDateString(),
                    'start_time' => $a->all_day ? '' : $a->start_at->format('H:i'),
                    'end_time' => $a->end_at ? $a->end_at->format('H:i') : '',
                    'all_day' => $a->all_day,
                    'group_id' => $a->group_id,
                    'visibility' => $a->visibility,
                ]);
            });

        // 2) จองห้องประชุม · 3) จองรถ (read-only)
        Booking::with(['bookable', 'user:id,name'])->active()
            ->whereIn('bookable_type', [MeetingRoom::class, Vehicle::class])
            ->where('start_at', '<=', $end)->where('end_at', '>=', $start)
            ->get()
            ->each(function (Booking $b) use ($events) {
                $isRoom = $b->bookable_type === MeetingRoom::class;
                $events->push([
                    'key' => 'b'.$b->id,
                    'id' => $b->id,
                    'type' => $isRoom ? 'room' : 'vehicle',
                    'type_label' => $isRoom ? 'จองห้องประชุม' : 'จองรถ',
                    'title' => $b->purpose ?: ($isRoom ? 'จองห้องประชุม' : 'ขอใช้รถ'),
                    'date' => $b->start_at->toDateString(),
                    'end_date' => $b->end_at->toDateString(),
                    'time_label' => $b->start_at->format('H:i').'–'.$b->end_at->format('H:i').' น.',
                    'location' => $b->bookable?->name,
                    'owner' => $b->user?->name,
                    'detail' => $isRoom ? null : $b->destination,
                    'can_edit' => false,
                ]);
            });

        // 4) วาระผู้บริหาร (read-only) — เฉพาะผู้มีสิทธิ์
        ExecutiveEvent::with('executive:id,name')
            ->whereBetween('start_at', [$start, $end])
            ->when(! $canSeeExec, fn ($q) => $q->whereRaw('1 = 0')) // ไม่มีสิทธิ์ = ไม่ดึง
            ->get()
            ->each(function (ExecutiveEvent $e) use ($events) {
                $events->push([
                    'key' => 'e'.$e->id,
                    'id' => $e->id,
                    'type' => 'exec',
                    'type_label' => 'วาระผู้บริหาร',
                    'title' => $e->title,
                    'date' => $e->start_at->toDateString(),
                    'end_date' => ($e->end_at ?? $e->start_at)->toDateString(),
                    'time_label' => $e->time_text ?: 'ทั้งวัน',
                    'location' => $e->location,
                    'owner' => $e->executive?->name,
                    'detail' => $e->description,
                    'can_edit' => false,
                ]);
            });

        return Inertia::render('Core::Activities/Index', [
            'events' => $events->sortBy('date')->values(),
            'month' => $month,
            'year' => $year,
            'yearThai' => $year + 543,
            'monthName' => $start->locale('th')->translatedFormat('F'),
            'visibilities' => Activity::VISIBILITIES,
            'groups' => Group::orderBy('name')->get(['id', 'name'])->map(fn ($g) => ['id' => $g->id, 'name' => $g->name]),
            'canSeeExec' => $canSeeExec,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        [$startAt, $endAt] = $this->composeTimes($data);

        Activity::create([
            'title' => $data['title'],
            'location' => $data['location'] ?? null,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'all_day' => $data['all_day'] ?? false,
            'detail' => $data['detail'] ?? null,
            'group_id' => $data['visibility'] === 'group' ? ($data['group_id'] ?? null) : null,
            'unit_id' => $request->user()->unit_id,
            'visibility' => $data['visibility'],
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'เพิ่มกิจกรรมเรียบร้อยแล้ว');
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        $this->guardOwner($request, $activity);
        $data = $this->validateData($request);
        [$startAt, $endAt] = $this->composeTimes($data);

        $activity->update([
            'title' => $data['title'],
            'location' => $data['location'] ?? null,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'all_day' => $data['all_day'] ?? false,
            'detail' => $data['detail'] ?? null,
            'group_id' => $data['visibility'] === 'group' ? ($data['group_id'] ?? null) : null,
            'visibility' => $data['visibility'],
        ]);

        return back()->with('success', 'แก้ไขกิจกรรมเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, Activity $activity): RedirectResponse
    {
        $this->guardOwner($request, $activity);
        $activity->delete();

        return back()->with('success', 'ลบกิจกรรมเรียบร้อยแล้ว');
    }

    /* ---------- helpers ---------- */

    private function guardOwner(Request $request, Activity $activity): void
    {
        abort_unless(
            $activity->created_by === $request->user()->id || $request->user()->hasAnyRole(['admin', 'area_admin']),
            403,
            'แก้ไข/ลบได้เฉพาะกิจกรรมที่ท่านสร้าง'
        );
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'all_day' => ['boolean'],
            'start_time' => ['nullable', 'required_if:all_day,false', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'detail' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', 'in:'.implode(',', array_keys(Activity::VISIBILITIES))],
            'group_id' => ['nullable', 'required_if:visibility,group', 'exists:groups,id'],
        ]);
    }

    private function composeTimes(array $data): array
    {
        $date = Carbon::parse($data['event_date'])->toDateString();
        $allDay = $data['all_day'] ?? false;
        $startAt = $allDay ? $date.' 00:00:00' : $date.' '.$data['start_time'].':00';
        $endAt = (! $allDay && ! empty($data['end_time'])) ? $date.' '.$data['end_time'].':00' : null;

        return [$startAt, $endAt];
    }

    private function timeLabel(bool $allDay, Carbon $start, ?Carbon $end): string
    {
        if ($allDay) {
            return 'ทั้งวัน';
        }

        return $start->format('H:i').($end ? '–'.$end->format('H:i') : '').' น.';
    }
}
