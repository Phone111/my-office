<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Vehicle;

/**
 * workflow ขอใช้รถส่วนกลาง (ต่อยอดบน Booking เดิม)
 * ผู้ขอ(จองรถ) → เสนอ → เจ้าหน้าที่จัดรถ(เลือกทะเบียน/คนขับ) → ผู้บริหารอนุมัติ → ลงปฏิทิน
 */
class VehicleFlowController extends Controller
{
    private function isOfficer(Request $r): bool
    {
        return $r->user()->hasAnyRole(['vehicle_booking_officer', 'admin']);
    }

    private function isApprover(Request $r): bool
    {
        return $r->user()->hasAnyRole(['director', 'deputy_director', 'admin']);
    }

    /** รถคันนี้ถูก "จัดแล้ว/อนุมัติแล้ว" ทับช่วงเวลานี้หรือไม่ (กันจัดคันซ้ำ) */
    private function vehicleClash(int $vehicleId, $start, $end, int $ignoreId): bool
    {
        return Booking::where('bookable_type', Vehicle::class)
            ->where('bookable_id', $vehicleId)
            ->whereIn('status', [Booking::STATUS_ASSIGNED, Booking::STATUS_BOOKED])
            ->where('id', '!=', $ignoreId)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->exists();
    }

    private function thai(?Carbon $d, bool $time = true): ?string
    {
        if (! $d) {
            return null;
        }
        $m = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $s = $d->day.' '.$m[$d->month].' '.($d->year + 543);

        return $time ? $s.' เวลา '.$d->format('H:i').' น.' : $s;
    }

