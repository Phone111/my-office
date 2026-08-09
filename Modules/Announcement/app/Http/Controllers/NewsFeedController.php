<?php

namespace Modules\Announcement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Announcement\Models\News;

/**
 * หน้าข่าวสาร/ประกาศ — อ่านได้ทุกคน (ข่าวด่วนขึ้นก่อน)
 * เพิ่ม/ลบข่าวได้เฉพาะผู้มีสิทธิ์ (รองผอ./เลขาฯ/ผอ./แอดมิน)
 */
class NewsFeedController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $canWrite = $user->hasAnyRole(['deputy_director', 'secretary', 'director', 'admin', 'group_clerk']);
        $isAdmin = $user->hasRole('admin');

        $news = News::with('creator:id,name')
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (News $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'category' => $n->category,
                'excerpt' => $n->excerpt,
                'content' => $n->content,
                'author' => $n->author ?? $n->creator?->name,
                'image_url' => $n->image_path ? asset('storage/'.$n->image_path) : null,
                'file_url' => $n->file_path ? asset('storage/'.$n->file_path) : null,
                'created_at' => $n->created_at->format('d/m/Y'),
                'is_urgent' => $n->category === 'ข่าวด่วน',
                'can_delete' => $n->creator_id === $user->id || $isAdmin,
            ])
            // ข่าวด่วนขึ้นก่อน (sort เสถียร คงลำดับล่าสุดในกลุ่มเดียวกัน)
            ->sortByDesc('is_urgent')
            ->values();

        return Inertia::render('Announcement::NewsFeed', [
            'news' => $news,
            'canWrite' => $canWrite,
            'categories' => QuickNewsController::CATEGORIES,
        ]);
    }
}
