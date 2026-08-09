<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Leave\Models\OfficialTrip;
use Modules\Saraban\Models\Circular;
use Modules\Saraban\Models\Document;

/**
 * ระบบสืบค้นข้อมูล — ตารางทะเบียน × ปี พ.ศ. กดที่ปีเพื่อดูรายการทะเบียนนั้นของปีนั้น
 * รวม: ทะเบียนรับ/ส่ง/คำสั่ง/บันทึกเสนอแฟ้ม (Document) + หนังสือเวียน (Circular) + ไปราชการ (OfficialTrip)
 */
class DocumentSearchController extends Controller
{
    /** นิยามทะเบียนที่สืบค้นได้ (เรียงตามที่แสดงเป็นแถว) */
    private const REGISTERS = [
        'incoming' => 'ทะเบียนรับ',
        'outgoing' => 'ทะเบียนส่ง',
        'order' => 'ทะเบียนคำสั่ง',
        'memo' => 'ทะเบียนบันทึกเสนอแฟ้ม',
        'circular' => 'ทะเบียนหนังสือเวียน',
        'trip' => 'ทะเบียนไปราชการ',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $seeAll = $user->hasAnyRole(['admin', 'saraban', 'secretary', 'director', 'deputy_director']);

        // ===== ตาราง: นับจำนวนต่อทะเบียนต่อปี พ.ศ. =====
        $matrix = [];
        $years = collect();
        foreach (array_keys(self::REGISTERS) as $key) {
            $counts = $this->yearCounts($key, $user, $seeAll); // [beYear => count]
            $matrix[$key] = $counts;
            $years = $years->merge(array_keys($counts));
        }
        $years = $years->unique()->sortDesc()->values();

        $registers = collect(self::REGISTERS)
            ->map(fn (string $label, string $key) => [
                'key' => $key,
                'label' => $label,
                'counts' => $matrix[$key], // {beYear: count}
            ])
            ->values();

        // ===== รายการของทะเบียน+ปีที่เลือก =====
        $register = (string) $request->get('register', '');
        $year = (int) $request->get('year', 0);
        $results = null;
        $resultTitle = null;
        if (array_key_exists($register, self::REGISTERS) && $year > 0) {
            $results = $this->results($register, $year, $user, $seeAll);
            $resultTitle = self::REGISTERS[$register].' ปี '.$year;
        }

        return Inertia::render('Core::DocumentSearch', [
            'registers' => $registers,
            'years' => $years,
            'selected' => ['register' => $register ?: null, 'year' => $year ?: null],
            'results' => $results,
            'resultTitle' => $resultTitle,
        ]);
    }

    /** นับจำนวนเอกสารต่อปี พ.ศ. ของทะเบียนหนึ่ง */
    private function yearCounts(string $register, $user, bool $seeAll): array
    {
        $rows = $this->baseQuery($register, $user, $seeAll)
            ->selectRaw('YEAR(created_at) as gy, COUNT(*) as c')
            ->groupBy('gy')
            ->pluck('c', 'gy');

        $out = [];
        foreach ($rows as $gy => $c) {
            $out[(int) $gy + 543] = (int) $c;
        }

        return $out;
    }

    /** รายการเอกสารของทะเบียน+ปี (normalize ให้คอลัมน์เหมือนกัน) */
    private function results(string $register, int $beYear, $user, bool $seeAll): array
    {
        $gy = $beYear - 543;
        $thai = fn ($d) => $d ? $d->locale('th')->translatedFormat('j M').' '.($d->year + 543) : null;

        return $this->baseQuery($register, $user, $seeAll)
            ->whereYear('created_at', $gy)
            ->latest()
            ->limit(500)
            ->get()
            ->map(function ($m) use ($register, $thai) {
                if ($register === 'circular') {
                    return [
                        'number' => '—',
                        'title' => $m->title,
                        'date_thai' => $thai($m->created_at),
                        'from' => $m->sender?->name,
                        'to' => 'บุคลากร '.count($m->target_users ?? []).' คน',
                        'link' => route('saraban.circulars.show', $m->id),
                    ];
                }
                if ($register === 'trip') {
                    return [
                        'number' => $m->document_number ?? '—',
                        'title' => $m->title,
                        'date_thai' => $thai($m->created_at),
                        'from' => $m->user?->name,
                        'to' => $m->destination,
                        'link' => route('official-trips.show', $m->id),
                    ];
                }

                // Document (incoming/outgoing/order/memo)
                return [
                    'number' => $m->document_number ?? '—',
                    'title' => $m->title,
                    'date_thai' => $thai($m->source_date ?? $m->created_at),
                    'from' => $m->source_name ?? $m->division ?? $m->creator?->name,
                    'to' => $register === 'incoming' ? 'โรงเรียนเศรษฐบุตรบำเพ็ญ' : ($m->source_name ?? '—'),
                    'link' => route('saraban.documents.show', $m->id),
                ];
            })
            ->all();
    }

    /** Query ฐานของแต่ละทะเบียน + จำกัดสิทธิ์ */
    private function baseQuery(string $register, $user, bool $seeAll): Builder
    {
        if ($register === 'circular') {
            $q = Circular::query()->with('sender:id,name');
            if (! $seeAll) {
                $q->where(fn (Builder $w) => $w->where('sender_id', $user->id)
                    ->orWhereJsonContains('target_users', $user->id));
            }

            return $q;
        }

        if ($register === 'trip') {
            $q = OfficialTrip::query()->with('user:id,name');
            if (! $seeAll) {
                $q->where(fn (Builder $w) => $w->where('user_id', $user->id)
                    ->orWhereHas('routes', fn (Builder $r) => $r->where('approver_id', $user->id)));
            }

            return $q;
        }

        // Document categories
        $q = Document::query()->with('creator:id,name')->where('category', $register);
        if (! $seeAll) {
            $q->where(fn (Builder $w) => $w->where('creator_id', $user->id)
                ->orWhereHas('routes', fn (Builder $r) => $r->where('approver_id', $user->id)));
        }

        return $q;
    }
}