    /** แฟ้มขอใช้รถยนต์ — รายการที่ "ฉัน" ต้องดำเนินการ/ติดตาม */
    public function index(Request $request): Response
    {
        $uid = $request->user()->id;

        $rows = Booking::with(['bookable:id,name,license_plate', 'user:id,name'])
            ->where('bookable_type', Vehicle::class)
            ->where(function ($q) use ($uid, $request) {
                // เรื่องของฉัน (รวมที่อนุมัติแล้ว เพื่อเปิดดู/ใบเบิกน้ำมันได้)
                $q->where(fn ($w) => $w->where('user_id', $uid)
                    ->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_SUBMITTED, Booking::STATUS_ASSIGNED, Booking::STATUS_REJECTED, Booking::STATUS_BOOKED]));
                if ($this->isOfficer($request)) {
                    $q->orWhere('status', Booking::STATUS_SUBMITTED);
                }
                if ($this->isApprover($request)) {
                    $q->orWhere('status', Booking::STATUS_ASSIGNED);
                }
            })
            ->latest()
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'subject' => 'บันทึกขอจองรถ ของ '.$b->user?->name,
                'requester' => $b->user?->name,
                'created_thai' => $this->thai($b->created_at),
                'status' => $b->status,
                'status_label' => Booking::FLOW_LABELS[$b->status] ?? $b->status,
                'vehicle' => $b->bookable?->name,
                'can_fuel' => $this->fuelEligible($b),
            ]);

        return Inertia::render('Booking::VehicleFlow/Index', ['rows' => $rows]);
    }

    /** ทะเบียนการจองรถ / ตรวจสอบทะเบียนรถ — รายการจองรถทั้งหมด (เช็ครถว่าง + ยกเลิกแทนได้) */
    public function registry(Request $request): Response
    {
        $isOfficer = $this->isOfficer($request);

        $rows = Booking::with(['bookable:id,name,license_plate', 'user:id,name'])
            ->where('bookable_type', Vehicle::class)
            ->orderByDesc('start_at')
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'when' => $this->thai($b->start_at, false).' - '.$this->thai($b->end_at, false),
                'plate' => $b->bookable?->license_plate,
                'vehicle' => $b->bookable?->name,
                'requester' => $b->user?->name,
                'purpose' => $b->purpose,
                'status' => $b->status,
                'status_label' => Booking::FLOW_LABELS[$b->status] ?? $b->status,
                'can_cancel' => $isOfficer && in_array($b->status, [
                    Booking::STATUS_PENDING, Booking::STATUS_SUBMITTED, Booking::STATUS_ASSIGNED, Booking::STATUS_BOOKED,
                ], true),
            ]);

        return Inertia::render('Booking::VehicleFlow/Registry', ['rows' => $rows]);
    }

    /** เจ้าหน้าที่ยกเลิกการจองแทน */
    public function officerCancel(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless(
            $this->isOfficer($request)
            && $booking->bookable_type === Vehicle::class
            && in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_SUBMITTED, Booking::STATUS_ASSIGNED, Booking::STATUS_BOOKED], true),
            403,
        );

        $booking->update(['status' => Booking::STATUS_CANCELLED]);

        return back()->with('success', 'ยกเลิกการจองรถเรียบร้อยแล้ว');
    }

    public function show(Request $request, Booking $booking): Response
    {
        abort_unless($booking->bookable_type === Vehicle::class, 404);
        $booking->load(['bookable', 'user.position:id,name', 'officer:id,name', 'approver:id,name']);
        $u = $request->user();

        return Inertia::render('Booking::VehicleFlow/Show', [
            'req' => [
                'id' => $booking->id,
                'requester' => $booking->user?->name,
                'requester_position' => $booking->user?->position?->name,
                'division' => $booking->division,
                'purpose' => $booking->purpose,
                'destination' => $booking->destination,
                'companions' => $booking->companions,
                'passengers' => $booking->passengers,
                'written_thai' => $this->thai($booking->written_date ?? $booking->created_at, false),
                'start_thai' => $this->thai($booking->start_at),
                'end_thai' => $this->thai($booking->end_at),
                'days' => max($booking->start_at->diffInDays($booking->end_at) + 1, 1),
                'fuel_label' => $booking->fuel_source ? (Booking::FUEL_LABELS[$booking->fuel_source] ?? $booking->fuel_source) : null,
                'vehicle' => $booking->bookable ? $booking->bookable->name.' ('.$booking->bookable->license_plate.')' : null,
                'vehicle_plate' => $booking->bookable?->license_plate,
                'vehicle_id' => $booking->bookable_id,
                'driver_name' => $booking->driver_name,
                'officer' => $booking->officer?->name,
                'officer_comment' => $booking->officer_comment,
                'approver' => $booking->approver?->name,
                'approver_comment' => $booking->approver_comment,
                'status' => $booking->status,
                'status_label' => Booking::FLOW_LABELS[$booking->status] ?? $booking->status,
                'file' => $booking->file_path ? asset('storage/'.$booking->file_path) : null,
            ],
            'vehicles' => Vehicle::where('is_active', true)->orderBy('name')->get(['id', 'name', 'license_plate', 'seats']),
            'canSubmit' => $booking->user_id === $u->id && in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_REJECTED], true),
            'canAssign' => $this->isOfficer($request) && $booking->status === Booking::STATUS_SUBMITTED,
            'canApprove' => $this->isApprover($request) && $booking->status === Booking::STATUS_ASSIGNED,
            'canCancel' => $booking->user_id === $u->id && in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_SUBMITTED, Booking::STATUS_ASSIGNED, Booking::STATUS_REJECTED], true),
            // ใบเบิกน้ำมัน — เมื่ออนุมัติแล้ว + เบิกจากราชการ (AMSS ส่วน 11)
            'canFuel' => $this->fuelEligible($booking)
                && ($booking->user_id === $u->id || $this->isOfficer($request) || $this->isApprover($request)),
        ]);
    }

    /** ใบเบิกน้ำมันใช้ได้เมื่ออนุมัติแล้ว และเบิกจากราชการ (ส่วนกลาง/โครงการ) */
    private function fuelEligible(Booking $booking): bool
    {
        return $booking->status === Booking::STATUS_BOOKED
            && in_array($booking->fuel_source, ['central', 'project'], true);
    }

    /** เมนู "ใบเบิกน้ำมัน" (เจ้าหน้าที่) — รายการรถที่อนุมัติแล้ว+เบิกราชการ ตาม AMSS ส่วน 11 */
    public function fuelList(Request $request): Response
    {
        abort_unless($this->isOfficer($request), 403);

        $rows = Booking::with(['bookable:id,name,license_plate', 'user:id,name'])
            ->where('bookable_type', Vehicle::class)
            ->where('status', Booking::STATUS_BOOKED)
            ->whereIn('fuel_source', ['central', 'project'])
            ->orderByDesc('start_at')
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'requester' => $b->user?->name,
                'driver_name' => $b->driver_name,
                'vehicle' => $b->bookable ? $b->bookable->name.' ('.$b->bookable->license_plate.')' : '—',
                'when' => $this->thai($b->start_at, false).' - '.$this->thai($b->end_at, false),
                'purpose' => $b->purpose,
                'fuel_source' => Booking::FUEL_LABELS[$b->fuel_source] ?? $b->fuel_source,
                'filled' => (bool) $b->fuel_filled_at,
                'fuel_amount' => $b->fuel_amount,
            ]);

        return Inertia::render('Booking::VehicleFlow/FuelList', ['rows' => $rows]);
    }

    /** ใบเบิกน้ำมันเชื้อเพลิงและน้ำมันหล่อลื่น (พิมพ์) — AMSS ส่วน 11 */
    public function fuelForm(Request $request, Booking $booking): Response
    {
        abort_unless($booking->bookable_type === Vehicle::class && $this->fuelEligible($booking), 404);
        $u = $request->user();
        abort_unless(
            $booking->user_id === $u->id || $this->isOfficer($request) || $this->isApprover($request),
            403,
        );
        $booking->load(['bookable', 'user.position:id,name', 'officer:id,name', 'approver:id,name']);

        return Inertia::render('Booking::VehicleFlow/FuelDisbursement', [
            'doc' => [
                'id' => $booking->id,
                'requester' => $booking->user?->name,
                'requester_position' => $booking->user?->position?->name,
                'purpose' => $booking->purpose,
                'destination' => $booking->destination,
                'start_thai' => $this->thai($booking->start_at),
                'end_thai' => $this->thai($booking->end_at),
                'written_thai' => $this->thai($booking->written_date ?? $booking->created_at, false),
                'vehicle' => $booking->bookable?->name,
                'vehicle_plate' => $booking->bookable?->license_plate,
                'driver_name' => $booking->driver_name,
                'fuel_source_label' => Booking::FUEL_LABELS[$booking->fuel_source] ?? $booking->fuel_source,
                'officer' => $booking->officer?->name,
                'approver' => $booking->approver?->name,
                'approver_comment' => $booking->approver_comment,
                'fuel_station' => $booking->fuel_station,
                'fuel_liters' => $booking->fuel_liters,
                'fuel_amount' => $booking->fuel_amount,
                'fuel_note' => $booking->fuel_note,
                'fuel_filled_thai' => $this->thai($booking->fuel_filled_at, false),
            ],
            'canEditFuel' => $this->isOfficer($request),
            'school' => 'โรงเรียนเศรษฐบุตรบำเพ็ญ',
        ]);
    }

    /** เจ้าหน้าที่บันทึกรายละเอียดการเบิกน้ำมัน */
    public function saveFuel(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($this->isOfficer($request) && $booking->bookable_type === Vehicle::class && $this->fuelEligible($booking), 403);

        $v = $request->validate([
            'fuel_station' => ['nullable', 'string', 'max:255'],
            'fuel_liters' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'fuel_amount' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'fuel_note' => ['nullable', 'string', 'max:500'],
        ]);

        $booking->update([
            'fuel_station' => $v['fuel_station'] ?? null,
            'fuel_liters' => $v['fuel_liters'] ?? null,
            'fuel_amount' => $v['fuel_amount'] ?? null,
            'fuel_note' => $v['fuel_note'] ?? null,
            'fuel_filled_at' => now(),
        ]);

        return back()->with('success', 'บันทึกใบเบิกน้ำมันเรียบร้อย');
    }

    /** ผู้ขอ: เสนอเจ้าหน้าที่จัดรถ */
    public function submit(Request $request, Booking $booking): RedirectResponse
    {
        // เสนอได้ทั้งคำขอใหม่ (pending) และคำขอที่ถูกส่งกลับให้แก้ (rejected)
        abort_unless(
            $booking->user_id === $request->user()->id
            && in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_REJECTED], true),
            403,
        );
        $booking->update(['status' => Booking::STATUS_SUBMITTED]);

        return redirect()->route('booking.vehicle-flow.index')->with('success', 'เสนอเรื่องให้เจ้าหน้าที่จัดรถแล้ว');
    }

    /** เจ้าหน้าที่จัดรถ: เลือกทะเบียนรถ + คนขับ → เสนอผู้บริหาร */
    public function assign(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($this->isOfficer($request) && $booking->status === Booking::STATUS_SUBMITTED, 403);

        $v = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'driver_name' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
        ]);

        if ($this->vehicleClash((int) $v['vehicle_id'], $booking->start_at, $booking->end_at, $booking->id)) {
            throw ValidationException::withMessages(['vehicle_id' => 'รถคันนี้ถูกจัด/อนุมัติให้คำขออื่นในช่วงเวลานี้แล้ว เลือกคันอื่น']);
        }

        $booking->update([
            'bookable_id' => $v['vehicle_id'],
            'driver_name' => $v['driver_name'],
            'officer_id' => $request->user()->id,
            'officer_comment' => $v['comment'] ?? null,
            'status' => Booking::STATUS_ASSIGNED,
        ]);

        return redirect()->route('booking.vehicle-flow.index')->with('success', 'จัดรถเรียบร้อย — เสนอผู้บริหารอนุมัติแล้ว');
    }

    /** ผู้บริหาร: อนุมัติ → ลงปฏิทิน (booked) */
    public function approve(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($this->isApprover($request) && $booking->status === Booking::STATUS_ASSIGNED, 403);
        $comment = $request->validate(['comment' => ['nullable', 'string', 'max:1000']])['comment'] ?? null;

        // ล็อกแถวกันอนุมัติชนเวลากันพร้อมกัน (TOCTOU)
        DB::transaction(function () use ($request, $booking, $comment) {
            $clash = Booking::where('bookable_type', Vehicle::class)
                ->where('bookable_id', $booking->bookable_id)
                ->whereIn('status', [Booking::STATUS_ASSIGNED, Booking::STATUS_BOOKED])
                ->where('id', '!=', $booking->id)
                ->where('start_at', '<', $booking->end_at)
                ->where('end_at', '>', $booking->start_at)
                ->lockForUpdate()
                ->exists();

            if ($clash) {
                throw ValidationException::withMessages(['comment' => 'รถถูกจัด/อนุมัติชนกับรายการอื่นแล้ว — ไม่อนุมัติแล้วให้เจ้าหน้าที่จัดรถใหม่']);
            }

            $booking->update([
                'status' => Booking::STATUS_BOOKED,
                'approver_id' => $request->user()->id,
                'approver_comment' => $comment,
            ]);
        });

        return redirect()->route('booking.vehicle-flow.index')->with('success', 'อนุมัติและลงปฏิทินการใช้รถเรียบร้อยแล้ว');
    }

    /** ไม่อนุมัติ/จัดรถไม่ได้ → ส่งกลับผู้ขอ */
    public function reject(Request $request, Booking $booking): RedirectResponse
    {
        $isOfficer = $this->isOfficer($request) && $booking->status === Booking::STATUS_SUBMITTED;
        $isApprover = $this->isApprover($request) && $booking->status === Booking::STATUS_ASSIGNED;
        abort_unless($isOfficer || $isApprover, 403);

        $v = $request->validate(['comment' => ['required', 'string']]);

        $booking->update([
            'status' => Booking::STATUS_REJECTED,
            $isOfficer ? 'officer_comment' : 'approver_comment' => $v['comment'],
            $isOfficer ? 'officer_id' : 'approver_id' => $request->user()->id,
        ]);

        return redirect()->route('booking.vehicle-flow.index')->with('success', 'ส่งกลับผู้ขอพร้อมเหตุผลแล้ว');
    }
}
