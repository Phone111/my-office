<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinancePetition;
use Modules\Finance\Models\FinancePetitionCancel;
use Modules\Finance\Support\FinanceHelpers;

/**
 * ทะเบียนยกเลิกฎีกา (AMSS 3.5)
 */
class PetitionCancelController extends Controller
{
    use FinanceHelpers;

    public function index(Request $request): Response
    {
        $year = (int) ($request->query('year') ?: FinanceFiscalYear::current());

        $rows = FinancePetitionCancel::with('petition')
            ->where('fiscal_year', $year)->latest('id')->get()
            ->map(fn (FinancePetitionCancel $c) => [
                'id' => $c->id,
                'petition_id' => $c->petition_id,
                'petition_no' => $c->petition_no ?: $c->petition?->petition_no,
                'ref_doc' => $c->ref_doc,
                'reason' => $c->reason,
                'cancel_date' => $c->cancel_date?->format('Y-m-d'),
                'cancel_date_thai' => $this->thai($c->cancel_date),
            ]);

        return Inertia::render('Finance::PetitionCancels', [
            'year' => $year,
            'years' => FinanceFiscalYear::orderByDesc('year')->get(['id', 'year', 'is_current']),
            'rows' => $rows,
            'petitions' => FinancePetition::where('fiscal_year', $year)->where('cancelled', false)
                ->orderBy('id')->get(['id', 'petition_no', 'title'])
                ->map(fn ($p) => ['id' => $p->id, 'label' => 'ฎีกา '.($p->petition_no ?: $p->id).' — '.$p->title]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $year = (int) ($request->input('fiscal_year') ?: FinanceFiscalYear::current());
        $v = $request->validate([
            'petition_id' => ['nullable', 'exists:finance_petitions,id'],
            'petition_no' => ['nullable', 'string', 'max:255'],
            'ref_doc' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:500'],
            'cancel_date' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($request, $year, $v) {
            FinancePetitionCancel::create([
                'fiscal_year' => $year,
                'petition_id' => $v['petition_id'] ?? null,
                'petition_no' => $v['petition_no'] ?? null,
                'ref_doc' => $v['ref_doc'] ?? null,
                'reason' => $v['reason'],
                'cancel_date' => $v['cancel_date'] ?? now()->toDateString(),
                'created_by' => $request->user()->id,
            ]);
            if (! empty($v['petition_id'])) {
                FinancePetition::where('id', $v['petition_id'])->update(['cancelled' => true]);
            }
        });

        return back()->with('success', 'บันทึกยกเลิกฎีกาเรียบร้อยแล้ว');
    }

    public function destroy(FinancePetitionCancel $petitionCancel): RedirectResponse
    {
        DB::transaction(function () use ($petitionCancel) {
            if ($petitionCancel->petition_id) {
                FinancePetition::where('id', $petitionCancel->petition_id)->update(['cancelled' => false]);
            }
            $petitionCancel->delete();
        });

        return back()->with('success', 'ลบรายการยกเลิกฎีกาแล้ว');
    }
}
