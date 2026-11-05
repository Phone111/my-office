<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
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

        $this->registerSqliteFunctions();
        $this->registerAuditLog();
    }

    /**
     * ลงทะเบียนฟังก์ชัน SQL ของ MySQL ให้ใช้งานบน SQLite ได้โดยไม่เกิด Error
     */
    private function registerSqliteFunctions(): void
    {
        try {
            if (DB::connection()->getDriverName() === 'sqlite') {
                $pdo = DB::connection()->getPdo();
                $pdo->sqliteCreateFunction('YEAR', fn ($date) => $date ? (int) date('Y', strtotime($date)) : null, 1);
                $pdo->sqliteCreateFunction('MONTH', fn ($date) => $date ? (int) date('n', strtotime($date)) : null, 1);
                $pdo->sqliteCreateFunction('DAY', fn ($date) => $date ? (int) date('j', strtotime($date)) : null, 1);
                $pdo->sqliteCreateFunction('LPAD', fn ($str, $len, $pad = ' ') => str_pad((string) $str, (int) $len, (string) $pad, STR_PAD_LEFT), 3);
                $pdo->sqliteCreateFunction('CONCAT', fn (...$args) => implode('', $args));
            }
        } catch (\Throwable $e) {
            //
        }
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
