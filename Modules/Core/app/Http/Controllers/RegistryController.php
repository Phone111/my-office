<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Announcement\Models\News;
use Modules\Leave\Models\LeaveRequest;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Models\DocumentCounter;

/**
 * ทะเบียนต่างๆ สำหรับผู้บริหาร/เลขาฯ (read-only)
 * คำสั่ง · ไปราชการสำนักงาน · ประกาศ · ลำดับเลขเอกสาร
 */
class RegistryController extends Controller
{
    /** ทะเบียนคำสั่ง — เอกสารหมวด "คำสั่ง" */
    public function orders(): Response
    {
        $thai = fn ($d) => $d ? $d->locale('th')->translatedFormat('j F').' '.($d->year + 543) : null;
        $uid = auth()->id();

        $orders = Document::with(['creator:id,name,group_id', 'creator.group:id,name'])
            ->where('category', Document::CATEGORY_ORDER)
            ->latest()
            ->limit(500)
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'number' => $d->document_number,
                'title' => $d->title,
                'content' => $d->content,
                'owner' => $d->division ?: ($d->creator?->group?->name ?? $d->creator?->name),
                'creator' => $d->creator?->name,
                'effective_thai' => $thai($d->effective_date),
                'order_thai' => $thai($d->source_date),
                'files' => collect($d->attachments ?? [])
                    ->map(fn ($p, $i) => ['name' => 'ไฟล์ '.($i + 1), 'url' => asset('storage/'.$p)])
                    ->when($d->file_path, fn ($c) => $c->push(['name' => 'หนังสือนำ', 'url' => asset('storage/'.$d->file_path)]))
                    ->values(),
                'has_file' => (bool) $d->file_path || ! empty($d->attachments),
                'can_edit' => $d->creator_id === $uid,
            ]);

        return Inertia::render('Core::Registry/Orders', ['orders' => $orders]);
    }

    /** ทะเบียนไปราชการสำนักงาน — คำขอไปราชการทั้งหมด */
    public function officialTrips(): Response
    {
        $trips = \Modules\Leave\Models\OfficialTrip::with('user:id,name')
            ->latest('depart_at')
            ->limit(300)
            ->get()
            ->map(fn ($t) => [
                'name' => $t->user?->name,
                'title' => $t->title,
                'destination' => $t->destination,
                'purpose' => $t->purpose,
                'start' => $t->depart_at?->format('d/m/Y'),
                'end' => $t->return_at?->format('d/m/Y'),
                'status' => $t->status,
            ]);

        return Inertia::render('Core::Registry/OfficialTrips', ['trips' => $trips]);
    }

    /** ทะเบียนประกาศ — ข่าว/ประกาศทั้งหมด (read-only) */
    public function announcements(): Response
    {
        $items = News::with('creator:id,name')
            ->latest()
            ->limit(300)
            ->get()
            ->map(fn (News $n) => [
                'title' => $n->title,
                'category' => $n->category,
                'excerpt' => $n->excerpt,
                'content' => $n->content,
                'author' => $n->author ?? $n->creator?->name,
                'file_url' => $n->file_path ? asset('storage/'.$n->file_path) : null,
                'created_at' => $n->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('Core::Registry/Announcements', ['items' => $items]);
    }

    /** ทะเบียนลำดับเลขเอกสาร — ตัวนับเลขที่หนังสือแต่ละประเภท/ปี */
    public function documentNumbers(): Response
    {
        $labels = Document::CATEGORIES + ['certificate' => 'เกียรติบัตร'];

        $counters = DocumentCounter::orderByDesc('year')->orderBy('book')->get()
            ->map(fn (DocumentCounter $c) => [
                'book' => $labels[$c->book] ?? $c->book,
                'year' => $c->year,
                'last_no' => $c->last_no,
            ]);

        return Inertia::render('Core::Registry/DocumentNumbers', ['counters' => $counters]);
    }
}
