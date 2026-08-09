<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\ActivityController;
use Modules\Core\Http\Controllers\AdminController;
use Modules\Core\Http\Controllers\AreaCertificateController;
use Modules\Core\Http\Controllers\AuditLogController;
use Modules\Core\Http\Controllers\AreaInfoController;
use Modules\Core\Http\Controllers\AchievementController;
use Modules\Core\Http\Controllers\PersonnelRecordController;
use Modules\Core\Http\Controllers\PaController;
use Modules\Core\Http\Controllers\AdminHomeController;
use Modules\Core\Http\Controllers\DatabaseMaintenanceController;
use Modules\Core\Http\Controllers\DepartmentController;
use Modules\Core\Http\Controllers\DevelopmentPlanController;
use Modules\Core\Http\Controllers\DirectorDashboardController;
use Modules\Core\Http\Controllers\DirectorReportController;
use Modules\Core\Http\Controllers\DocumentSearchController;
use Modules\Core\Http\Controllers\ExecutiveDutyController;
use Modules\Core\Http\Controllers\ImportController;
use Modules\Core\Http\Controllers\KrsController;
use Modules\Core\Http\Controllers\PerformanceController;
use Modules\Core\Http\Controllers\RegistryController;
use Modules\Core\Http\Controllers\SchoolAnnouncementController;
use Modules\Core\Http\Controllers\SchoolClerkController;
use Modules\Core\Http\Controllers\SupervisionController;
use Modules\Core\Http\Controllers\SupervisionFrameworkController;
use Modules\Core\Http\Controllers\OpportunityController;
use Modules\Core\Http\Controllers\ExamController;
use Modules\Core\Http\Controllers\StudentController;
use Modules\Core\Http\Controllers\EvaluationController;
use Modules\Core\Http\Controllers\UserRoleController;
use Modules\Core\Http\Controllers\SurveyController;
use Modules\Core\Http\Controllers\UnitController;
use Modules\Core\Http\Controllers\GroupController;
use Modules\Core\Http\Controllers\NewsAdminController;
use Modules\Core\Http\Controllers\PersonnelController;
use Modules\Core\Http\Controllers\PositionController;
use Modules\Core\Http\Controllers\SignatureController;

