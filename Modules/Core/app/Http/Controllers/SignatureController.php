<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Signature;

/**
 * ลายเซ็นบุคลากร — อัปโหลด/แทนที่/ลบไฟล์ลายเซ็นของผู้ใช้แต่ละคน
 */
class SignatureController extends Controller
{
    public function index(): Response
    {
        $users = User::with(['signature:id,user_id,file_path', 'group:id,name', 'department:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'group' => $u->group?->name,
                'department' => $u->department?->name,
                'signature_path' => $u->signature?->file_path,
                'signature_id' => $u->signature?->id,
            ]);

        return Inertia::render('Core::Admin/Signatures', [
            'users' => $users,
        ]);
    }

    public function show(Signature $signature): \Illuminate\Http\JsonResponse
    {
        return response()->json($signature->load('user:id,name'));
    }

    /**
     * อัปโหลด/แทนที่ลายเซ็นของผู้ใช้
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'file' => ['required', 'image', 'max:2048'],
        ]);

        $existing = Signature::where('user_id', $validated['user_id'])->first();
        if ($existing && $existing->file_path) {
            Storage::disk('public')->delete($existing->file_path);
        }

        $path = $request->file('file')->store('signatures', 'public');

        Signature::updateOrCreate(
            ['user_id' => $validated['user_id']],
            ['file_path' => $path, 'uploaded_by' => $request->user()->id],
        );

        return back()->with('success', 'บันทึกลายเซ็นเรียบร้อยแล้ว');
    }

    public function destroy(Signature $signature): RedirectResponse
    {
        if ($signature->file_path) {
            Storage::disk('public')->delete($signature->file_path);
        }

        $signature->delete();

        return back()->with('success', 'ลบลายเซ็นเรียบร้อยแล้ว');
    }
}
