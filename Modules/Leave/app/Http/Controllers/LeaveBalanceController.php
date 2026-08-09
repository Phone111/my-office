<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Leave\Models\LeaveBalance;
use Modules\Leave\Models\LeaveType;

/**
 * บันทึกวันลาสะสม (AMSS ส่วน 9) — เจ้าหน้าที่วันลาตั้งวันลาที่มีสิทธิ์ (สะสม + ประจำปี)
 * ต่อบุคคล/ประเภท/ปีงบประมาณ พ.ศ. — โดยเฉพาะลาพักผ่อนสะสม
 */
class LeaveBalanceController extends Controller
{
    /** ปีงบประมาณ พ.ศ. ปัจจุบัน (1 ต.ค.–30 ก.ย.) */
    private function fiscalBeYear(): int
    {
        $now = now();
        $endYear = $now->month >= 10 ? $now->year + 1 : $now->year;

        return $endYear + 543;
    }

    public function index(Request $request): Response
    {
        $year = $this->fiscalBeYear();
        $me = $request->user();

        // ประเภทลาที่มีโควต้า (เช่น ลาพักผ่อน) เท่านั้น
        $types = LeaveType::where('is_active', true)->where('default_days', '>', 0)
            ->orderBy('id')->get(['id', 'name', 'default_days']);

        // ขอบเขตบุคลากร: admin เห็นทุกหน่วยงาน, อื่น ๆ เฉพาะหน่วยงานตน
        $usersQ = User::orderBy('name');
        if (! $me->hasAnyRole(['admin', 'area_admin'])) {
            $usersQ->where('unit_id', $me->unit_id);
        }
        $users = $usersQ->get(['id', 'name']);

        $balances = LeaveBalance::where('year', $year)
            ->whereIn('user_id', $users->pluck('id'))
            ->get()
            ->keyBy(fn (LeaveBalance $b) => $b->user_id.'-'.$b->leave_type_id);

        $rows = $users->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'types' => $types->mapWithKeys(function (LeaveType $t) use ($balances, $u) {
                $b = $balances->get($u->id.'-'.$t->id);

                return [$t->id => [
                    'entitled' => $b ? (float) $b->entitled_days : (float) $t->default_days,
                    'used' => $b ? (float) $b->used_days : 0.0,
                    'remaining' => $b ? $b->remaining() : (float) $t->default_days,
                ]];
            }),
        ]);

        return Inertia::render('Leave::Admin/LeaveBalances', [
            'year' => $year,
            'types' => $types->map(fn (LeaveType $t) => ['id' => $t->id, 'name' => $t->name, 'default_days' => (float) $t->default_days]),
            'rows' => $rows,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $year = $this->fiscalBeYear();
        $me = $request->user();

        $v = $request->validate([
            'entries' => ['required', 'array'],
            'entries.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'entries.*.leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'entries.*.entitled_days' => ['required', 'numeric', 'min:0', 'max:365'],
        ]);

        $scoped = $me->hasAnyRole(['admin', 'area_admin'])
            ? null
            : User::where('unit_id', $me->unit_id)->pluck('id')->all();

        $saved = 0;
        foreach ($v['entries'] as $e) {
            // กันแก้ข้ามหน่วยงาน
            if ($scoped !== null && ! in_array((int) $e['user_id'], $scoped, true)) {
                continue;
            }
            LeaveBalance::updateOrCreate(
                ['user_id' => $e['user_id'], 'leave_type_id' => $e['leave_type_id'], 'year' => $year],
                ['entitled_days' => $e['entitled_days']],
            );
            $saved++;
        }

        return back()->with('success', "บันทึกวันลาสะสม {$saved} รายการ (ปีงบประมาณ {$year})");
    }
}
