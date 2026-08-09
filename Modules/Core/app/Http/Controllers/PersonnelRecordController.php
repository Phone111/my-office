<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\PersonnelDecoration;
use Modules\Core\Models\PersonnelProfile;

/**
 * ทะเบียนประวัติบุคลากร (ก.พ.7) + วิทยฐานะ + เครื่องราชอิสริยาภรณ์
 * เขต/แอดมิน = ทุกหน่วยงาน · อื่นๆ = หน่วยงานตน
 */
class PersonnelRecordController extends Controller
{
    private function overseer(Request $request): bool
    {
        return $request->user()->hasAnyRole(['admin', 'area_admin']);
    }

    private function thai($d): ?string
    {
        if (! $d) {
            return null;
        }
        $m = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $d->day.' '.$m[$d->month].' '.($d->year + 543);
    }

    public function index(Request $request): Response
    {
        $overseer = $this->overseer($request);
        $scope = $overseer ? null : $request->user()->unit_id;

        $users = User::with(['position:id,name', 'personnelProfile'])
            ->when($scope, fn ($q) => $q->where('unit_id', $scope))
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'position' => $u->position?->name,
                'standing' => $u->personnelProfile?->academic_standing,
                'appointed' => $this->thai($u->personnelProfile?->appointed_date),
                'has_profile' => $u->personnelProfile !== null,
            ]);

        // สรุปจำนวนตามวิทยฐานะ
        $standingSummary = $users->groupBy(fn ($u) => $u['standing'] ?: 'ยังไม่ระบุ')
            ->map->count()->sortDesc();

        return Inertia::render('Core::Personnel/Records', [
            'users' => $users,
            'standingSummary' => $standingSummary,
            'total' => $users->count(),
            'withProfile' => $users->where('has_profile', true)->count(),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $this->authorizeUnit($request, $user);
        $profile = PersonnelProfile::firstOrNew(['user_id' => $user->id]);
        $decorations = PersonnelDecoration::where('user_id', $user->id)->orderByDesc('year')->get(['id', 'name', 'year']);

        return Inertia::render('Core::Personnel/Record', [
            'person' => [
                'id' => $user->id,
                'name' => $user->name,
                'position' => $user->load('position:id,name')->position?->name,
                'unit' => $user->load('unit:id,name')->unit?->name,
            ],
            'profile' => [
                'citizen_id' => $profile->citizen_id,
                'birthdate' => $profile->birthdate?->format('Y-m-d'),
                'gender' => $profile->gender,
                'appointed_date' => $profile->appointed_date?->format('Y-m-d'),
                'education_level' => $profile->education_level,
                'education_major' => $profile->education_major,
                'academic_standing' => $profile->academic_standing,
                'academic_standing_date' => $profile->academic_standing_date?->format('Y-m-d'),
                'rank' => $profile->rank,
                'address' => $profile->address,
                'note' => $profile->note,
            ],
            'decorations' => $decorations,
            'standings' => PersonnelProfile::STANDINGS,
            'educationLevels' => PersonnelProfile::EDUCATION_LEVELS,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUnit($request, $user);
        $v = $request->validate([
            'citizen_id' => ['nullable', 'string', 'regex:/^\d{13}$/'],
            'birthdate' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:M,F'],
            'appointed_date' => ['nullable', 'date'],
            'education_level' => ['nullable', 'string', 'max:50'],
            'education_major' => ['nullable', 'string', 'max:150'],
            'academic_standing' => ['nullable', 'string', 'max:80'],
            'academic_standing_date' => ['nullable', 'date'],
            'rank' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        PersonnelProfile::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($v, ['updated_by' => $request->user()->id]),
        );

        return back()->with('success', 'บันทึกทะเบียนประวัติเรียบร้อยแล้ว');
    }

    public function addDecoration(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUnit($request, $user);
        $v = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'year' => ['nullable', 'integer', 'min:2400', 'max:2700'],
        ]);

        PersonnelDecoration::create(['user_id' => $user->id, 'name' => $v['name'], 'year' => $v['year'] ?? null]);

        return back()->with('success', 'เพิ่มเครื่องราชอิสริยาภรณ์แล้ว');
    }

    public function removeDecoration(Request $request, PersonnelDecoration $decoration): RedirectResponse
    {
        $this->authorizeUnit($request, $decoration->user);

        $decoration->delete();

        return back()->with('success', 'ลบรายการแล้ว');
    }

    /** เขต/แอดมิน = ทุกคน · อื่นๆ = เฉพาะหน่วยงานตน */
    private function authorizeUnit(Request $request, User $user): void
    {
        abort_unless(
            $this->overseer($request) || $user->unit_id === $request->user()->unit_id,
            403,
            'เข้าถึงได้เฉพาะบุคลากรในหน่วยงานของท่าน'
        );
    }
}
