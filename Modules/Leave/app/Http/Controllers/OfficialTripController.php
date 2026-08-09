<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Vehicle;
use Modules\Core\Models\Signature;
use Modules\Core\Services\ApprovalWorkflowService;
use Modules\Leave\Models\OfficialTrip;
use Modules\Leave\Models\OfficialTripRoute;
use RuntimeException;

/**
 * ขออนุญาตไปราชการ — ฟอร์มเฉพาะ + เส้นทาง หัวหน้ากลุ่ม → ผอ.
 */
class OfficialTripController extends Controller
{
    public function __construct(private readonly ApprovalWorkflowService $engine)
    {
    }

    /**
     * AMSS ส่วน 10: ผู้บังคับบัญชาขั้นต้นไม่ลงความเห็นภายใน 24 ชม. → ผ่านไปผู้อนุมัติเอง
     * เรียกแบบ lazy เมื่อมีผู้เปิดดูแฟ้ม (ผู้อนุมัติ/ผู้ยื่น) + มี console command สำหรับ schedule
     */
    public static function autoAdvanceOverdue(ApprovalWorkflowService $engine): int
    {
        $cutoff = Carbon::now()->subDay();
        $count = 0;

        $overdue = OfficialTripRoute::where('step_order', 1)
            ->where('status', OfficialTripRoute::STATUS_PENDING)
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($overdue as $route) {
            // ผ่านอัตโนมัติเฉพาะเมื่อมีขั้นถัดไป (ผู้อนุมัติ) — ไม่ข้ามขั้นสุดท้าย
            $hasNext = OfficialTripRoute::where('official_trip_id', $route->official_trip_id)
                ->where('step_order', '>', 1)
                ->exists();
            if ($hasNext) {
                $engine->approve($route, '[ผ่านอัตโนมัติ: ผู้บังคับบัญชาขั้นต้นไม่ลงความเห็นภายใน 24 ชม.]');
                $count++;
            }
        }

        return $count;
    }

