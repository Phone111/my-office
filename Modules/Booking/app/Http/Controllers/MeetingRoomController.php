<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Booking\Models\MeetingRoom;

class MeetingRoomController extends Controller
{
    /**
     * จัดการห้องประชุม (เฉพาะ admin)
     */
    public function index(): Response
    {
        return Inertia::render('Booking::MeetingRooms', [
            'rooms' => MeetingRoom::latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        MeetingRoom::create($this->validateData($request));

        return back()->with('success', 'เพิ่มห้องประชุมเรียบร้อยแล้ว');
    }

    public function update(Request $request, MeetingRoom $meetingRoom): RedirectResponse
    {
        $meetingRoom->update($this->validateData($request, $meetingRoom));

        return back()->with('success', 'แก้ไขข้อมูลห้องประชุมเรียบร้อยแล้ว');
    }

    public function destroy(MeetingRoom $meetingRoom): RedirectResponse
    {
        $meetingRoom->delete();

        return back()->with('success', 'ลบห้องประชุมเรียบร้อยแล้ว');
    }

    private function validateData(Request $request, ?MeetingRoom $room = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('meeting_rooms', 'name')->ignore($room?->id)],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);
    }
}
