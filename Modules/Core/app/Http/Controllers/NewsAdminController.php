<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Announcement\Models\News;

/**
 * จัดการข่าวประชาสัมพันธ์ (admin) — CRUD ข่าวทั้งหมดในระบบ
 * (อยู่ในชุดเครื่องมือผู้ดูแลระบบของ Core; ใช้โมเดล News ของโมดูล Announcement)
 */
class NewsAdminController extends Controller
{
    /** หมวดหมู่ข่าว (dropdown) */
    public const CATEGORIES = [
        'ข่าวประชาสัมพันธ์',
        'ข่าวกิจกรรม',
        'ข่าวจัดซื้อจัดจ้าง',
        'ข่าวรับสมัครงาน',
        'ข่าวทั่วไป',
    ];

    public function index(): Response
    {
        $news = News::with('creator:id,name')
            ->latest()
            ->paginate(15)
            ->through(fn (News $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'category' => $n->category,
                'excerpt' => $n->excerpt,
                'content' => $n->content,
                'author' => $n->author,
                'allow_comments' => $n->allow_comments,
                'file_path' => $n->file_path,
                'creator' => $n->creator?->name,
                'created_at' => $n->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('Core::Admin/News', [
            'news' => $news,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        // ผู้บันทึก — ใช้ชื่อผู้ใช้ปัจจุบันหากไม่ได้ระบุ
        $data['author'] = $data['author'] ?: $request->user()->name;

        $news = new News([
            ...$data,
            'creator_id' => $request->user()->id,
        ]);

        if ($request->hasFile('file')) {
            $news->file_path = $request->file('file')->store('announcements', 'public');
        }

        $news->save();

        return back()->with('success', 'เพิ่มข่าวประชาสัมพันธ์เรียบร้อยแล้ว');
    }

    public function show(News $news): JsonResponse
    {
        return response()->json($news->load('creator:id,name'));
    }

    public function update(Request $request, News $news): RedirectResponse
    {
        $this->guardOwner($request, $news);
        $news->fill($this->validateData($request));

        if ($request->hasFile('file')) {
            if ($news->file_path) {
                Storage::disk('public')->delete($news->file_path);
            }
            $news->file_path = $request->file('file')->store('announcements', 'public');
        }

        $news->save();

        return back()->with('success', 'แก้ไขข่าวประชาสัมพันธ์เรียบร้อยแล้ว');
    }

    public function destroy(Request $request, News $news): RedirectResponse
    {
        $this->guardOwner($request, $news);
        if ($news->file_path) {
            Storage::disk('public')->delete($news->file_path);
        }

        $news->delete();

        return back()->with('success', 'ลบข่าวประชาสัมพันธ์เรียบร้อยแล้ว');
    }

    /** แก้ไข/ลบได้เฉพาะข่าวที่ตนสร้าง (ยกเว้น admin/เขต) */
    private function guardOwner(Request $request, News $news): void
    {
        abort_unless(
            $request->user()->hasAnyRole(['admin', 'area_admin']) || $news->creator_id === $request->user()->id,
            403,
            'แก้ไข/ลบได้เฉพาะข่าวที่ท่านสร้าง'
        );
    }

    /**
     * ลบหลายรายการพร้อมกัน (เลือกจากตาราง)
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:news,id'],
        ]);

        // non-admin ลบได้เฉพาะข่าวที่ตนสร้าง
        $overseer = $request->user()->hasAnyRole(['admin', 'area_admin']);
        $items = News::whereIn('id', $validated['ids'])
            ->when(! $overseer, fn ($q) => $q->where('creator_id', $request->user()->id))
            ->get();
        foreach ($items as $item) {
            if ($item->file_path) {
                Storage::disk('public')->delete($item->file_path);
            }
            $item->delete();
        }

        return back()->with('success', 'ลบข่าว '.$items->count().' รายการเรียบร้อยแล้ว');
    }

    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(self::CATEGORIES)],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'author' => ['nullable', 'string', 'max:255'],
            'allow_comments' => ['boolean'],
            'file' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        unset($validated['file']);

        return $validated;
    }
}
