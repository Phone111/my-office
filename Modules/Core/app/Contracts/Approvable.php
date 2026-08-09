<?php

namespace Modules\Core\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * โมเดลที่สามารถเดินตามเส้นทางอนุมัติได้ (เช่น เอกสารสารบรรณ, ใบลา)
 * ใช้ร่วมกับ ApprovalWorkflowService ใน Core
 */
interface Approvable
{
    /** ขั้นตอนการอนุมัติทั้งหมดของรายการนี้ (route steps) */
    public function approvalRoutes(): HasMany;

    /** ผู้สร้าง/ผู้ยื่น (ต้องมี department_id) */
    public function approvalCreator(): ?User;

    /** ปรับสถานะของรายการ (pending/approved/rejected) */
    public function setApprovalStatus(string $status): void;

    /** หัวข้อสำหรับข้อความแจ้งเตือน */
    public function approvalSubject(): string;

    /** URL สำหรับเปิดดูรายการนี้ (ใช้ใน notification) */
    public function approvalLink(): string;
}
