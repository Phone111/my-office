<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Saraban\Models\GroupActivity;

/**
 * ปฏิทินกิจกรรมของกลุ่ม — ธุรการกลุ่ม/หัวหน้ากลุ่มบันทึกกิจกรรมของกลุ่ม (เห็นเฉพาะกลุ่มตัวเอง)
 */
class GroupActivityController extends Controller
{
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
        $user = $request->user();
        $gid = $user->group_id;
        $seeAll = $user->hasAnyRole(['admin', 'secretary', 'director', 'deputy_director']) && ! $gid;

        $rows = GroupActivity::with(['creator:id,name', 'group:id,name'])
            ->when(! $seeAll, fn ($q) => $q->where('group_id', $gid ?: 0))
            ->orderByDesc('activity_date')
            ->get()
            ->map(fn (GroupActivity $a) => [
                'id' => $a->id,
                'date' => $a->activity_date?->toDateString(),
                'date_thai' => $this->thai($a->activity_date),
                'time_text' => $a->time_text,
                'days' => $a->days,
                'title' => $a->title,
                'detail' => $a->detail,
                'creator' => $a->creator?->name,
                'group' => $a->group?->name,
                'can_delete' => $a->created_by === $user->id || $user->hasRole('admin'),
            ]);

        return Inertia::render('Saraban::GroupActivities', [
            'activities' => $rows,
            'groupName' => $user->group?->name,
            'canManage' => (bool) $gid, // ต้องสังกัดกลุ่มจึงบันทึกได้
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->group_id, 403, 'ต้องสังกัดกลุ่มจึงบันทึกกิจกรรมได้');

        $v = $request->validate([
            'activity_date' => ['required', 'date'],
            'time_text' => ['nullable', 'string', 'max:100'],
            'days' => ['required', 'integer', 'min:1', 'max:60'],
            'title' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:2000'],
        ]);

        GroupActivity::create([
            ...$v,
            'group_id' => $request->user()->group_id,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'บันทึกกิจกรรมลงปฏิทินกลุ่มเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, GroupActivity $groupActivity): RedirectResponse
    {
        abort_unless($groupActivity->created_by === $request->user()->id || $request->user()->hasRole('admin'), 403);
        $groupActivity->delete();

        return back()->with('success', 'ลบกิจกรรมแล้ว');
    }
}