    /** แฟ้มไปราชการของฉัน */
    public function index(Request $request): Response
    {
        self::autoAdvanceOverdue($this->engine);

        $rows = OfficialTrip::with(['routes.approver:id,name'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (OfficialTrip $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'destination' => $t->destination,
                'depart_thai' => $this->thaiDate($t->depart_at),
                'return_thai' => $this->thaiDate($t->return_at),
                'status' => $t->status,
                'current_approver' => $t->currentRoute?->approver?->name,
            ]);

        return Inertia::render('Leave::OfficialTrips/Index', ['trips' => $rows]);
    }

    public function create(): Response
    {
        return Inertia::render('Leave::OfficialTrips/Create', [
            'vehicles' => collect(OfficialTrip::VEHICLES)->map(fn ($l, $k) => ['value' => $k, 'label' => $l])->values(),
            // รถยนต์ราชการในทะเบียน (สำหรับเลือก + ลิงค์ระบบจองรถ)
            'cars' => Vehicle::where('is_active', true)->orderBy('name')->get(['id', 'name', 'license_plate', 'seats']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'companions' => ['nullable', 'string', 'max:1000'],
            'purpose' => ['required', 'string', 'max:2000'],
            'destination' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'depart_at' => ['required', 'date'],
            'return_at' => ['required', 'date', 'after_or_equal:depart_at'],
            'vehicle_type' => ['required', 'in:'.implode(',', array_keys(OfficialTrip::VEHICLES))],
            'vehicle_plate' => ['nullable', 'string', 'max:50'],
            'vehicle_other' => ['nullable', 'string', 'max:255'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'], // รถยนต์ราชการจากทะเบียน
            'budget_source' => ['nullable', 'string', 'max:255'],
            'files' => ['array', 'max:4'],
            'files.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);

        // เลือกรถยนต์ราชการ → ผูกกับระบบจองรถ (เช็คชนเวลา + ดึงทะเบียนจริง)
        $car = null;
        if ($data['vehicle_type'] === 'official_car' && ! empty($data['vehicle_id'])) {
            $car = Vehicle::find($data['vehicle_id']);
            if ($car && Booking::hasConflict(Vehicle::class, $car->id, $data['depart_at'], $data['return_at'])) {
                return back()
                    ->withInput()
                    ->with('error', "รถ \"{$car->name}\" ({$car->license_plate}) ถูกจองในช่วงเวลานี้แล้ว กรุณาเลือกรถคันอื่นหรือปรับเวลา");
            }
        }

        $paths = [];
        foreach ((array) $request->file('files', []) as $file) {
            if ($file) {
                $paths[] = $file->store('official-trips', 'public');
            }
        }

        $trip = OfficialTrip::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'companions' => $data['companions'] ?? null,
            'purpose' => $data['purpose'],
            'destination' => $data['destination'],
            'reference' => $data['reference'] ?? null,
            'depart_at' => $data['depart_at'],
            'return_at' => $data['return_at'],
            'vehicle_type' => $data['vehicle_type'],
            // ถ้าเลือกรถราชการ ใช้ทะเบียนจริงจากระบบจองรถ
            'vehicle_plate' => $car ? $car->license_plate : ($data['vehicle_plate'] ?? null),
            'vehicle_other' => $data['vehicle_other'] ?? null,
            'vehicle_id' => $car?->id,
            'budget_source' => $data['budget_source'] ?? null,
            'attachments' => $paths,
            'status' => OfficialTrip::STATUS_DRAFT,
        ]);

        // หมายเหตุ: รถจะถูกจองจริงเมื่อ "อนุมัติ" แล้วเท่านั้น (ดูใน approve())

        try {
            $this->engine->start($trip, ['head_of_department', 'director']);
        } catch (RuntimeException $e) {
            $trip->delete();

            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('official-trips.index')->with('success', 'ส่งคำขอไปราชการเรียบร้อยแล้ว');
    }

    public function show(Request $request, OfficialTrip $officialTrip): Response
    {
        $uid = $request->user()->id;
        $officialTrip->load(['user:id,name', 'routes.approver:id,name']);

        $myRoute = $officialTrip->routes->first(
            fn (OfficialTripRoute $r) => $r->approver_id === $uid && $r->status === OfficialTripRoute::STATUS_PENDING,
        );

        abort_unless(
            $officialTrip->user_id === $uid || $request->user()->hasRole('admin') || $myRoute,
            403,
        );

        // เคลียร์แจ้งเตือนกระดิ่งของคำขอนี้เมื่อผู้เกี่ยวข้องเปิดอ่าน
        $tripUrl = route('official-trips.show', $officialTrip->id);
        $request->user()->unreadNotifications->each(function ($n) use ($tripUrl) {
            if (($n->data['url'] ?? null) === $tripUrl) {
                $n->markAsRead();
            }
        });

        $sigIds = $officialTrip->routes->pluck('approver_id')->push($officialTrip->user_id)->unique();
        $signatures = Signature::whereIn('user_id', $sigIds)->pluck('file_path', 'user_id');

        $acted = $officialTrip->routes
            ->filter(fn (OfficialTripRoute $r) => in_array($r->status, [OfficialTripRoute::STATUS_APPROVED, OfficialTripRoute::STATUS_REJECTED], true))
            ->map(fn (OfficialTripRoute $r) => [
                'role_label' => $r->step_order <= 1 ? 'ความเห็นหัวหน้ากลุ่ม' : 'ผู้อนุมัติ',
                'approver' => $r->approver?->name,
                'status' => $r->status,
                'comment' => $r->comment,
                'acted_thai' => $r->acted_at ? $r->acted_at->locale('th')->translatedFormat('j M').' '.($r->acted_at->year + 543) : null,
                'signature_url' => isset($signatures[$r->approver_id]) ? asset('storage/'.$signatures[$r->approver_id]) : null,
            ])->values();

        return Inertia::render('Leave::OfficialTrips/Show', [
            'trip' => [
                'id' => $officialTrip->id,
                'title' => $officialTrip->title,
                'requester' => $officialTrip->user->name,
                'requester_position' => $this->roleLabel($officialTrip->user),
                'companions' => $officialTrip->companions,
                'purpose' => $officialTrip->purpose,
                'destination' => $officialTrip->destination,
                'reference' => $officialTrip->reference,
                'depart_thai' => $this->thaiDateTime($officialTrip->depart_at),
                'return_thai' => $this->thaiDateTime($officialTrip->return_at),
                'vehicle' => OfficialTrip::VEHICLES[$officialTrip->vehicle_type] ?? '-',
                'vehicle_plate' => $officialTrip->vehicle_plate,
                'vehicle_other' => $officialTrip->vehicle_other,
                'budget_source' => $officialTrip->budget_source,
                'document_number' => $officialTrip->document_number,
                'created_thai' => $this->thaiDate($officialTrip->created_at),
                'signature_url' => isset($signatures[$officialTrip->user_id]) ? asset('storage/'.$signatures[$officialTrip->user_id]) : null,
                'attachments' => collect($officialTrip->attachments ?? [])->map(fn ($p, $i) => ['name' => 'ไฟล์แนบ '.($i + 1), 'url' => asset('storage/'.$p)])->values(),
                'status' => $officialTrip->status,
            ],
            'myRouteId' => $myRoute?->id,
            'acted' => $acted,
            'canPrint' => $officialTrip->status === OfficialTrip::STATUS_APPROVED,
        ]);
    }

    public function approve(Request $request, OfficialTripRoute $route): RedirectResponse
    {
        $this->authorizeAction($route, $request);
        $validated = $request->validate(['comment' => ['nullable', 'string', 'max:1000']]);
        $this->engine->approve($route, $validated['comment'] ?? null);

        // อนุมัติครบทุกขั้น (ขั้นสุดท้าย) + เลือกรถยนต์ราชการไว้ → จองรถให้อัตโนมัติ
        $message = 'ดำเนินการเรียบร้อยแล้ว';
        $trip = OfficialTrip::find($route->official_trip_id);
        if ($trip && $trip->status === OfficialTrip::STATUS_APPROVED && $trip->vehicle_id && ! $trip->vehicle_booking_id) {
            $car = Vehicle::find($trip->vehicle_id);
            if ($car) {
                $start = $trip->depart_at->toDateTimeString();
                $end = $trip->return_at->toDateTimeString();
                if (Booking::hasConflict(Vehicle::class, $car->id, $start, $end)) {
                    $message = "อนุมัติแล้ว แต่รถ \"{$car->name}\" ({$car->license_plate}) ถูกจองช่วงเวลานี้ไปแล้ว กรุณาจัดรถใหม่";
                } else {
                    $booking = Booking::create([
                        'bookable_type' => Vehicle::class,
                        'bookable_id' => $car->id,
                        'user_id' => $trip->user_id,
                        'start_at' => $start,
                        'end_at' => $end,
                        'purpose' => $trip->purpose,
                        'destination' => $trip->destination,
                        'companions' => $trip->companions,
                        'status' => Booking::STATUS_BOOKED,
                    ]);
                    $trip->update(['vehicle_booking_id' => $booking->id]);
                    $message = "อนุมัติเรียบร้อย และจองรถ \"{$car->name}\" ({$car->license_plate}) ให้แล้ว";
                }
            }
        }

        return redirect()->route('official-trips.inbox')->with('success', $message);
    }

    public function reject(Request $request, OfficialTripRoute $route): RedirectResponse
    {
        $this->authorizeAction($route, $request);
        $validated = $request->validate(['comment' => ['required', 'string', 'max:1000']]);
        $this->engine->reject($route, $validated['comment']);

        // ตีกลับ → ยกเลิกการจองรถที่ผูกไว้ (คืนคิวรถ)
        $trip = $route->officialTrip;
        if ($trip && $trip->vehicle_booking_id) {
            Booking::where('id', $trip->vehicle_booking_id)->update(['status' => Booking::STATUS_CANCELLED]);
        }

        return redirect()->route('official-trips.inbox')->with('success', 'ตีกลับเรียบร้อยแล้ว');
    }

    /** แฟ้มตรวจการไปราชการ (ผู้อนุมัติ) */
    public function inbox(Request $request): Response
    {
        self::autoAdvanceOverdue($this->engine);

        $isHead = $request->user()->hasRole('head_of_department') || $request->user()->hasRole('head_of_subject');

        $rows = OfficialTripRoute::with(['officialTrip.user:id,name'])
            ->where('approver_id', $request->user()->id)
            ->where('status', OfficialTripRoute::STATUS_PENDING)
            ->latest()
            ->get()
            ->filter(fn (OfficialTripRoute $r) => $r->officialTrip)
            ->map(fn (OfficialTripRoute $r) => [
                'id' => $r->official_trip_id,
                'title' => $r->officialTrip->title,
                'destination' => $r->officialTrip->destination,
                'sender' => $r->officialTrip->user?->name,
                'sent_thai' => $this->thaiDateTime($r->officialTrip->created_at),
                'status' => $r->step_order <= 1 ? 'รอความเห็นหัวหน้ากลุ่ม' : 'รอผู้อนุมัติ',
            ])->values();

        return Inertia::render('Leave::OfficialTrips/Inbox', ['requests' => $rows]);
    }

    private function authorizeAction(OfficialTripRoute $route, Request $request): void
    {
        abort_unless(
            $route->approver_id === $request->user()->id && $route->status === OfficialTripRoute::STATUS_PENDING,
            403,
        );
    }

    private function roleLabel(User $user): string
    {
        $map = [
            'director' => 'ผู้อำนวยการ', 'deputy_director' => 'รองผู้อำนวยการ',
            'head_of_department' => 'หัวหน้ากลุ่ม', 'head_of_subject' => 'หัวหน้ากลุ่มสาระ',
            'secretary' => 'เลขานุการ', 'teacher' => 'ครู',
        ];
        foreach ($user->getRoleNames() as $r) {
            if (isset($map[$r])) {
                return $map[$r];
            }
        }

        return 'บุคลากร';
    }

    private function thaiDate(?Carbon $d): ?string
    {
        return $d ? $this->thaiNum($d->locale('th')->translatedFormat('j F').' '.($d->year + 543)) : null;
    }

    private function thaiDateTime(?Carbon $d): ?string
    {
        return $d ? $this->thaiNum($d->locale('th')->translatedFormat('j M').' '.($d->year + 543).' เวลา '.$d->format('H:i').' น.') : null;
    }

    private function thaiNum(string $v): string
    {
        return strtr($v, ['0' => '๐', '1' => '๑', '2' => '๒', '3' => '๓', '4' => '๔', '5' => '๕', '6' => '๖', '7' => '๗', '8' => '๘', '9' => '๙']);
    }
}
