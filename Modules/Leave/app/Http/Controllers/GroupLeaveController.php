<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Leave\Models\LeaveRequest;

/**
 * ทะเบียนลาของกลุ่ม — หัวหน้ากลุ่ม/ธุรการกลุ่ม ดูใบลาของสมาชิกในกลุ่ม (read-only)
 */
class GroupLeaveController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $gid = $user->group_id;
        // จำกัดสมาชิกกลุ่มในหน่วยงานตน (กันเห็นข้ามหน่วยเมื่อ group ซ้ำข้ามหน่วยงาน)
        $memberIds = $gid ? User::where('group_id', $gid)->where('unit_id', $user->unit_id)->pluck('id')->all() : [];
        $scope = $memberIds ?: [0];

        $thai = fn ($d) => $d ? $d->locale('th')->translatedFormat('j M').' '.($d->year + 543) : null;

        $rows = LeaveRequest::with(['user:id,name', 'leaveType:id,name'])
            ->whereIn('user_id', $scope)
            ->latest()
            ->get()
            ->map(fn (LeaveRequest $r) => [
                'id' => $r->id,
                'name' => $r->user?->name,
                'type' => $r->leaveType?->name ?? 'ลา',
                'start' => $thai($r->start_date),
                'end' => $thai($r->end_date),
                'days' => $r->total_days,
                'reason' => $r->reason,
                'status' => $r->status,
            ]);

        return Inertia::render('Leave::GroupLeave', [
            'rows' => $rows,
            'groupName' => $user->group?->name,
            'summary' => [
                'total' => $rows->count(),
                'pending' => $rows->where('status', 'pending')->count(),
                'approved' => $rows->where('status', 'approved')->count(),
                'rejected' => $rows->where('status', 'rejected')->count(),
                'cancelled' => $rows->where('status', 'cancelled')->count(),
            ],
        ]);
    }
}
