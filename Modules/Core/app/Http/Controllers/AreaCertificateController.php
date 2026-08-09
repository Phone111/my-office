<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\AreaCertificate;
use Modules\Core\Models\CertificateSigner;
use Modules\Core\Models\Unit;
use Modules\Saraban\Services\NumberRegisterService;

/**
 * ทะเบียนเกียรติบัตรเขต + ผู้ลงนาม (ระบบเขต ขั้น 5)
 * ออกเลขเกียรติบัตรต่อหน่วยงาน/ปี · ออกเป็นชุดได้ · เลือกผู้ลงนามจากทะเบียน
 */
class AreaCertificateController extends Controller
{
    private function isOverseer(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'area_admin']);
    }

    /** หน่วยงานเจ้าของทะเบียนที่กำลังดู */
    private function ownerUnit(Request $request): int
    {
        $u = $request->user();
        if ($this->isOverseer($u) && $request->input('unit')) {
            return (int) $request->input('unit');
        }

        return (int) $u->unit_id;
    }

    private function thai($d): ?string
    {
        if (! $d) {
            return null;
        }
        $m = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $d->day.' '.$m[$d->month].' '.($d->year + 543);
    }

    public function index(Request $request): Response
    {
        $unit = $this->ownerUnit($request);
        $overseer = $this->isOverseer($request->user());

        $rows = AreaCertificate::with(['signer:id,name,position', 'issuer:id,name'])
            ->where('unit_id', $unit)
            ->orderByDesc('cert_year')->orderByDesc('cert_no')
            ->get()->map(fn (AreaCertificate $c) => [
                'id' => $c->id,
                'cert_label' => $c->cert_no.'/'.$c->cert_year,
                'category' => $c->category,
                'title' => $c->title,
                'recipient_name' => $c->recipient_name,
                'recipient_org' => $c->recipient_org,
                'issued_thai' => $this->thai($c->issued_date),
                'signer' => $c->signer ? trim(($c->signer->position ? $c->signer->position.' ' : '').$c->signer->name) : null,
                'issuer' => $c->issuer?->name,
                'note' => $c->note,
            ]);

        return Inertia::render('Core::Certificates/Index', [
            'rows' => $rows,
            'signers' => CertificateSigner::where('unit_id', $unit)->where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'position'])->map(fn ($s) => ['id' => $s->id, 'name' => trim(($s->position ? $s->position.' ' : '').$s->name)]),
            'unitName' => Unit::find($unit)?->name,
            'units' => $overseer ? Unit::where('is_active', true)->orderByRaw("type = 'area' desc")->orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]) : [],
            'selectedUnit' => $unit,
            'canPickUnit' => $overseer,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $unit = $this->ownerUnit($request);
        abort_unless($unit, 403, 'บัญชีของคุณยังไม่ได้สังกัดหน่วยงาน');

        $v = $request->validate([
            'category' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'recipients' => ['required', 'string', 'max:8000'], // หนึ่งบรรทัดต่อหนึ่งคน (ออกเป็นชุดได้)
            'recipient_org' => ['nullable', 'string', 'max:255'],
            'issued_date' => ['required', 'date'],
            'signer_id' => ['nullable', 'integer', 'exists:certificate_signers,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $names = collect(preg_split('/\r\n|\r|\n/', $v['recipients']))
            ->map(fn ($n) => trim($n))->filter()->values();
        abort_if($names->isEmpty(), 422, 'กรุณาระบุผู้รับอย่างน้อย 1 คน');

        $beYear = (int) now()->year + 543;
        $svc = app(NumberRegisterService::class);

        $first = null;
        $last = null;
        foreach ($names as $name) {
            // ออกเลขเกียรติบัตรแบบล็อกแถว กันเลขซ้ำเมื่อออกพร้อมกัน (ตั้งต้นจากเลขสูงสุดเดิม)
            $no = $svc->nextScoped(
                "area_cert:{$unit}",
                $beYear,
                fn () => (int) (AreaCertificate::where('unit_id', $unit)->where('cert_year', $beYear)->max('cert_no') ?? 0),
            );
            $cert = AreaCertificate::create([
                'unit_id' => $unit,
                'cert_no' => $no,
                'cert_year' => $beYear,
                'category' => $v['category'] ?? null,
                'title' => $v['title'],
                'recipient_name' => $name,
                'recipient_org' => $v['recipient_org'] ?? null,
                'issued_date' => $v['issued_date'],
                'signer_id' => $v['signer_id'] ?? null,
                'note' => $v['note'] ?? null,
                'issued_by' => $request->user()->id,
            ]);
            $first ??= $cert->cert_no;
            $last = $cert->cert_no;
        }

        $range = $first === $last ? "{$first}/{$beYear}" : "{$first}-{$last}/{$beYear}";
        $back = $this->isOverseer($request->user()) ? redirect()->route('area-certificates.index', ['unit' => $unit]) : redirect()->route('area-certificates.index');

        return $back->with('success', "ออกเกียรติบัตร {$names->count()} ฉบับ (เลขที่ {$range})");
    }

    public function destroy(Request $request, AreaCertificate $area_certificate): RedirectResponse
    {
        $allowed = $this->isOverseer($request->user()) || $area_certificate->unit_id === (int) $request->user()->unit_id;
        abort_unless($allowed, 403);
        $area_certificate->delete();

        return back()->with('success', 'ลบรายการเกียรติบัตรแล้ว');
    }

    /** ทะเบียนผู้ลงนาม */
    public function signers(Request $request): Response
    {
        $unit = $this->ownerUnit($request);
        $overseer = $this->isOverseer($request->user());

        return Inertia::render('Core::Certificates/Signers', [
            'rows' => CertificateSigner::where('unit_id', $unit)->orderBy('name')->get()
                ->map(fn (CertificateSigner $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'position' => $s->position,
                    'is_active' => $s->is_active,
                    'signature_url' => $s->signature_path ? Storage::url($s->signature_path) : null,
                ]),
            'unitName' => Unit::find($unit)?->name,
            'units' => $overseer ? Unit::where('is_active', true)->orderByRaw("type = 'area' desc")->orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]) : [],
            'selectedUnit' => $unit,
            'canPickUnit' => $overseer,
        ]);
    }

    public function storeSigner(Request $request): RedirectResponse
    {
        $unit = $this->ownerUnit($request);
        abort_unless($unit, 403, 'บัญชีของคุณยังไม่ได้สังกัดหน่วยงาน');

        $v = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'signature' => ['nullable', 'image', 'max:4096'],
        ]);

        $path = null;
        if ($request->hasFile('signature')) {
            $path = $request->file('signature')->store('signatures', 'public');
        }

        CertificateSigner::create([
            'unit_id' => $unit,
            'name' => $v['name'],
            'position' => $v['position'] ?? null,
            'signature_path' => $path,
            'is_active' => true,
        ]);

        return back()->with('success', 'เพิ่มผู้ลงนามเรียบร้อย');
    }

    public function destroySigner(Request $request, CertificateSigner $signer): RedirectResponse
    {
        $allowed = $this->isOverseer($request->user()) || $signer->unit_id === (int) $request->user()->unit_id;
        abort_unless($allowed, 403);
        $signer->delete();

        return back()->with('success', 'ลบผู้ลงนามแล้ว');
    }
}
