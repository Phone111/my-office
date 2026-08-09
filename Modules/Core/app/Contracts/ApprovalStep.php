<?php

namespace Modules\Core\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ขั้นตอนหนึ่งในเส้นทางอนุมัติ (route step)
 * ต้องมีฟิลด์: step_order, approver_id, status, comment, acted_at
 */
interface ApprovalStep
{
    /** ความสัมพันธ์กลับไปยังรายการแม่ (Approvable) */
    public function approvable(): BelongsTo;
}