Route::middleware(['auth', 'verified'])->group(function () {
    // แดชบอร์ดผู้บริหาร — เฉพาะผู้อำนวยการ/รองผู้อำนวยการ/ผู้ดูแลระบบ
    Route::middleware('role:director|deputy_director|admin')->group(function () {
        Route::get('director/dashboard', [DirectorDashboardController::class, 'index'])
            ->name('director.dashboard');
    });

    // ระบบสืบค้นหนังสือ (สำหรับครูและบุคลากรทุกคน)
    Route::get('documents/search', [DocumentSearchController::class, 'index'])
        ->name('documents.search');

    // ปฏิทินกิจกรรม (รวม) — ทุกคนดู/เพิ่มกิจกรรมของตน + แสดงการจอง/วาระผู้บริหารรวม
    Route::prefix('activities')->name('activities.')->group(function () {
        Route::get('/', [ActivityController::class, 'index'])->name('index');
        Route::post('/', [ActivityController::class, 'store'])->name('store');
        Route::put('{activity}', [ActivityController::class, 'update'])->name('update');
        Route::delete('{activity}', [ActivityController::class, 'destroy'])->name('destroy');
    });

    // รายงานสำหรับผู้บริหาร/เลขาฯ
    Route::prefix('reports')->name('reports.')->group(function () {
        // ผลการปฏิบัติงานของกลุ่ม/บุคลากร — หัวหน้ากลุ่ม/ธุรการกลุ่ม + ผู้บริหาร/เลขาฯ
        Route::middleware('role:head_of_department|head_of_subject|group_clerk|secretary|director|deputy_director|admin')
            ->get('performance', [PerformanceController::class, 'index'])->name('performance');

        // รายงานลงเวลา/การลา — ผู้บริหาร/เลขาฯ + เจ้าหน้าที่วันลา
        Route::middleware('role:director|deputy_director|secretary|leave_officer|admin')->group(function () {
            Route::get('attendance', [DirectorReportController::class, 'attendance'])->name('attendance');
            Route::get('attendance-ledger', [DirectorReportController::class, 'attendanceLedger'])->name('attendance-ledger');
            Route::get('leave-statistics', [DirectorReportController::class, 'leaveStatistics'])->name('leave-statistics');
        });

        // ทะเบียน/สมุดโทรศัพท์ — ผู้บริหาร/เลขาฯ/สารบรรณกลาง
        Route::middleware('role:director|deputy_director|secretary|saraban|admin')->group(function () {
            Route::get('documents', [DirectorReportController::class, 'documents'])->name('documents');
            Route::get('phone-book', [DirectorReportController::class, 'phoneBook'])->name('phone-book');
            Route::get('registry/orders', [RegistryController::class, 'orders'])->name('registry.orders');
            Route::get('registry/official-trips', [RegistryController::class, 'officialTrips'])->name('registry.official-trips');
            // ระบบประกาศโรงเรียน (ออกเลขที่ + แนบไฟล์)
            Route::get('registry/announcements/create', [SchoolAnnouncementController::class, 'create'])->name('registry.announcements.create');
            Route::post('registry/announcements', [SchoolAnnouncementController::class, 'store'])->name('registry.announcements.store');
            Route::get('registry/announcements', [SchoolAnnouncementController::class, 'index'])->name('registry.announcements');
            Route::get('registry/document-numbers', [RegistryController::class, 'documentNumbers'])->name('registry.document-numbers');
        });
    });

    // คำรับรองปฏิบัติราชการ KRS/ARS (ระบบเขต Phase 3)
    Route::prefix('krs')->name('krs.')->group(function () {
        // ผู้รายงานตัวชี้วัด (ทุกคนที่ถูกมอบหมาย)
        Route::get('my', [KrsController::class, 'my'])->name('my');
        Route::post('submit', [KrsController::class, 'submit'])->name('submit');
        // จัดการตัวชี้วัด + รับรายงาน — จนท.คำรับรอง/เลขาฯ/admin
        Route::middleware('role:krs_officer|secretary|admin')->group(function () {
            Route::get('/', [KrsController::class, 'index'])->name('index');
            Route::post('indicators', [KrsController::class, 'storeIndicator'])->name('indicators.store');
            Route::put('indicators/{indicator}', [KrsController::class, 'updateIndicator'])->name('indicators.update');
            Route::delete('indicators/{indicator}', [KrsController::class, 'destroyIndicator'])->name('indicators.destroy');
            Route::post('reports/{report}/receive', [KrsController::class, 'receive'])->name('reports.receive');
        });
    });

    // นำเข้าข้อมูลจากระบบเก่า (CSV)
    Route::middleware('role:admin|area_admin')->prefix('import')->name('import.')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::post('units', [ImportController::class, 'importUnits'])->name('units');
        Route::post('users', [ImportController::class, 'importUsers'])->name('users');
        Route::get('template/{type}', [ImportController::class, 'template'])->name('template')->where('type', 'users|units');
    });

    // ระบบเขต Phase 2.2 — ตั้งค่าสารบรรณโรงเรียน (ผอ.ร.ร. แต่งตั้ง)
    Route::middleware('role:director|deputy_director|area_admin|admin')->prefix('school-clerks')->name('school-clerks.')->group(function () {
        Route::get('/', [SchoolClerkController::class, 'index'])->name('index');
        Route::post('/', [SchoolClerkController::class, 'save'])->name('save');
    });

    // ระบบแบบสอบถาม (Survey) — ทุกคนตอบ/ดูที่เปิด · ผู้บริหาร/เขตสร้าง+สรุปผล (gate ในคอนโทรลเลอร์)
    Route::prefix('surveys')->name('surveys.')->group(function () {
        Route::get('/', [SurveyController::class, 'index'])->name('index');
        Route::get('create', [SurveyController::class, 'create'])->name('create');
        Route::post('/', [SurveyController::class, 'store'])->name('store');
        Route::get('{survey}', [SurveyController::class, 'show'])->name('show');
        Route::post('{survey}/submit', [SurveyController::class, 'submit'])->name('submit');
        Route::post('{survey}/toggle', [SurveyController::class, 'toggle'])->name('toggle');
        Route::delete('{survey}', [SurveyController::class, 'destroy'])->name('destroy');
    });

    // ทะเบียนเกียรติบัตรเขต + ผู้ลงนาม (เขต/โรงเรียน ออกเลขเกียรติบัตรของหน่วยงานตน)
    Route::middleware('role:admin|area_admin|director|deputy_director|secretary|saraban|school_clerk')->prefix('area-certificates')->name('area-certificates.')->group(function () {
        Route::get('/', [AreaCertificateController::class, 'index'])->name('index');
        Route::post('/', [AreaCertificateController::class, 'store'])->name('store');
        Route::delete('{area_certificate}', [AreaCertificateController::class, 'destroy'])->name('destroy');
        // ผู้ลงนาม
        Route::get('signers', [AreaCertificateController::class, 'signers'])->name('signers');
        Route::post('signers', [AreaCertificateController::class, 'storeSigner'])->name('signers.store');
        Route::delete('signers/{signer}', [AreaCertificateController::class, 'destroySigner'])->name('signers.destroy');
    });

    // ระบบนิเทศการศึกษา — เขตนิเทศโรงเรียนในสังกัด / โรงเรียนรับทราบผล
    Route::middleware('role:admin|area_admin|supervisor|director|deputy_director|secretary|school_clerk')->prefix('supervisions')->name('supervisions.')->group(function () {
        Route::get('/', [SupervisionController::class, 'index'])->name('index');
        Route::get('create', [SupervisionController::class, 'create'])->name('create');
        Route::post('/', [SupervisionController::class, 'store'])->name('store');

        // กรอบการนิเทศ (มาตรฐาน/ตัวชี้วัด/รอบ) + รายงานระดับเขต — ต้องมาก่อน {supervision}
        Route::get('settings', [SupervisionFrameworkController::class, 'settings'])->name('settings');
        Route::post('standards', [SupervisionFrameworkController::class, 'storeStandard'])->name('standards.store');
        Route::delete('standards/{standard}', [SupervisionFrameworkController::class, 'destroyStandard'])->name('standards.destroy');
        Route::post('indicators', [SupervisionFrameworkController::class, 'storeIndicator'])->name('indicators.store');
        Route::delete('indicators/{indicator}', [SupervisionFrameworkController::class, 'destroyIndicator'])->name('indicators.destroy');
        Route::post('rounds', [SupervisionFrameworkController::class, 'storeRound'])->name('rounds.store');
        Route::post('rounds/{round}/current', [SupervisionFrameworkController::class, 'setCurrentRound'])->name('rounds.current');
        Route::delete('rounds/{round}', [SupervisionFrameworkController::class, 'destroyRound'])->name('rounds.destroy');
        Route::get('report', [SupervisionFrameworkController::class, 'report'])->name('report');

        Route::get('{supervision}', [SupervisionController::class, 'show'])->name('show');
        Route::put('{supervision}', [SupervisionController::class, 'update'])->name('update');
        Route::post('{supervision}/acknowledge', [SupervisionController::class, 'acknowledge'])->name('acknowledge');
        Route::delete('{supervision}', [SupervisionController::class, 'destroy'])->name('destroy');
    });

    // ระบบประเมินผลการปฏิบัติงาน (ทุกคนดูผลของตน · ผู้บังคับบัญชาประเมิน)
    Route::prefix('evaluations')->name('evaluations.')->group(function () {
        Route::get('/', [EvaluationController::class, 'index'])->name('index');
        Route::get('settings', [EvaluationController::class, 'settings'])->name('settings');
        Route::get('report', [EvaluationController::class, 'report'])->name('report');
        Route::get('create', [EvaluationController::class, 'create'])->name('create');
        Route::post('criteria', [EvaluationController::class, 'storeCriteria'])->name('criteria.store');
        Route::delete('criteria/{criteria}', [EvaluationController::class, 'destroyCriteria'])->name('criteria.destroy');
        Route::post('rounds', [EvaluationController::class, 'storeRound'])->name('rounds.store');
        Route::post('rounds/{round}/current', [EvaluationController::class, 'setCurrentRound'])->name('rounds.current');
        Route::delete('rounds/{round}', [EvaluationController::class, 'destroyRound'])->name('rounds.destroy');
        Route::post('/', [EvaluationController::class, 'store'])->name('store');
        Route::get('{evaluation}', [EvaluationController::class, 'show'])->name('show');
        Route::put('{evaluation}', [EvaluationController::class, 'update'])->name('update');
        Route::post('{evaluation}/acknowledge', [EvaluationController::class, 'acknowledge'])->name('acknowledge');
        Route::delete('{evaluation}', [EvaluationController::class, 'destroy'])->name('destroy');
    });

    // มอบหมายหน้าที่ (Duty Assignment) — จัดการ role ของผู้ใช้ (เฉพาะ admin/ผอ.)
    Route::middleware('role:admin|director')->group(function () {
        Route::post('users/{user}/roles/assign', [UserRoleController::class, 'assignRole'])->name('user-roles.assign');
        Route::delete('users/{user}/roles/remove', [UserRoleController::class, 'removeRole'])->name('user-roles.remove');
        Route::put('users/{user}/roles/sync', [UserRoleController::class, 'syncRoles'])->name('user-roles.sync');
    });

    // ระบบข้อมูลนักเรียน — ทะเบียนนักเรียนรายคนต่อโรงเรียน
    Route::middleware('role:admin|area_admin|director|deputy_director|secretary|school_clerk')->prefix('students')->name('students.')->group(function () {
        Route::get('/', [StudentController::class, 'index'])->name('index');
        Route::get('report', [StudentController::class, 'report'])->name('report');
        Route::get('disability', [StudentController::class, 'disabilityReport'])->name('disability');
        Route::get('template', [StudentController::class, 'template'])->name('template');
        Route::post('import', [StudentController::class, 'import'])->name('import');
        Route::post('/', [StudentController::class, 'store'])->name('store');
        Route::put('{student}', [StudentController::class, 'update'])->name('update');
        Route::delete('{student}', [StudentController::class, 'destroy'])->name('destroy');
    });

    // รายงานสารสนเทศเขต (EMIS) — ภาพรวมโรงเรียน/นักเรียน/บุคลากรทั้งเขต
    Route::middleware('role:admin|area_admin|secretary|supervisor|director|deputy_director')
        ->get('area-info', [AreaInfoController::class, 'index'])->name('area-info.index');

    // ว.PA — ข้อตกลงในการพัฒนางาน (ทุกคนมีของตน · ผู้บริหารเห็นชอบ/ประเมิน)
    Route::get('pa', [PaController::class, 'index'])->name('pa.index');
    Route::get('pa/edit', [PaController::class, 'edit'])->name('pa.edit');
    Route::post('pa', [PaController::class, 'store'])->name('pa.store');
    Route::get('pa/{pa}', [PaController::class, 'show'])->name('pa.show');
    Route::post('pa/{pa}/approve', [PaController::class, 'approve'])->name('pa.approve');
    Route::post('pa/{pa}/evaluate', [PaController::class, 'evaluate'])->name('pa.evaluate');

    // ทะเบียนประวัติบุคลากร (ก.พ.7) + วิทยฐานะ + เครื่องราชฯ
    Route::middleware('role:admin|area_admin|secretary|director|deputy_director')->group(function () {
        Route::get('personnel-records', [PersonnelRecordController::class, 'index'])->name('personnel-records.index');
        Route::get('personnel-records/{user}', [PersonnelRecordController::class, 'show'])->name('personnel-records.show');
        Route::put('personnel-records/{user}', [PersonnelRecordController::class, 'update'])->name('personnel-records.update');
        Route::post('personnel-records/{user}/decorations', [PersonnelRecordController::class, 'addDecoration'])->name('personnel-records.decorations.add');
        Route::delete('personnel-decorations/{decoration}', [PersonnelRecordController::class, 'removeDecoration'])->name('personnel-records.decorations.remove');
    });

    // ผลสัมฤทธิ์ระดับชาติ (O-NET/NT/RT) — เขตกรอกทุกโรงเรียน / โรงเรียนกรอกของตน
    Route::middleware('role:admin|area_admin|secretary|supervisor|director|deputy_director|school_clerk')->group(function () {
        Route::get('achievement', [AchievementController::class, 'index'])->name('achievement.index');
        Route::get('achievement/template', [AchievementController::class, 'template'])->name('achievement.template');
        Route::post('achievement', [AchievementController::class, 'store'])->name('achievement.store');
        Route::post('achievement/import', [AchievementController::class, 'import'])->name('achievement.import');
    });

    // ระบบสิทธิและโอกาสทางการศึกษา — ทะเบียนประชากรวัยเรียน + ติดตามการเข้าเรียน
    Route::middleware('role:admin|area_admin|secretary|supervisor')->prefix('opportunity')->name('opportunity.')->group(function () {
        Route::get('/', [OpportunityController::class, 'index'])->name('index');
        Route::get('report', [OpportunityController::class, 'report'])->name('report');
        Route::post('/', [OpportunityController::class, 'store'])->name('store');
        Route::put('{child}', [OpportunityController::class, 'update'])->name('update');
        Route::delete('{child}', [OpportunityController::class, 'destroy'])->name('destroy');
    });

    // ระบบทดสอบการศึกษา — ระดับเขต (จนท.เขตจัดการคลังข้อสอบ/แบบ/รายการสอบ + ป้อนผลรายโรงเรียน)
    Route::middleware('role:admin|area_admin|secretary|supervisor')->prefix('exam')->name('exam.')->group(function () {
        Route::get('questions', [ExamController::class, 'questions'])->name('questions');
        Route::post('questions', [ExamController::class, 'storeQuestion'])->name('questions.store');
        Route::delete('questions/{question}', [ExamController::class, 'destroyQuestion'])->name('questions.destroy');
        Route::get('tests', [ExamController::class, 'tests'])->name('tests');
        Route::post('tests', [ExamController::class, 'storeTest'])->name('tests.store');
        Route::delete('tests/{test}', [ExamController::class, 'destroyTest'])->name('tests.destroy');
        Route::get('runs', [ExamController::class, 'runs'])->name('runs');
        Route::post('runs', [ExamController::class, 'storeRun'])->name('runs.store');
        Route::delete('runs/{run}', [ExamController::class, 'destroyRun'])->name('runs.destroy');
        Route::get('runs/{run}', [ExamController::class, 'run'])->name('run');
        Route::post('runs/{run}/result', [ExamController::class, 'saveResult'])->name('run.result');
        Route::delete('runs/{run}/results/{result}', [ExamController::class, 'destroyResult'])->name('run.result.destroy');
    });

    // ระบบเขต Phase 1 — จัดการหน่วยงาน/โรงเรียนในสังกัด
    Route::middleware('role:admin|area_admin')->prefix('units')->name('units.')->group(function () {
        Route::get('/', [UnitController::class, 'index'])->name('index');
        Route::post('/', [UnitController::class, 'store'])->name('store');
        Route::put('area/{unit}', [UnitController::class, 'updateArea'])->name('area.update');
        Route::put('{unit}', [UnitController::class, 'update'])->name('update');
        Route::delete('{unit}', [UnitController::class, 'destroy'])->name('destroy');
    });

    // การปฏิบัติราชการของผู้บริหาร — ดูบอร์ดได้กว้าง · แต่ toggle ได้เฉพาะ admin/ผอ.
    Route::middleware('role:admin|director|deputy_director|secretary|saraban')
        ->get('executive-duty', [ExecutiveDutyController::class, 'index'])->name('executive-duty.index');
    Route::middleware('role:admin|director')
        ->post('executive-duty', [ExecutiveDutyController::class, 'save'])->name('executive-duty.save');

    // ID Plan ของฉัน — แผนพัฒนาตนเอง
    Route::prefix('id-plan')->name('id-plan.')->group(function () {
        Route::get('/', [DevelopmentPlanController::class, 'index'])->name('index');
        Route::post('/', [DevelopmentPlanController::class, 'store'])->name('store');
        // ข้อมูลรางวัล + การอบรม (ของตนเอง)
        Route::post('awards', [DevelopmentPlanController::class, 'storeAward'])->name('awards.store');
        Route::post('trainings', [DevelopmentPlanController::class, 'storeTraining'])->name('trainings.store');
        Route::post('{plan}', [DevelopmentPlanController::class, 'update'])->name('update');
        Route::delete('{plan}', [DevelopmentPlanController::class, 'destroy'])->name('destroy');
    });

    // จัดการข่าว ปชส. — ผู้บริหาร/สารบรรณ/เลขาฯ + admin
    Route::middleware('role:admin|director|deputy_director|saraban|secretary')->prefix('admin')->name('admin.')->group(function () {
        Route::post('news/bulk-delete', [NewsAdminController::class, 'bulkDestroy'])->name('news.bulk-destroy');
        Route::resource('news', NewsAdminController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    });

    // ===== ระบบจัดการสำหรับผู้ดูแลระบบ (admin) =====
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        // หน้าหลักผู้ดูแลระบบ (hub)
        Route::get('/', [AdminHomeController::class, 'index'])->name('home');

        // บันทึกการใช้งาน (Audit Log)
        Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

        // #2 จัดการ Admin / ผู้ดูแลระบบ
        Route::post('admins/bulk-delete', [AdminController::class, 'bulkDestroy'])->name('admins.bulk-destroy');
        Route::resource('admins', AdminController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy'])
            ->parameters(['admins' => 'admin']);

        // #4 จัดการกลุ่ม
        Route::resource('groups', GroupController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy']);

        // #5 จัดการกลุ่มสาระ
        Route::resource('departments', DepartmentController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy']);

        // #6 จัดการตำแหน่ง
        Route::post('positions/{position}/move', [PositionController::class, 'move'])->name('positions.move');
        Route::resource('positions', PositionController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy']);

        // #2 จัดการบุคลากร (Users)
        Route::resource('personnel', PersonnelController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy'])
            ->parameters(['personnel' => 'user']);

        // #3 ลายเซ็นบุคลากร
        Route::get('signatures', [SignatureController::class, 'index'])->name('signatures.index');
        Route::post('signatures', [SignatureController::class, 'store'])->name('signatures.store');
        Route::get('signatures/{signature}', [SignatureController::class, 'show'])->name('signatures.show');
        Route::delete('signatures/{signature}', [SignatureController::class, 'destroy'])->name('signatures.destroy');

        // #11 ปรับปรุงดูแลฐานข้อมูล (Backup / Clear Cache)
        Route::get('maintenance', [DatabaseMaintenanceController::class, 'index'])->name('maintenance.index');
        Route::post('maintenance/clear-cache', [DatabaseMaintenanceController::class, 'clearCache'])->name('maintenance.clear-cache');
        Route::post('maintenance/optimize', [DatabaseMaintenanceController::class, 'optimize'])->name('maintenance.optimize');
        Route::post('maintenance/backup', [DatabaseMaintenanceController::class, 'backup'])->name('maintenance.backup');
        Route::delete('maintenance/backup/{name}', [DatabaseMaintenanceController::class, 'destroyBackup'])->name('maintenance.backup.destroy');
    });
});
