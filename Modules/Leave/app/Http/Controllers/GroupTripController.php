<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Leave\Models\OfficialTrip;

/**
 * ทะเบียนไปราชการของกลุ่ม — หัวหน้ากลุ่ม/ธุรการกลุ่ม ดูคำขอไปราชการของสมาชิกในกลุ่ม (read-only)
 */
class GroupTripController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $gid = $user->group_id;
        // จำกัดสมาชิกกลุ่มในหน่วยงานตน (กันเห็นข้ามหน่วยเมื่อ group ซ้ำข้ามหน่วยงาน)
        $memberIds = $gid ? User::where('group_id', $gid)->where('unit_id', $user->unit_id)->pluck('id')->all() : [];
        $scope = $memberIds ?: [0];

        $thai = fn ($d) => $d ? $d->locale('th')->translatedFormat('j M').' '.($d->year + 543) : null;

        $rows = OfficialTrip::with('user:id,name')
            ->whereIn('user_id', $scope)
            ->latest()
            ->get()
            ->map(fn (OfficialTrip $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'destination' => $t->destination,
                'requester' => $t->user?->name,
                'start' => $thai($t->depart_at),
                'end' => $thai($t->return_at),
                'status' => $t->status,
            ]);

        return Inertia::render('Leave::GroupTrip', [
            'rows' => $rows,
            'groupName' => $user->group?->name,
            'summary' => [
                'total' => $rows->count(),
                'pending' => $rows->where('status', 'pending')->count(),
                'approved' => $rows->where('status', 'approved')->count(),
                'rejected' => $rows->where('status', 'rejected')->count(),
            ],
        ]);
    }
}
