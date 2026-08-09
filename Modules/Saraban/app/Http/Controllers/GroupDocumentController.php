<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Models\SarabanSetting;

/**
 * เอกสารของกลุ่ม — หัวหน้ากลุ่ม/ธุรการกลุ่ม ดูเอกสารของสมาชิกในกลุ่มตัวเอง (read-only)
 * แท็บหมวด (บันทึก/รับ/ส่ง/ภายใน/คำสั่ง/ทั่วไป) + เลือกปี
 */
class GroupDocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $groupId = $user->group_id;
        $memberIds = $groupId
            ? User::where('group_id', $groupId)->pluck('id')->all()
            : [];
        $scope = $memberIds ?: [0]; // ไม่มีสมาชิก = ไม่เห็นอะไร

        $category = $request->string('category')->toString();
        if (! array_key_exists($category, Document::CATEGORIES)) {
            $category = Document::CATEGORY_MEMO;
        }

        $activeYear = (int) (SarabanSetting::get('active_year') ?: (now()->year + 543));
        $year = (int) ($request->get('year') ?: $activeYear);
        $gy = $year - 543;

        $documents = Document::with('creator:id,name')
            ->whereIn('creator_id', $scope)
            ->where('category', $category)
            ->whereYear('created_at', $gy)
            ->latest()
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'document_number' => $d->document_number,
                'title' => $d->title,
                'creator' => $d->creator?->name,
                'priority' => $d->priority ?? 'normal',
                'status' => $d->status,
                'source_name' => $d->source_name,
                'created_at' => $d->created_at->format('d/m/Y'),
            ]);

        $counts = Document::whereIn('creator_id', $scope)
            ->whereYear('created_at', $gy)
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $folders = collect(Document::CATEGORIES)
            ->except(Document::ISSUER_ONLY)
            ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label, 'count' => (int) ($counts[$key] ?? 0)])
            ->values();

        $years = Document::whereIn('creator_id', $scope)
            ->selectRaw('DISTINCT YEAR(created_at) as gy')
            ->orderByDesc('gy')
            ->pluck('gy')
            ->map(fn ($g) => (int) $g + 543)
            ->values();
        if (! $years->contains($year)) {
            $years->prepend($year);
        }

        return Inertia::render('Saraban::GroupDocuments', [
            'documents' => $documents,
            'category' => $category,
            'folders' => $folders,
            'year' => $year,
            'years' => $years,
            'groupName' => $user->group?->name,
        ]);
    }
}
