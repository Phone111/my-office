<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Announcement\Models\News;
use Modules\Booking\Models\MeetingRoom;
use Modules\Booking\Models\Vehicle;
use Modules\Core\Models\Department;
use Modules\Core\Models\Group;
use Modules\Core\Models\Position;
use Modules\Core\Models\Signature;

/**
 * หน้าหลักผู้ดูแลระบบ — ศูนย์รวมระบบจัดการ (card hub) พร้อมสถิติย่อ
 */
class AdminHomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Core::Admin/Home', [
            'stats' => [
                'admins' => User::role('system_admin')->count(),
                'personnel' => User::count(),
                'signatures' => Signature::count(),
                'groups' => Group::count(),
                'departments' => Department::count(),
                'positions' => Position::count(),
                'news' => News::count(),
                'vehicles' => Vehicle::count(),
                'meeting_rooms' => MeetingRoom::count(),
            ],
        ]);
    }
}
