<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Unit;
use Modules\Saraban\Models\SchoolGroup;

/**
 * จัดการกลุ่มโรงเรียน (กลุ่มสถานศึกษา) — AMSS ส่วน 16 ตั้งค่าระบบ
 * เขตกำหนดชื่อกลุ่ม + เลือกโรงเรียนสมาชิก เพื่อส่งหนังสือถึงทั้งกลุ่มทีเดียว
 */
class SchoolGroupController extends Controller
{
    /** เขตของผู้ใช้ (เจ้าของกลุ่ม) */
    private function ownerUnit(Request $request): ?int
    {
        $u = $request->user();
        // admin/area_admin จัดการกลุ่มของเขตที่ตนสังกัด (หรือเขตแรก ถ้าไม่ได้สังกัด)
        if ($u->unit_id && Unit::where('id', $u->unit_id)->where('type', Unit::TYPE_AREA)->exists()) {
            return $u->unit_id;
        }

        return Unit::where('type', Unit::TYPE_AREA)->value('id');
    }

    public function index(Request $request): Response
    {
        $unit = $this->ownerUnit($request);

        $groups = SchoolGroup::where('unit_id', $unit)
            ->with('members:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (SchoolGroup $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'code' => $g->code,
                'is_active' => $g->is_active,
                'member_ids' => $g->members->pluck('id'),
                'member_count' => $g->members->count(),
            ]);

        $schools = Unit::where('type', Unit::TYPE_SCHOOL)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Unit $u) => ['id' => $u->id, 'name' => $u->name]);

        return Inertia::render('Saraban::Admin/SchoolGroups', [
            'groups' => $groups,
            'schools' => $schools,
            'ownerName' => Unit::find($unit)?->name,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $unit = $this->ownerUnit($request);
        abort_unless($unit, 422, 'ยังไม่มีหน่วยงานแบบเขตในระบบ');
        $v = $this->validateData($request);

        $group = SchoolGroup::create([
            'unit_id' => $unit,
            'name' => $v['name'],
            'code' => $v['code'] ?? null,
            'is_active' => $v['is_active'] ?? true,
        ]);
        $group->members()->sync($this->schoolMemberIds($v['member_ids'] ?? []));

        return back()->with('success', 'เพิ่มกลุ่มโรงเรียน "'.$group->name.'" เรียบร้อย');
    }

    public function update(Request $request, SchoolGroup $schoolGroup): RedirectResponse
    {
        abort_unless($schoolGroup->unit_id === $this->ownerUnit($request), 403);
        $v = $this->validateData($request);

        $schoolGroup->update([
            'name' => $v['name'],
            'code' => $v['code'] ?? null,
            'is_active' => $v['is_active'] ?? true,
        ]);
        $schoolGroup->members()->sync($this->schoolMemberIds($v['member_ids'] ?? []));

        return back()->with('success', 'แก้ไขกลุ่ม "'.$schoolGroup->name.'" เรียบร้อย');
    }

    public function destroy(Request $request, SchoolGroup $schoolGroup): RedirectResponse
    {
        abort_unless($schoolGroup->unit_id === $this->ownerUnit($request), 403);
        $name = $schoolGroup->name;
        $schoolGroup->delete();

        return back()->with('success', 'ลบกลุ่ม "'.$name.'" เรียบร้อย');
    }

    /** @return array<int, int> เฉพาะ id ที่เป็นโรงเรียนจริง (กันยัด id หน่วยงานอื่น) */
    private function schoolMemberIds(array $ids): array
    {
        return Unit::whereIn('id', $ids)->where('type', Unit::TYPE_SCHOOL)->pluck('id')->all();
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'member_ids' => ['array'],
            'member_ids.*' => ['integer', 'exists:units,id'],
        ]);
    }
}
