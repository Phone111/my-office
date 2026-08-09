<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancePayment extends Model
{
    protected $fillable = [
        'fiscal_year', 'money_class', 'doc_no', 'withdrawal_id', 'petition_id',
        'money_type_id', 'expense_category_id', 'title', 'amount', 'payee',
        'approval_status', 'approve_note', 'approved_by', 'approved_at',
        'paid', 'paid_at', 'paid_by', 'is_advance_return', 'order_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid' => 'boolean',
            'paid_at' => 'datetime',
            'is_advance_return' => 'boolean',
            'order_date' => 'date',
        ];
    }

    /** ประเภทการสั่งจ่าย (4.1–4.4) */
    public const CLASSES = [
        'budget' => 'สั่งจ่ายเงินงบประมาณ',
        'nonbudget' => 'สั่งจ่ายเงินนอกงบประมาณ',
        'state_revenue' => 'สั่งจ่ายเงินรายได้แผ่นดิน',
        'advance' => 'เงินทดรองราชการ',
    ];

    public const STATUS = [
        'pending' => 'รออนุมัติ',
        'approved' => 'อนุมัติ',
        'rejected' => 'ไม่อนุมัติ',
    ];

    /** จับคู่ class กับ main_type ของ finance_masters(money_type) */
    public const CLASS_MAIN = [
        'nonbudget' => '1',
        'state_revenue' => '3',
    ];

    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(FinanceWithdrawal::class, 'withdrawal_id');
    }

    public function petition(): BelongsTo
    {
        return $this->belongsTo(FinancePetition::class, 'petition_id');
    }

    public function moneyType(): BelongsTo
    {
        return $this->belongsTo(FinanceMaster::class, 'money_type_id');
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(FinanceMaster::class, 'expense_category_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
