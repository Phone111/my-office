<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\SchoolAgeChild;
use Modules\Core\Models\Unit;

/**
 * ระบบสิทธิและโอกาสทางการศึกษา — ทะเบียนประชากรวัยเรียน + ติดตามการเข้าเรียน
 */
class OpportunityController extends Controller
{
    private function myUnit(Request $request): ?int
    {
        return $request->user()->unit_id;
    }

    /** admin/เขต เห็น+จัดการได้ทุกหน่วยงาน */
    private function isOverseer(Request $request): bool
    {
        return $request->user()->hasAnyRole(['admin', 'area_admin']);
    }

    /** จัดการได้เฉพาะข้อมูลของหน่วยงานตน (ยกเว้น admin/เขต) */
    private function guardScope(Request $request, SchoolAgeChild $child): void
    {
        abort_unless($this->isOverseer($request) || (int) $child->unit_id === (int) $request->user()->unit_id, 403, 'จัดการได้เฉพาะข้อมูลของหน่วยงานท่าน');
    }

    public function index(Request $request): Response
    {
        $overseer = $this->isOverseer($request);
        $myUnit = $request->user()->unit_id;
        $scope = fn ($x) => $overseer ? $x : $x->where('unit_id', $myUnit);

        $q = $scope(SchoolAgeChild::with('serviceSchool:id,name'))->latest();
        if ($ag = $request->input('age_group')) {
            $q->where('age_group', $ag);
        }
        if ($request->input('status') === 'enrolled') {
            $q->where('enrolled', true);
        } elseif ($request->input('status') === 'not') {
            $q->where('enrolled', false);
        }

        $rows = $q->limit(500)->get()->map(fn (SchoolAgeChild $c) => [
            'id' => $c->id,
            'fullname' => trim($c->prename.$c->name.' '.$c->surname),
            'citizen_id' => $c->citizen_id,
            'age_group' => SchoolAgeChild::AGE_GROUPS[$c->age_group] ?? $c->age_group,
            'address' => trim(implode(' ', array_filter([$c->address, $c->tambon, $c->amphoe, $c->province]))),
            'service_school' => $c->serviceSchool?->name,
            'enrolled' => $c->enrolled,
            'enroll_school' => $c->enroll_school,
            'reason' => $c->non_enroll_reason ? (SchoolAgeChild::REASONS[$c->non_enroll_reason] ?? $c->non_enroll_reason) : null,
        ]);

        $all = $scope(SchoolAgeChild::query());

        return Inertia::render('Core::Opportunity/Index', [
            'rows' => $rows,
            'filters' => ['age_group' => $request->input('age_group'), 'status' => $request->input('status')],
            'ageGroups' => collect(SchoolAgeChild::AGE_GROUPS)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'reasons' => collect(SchoolAgeChild::REASONS)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'schools' => Unit::schools()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name'])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            'stats' => [
                'total' => (clone $all)->count(),
                'enrolled' => (clone $all)->where('enrolled', true)->count(),
                'not_enrolled' => (clone $all)->where('enrolled', false)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $v = $this->validateData($request);
        $v['unit_id'] = $this->myUnit($request);
        abort_if($v['unit_id'] === null, 422, 'บัญชีของท่านยังไม่ได้สังกัดหน่วยงาน');
        $v['created_by'] = $request->user()->id;
        SchoolAgeChild::create($v);

        return back()->with('success', 'เพิ่มข้อมูลประชากรวัยเรียนเรียบร้อย');
    }

    public function update(Request $request, SchoolAgeChild $child): RedirectResponse
    {
        $this->guardScope($request, $child);
        $child->update($this->validateData($request));

        return back()->with('success', 'อัปเดตข้อมูลเรียบร้อย');
    }

    public function destroy(Request $request, SchoolAgeChild $child): RedirectResponse
    {
        $this->guardScope($request, $child);
        $child->delete();

        return back()->with('success', 'ลบข้อมูลแล้ว');
    }

    private function validateData(Request $request): array
    {
        $v = $request->validate([
            'citizen_id' => ['nullable', 'string', 'regex:/^\d{13}$/'],
            'prename' => ['nullable', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:100'],
            'surname' => ['nullable', 'string', 'max:100'],
            'birthdate' => ['nullable', 'date'],
            'age_group' => ['required', 'in:'.implode(',', array_keys(SchoolAgeChild::AGE_GROUPS))],
            'address' => ['nullable', 'string', 'max:255'],
            'tambon' => ['nullable', 'string', 'max:100'],
            'amphoe' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'service_school_id' => ['nullable', 'integer', 'exists:units,id'],
            'enrolled' => ['boolean'],
            'enroll_school' => ['nullable', 'string', 'max:255'],
            'non_enroll_reason' => ['nullable', 'in:'.implode(',', array_keys(SchoolAgeChild::REASONS))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        // ถ้าเข้าเรียนแล้ว ล้างสาเหตุไม่เข้าเรียน
        if (! empty($v['enrolled'])) {
            $v['non_enroll_reason'] = null;
        }

        return $v;
    }

    public function report(Request $request): Response
    {
        $overseer = $this->isOverseer($request);
        $base = SchoolAgeChild::query()->when(! $overseer, fn ($x) => $x->where('unit_id', $request->user()->unit_id));

        $byAge = collect(SchoolAgeChild::AGE_GROUPS)->map(fn ($label, $key) => [
            'group' => $label,
            'total' => (clone $base)->where('age_group', $key)->count(),
            'enrolled' => (clone $base)->where('age_group', $key)->where('enrolled', true)->count(),
            'not' => (clone $base)->where('age_group', $key)->where('enrolled', false)->count(),
        ])->values();

        $byReason = collect(SchoolAgeChild::REASONS)->map(fn ($label, $key) => [
            'reason' => $label,
            'count' => (clone $base)->where('enrolled', false)->where('non_enroll_reason', $key)->count(),
        ])->filter(fn ($r) => $r['count'] > 0)->values();

        return Inertia::render('Core::Opportunity/Report', [
            'byAge' => $byAge,
            'byReason' => $byReason,
            'overall' => [
                'total' => (clone $base)->count(),
                'enrolled' => (clone $base)->where('enrolled', true)->count(),
                'not' => (clone $base)->where('enrolled', false)->count(),
            ],
        ]);
    }
}
