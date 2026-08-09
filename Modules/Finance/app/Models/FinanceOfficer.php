<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceOfficer extends Model
{
    protected $fillable = [
        'user_id', 'can_approve', 'fund_allocation', 'can_withdraw', 'file_petition',
        'budget_money', 'nonbudget_money', 'state_revenue', 'advance_money',
        'pay_money', 'view_reports',
    ];

    protected function casts(): array
    {
        return [
            'can_approve' => 'boolean',
            'fund_allocation' => 'boolean',
            'can_withdraw' => 'boolean',
            'file_petition' => 'boolean',
            'budget_money' => 'boolean',
            'nonbudget_money' => 'boolean',
            'state_revenue' => 'boolean',
            'advance_money' => 'boolean',
            'pay_money' => 'boolean',
            'view_reports' => 'boolean',
        ];
    }

    /** สิทธิ์ทั้งหมด + ป้ายไทย (เรียงตามคู่มือ 1.1) */
    public const RIGHTS = [
        'can_approve' => 'ผู้อนุมัติ (สั่งจ่ายทุกประเภท)',
        'fund_allocation' => 'เงินงวด (คุมเงินประจำงวด)',
        'can_withdraw' => 'ขอเบิก/ขอยืม',
        'file_petition' => 'วางฎีกา (เบิกเงินคงคลัง)',
        'budget_money' => 'เงินงบประมาณ',
        'nonbudget_money' => 'เงินนอกงบประมาณ',
        'state_revenue' => 'เงินรายได้แผ่นดิน',
        'advance_money' => 'เงินทดรองราชการ',
        'pay_money' => 'จ่ายเงิน',
        'view_reports' => 'เรียกดูรายงาน',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
