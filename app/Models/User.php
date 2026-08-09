<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Core\Models\Department;
use Modules\Core\Models\Group;
use Modules\Core\Models\Position;
use Modules\Core\Models\Signature;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles {
        hasRole as protected traitHasRole;
        assignRole as protected traitAssignRole;
        syncRoles as protected traitSyncRoles;
        removeRole as protected traitRemoveRole;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'department_id',
        'group_id',
        'unit_id',
        'position_id',
        'profile_image',
        'phone',
        'id_plan',
        'duty_active',
        'duty_order',
        'is_acting_director',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'duty_active' => 'boolean',
            'is_acting_director' => 'boolean',
        ];
    }

    /**
     * กลุ่มงาน/กลุ่มสาระที่สังกัด
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * กลุ่มงานที่สังกัด
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * ตำแหน่งของบุคลากร
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * หน่วยงาน/โรงเรียนที่สังกัด (สำหรับระบบเขต)
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Unit::class);
    }

    /**
     * ลายเซ็นของบุคลากร
     */
    public function signature(): HasOne
    {
        return $this->hasOne(Signature::class);
    }

    /**
     * ทะเบียนประวัติบุคลากร (ก.พ.7)
     */
    public function personnelProfile(): HasOne
    {
        return $this->hasOne(\Modules\Core\Models\PersonnelProfile::class);
    }

    // ===================================================================
    // ระบบสิทธิ์ 2 ชั้น: "ระดับ (level)" 5 บทบาท + "หน้าที่ย่อย (duty)" ที่มอบเพิ่ม
    //  - 4 ระดับมีชื่อเดิมเป็น alias ตรงตัว (แปลอัตโนมัติ)
    //  - officer ไม่มี alias — การเห็นเมนู/เข้าถึงมาจาก "หน้าที่ย่อย" ที่ถือจริง
    //    (สารบรรณ/วันลา/นิเทศ/งบ ฯลฯ) ทำให้แยกละเอียดได้เหมือนระบบ 16 role เดิม
    // ===================================================================

    /** บทบาทระดับหลัก (ผู้ใช้ถือ 1 ระดับ) — ผอ.+รองผอ. รวมเป็น "ผู้บริหาร (executive)" แยกกันด้วยหน้าที่ย่อย */
    public const LEVELS = ['system_admin', 'executive', 'head', 'officer', 'staff'];

    /** ระดับ => ชื่อเดิมที่เป็น alias ตรงตัว (executive/officer ไม่มี — ผอ./รองผอ. อยู่ในหน้าที่ย่อย) */
    public const ROLE_ALIASES = [
        'system_admin' => ['admin', 'area_admin'],
        'head' => ['head_of_department', 'head_of_subject'],
        'staff' => ['teacher'],
    ];

    /** หน้าที่ย่อย (duty) — role จริงที่มอบเพิ่มให้ผู้ใช้ คุมสิทธิ์/เมนูแบบละเอียด
     *  director/deputy_director = ตำแหน่ง ผอ./รองผอ. (ต้องอยู่บนระดับ executive) */
    public const DUTIES = [
        'director', 'deputy_director',
        'secretary', 'saraban', 'school_clerk', 'group_clerk', 'supervisor',
        'leave_officer', 'budget_officer', 'vehicle_booking_officer', 'krs_officer',
    ];

    /** หน้าที่ระดับผู้บริหาร — เติมระดับ executive ให้อัตโนมัติเมื่อมอบหน้าที่นี้ */
    public const EXECUTIVE_DUTIES = ['director', 'deputy_director'];

    /** map ชื่อ role เดิม -> ระดับ (idempotent: ระดับ/หน้าที่ย่อย คืนค่าตัวเอง) */
    public static function newRoleFor(string $name): string
    {
        static $map = null;
        if ($map === null) {
            $map = [];
            foreach (self::ROLE_ALIASES as $new => $olds) {
                $map[$new] = $new;
                foreach ($olds as $old) {
                    $map[$old] = $new;
                }
            }
        }

        return $map[$name] ?? $name;
    }

    /** แปลงรายชื่อ role (ชื่อเดิม -> ใหม่) ก่อนตรวจสิทธิ์ */
    private static function translateRoles($roles)
    {
        if (is_string($roles)) {
            return self::newRoleFor($roles);
        }
        if (is_array($roles)) {
            return array_values(array_unique(array_map(
                fn ($r) => is_string($r) ? self::newRoleFor($r) : $r,
                $roles
            )));
        }
        if ($roles instanceof \Illuminate\Support\Collection) {
            return $roles->map(fn ($r) => is_string($r) ? self::newRoleFor($r) : $r);
        }

        return $roles;
    }

    /** override hasRole — แปลงชื่อ role เดิมเป็นใหม่อัตโนมัติ (ครอบ middleware role: + hasAnyRole + @role) */
    public function hasRole($roles, ?string $guard = null): bool
    {
        return $this->traitHasRole(self::translateRoles($roles), $guard);
    }

    /** รวม args (variadic/array/Role) เป็น array แล้วแปลงชื่อเดิม -> ใหม่ */
    private function mapRoleArgs(array $args): array
    {
        $flat = [];
        foreach ($args as $a) {
            if (is_iterable($a)) {
                foreach ($a as $x) {
                    $flat[] = $x;
                }
            } else {
                $flat[] = $a;
            }
        }

        $mapped = self::translateRoles($flat);

        // ผอ./รองผอ. เป็นหน้าที่ย่อยที่ต้องอยู่บนระดับ "ผู้บริหาร" — เติม executive ให้ถ้ายังไม่ได้เลือกระดับใด
        $names = array_filter($mapped, 'is_string');
        if (array_intersect(self::EXECUTIVE_DUTIES, $names) && ! array_intersect(self::LEVELS, $names)) {
            $mapped[] = 'executive';
        }

        return array_values(array_unique($mapped));
    }

    /** override การมอบ/อัปเดต/ถอด role — แปลงชื่อ role เดิมเป็นใหม่อัตโนมัติ (ครอบทุกจุดที่ assign ชื่อเดิม) */
    public function assignRole(...$roles)
    {
        return $this->traitAssignRole($this->mapRoleArgs($roles));
    }

    public function syncRoles(...$roles)
    {
        return $this->traitSyncRoles($this->mapRoleArgs($roles));
    }

    public function removeRole($role)
    {
        return $this->traitRemoveRole(is_string($role) ? self::newRoleFor($role) : $role);
    }

    /** รายชื่อ role ของผู้ใช้ + alias ชื่อเดิม (ให้ frontend ที่อ้างชื่อเดิมยังกรองเมนูได้) */
    public function getRoleNamesExpanded(): \Illuminate\Support\Collection
    {
        $out = collect();
        foreach ($this->getRoleNames() as $name) {
            $out->push($name);
            foreach (self::ROLE_ALIASES[$name] ?? [] as $alias) {
                $out->push($alias);
            }
        }

        return $out->unique()->values();
    }
}

