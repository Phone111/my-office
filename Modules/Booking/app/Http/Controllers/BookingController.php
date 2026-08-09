<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\MeetingRoom;
use Modules\Booking\Models\Vehicle;
use Modules\Core\Models\Group;

class BookingController extends Controller
{
    /**
     * map ชนิดทรัพยากร -> คลาสโมเดล
     */
    private const KINDS = [
        'vehicle' => Vehicle::class,
        'room' => MeetingRoom::class,
    ];

    /**
     * หน้าจองทรัพยากร: รายการทรัพยากร + ตารางการจองล่วงหน้า + การจองของฉัน
     */
    public function index(Request $request): Response
    {
        $upcoming = Booking::with(['bookable', 'user:id,name'])
            ->active()
            ->where('end_at', '>=', Carbon::now())
            ->orderBy('start_at')
            ->get()
            ->map(fn (Booking $b) => [
                'kind' => $b->bookable_type === Vehicle::class ? 'vehicle' : 'room',
                'bookable_id' => $b->bookable_id,
                'resource_name' => $b->bookable?->name,
                'start_at' => $b->start_at->format('Y-m-d H:i'),
                'end_at' => $b->end_at->format('Y-m-d H:i'),
                'purpose' => $b->purpose,
                'user' => $b->user->name,
            ]);

        $myBookings = Booking::with('bookable')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('start_at')
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'kind_label' => $b->bookable_type === Vehicle::class ? 'รถยนต์' : 'ห้องประชุม',
                'resource_name' => $b->bookable?->name,
                'start_at' => $b->start_at->format('Y-m-d H:i'),
                'end_at' => $b->end_at->format('Y-m-d H:i'),
                'purpose' => $b->purpose,
                'status' => $b->status,
            ]);

        // ข้อมูลสำหรับปฏิทิน (ทุกการจองที่ใช้งานอยู่ ไม่ใช่เฉพาะอนาคต)
        $calendarBookings = Booking::with(['bookable', 'user:id,name'])
            ->active()
            ->orderBy('start_at')
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'kind' => $b->bookable_type === Vehicle::class ? 'vehicle' : 'room',
                'bookable_id' => $b->bookable_id,
                'resource_name' => $b->bookable?->name,
                'purpose' => $b->purpose,
                'user' => $b->user->name,
                'start_at' => $b->start_at->format('Y-m-d H:i'),
                'start_date' => $b->start_at->toDateString(),
                'end_at' => $b->end_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Booking::Index', [
            'vehicles' => Vehicle::where('is_active', true)->get(['id', 'name', 'license_plate', 'seats']),
            'rooms' => MeetingRoom::where('is_active', true)->get(['id', 'name', 'location', 'capacity']),
            'divisions' => Group::orderBy('name')->get(['id', 'name']),
            'upcoming' => $upcoming,
            'myBookings' => $myBookings,
            'calendarBookings' => $calendarBookings,
        ]);
    }

    /**
     * บันทึกการจอง พร้อมตรวจสอบการชนกันของเวลา
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kind' => ['required', 'in:vehicle,room'],
            'resource_id' => ['required', 'integer'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'purpose' => ['required', 'string', 'max:255'],
            // ฟิลด์เพิ่มเติมสำหรับจองรถ (ไม่บังคับสำหรับห้องประชุม)
            'written_date' => ['nullable', 'date'],
            'division' => ['nullable', 'string', 'max:255'],
            'companions' => ['nullable', 'string', 'max:2000'],
            'destination' => ['nullable', 'string', 'max:255'],
            'passengers' => ['nullable', 'integer', 'min:0', 'max:99'],
            'fuel_source' => ['nullable', 'in:central,project,user'],
            'attendees' => ['nullable', 'integer', 'min:0', 'max:999'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ], [
            'date.after_or_equal' => 'ไม่สามารถจองวันที่ผ่านมาแล้วได้ กรุณาเลือกวันที่ตั้งแต่วันนี้เป็นต้นไป',
            'end_date.after_or_equal' => 'วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่ม',
        ]);

        $modelClass = self::KINDS[$validated['kind']];

        // ตรวจว่าทรัพยากรมีอยู่จริงและพร้อมใช้งาน
        $resource = $modelClass::where('is_active', true)->find($validated['resource_id']);
        if (! $resource) {
            throw ValidationException::withMessages(['resource_id' => 'ไม่พบทรัพยากรที่เลือก']);
        }

        $endDate = $validated['end_date'] ?? $validated['date'];
        $startAt = Carbon::parse($validated['date'].' '.$validated['start_time']);
        $endAt = Carbon::parse($endDate.' '.$validated['end_time']);

        if ($endAt->lessThanOrEqualTo($startAt)) {
            throw ValidationException::withMessages(['end_time' => 'เวลาสิ้นสุดต้องอยู่หลังเวลาเริ่ม']);
        }

        // ตรวจสอบการชนกันของเวลา
        if (Booking::hasConflict($modelClass, $resource->id, $startAt->toDateTimeString(), $endAt->toDateTimeString())) {
            throw ValidationException::withMessages([
                'start_time' => 'ช่วงเวลานี้มีผู้จองทรัพยากรไว้แล้ว กรุณาเลือกเวลาอื่น',
            ]);
        }

        $filePath = $request->hasFile('file')
            ? $request->file('file')->store('bookings', 'public')
            : null;

        $isVehicle = $validated['kind'] === 'vehicle';

        Booking::create([
            'bookable_type' => $modelClass,
            'bookable_id' => $resource->id,
            'user_id' => $request->user()->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'purpose' => $validated['purpose'],
            // จองรถ = เข้า workflow (รอเสนอแฟ้ม) · ห้องประชุม = จองตรง
            'status' => $isVehicle ? Booking::STATUS_PENDING : Booking::STATUS_BOOKED,
            // รายละเอียดเพิ่มเติม (จองรถ)
            'written_date' => $validated['written_date'] ?? null,
            'division' => $validated['division'] ?? null,
            'companions' => $validated['companions'] ?? null,
            'destination' => $validated['destination'] ?? null,
            'passengers' => $validated['passengers'] ?? null,
            'fuel_source' => $validated['fuel_source'] ?? null,
            'attendees' => $validated['attendees'] ?? null,
            'file_path' => $filePath,
        ]);

        return $isVehicle
            ? redirect()->route('booking.vehicle-flow.index')->with('success', 'บันทึกคำขอใช้รถแล้ว — กด "ดำเนินการต่อ" เพื่อเสนอเจ้าหน้าที่จัดรถ')
            : back()->with('success', 'บันทึกการจองเรียบร้อยแล้ว');
    }

    /**
     * ยกเลิกการจอง (เฉพาะเจ้าของ)
     */
    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless(in_array($booking->status, [
            Booking::STATUS_BOOKED, Booking::STATUS_PENDING, Booking::STATUS_SUBMITTED, Booking::STATUS_ASSIGNED,
        ], true), 403);

        $booking->update(['status' => Booking::STATUS_CANCELLED]);

        return back()->with('success', 'ยกเลิกการจองเรียบร้อยแล้ว');
    }
}
