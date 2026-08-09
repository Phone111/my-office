<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Position;

/**
 * จัดการตำแหน่ง (positions) — ประเภทตำแหน่ง / สถานะ / เรียงลำดับ
 */
class PositionController extends Controller
{
    public function index(): Response
    {
        $positions = Position::orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Position $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->type,
                'type_label' => $p->typeLabel(),
                'is_active' => $p->is_active,
                'sort_order' => $p->sort_order,
            ]);

        return Inertia::render('Core::Admin/Positions', [
            'positions' => $positions,
            'types' => collect(Position::TYPES)->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values(),
        ]);
    }

    public function show(Position $position): \Illuminate\Http\JsonResponse
    {
        return response()->json($position->loadCount('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['sort_order'] = (int) (Position::max('sort_order') ?? 0) + 1;

        Position::create($data);

        return back()->with('success', 'เพิ่มตำแหน่งเรียบร้อยแล้ว');
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        $position->update($this->validateData($request, $position->id));

        return back()->with('success', 'แก้ไขตำแหน่งเรียบร้อยแล้ว');
    }

    public function destroy(Position $position): RedirectResponse
    {
        if ($position->users()->exists()) {
            return back()->with('error', 'ไม่สามารถลบตำแหน่งที่มีบุคลากรใช้งานอยู่');
        }

        $position->delete();

        return back()->with('success', 'ลบตำแหน่งเรียบร้อยแล้ว');
    }

    /** เลื่อนลำดับขึ้น/ลง — สลับ sort_order กับแถวที่อยู่ติดกัน */
    public function move(Request $request, Position $position): RedirectResponse
    {
        $direction = $request->input('direction') === 'up' ? 'up' : 'down';

        $neighbor = $direction === 'up'
            ? Position::where('sort_order', '<', $position->sort_order)->orderByDesc('sort_order')->first()
            : Position::where('sort_order', '>', $position->sort_order)->orderBy('sort_order')->first();

        if ($neighbor) {
            $tmp = $position->sort_order;
            $position->update(['sort_order' => $neighbor->sort_order]);
            $neighbor->update(['sort_order' => $tmp]);
        }

        return back();
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('positions', 'name')->ignore($ignoreId)],
            'type' => ['required', Rule::in(array_keys(Position::TYPES))],
            'is_active' => ['boolean'],
        ]);
    }
}
