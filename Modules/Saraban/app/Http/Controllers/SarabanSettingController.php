<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Models\DocumentCounter;

/**
 * ตั้งค่าระบบงานสารบรรณ — จัดการเลขทะเบียนที่รันอัตโนมัติ (DocumentCounter)
 */
class SarabanSettingController extends Controller
{
    /** เล่มทะเบียนที่ระบบใช้ + ป้ายชื่อ */
    private function books(): array
    {
        return array_merge(
            Document::CATEGORIES,
            ['certificate' => 'เลขเกียรติบัตร'],
        );
    }

    public function index(): Response
    {
        $counters = DocumentCounter::orderBy('book')->orderByDesc('year')->get()
            ->map(fn (DocumentCounter $c) => [
                'id' => $c->id,
                'book' => $c->book,
                'book_label' => $this->books()[$c->book] ?? $c->book,
                'year' => $c->year,
                'last_no' => $c->last_no,
            ]);

        return Inertia::render('Saraban::Admin/NumberSettings', [
            'counters' => $counters,
            'books' => $this->books(),
            'prefixes' => Document::NUMBER_PREFIXES,
        ]);
    }

    public function show(DocumentCounter $counter): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            ...$counter->toArray(),
            'book_label' => $this->books()[$counter->book] ?? $counter->book,
        ]);
    }

    /**
     * สร้างเล่มทะเบียนใหม่ (กำหนดเลขเริ่มต้น)
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'book' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:2400', 'max:2700'],
            'last_no' => ['required', 'integer', 'min:0'],
        ]);

        DocumentCounter::updateOrCreate(
            ['book' => $validated['book'], 'year' => $validated['year']],
            ['last_no' => $validated['last_no']],
        );

        return back()->with('success', 'บันทึกการตั้งค่าเลขทะเบียนเรียบร้อยแล้ว');
    }

    /**
     * ปรับเลขล่าสุดของเล่มทะเบียน
     */
    public function update(Request $request, DocumentCounter $counter): RedirectResponse
    {
        $validated = $request->validate([
            'last_no' => ['required', 'integer', 'min:0'],
        ]);

        $counter->update(['last_no' => $validated['last_no']]);

        return back()->with('success', 'ปรับเลขทะเบียนเรียบร้อยแล้ว');
    }

    public function destroy(DocumentCounter $counter): RedirectResponse
    {
        $counter->delete();

        return back()->with('success', 'ลบเล่มทะเบียนเรียบร้อยแล้ว');
    }
}
