<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Support\AuditLogger;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->registerAuditLog();
    }

    /**
     * บันทึก Audit Log อัตโนมัติสำหรับข้อมูลสำคัญ (เพิ่ม/แก้ไข/ลบ)
     */
    private function registerAuditLog(): void
    {
        // model => [ป้ายชื่อไทย, log การแก้ไขด้วยไหม]
        $audited = [
            \App\Models\User::class => ['บัญชีผู้ใช้', true],
            \Modules\Saraban\Models\Document::class => ['หนังสือ', false],
            \Modules\Core\Models\PersonnelProfile::class => ['ทะเบียนประวัติ ก.พ.7', true],
            \Modules\Core\Models\AreaCertificate::class => ['เกียรติบัตร', false],
            \Modules\Core\Models\Evaluation::class => ['ผลประเมิน', true],
            \Modules\Core\Models\Unit::class => ['หน่วยงาน/โรงเรียน', true],
            \Modules\Booking\Models\Vehicle::class => ['รถยนต์', true],
        ];

        foreach ($audited as $class => [$label, $logUpdate]) {
            if (! class_exists($class)) {
                continue;
            }
            $class::created(fn ($m) => AuditLogger::log('created', $m, "เพิ่ม{$label}"));
            $class::deleted(fn ($m) => AuditLogger::log('deleted', $m, "ลบ{$label}"));
            if ($logUpdate) {
                $class::updated(function ($m) use ($label) {
                    $fields = AuditLogger::changedFields($m);
                    if ($fields !== '') {
                        AuditLogger::log('updated', $m, "แก้ไข{$label}: {$fields}");
                    }
                });
            }
        }
    }
}
