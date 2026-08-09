<?php

namespace Modules\Announcement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Announcement\Models\News;

/**
 * เขียนข่าวด่วน — สำหรับรองผอ./เลขาฯ/ผอ. โพสต์ข่าว/ประกาศได้เอง (ไม่ต้องผ่าน admin)
 */
class QuickNewsController extends Controller
{
    /** หมวดหมู่ข่าว */
    public const CATEGORIES = [
        'ข่าวประชาสัมพันธ์',
        'ข่าวด่วน',
        'ข่าวกิจกรรม',
        'ข่าวจัดซื้อจัดจ้าง',
        'ข่าวทั่วไป',
    ];

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string'],
            'allow_comments' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);

        News::create([
            'title' => $data['title'],
            'category' => $data['category'],
            'content' => $data['content'],
            'excerpt' => Str::limit(strip_tags($data['content']), 120),
            'image_path' => $request->hasFile('image')
                ? $request->file('image')->store('news', 'public')
                : null,
            'author' => $request->user()->name,
            'allow_comments' => $data['allow_comments'] ?? false,
            'file_path' => $request->hasFile('file')
                ? $request->file('file')->store('news', 'public')
                : null,
            'creator_id' => $request->user()->id,
        ]);

        return back()->with('success', 'โพสต์ข่าวเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, News $news): RedirectResponse
    {
        abort_unless($news->creator_id === $request->user()->id || $request->user()->hasRole('admin'), 403);

        $news->delete();

        return back()->with('success', 'ลบข่าวเรียบร้อยแล้ว');
    }
}
