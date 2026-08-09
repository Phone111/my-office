<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Unit;

/**
 * จัดการหน่วยงาน — สำนักงานเขต + โรงเรียนในสังกัด (Phase 1 ระบบเขต)
 */
class UnitController extends Controller
{
    public function index(): Response
    {
        $area = Unit::area()->withCount('users')->first();

        $schools = Unit::schools()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Unit $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'code' => $u->code,
                'book_prefix' => $u->book_prefix,
                'address' => $u->address,
                'phone' => $u->phone,
                'is_active' => $u->is_active,
                'users_count' => $u->users_count,
            ]);

        return Inertia::render('Core::Units', [
            'area' => $area ? [
                'id' => $area->id,
                'name' => $area->name,
                'code' => $area->code,
                'book_prefix' => $area->book_prefix,
                'address' => $area->address,
                'phone' => $area->phone,
                'users_count' => $area->users_count,
            ] : null,
            'schools' => $schools,
            'summary' => [
                'schools' => $schools->count(),
                'active' => $schools->where('is_active', true)->count(),
            ],
        ]);
    }

    /** แก้ไขข้อมูลสำนักงานเขต */
    public function updateArea(Request $request, Unit $unit): RedirectResponse
    {
        abort_unless($unit->type === Unit::TYPE_AREA, 404);
        $unit->update($this->validateData($request, false, $unit->id));

        return back()->with('success', 'บันทึกข้อมูลสำนักงานเขตแล้ว');
    }

    /** เพิ่มโรงเรียนในสังกัด */
    public function store(Request $request): RedirectResponse
    {
        $area = Unit::area()->first();
        Unit::create([
            ...$this->validateData($request),
            'type' => Unit::TYPE_SCHOOL,
            'parent_id' => $area?->id,
        ]);

        return back()->with('success', 'เพิ่มโรงเรียนในสังกัดแล้ว');
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        abort_unless($unit->type === Unit::TYPE_SCHOOL, 404);
        $unit->update($this->validateData($request, true, $unit->id));

        return back()->with('success', 'แก้ไขข้อมูลโรงเรียนแล้ว');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        abort_unless($unit->type === Unit::TYPE_SCHOOL, 404);
        if ($unit->users()->exists()) {
            return back()->with('error', 'ลบไม่ได้ — ยังมีผู้ใช้สังกัดโรงเรียนนี้');
        }
        $unit->delete();

        return back()->with('success', 'ลบโรงเรียนแล้ว');
    }

    private function validateData(Request $request, bool $withActive = true, ?int $ignoreId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('units', 'code')->ignore($ignoreId)],
            'book_prefix' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
        if ($withActive) {
            $rules['is_active'] = ['boolean'];
        }

        return $request->validate($rules);
    }
}
