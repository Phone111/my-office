<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Saraban\Models\Certificate;
use Modules\Saraban\Services\NumberRegisterService;

/**
 * ทะเบียนเลขเกียรติบัตร — ออกเลขที่เกียรติบัตรอัตโนมัติและเก็บบันทึก
 */
class CertificateController extends Controller
{
    public function __construct(private readonly NumberRegisterService $numbers)
    {
    }

    public function index(): Response
    {
        $certificates = Certificate::with('issuer:id,name')
            ->latest()
            ->get()
            ->map(fn (Certificate $c) => [
                'id' => $c->id,
                'certificate_number' => $c->certificate_number,
                'title' => $c->title,
                'recipient_name' => $c->recipient_name,
                'issued_date' => $c->issued_date?->format('Y-m-d'),
                'note' => $c->note,
                'issuer' => $c->issuer?->name,
            ]);

        return Inertia::render('Saraban::Certificates', [
            'certificates' => $certificates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'issued_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        // ออกเลขที่เกียรติบัตรอัตโนมัติ (เล่มทะเบียน certificate)
        $number = $this->numbers->issue('certificate');

        Certificate::create([
            ...$validated,
            'certificate_number' => $number,
            'issuer_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('saraban.certificates.index')
            ->with('success', "ออกเลขเกียรติบัตรเรียบร้อยแล้ว (เลขที่ {$number})");
    }
}
