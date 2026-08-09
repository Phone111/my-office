<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceReceipt extends Model
{
    protected $fillable = [
        'fiscal_year', 'money_class', 'money_type_id', 'doc_no', 'title',
        'nature', 'amount', 'receive_date', 'file_path', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'receive_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /** ประเภทหลักของเงินที่รับได้ (2.2–2.4) */
    public const CLASSES = [
        'budget' => 'รับเงินงบประมาณ',
        'nonbudget' => 'รับเงินนอกงบประมาณ',
        'state_revenue' => 'รับเงินรายได้แผ่นดิน',
    ];

    /** ลักษณะรายการรับ */
    public const NATURES = [
        'cash' => 'รับเงินสด',
        'cheque' => 'รับเช็ค',
        'bank' => 'เงินฝากธนาคาร',
        'other' => 'อื่น ๆ',
    ];

    /** จับคู่ money_class กับ main_type ของ finance_masters(money_type) */
    public const CLASS_MAIN = [
        'nonbudget' => '1',     // เงินนอกงบประมาณ
        'state_revenue' => '3', // เงินรายได้แผ่นดิน
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
