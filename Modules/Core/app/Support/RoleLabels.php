<?php

namespace Modules\Core\Support;

use Spatie\Permission\Models\Role;

/**
 * แหล่งข้อมูลกลางของป้ายชื่อ role (ปฏิบัติหน้าที่ / ระดับสิทธิ์) ภาษาไทย
 * ใช้ร่วมกันทุกหน้า เพื่อไม่ให้ป้ายชื่อปนไทย/อังกฤษหรือไม่ตรงกัน
 */
class RoleLabels
{
    /** ป้ายชื่อ role 5 ระดับ + ป้ายชื่อเดิม (alias) เผื่อแสดงผล */
    public const LABELS = [
        // 5 ระดับ
        'system_admin' => 'ผู้ดูแลระบบ',
        'executive' => 'ผู้บริหาร',
        'head' => 'หัวหน้างาน / หัวหน้ากลุ่ม',
        'officer' => 'เจ้าหน้าที่',
        'staff' => 'บุคลากรทั่วไป',
        // หน้าที่ระดับผู้บริหาร (duty คู่กับระดับ executive)
        'director' => 'ผู้อำนวยการ',
        'deputy_director' => 'รองผู้อำนวยการ',
        // alias ชื่อเดิม (ยังใช้อ้างในด่านสิทธิ์ภายใน)
        'admin' => 'ผู้ดูแลระบบ',
        'area_admin' => 'ผู้ดูแลระบบเขต',
        'school_executive' => 'ผู้อำนวยการ',
        'school_deputy' => 'รองผู้อำนวยการ',
        'head_of_department' => 'หัวหน้ากลุ่มงาน',
        'head_of_subject' => 'หัวหน้ากลุ่มสาระ',
        'secretary' => 'เลขานุการ',
        'saraban' => 'สารบรรณกลาง',
        'school_clerk' => 'สารบรรณโรงเรียน',
        'group_clerk' => 'ธุรการกลุ่ม',
        'supervisor' => 'ศึกษานิเทศก์',
        'leave_officer' => 'เจ้าหน้าที่วันลา',
        'budget_officer' => 'จนท.งบประมาณ',
        'vehicle_booking_officer' => 'เจ้าหน้าที่รับจองรถ',
        'krs_officer' => 'จนท.คำรับรอง',
        'teacher' => 'ครู / เจ้าหน้าที่',
    ];

    /** role ที่มอบได้ จัดเป็น 2 กลุ่ม: ระดับหลัก + หน้าที่ย่อย (duty) */
    public const LEVELS = [
        ['ระดับบทบาท', ['system_admin', 'executive', 'head', 'officer', 'staff']],
        ['หน้าที่ย่อย (ผอ./รองผอ. เลือกคู่กับระดับผู้บริหาร · ที่เหลือมอบเพิ่มให้เจ้าหน้าที่)', ['director', 'deputy_director', 'secretary', 'saraban', 'school_clerk', 'group_clerk', 'supervisor', 'leave_officer', 'budget_officer', 'vehicle_booking_officer', 'krs_officer']],
    ];

    /** ป้ายชื่อไทยของ role หนึ่งตัว (role ที่ผู้ใช้สร้างเองและไม่มีในแมป จะคืนชื่อเดิม) */
    public static function label(string $name): string
    {
        return self::LABELS[$name] ?? $name;
    }

    /**
     * รายการ role จัดกลุ่มตาม 5 ระดับ พร้อมป้ายไทย (สำหรับ dropdown มอบสิทธิ์)
     *
     * @param  array<int, string>  $except  ชื่อ role ที่ไม่ต้องการรวม
     * @return array<int, array{level: string, roles: array<int, array{name: string, label: string}>}>
     */
    public static function grouped(array $except = []): array
    {
        return collect(self::LEVELS)
            ->map(fn (array $lv) => [
                'level' => $lv[0],
                'roles' => collect($lv[1])
                    ->reject(fn (string $name) => in_array($name, $except, true))
                    ->map(fn (string $name) => ['name' => $name, 'label' => self::label($name)])
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $g) => count($g['roles']) > 0)
            ->values()
            ->all();
    }

    /**
     * รายการ role พร้อมป้ายไทย สำหรับส่งให้ frontend
     *
     * @param  iterable<string>|null  $names  ถ้าไม่ระบุ จะดึง role ทั้งหมดจากฐานข้อมูล
     * @param  array<int, string>  $except  ชื่อ role ที่ไม่ต้องการรวม
     * @return array<int, array{name: string, label: string}>
     */
    public static function options(?iterable $names = null, array $except = []): array
    {
        $names ??= Role::orderBy('name')->pluck('name');

        return collect($names)
            ->reject(fn (string $name) => in_array($name, $except, true))
            ->map(fn (string $name) => ['name' => $name, 'label' => self::label($name)])
            ->values()
            ->all();
    }
}
