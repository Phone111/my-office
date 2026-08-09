<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        // ผูกเวอร์ชันกับ hash ของ Vite manifest — เมื่อ build ใหม่ Inertia จะบังคับ
        // โหลด asset ใหม่อัตโนมัติ (กันปัญหา SPA ค้าง JS เก่าหลัง deploy/build)
        foreach (['build/.vite/manifest.json', 'build/manifest.json'] as $p) {
            if (is_file(public_path($p))) {
                return md5_file(public_path($p));
            }
        }

        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                // รายชื่อ role ของผู้ใช้ (role ใหม่ 5 ระดับ + alias ชื่อเดิม) — frontend ใช้ควบคุมเมนู
                'roles' => $user ? $user->getRoleNamesExpanded() : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'importErrors' => fn () => $request->session()->get('importErrors'),
            ],
            // จำนวนหนังสือเวียนเข้าใหม่ (ยังไม่อ่าน) — ใช้แสดงป้ายบนเมนูแฟ้มหนังสือเวียน
            'circularUnread' => fn () => $user
                ? \Modules\Saraban\Models\Circular::whereJsonContains('target_users', $user->id)
                    ->where(fn ($q) => $q->whereNull('read_by')->orWhereJsonDoesntContain('read_by', $user->id))
                    ->where(fn ($q) => $q->whereNull('filed_by')->orWhereJsonDoesntContain('filed_by', $user->id))
                    ->count()
                : 0,
            // จำนวนเอกสารส่วนตัวที่ได้รับแต่ยังไม่จัดเก็บ — ป้ายบนเมนูแฟ้มรับเอกสาร
            'personalUnread' => fn () => $user
                ? \Modules\Saraban\Models\PersonalDocument::where('recipient_id', $user->id)
                    ->whereNull('filed_at')->count()
                : 0,
            // จำนวนคำขอลาที่ยังไม่เสนอแฟ้ม (ร่าง) — ใช้แสดงป้ายบนเมนูแฟ้มการลา
            'leaveFolderCount' => fn () => $user
                ? \Modules\Leave\Models\LeaveRequest::where('user_id', $user->id)
                    ->where('status', \Modules\Leave\Models\LeaveRequest::STATUS_DRAFT)
                    ->count()
                : 0,
            // จำนวนใบลาที่รอ "ฉัน" ดำเนินการ (เจ้าหน้าที่วันลา/ผู้อนุญาต) — ป้ายบนเมนูตรวจสอบวันลา
            'leaveInboxCount' => fn () => $user && $user->hasAnyRole(['director', 'deputy_director', 'head_of_department', 'head_of_subject', 'leave_officer'])
                ? \Modules\Leave\Models\LeaveRequestRoute::where('approver_id', $user->id)
                    ->where('status', 'pending')
                    ->count()
                : 0,
            // จำนวนคำขอไปราชการที่รอ "ฉัน" ดำเนินการ — ป้ายบนเมนูตรวจการไปราชการ
            'tripInboxCount' => fn () => $user && $user->hasAnyRole(['director', 'deputy_director', 'head_of_department', 'head_of_subject'])
                ? \Modules\Leave\Models\OfficialTripRoute::where('approver_id', $user->id)
                    ->where('status', 'pending')
                    ->count()
                : 0,
            // จำนวนคำขอใช้รถที่รอ "ฉัน" ดำเนินการ/ติดตาม — ป้ายบนเมนูแฟ้มขอใช้รถยนต์
            'vehicleFlowCount' => function () use ($user) {
                if (! $user) {
                    return 0;
                }
                $isOfficer = $user->hasAnyRole(['vehicle_booking_officer', 'admin']);
                $isApprover = $user->hasAnyRole(['director', 'deputy_director', 'admin']);

                return \Modules\Booking\Models\Booking::where('bookable_type', \Modules\Booking\Models\Vehicle::class)
                    ->where(function ($q) use ($user, $isOfficer, $isApprover) {
                        $q->where(fn ($w) => $w->where('user_id', $user->id)
                            ->whereIn('status', ['pending', 'submitted', 'assigned', 'rejected']));
                        if ($isOfficer) {
                            $q->orWhere('status', 'submitted');
                        }
                        if ($isApprover) {
                            $q->orWhere('status', 'assigned');
                        }
                    })
                    ->count();
            },
            // จำนวนเอกสารสารบรรณที่รอ "ฉัน" ดำเนินการ แยกตามกลุ่มแฟ้ม (บันทึกเสนอ/หนังสือราชการ/...)
            // ใช้แยกเมนูซ้าย + ป้าย + ซ่อนเมื่อว่าง
            'inboxFolders' => function () use ($user) {
                $folders = collect(\Modules\Saraban\Models\Document::INBOX_FOLDERS)->map(fn () => 0)->all();
                if (! $user || ! $user->hasAnyRole(['director', 'deputy_director', 'head_of_department', 'head_of_subject'])) {
                    return $folders;
                }
                $byCat = \Modules\Saraban\Models\DocumentRoute::where('approver_id', $user->id)
                    ->where('status', 'pending')
                    ->with('document:id,category')
                    ->get()
                    ->groupBy(fn ($r) => $r->document?->category)
                    ->map->count();
                foreach (\Modules\Saraban\Models\Document::INBOX_FOLDERS as $key => $f) {
                    $folders[$key] = collect($f['categories'])->sum(fn ($c) => $byCat[$c] ?? 0);
                }

                return $folders;
            },
            // จำนวนหนังสือส่งที่ออกเลขแล้วแต่ยังไม่แนบไฟล์ (ของฉัน) — ป้ายแฟ้มรอแนบไฟล์ส่ง
            'outgoingPendingCount' => fn () => $user && $user->hasAnyRole(['saraban', 'secretary', 'admin'])
                ? \Modules\Saraban\Models\Document::where('category', \Modules\Saraban\Models\Document::CATEGORY_OUTGOING)
                    ->where('creator_id', $user->id)
                    ->whereNull('file_path')
                    ->where(fn ($q) => $q->whereNull('attachments')->orWhereJsonLength('attachments', 0))
                    ->count()
                : 0,
            // จำนวนตัวชี้วัด KRS/ARS ที่ฉันรับผิดชอบ (เปิดใช้งาน) — ป้าย/ซ่อนเมนูรายงานตัวชี้วัด
            'krsMyCount' => fn () => $user
                ? \Modules\Core\Models\KrsIndicator::where('reporter_id', $user->id)->where('is_active', true)->count()
                : 0,
            // หนังสือราชการระหว่างหน่วยงานที่ส่งถึงหน่วยงานเรา รอลงทะเบียนรับ — ป้ายหนังสือรับ (เขต↔ร.ร.)
            'areaMailInbox' => fn () => $user && $user->unit_id && $user->hasAnyRole(['saraban', 'secretary', 'admin', 'school_clerk'])
                ? \Modules\Saraban\Models\InterUnitMail::where('to_unit_id', $user->unit_id)
                    ->where('status', \Modules\Saraban\Models\InterUnitMail::STATUS_SENT)
                    ->count()
                : 0,
            // จำนวนเอกสารของฉันที่ถูกตีกลับ (รอแก้ไข+เสนอใหม่) — ป้ายแฟ้มเอกสารถูกตีกลับ
            'rejectedCount' => fn () => $user
                ? \Modules\Saraban\Models\Document::where('creator_id', $user->id)
                    ->where('status', \Modules\Saraban\Models\Document::STATUS_REJECTED)
                    ->count()
                : 0,
            // จำนวนร่างของฉันที่ยังไม่ได้เสนอแฟ้ม — ป้ายแฟ้มร่างรอเสนอ
            'draftCount' => fn () => $user
                ? \Modules\Saraban\Models\Document::where('creator_id', $user->id)
                    ->where('status', \Modules\Saraban\Models\Document::STATUS_DRAFT)
                    ->count()
                : 0,
            // จำนวนคำสั่งที่ออกเลขแล้วแต่ยังไม่แนบไฟล์ (ของฉัน) — ป้ายแฟ้มรอแนบไฟล์คำสั่ง
            'orderPendingCount' => fn () => $user && $user->hasAnyRole(['saraban', 'secretary', 'admin'])
                ? \Modules\Saraban\Models\Document::where('category', \Modules\Saraban\Models\Document::CATEGORY_ORDER)
                    ->where('creator_id', $user->id)
                    ->whereNull('file_path')
                    ->where(fn ($q) => $q->whereNull('attachments')->orWhereJsonLength('attachments', 0))
                    ->count()
                : 0,
            // การแจ้งเตือน (กระดิ่งบน topbar) — จำนวนที่ยังไม่อ่าน + 6 รายการล่าสุด
            'notifications' => fn () => $user ? [
                'unread' => $user->unreadNotifications()->count(),
                'items' => $user->notifications()->latest()->limit(6)->get()->map(fn ($n) => [
                    'id' => $n->id,
                    'title' => $n->data['title'] ?? '',
                    'message' => $n->data['message'] ?? '',
                    'type' => $n->data['type'] ?? 'info',
                    'is_read' => $n->read_at !== null,
                    'created_at' => $n->created_at->diffForHumans(),
                ]),
            ] : ['unread' => 0, 'items' => []],
        ];
    }
}
