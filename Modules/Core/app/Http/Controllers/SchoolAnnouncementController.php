<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\SchoolAnnouncement;
use Modules\Saraban\Services\NumberRegisterService;

/**
 * ระบบประกาศโรงเรียน (ทางการ) — ออกเลขที่รันต่อปี + แนบไฟล์
 */
class SchoolAnnouncementController extends Controller
{
    public function __construct(private readonly NumberRegisterService $numbers)
    {
    }

    /** ทะเบียนประกาศ — เฉพาะหน่วยงานตน (admin/เขต เห็นทุกหน่วยงาน) */
    public function index(Request $request): Response
    {
        $overseer = $request->user()->hasAnyRole(['admin', 'area_admin']);
        $myUnit = $request->user()->unit_id;
        $items = SchoolAnnouncement::with('creator:id,name')
            ->when(! $overseer, fn ($q) => $q->where('unit_id', $myUnit))
            ->orderByDesc('year')->orderByDesc('number')
            ->limit(500)
            ->get()
            ->map(fn (SchoolAnnouncement $a) => [
                'id' => $a->id,
                'number' => $a->number,
                'year' => $a->year,
                'ref' => sprintf('%d/%d', $a->number, $a->year),
                'title' => $a->title,
                'announced_thai' => $a->announced_date->locale('th')->translatedFormat('j F').' '.($a->announced_date->year + 543),
                'attachments' => collect($a->attachments ?? [])
                    ->map(fn ($p, $i) => ['name' => 'ไฟล์แนบ '.($i + 1), 'url' => asset('storage/'.$p)])
                    ->values(),
                'author' => $a->creator?->name,
            ]);

        return Inertia::render('Core::Registry/Announcements', ['items' => $items]);
    }

    /** ฟอร์มออกประกาศ */
    public function create(Request $request): Response
    {
        $year = $this->numbers->buddhistYear();
        $unit = $request->user()->unit_id;
        $next = (int) (SchoolAnnouncement::where('unit_id', $unit)->where('year', $year)->max('number') ?? 0) + 1;

        return Inertia::render('Core::Registry/AnnouncementCreate', [
            'nextNumber' => $next,
            'year' => $year,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'announced_date' => ['required', 'date'],
            'files' => ['array', 'max:4'],
            'files.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);

        $year = (int) Carbon::parse($data['announced_date'])->year + 543;
        $unit = $request->user()->unit_id;
        // เลขที่รันแยกตามหน่วยงาน/ปี (ล็อกแถว ตั้งต้นจากเลขสูงสุดเดิม)
        $number = $this->numbers->nextScoped(
            "announcement:{$unit}",
            $year,
            fn () => (int) (SchoolAnnouncement::where('unit_id', $unit)->where('year', $year)->max('number') ?? 0),
        );

        $paths = [];
        foreach ((array) $request->file('files', []) as $file) {
            if ($file) {
                $paths[] = $file->store('announcements', 'public');
            }
        }

        SchoolAnnouncement::create([
            'unit_id' => $unit,
            'number' => $number,
            'year' => $year,
            'title' => $data['title'],
            'announced_date' => $data['announced_date'],
            'attachments' => $paths,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('reports.registry.announcements')
            ->with('success', "ออกประกาศเรียบร้อยแล้ว (ฉบับที่ {$number}/{$year})");
    }
}
