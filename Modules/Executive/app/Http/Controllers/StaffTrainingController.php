<?php

namespace Modules\Executive\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Executive\Models\StaffTraining;

/**
 * สรุปการอบรมของบุคลากร (เลขาฯ/รองผอ./แอดมิน)
 */
class StaffTrainingController extends Controller
{
    public function index(): Response
    {
        $trainings = StaffTraining::latest()->get()->map(fn (StaffTraining $t) => [
            'id' => $t->id,
            'staff_name' => $t->staff_name,
            'course_name' => $t->course_name,
            'organizer' => $t->organizer,
            'start_date' => $t->start_date->format('d/m/Y'),
            'end_date' => $t->end_date?->format('d/m/Y'),
            'hours' => $t->hours,
            'location' => $t->location,
            'note' => $t->note,
        ]);

        return Inertia::render('Executive::Trainings', [
            'trainings' => $trainings,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        StaffTraining::create([
            ...$this->validateData($request),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'บันทึกการอบรมเรียบร้อยแล้ว');
    }

    public function update(Request $request, StaffTraining $training): RedirectResponse
    {
        $training->update($this->validateData($request));

        return back()->with('success', 'แก้ไขข้อมูลการอบรมเรียบร้อยแล้ว');
    }

    public function destroy(StaffTraining $training): RedirectResponse
    {
        $training->delete();

        return back()->with('success', 'ลบรายการเรียบร้อยแล้ว');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'staff_name' => ['required', 'string', 'max:255'],
            'course_name' => ['required', 'string', 'max:255'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'hours' => ['required', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
