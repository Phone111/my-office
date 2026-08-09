<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceStatusChange extends Model
{
    protected $fillable = [
        'fiscal_year', 'money_class', 'money_type_id', 'doc_no', 'title',
        'nature', 'amount', 'change_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'amount' => 'decimal:2',
            'change_date' => 'date',
        ];
    }

    public const CLASSES = [
        'budget' => 'เงินงบประมาณ',
        'nonbudget' => 'เงินนอกงบประมาณ',
        'state_revenue' => 'เงินรายได้แผ่นดิน',
    ];

    /** ลักษณะการเปลี่ยนสถานะเงิน */
    public const NATURES = [
        'bank_to_cash' => 'ถอนเงินจากธนาคารเป็นเงินสด',
        'cash_to_bank' => 'นำเงินสดฝากธนาคาร',
        'cash_to_treasury' => 'นำเงินส่งคลัง',
        'other' => 'อื่น ๆ',
    ];

    public const CLASS_MAIN = [
        'nonbudget' => '1',
        'state_revenue' => '3',
    ];

    public function moneyType(): BelongsTo
    {
        return $this->belongsTo(FinanceMaster::class, 'money_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
