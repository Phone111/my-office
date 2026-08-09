<?php

namespace Modules\Saraban\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Contracts\Approvable;

// use Modules\Saraban\Database\Factories\DocumentFactory;

class Document extends Model implements Approvable
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /** ส่วนราชการ/กลุ่มงาน (สำหรับบันทึกข้อความ) */
    public const DIVISIONS = [
        'กลุ่มบริหารงานทั่วไป',
        'กลุ่มบริหารงานบุคคล',
        'กลุ่มบริหารงบประมาณ',
        'กลุ่มบริหารงานวิชาการ',
        'สำนักงานผู้อำนวยการ',
        'งานสารบรรณ',
    ];

    /** ระดับความเร่งด่วน (ป้าย + สี) */
    public const PRIORITIES = [
        'normal' => ['label' => 'ปกติ', 'classes' => 'bg-emerald-100 text-emerald-700'],
        'urgent' => ['label' => 'ด่วน', 'classes' => 'bg-fuchsia-100 text-fuchsia-700'],
        'very_urgent' => ['label' => 'ด่วนมาก', 'classes' => 'bg-orange-100 text-orange-700'],
        'most_urgent' => ['label' => 'ด่วนที่สุด', 'classes' => 'bg-rose-100 text-rose-700'],
    ];

    // หมวดหมู่แฟ้ม (ใช้จัดหมวดในหน้า UI ของผู้ใช้)
    public const CATEGORY_MEMO = 'memo';
    public const CATEGORY_INCOMING = 'incoming';
    public const CATEGORY_OUTGOING = 'outgoing';
    public const CATEGORY_INTERNAL_IN = 'internal_in';
    public const CATEGORY_INTERNAL_OUT = 'internal_out';
    public const CATEGORY_GENERAL_IN = 'general_in';
    public const CATEGORY_GENERAL_OUT = 'general_out';
    public const CATEGORY_REPORT = 'report';
    public const CATEGORY_ORDER = 'order';
    public const CATEGORY_SEQUENCE = 'sequence';

    /** ชื่อแฟ้มแต่ละหมวด (เรียงตามที่แสดงเป็นแท็บ) */
    public const CATEGORIES = [
        self::CATEGORY_MEMO => 'แฟ้มบันทึกข้อความ',
        self::CATEGORY_INCOMING => 'แฟ้มรับหนังสือราชการ',
        self::CATEGORY_OUTGOING => 'แฟ้มส่งหนังสือราชการ',
        self::CATEGORY_INTERNAL_OUT => 'แฟ้มส่งหนังสือภายใน',
        self::CATEGORY_INTERNAL_IN => 'แฟ้มรับหนังสือภายใน',
        self::CATEGORY_GENERAL_OUT => 'แฟ้มส่งเอกสารทั่วไป',
        self::CATEGORY_GENERAL_IN => 'แฟ้มรับเอกสารทั่วไป',
        self::CATEGORY_REPORT => 'แฟ้มรายงานโครงการ',
        self::CATEGORY_ORDER => 'แฟ้มคำสั่ง',
        self::CATEGORY_SEQUENCE => 'แฟ้มลำดับเอกสาร',
    ];

    /** หมวดที่ออกเลขผ่านระบบเฉพาะเท่านั้น — ห้ามเขียน/สร้างผ่านฟอร์มบันทึกทั่วไป */
    public const ISSUER_ONLY = [self::CATEGORY_SEQUENCE];

    /** หมวดที่ผู้ใช้เขียนผ่านฟอร์มบันทึกได้ (ตัดหมวดที่ออกเลขเฉพาะออก) */
    public static function composableCategories(): array
    {
        return array_values(array_diff(array_keys(self::CATEGORIES), self::ISSUER_ONLY));
    }

    /**
     * กลุ่มแฟ้มในหน้า "เอกสารรอดำเนินการ" (แยกเมนูซ้าย + นับป้ายแยก)
     * key => [label, categories[]]
     */
    public const INBOX_FOLDERS = [
        'proposal' => ['label' => 'แฟ้มบันทึกเสนอ', 'categories' => ['memo', 'order', 'report']],
        'official' => ['label' => 'แฟ้มหนังสือราชการ', 'categories' => ['incoming']],
        'internal' => ['label' => 'แฟ้มหนังสือภายใน', 'categories' => ['internal_in', 'internal_out']],
        'general' => ['label' => 'แฟ้มเอกสารทั่วไป', 'categories' => ['general_in', 'general_out']],
    ];

    /** คำนำหน้าเลขทะเบียนของแต่ละหมวด */
    public const NUMBER_PREFIXES = [
        self::CATEGORY_MEMO => 'บค',
        self::CATEGORY_INCOMING => 'รับ',
        self::CATEGORY_OUTGOING => 'ส่ง',
        self::CATEGORY_INTERNAL_OUT => 'สภ',
        self::CATEGORY_INTERNAL_IN => 'รภ',
        self::CATEGORY_GENERAL_OUT => 'สท',
        self::CATEGORY_GENERAL_IN => 'รท',
        self::CATEGORY_REPORT => 'รง',
        self::CATEGORY_ORDER => 'คส',
        self::CATEGORY_SEQUENCE => '',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'category',
        'title',
        'document_number',
        'number_issued_at',
        'content',
        'file_path',
        'attachments',
        'status',
        'is_urgent',
        'priority',
        'division',
        'filing',
        'handed_to_saraban_id',
        'creator_id',
        'source_name',
        'source_date',
        'source_number',
        'effective_date',
        'destroyed_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'number_issued_at' => 'datetime',
            'source_date' => 'datetime',
            'effective_date' => 'date',
            'attachments' => 'array',
            'is_urgent' => 'boolean',
        ];
    }

    /**
     * ผู้สร้างเอกสาร
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * ทุกขั้นของเส้นทางการเดินเอกสาร
     */
    public function routes(): HasMany
    {
        return $this->hasMany(DocumentRoute::class)->orderBy('step_order');
    }

    /**
     * ขั้นที่กำลังรออนุมัติอยู่ตอนนี้
     */
    public function currentRoute(): HasOne
    {
        return $this->hasOne(DocumentRoute::class)
            ->where('status', DocumentRoute::STATUS_PENDING);
    }

    // ===== Approvable contract (ใช้กับ ApprovalWorkflowService ของ Core) =====

    public function approvalRoutes(): HasMany
    {
        return $this->routes();
    }

    public function approvalCreator(): ?User
    {
        return $this->creator;
    }

    public function setApprovalStatus(string $status): void
    {
        $this->update(['status' => $status]);
    }

    public function approvalSubject(): string
    {
        return $this->title;
    }

    public function approvalLink(): string
    {
        return route('saraban.documents.show', $this->id);
    }
}
