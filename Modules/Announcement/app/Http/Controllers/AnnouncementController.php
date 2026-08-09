<?php

namespace Modules\Announcement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Announcement\Models\News;

class AnnouncementController extends Controller
{
    /**
     * รายการข่าวสารทั้งหมด
     */
    public function index(): Response
    {
        $news = News::with('creator:id,name')
            ->latest()
            ->paginate(10)
            ->through(fn (News $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'content' => $item->content,
                'file_path' => $item->file_path,
                'creator' => $item->creator?->name,
                'created_at' => $item->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Announcement::Index', [
            'news' => $news,
        ]);
    }

    /**
     * ฟอร์มสร้างข่าวใหม่
     */
    public function create(): Response
    {
        return Inertia::render('Announcement::Create');
    }

    /**
     * บันทึกข่าวใหม่
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'], // สูงสุด 10MB
        ]);

        $news = new News([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'creator_id' => $request->user()->id,
        ]);

        if ($request->hasFile('file')) {
            $news->file_path = $request->file('file')->store('announcements', 'public');
        }

        $news->save();

        return redirect()
            ->route('announcements.index')
            ->with('success', 'เพิ่มข่าวสารเรียบร้อยแล้ว');
    }

    /**
     * ฟอร์มแก้ไขข่าว
     */
    public function edit(News $news): Response
    {
        return Inertia::render('Announcement::Edit', [
            'news' => [
                'id' => $news->id,
                'title' => $news->title,
                'content' => $news->content,
                'file_path' => $news->file_path,
            ],
        ]);
    }

    /**
     * อัปเดตข่าว
     */
    public function update(Request $request, News $news): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);

        $news->fill([
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        if ($request->hasFile('file')) {
            // ลบไฟล์เดิมก่อน
            if ($news->file_path) {
                Storage::disk('public')->delete($news->file_path);
            }
            $news->file_path = $request->file('file')->store('announcements', 'public');
        }

        $news->save();

        return redirect()
            ->route('announcements.index')
            ->with('success', 'แก้ไขข่าวสารเรียบร้อยแล้ว');
    }

    /**
     * ลบข่าว
     */
    public function destroy(News $news): RedirectResponse
    {
        if ($news->file_path) {
            Storage::disk('public')->delete($news->file_path);
        }

        $news->delete();

        return redirect()
            ->route('announcements.index')
            ->with('success', 'ลบข่าวสารเรียบร้อยแล้ว');
    }
}
