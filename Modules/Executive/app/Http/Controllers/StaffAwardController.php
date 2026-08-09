<?php

namespace Modules\Executive\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Executive\Models\StaffAward;

/**
 * สรุปรางวัลของบุคลากร (เลขาฯ/รองผอ./แอดมิน)
 */
class StaffAwardController extends Controller
{
    public function index(): Response
    {
        $awards = StaffAward::latest()->get()->map(fn (StaffAward $a) => [
            'id' => $a->id,
            'staff_name' => $a->staff_name,
            'award_name' => $a->award_name,
            'level' => $a->level,
            'awarded_by' => $a->awarded_by,
            'awarded_date' => $a->awarded_date->format('d/m/Y'),
            'note' => $a->note,
        ]);

        return Inertia::render('Executive::Awards', [
            'awards' => $awards,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        StaffAward::create([
            ...$this->validateData($request),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'บันทึกรางวัลเรียบร้อยแล้ว');
    }

    public function update(Request $request, StaffAward $award): RedirectResponse
    {
        $award->update($this->validateData($request));

        return back()->with('success', 'แก้ไขข้อมูลรางวัลเรียบร้อยแล้ว');
    }

    public function destroy(StaffAward $award): RedirectResponse
    {
        $award->delete();

        return back()->with('success', 'ลบรายการเรียบร้อยแล้ว');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'staff_name' => ['required', 'string', 'max:255'],
            'award_name' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:100'],
            'awarded_by' => ['nullable', 'string', 'max:255'],
            'awarded_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
