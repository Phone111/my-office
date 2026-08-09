<?php

use Illuminate\Support\Facades\Route;
use Modules\Saraban\Http\Controllers\CertificateController;
use Modules\Saraban\Http\Controllers\CircularController;
use Modules\Saraban\Http\Controllers\DocumentController;
use Modules\Saraban\Http\Controllers\DocumentDestroyController;
use Modules\Saraban\Http\Controllers\ExternalIncomingController;
use Modules\Saraban\Http\Controllers\GroupActivityController;
use Modules\Saraban\Http\Controllers\GroupDelegateController;
use Modules\Saraban\Http\Controllers\GroupDocumentController;
use Modules\Saraban\Http\Controllers\IncomingController;
use Modules\Saraban\Http\Controllers\InterUnitMailController;
use Modules\Saraban\Http\Controllers\OrderController;
use Modules\Saraban\Http\Controllers\OutgoingController;
use Modules\Saraban\Http\Controllers\PersonalDocumentController;
use Modules\Saraban\Http\Controllers\SequenceController;
use Modules\Saraban\Http\Controllers\SarabanSettingController;
use Modules\Saraban\Http\Controllers\SarabanYearController;
use Modules\Saraban\Http\Controllers\SchoolGroupController;

Route::middleware(['auth', 'verified'])
    ->prefix('saraban')
    ->name('saraban.')
    ->group(function () {
        // แฟ้มเอกสาร แยกตามหมวดหมู่ (เขียน/ส่ง)
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('documents/create', [DocumentController::class, 'create'])->name('documents.create');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
        Route::put('documents/{document}/resubmit', [DocumentController::class, 'resubmit'])->name('documents.resubmit');
        Route::get('documents/drafts', [DocumentController::class, 'drafts'])->name('documents.drafts');
        Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
        Route::post('documents/{document}/file', [DocumentController::class, 'file'])->name('documents.file');
        Route::post('documents/{document}/hand-to-saraban', [DocumentController::class, 'handToSaraban'])->name('documents.hand-to-saraban');
        Route::post('documents/{document}/propose', [DocumentController::class, 'propose'])->name('documents.propose');
        Route::post('documents/{document}/attach', [DocumentController::class, 'attachFile'])->name('documents.attach');
        Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

        // ลงทะเบียนรับหนังสือนอกระบบ (เจ้าหน้าที่สารบรรณ)
        Route::middleware('role:admin|saraban|secretary')->group(function () {
            Route::get('incoming/register', [IncomingController::class, 'create'])->name('incoming.create');
            Route::post('incoming/register', [IncomingController::class, 'store'])->name('incoming.store');
            // จัดการปีสารบรรณ
            Route::get('saraban-year', [SarabanYearController::class, 'index'])->name('year.index');
            Route::post('saraban-year', [SarabanYearController::class, 'setYear'])->name('year.set');
            Route::post('saraban-year/system', [SarabanYearController::class, 'useSystemYear'])->name('year.system');

            // ระบบทำลายหนังสือ (soft delete + ทะเบียนทำลาย)
            Route::get('destroy', [DocumentDestroyController::class, 'index'])->name('destroy.index');
            Route::post('destroy/run', [DocumentDestroyController::class, 'run'])->name('destroy.run');
            Route::post('destroy/{id}/restore', [DocumentDestroyController::class, 'restore'])->name('destroy.restore');

            // ออกเลขทะเบียนส่ง (หนังสือส่งออก)
            Route::get('outgoing/register', [OutgoingController::class, 'create'])->name('outgoing.create');
            Route::post('outgoing/register', [OutgoingController::class, 'store'])->name('outgoing.store');
            // แฟ้มรอแนบไฟล์ส่ง + แนบไฟล์หนังสือส่ง
            Route::get('outgoing/pending', [OutgoingController::class, 'pending'])->name('outgoing.pending');
            Route::get('outgoing/{document}/attach', [OutgoingController::class, 'attachForm'])->name('outgoing.attach-form');
            Route::post('outgoing/{document}/attach', [OutgoingController::class, 'attach'])->name('outgoing.attach');
            Route::get('outgoing/{document}', [OutgoingController::class, 'show'])->name('outgoing.show');
            // ออกเลขคำสั่ง + แนบไฟล์คำสั่ง
            Route::get('orders/register', [OrderController::class, 'create'])->name('orders.create');
            Route::post('orders/register', [OrderController::class, 'store'])->name('orders.store');
            Route::get('orders/pending', [OrderController::class, 'pending'])->name('orders.pending');
            Route::get('orders/{document}/attach', [OrderController::class, 'attachForm'])->name('orders.attach-form');
            Route::post('orders/{document}/attach', [OrderController::class, 'attach'])->name('orders.attach');
        });

        // ออกเลขลำดับเอกสาร + ทะเบียนลำดับเอกสาร — ทุกบุคลากรออกเลขได้ (ตามระบบเก่า)
        Route::get('sequence', [SequenceController::class, 'create'])->name('sequence.create');
        Route::post('sequence', [SequenceController::class, 'store'])->name('sequence.store');
        Route::get('sequence/registry', [SequenceController::class, 'index'])->name('sequence.registry');

        // ส่งหนังสือเวียนภายใน (broadcast ถึงกลุ่ม)
        Route::get('circulars', [CircularController::class, 'index'])->name('circulars.index');
        Route::get('circulars/inbox', [CircularController::class, 'inbox'])->name('circulars.inbox');
        Route::get('circulars/create', [CircularController::class, 'create'])->name('circulars.create');
        Route::post('circulars', [CircularController::class, 'store'])->name('circulars.store');
        Route::post('circulars/{circular}/file', [CircularController::class, 'file'])->name('circulars.file');
        Route::post('circulars/{circular}/respond', [CircularController::class, 'respond'])->name('circulars.respond');
        Route::get('circulars/{circular}', [CircularController::class, 'show'])->name('circulars.show');

        // ส่งเอกสารส่วนตัว (ส่งถึงบุคคลโดยตรง)
        Route::get('personal-documents', [PersonalDocumentController::class, 'index'])->name('personal-documents.index');
        Route::get('personal-documents/create', [PersonalDocumentController::class, 'create'])->name('personal-documents.create');
        Route::post('personal-documents', [PersonalDocumentController::class, 'store'])->name('personal-documents.store');
        Route::post('personal-documents/{personalDocument}/read', [PersonalDocumentController::class, 'markRead'])->name('personal-documents.read');
        Route::post('personal-documents/{personalDocument}/file', [PersonalDocumentController::class, 'file'])->name('personal-documents.file');

        // แฟ้มเอกสารรอดำเนินการ (ผู้อนุมัติ)
        Route::get('inbox', [DocumentController::class, 'inbox'])->name('documents.inbox');
        Route::get('inbox/acted', [DocumentController::class, 'acted'])->name('documents.acted');
        Route::get('rejected', [DocumentController::class, 'rejected'])->name('documents.rejected');

        // เอกสารของกลุ่ม (หัวหน้ากลุ่ม/ธุรการกลุ่ม ดูเอกสารของสมาชิก)
        Route::middleware('role:head_of_department|head_of_subject|group_clerk|secretary|admin')
            ->get('group-documents', [GroupDocumentController::class, 'index'])->name('group.documents');

        // จัดการผู้ปฏิบัติงานแทน (หัวหน้ากลุ่มแต่งตั้ง จนท.ธุรการกลุ่ม)
        Route::middleware('role:head_of_department|head_of_subject|secretary|admin')->group(function () {
            Route::get('group-delegates', [GroupDelegateController::class, 'index'])->name('group.delegates');
            Route::post('group-delegates', [GroupDelegateController::class, 'save'])->name('group.delegates.save');
        });

        // รับส่งหนังสือราชการระหว่างหน่วยงาน (ระบบเขต) — สารบรรณเขต/โรงเรียน/เลขาฯ/admin
        Route::middleware('role:saraban|secretary|admin|school_clerk|group_clerk')->prefix('area-mail')->name('area-mail.')->group(function () {
            Route::get('compose', [InterUnitMailController::class, 'compose'])->name('compose');
            Route::post('/', [InterUnitMailController::class, 'store'])->name('store');
            Route::get('outbox', [InterUnitMailController::class, 'outbox'])->name('outbox');
            Route::get('inbox', [InterUnitMailController::class, 'inbox'])->name('inbox');
            Route::get('track', [InterUnitMailController::class, 'track'])->name('track');
            Route::get('{mail}', [InterUnitMailController::class, 'show'])->name('show');
            Route::post('{mail}/receive', [InterUnitMailController::class, 'receive'])->name('receive');
            Route::post('{mail}/assign-group', [InterUnitMailController::class, 'assignGroup'])->name('assign-group');
            Route::post('{mail}/forward', [InterUnitMailController::class, 'forward'])->name('forward');
        });

        // จัดการกลุ่มโรงเรียน (กลุ่มสถานศึกษา) — AMSS ส่วน 16 ตั้งค่าระบบ
        Route::middleware('role:admin|saraban|secretary')->prefix('school-groups')->name('school-groups.')->group(function () {
            Route::get('/', [SchoolGroupController::class, 'index'])->name('index');
            Route::post('/', [SchoolGroupController::class, 'store'])->name('store');
            Route::put('{schoolGroup}', [SchoolGroupController::class, 'update'])->name('update');
            Route::delete('{schoolGroup}', [SchoolGroupController::class, 'destroy'])->name('destroy');
        });

        // รับหนังสือจากหน่วยงานภายนอก (เหนือเขต) — สพฐ./ศธจ./จังหวัด (สารบรรณเขต)
        Route::middleware('role:saraban|secretary|admin|area_admin')->prefix('external-mail')->name('external-mail.')->group(function () {
            Route::get('/', [ExternalIncomingController::class, 'index'])->name('index');
            Route::get('create', [ExternalIncomingController::class, 'create'])->name('create');
            Route::post('/', [ExternalIncomingController::class, 'store'])->name('store');
            Route::get('{external_mail}', [ExternalIncomingController::class, 'show'])->name('show');
            Route::post('{external_mail}/assign', [ExternalIncomingController::class, 'assign'])->name('assign');
        });

        // ปฏิทินกิจกรรมของกลุ่ม
        Route::middleware('role:group_clerk|head_of_department|head_of_subject|secretary|admin')->group(function () {
            Route::get('group-activities', [GroupActivityController::class, 'index'])->name('group.activities');
            Route::post('group-activities', [GroupActivityController::class, 'store'])->name('group.activities.store');
            Route::delete('group-activities/{groupActivity}', [GroupActivityController::class, 'destroy'])->name('group.activities.destroy');
        });
        Route::post('routes/{route}/approve', [DocumentController::class, 'approve'])->name('routes.approve');
        Route::post('routes/{route}/forward', [DocumentController::class, 'forward'])->name('routes.forward');
        Route::post('routes/{route}/reject', [DocumentController::class, 'reject'])->name('routes.reject');

        // ทะเบียนเลขเกียรติบัตร (เจ้าหน้าที่สารบรรณ/ธุรการ/ผู้บริหาร)
        Route::middleware('role:admin|saraban|secretary|director|deputy_director')->group(function () {
            Route::get('certificates', [CertificateController::class, 'index'])->name('certificates.index');
            Route::post('certificates', [CertificateController::class, 'store'])->name('certificates.store');
        });

        // #12 ตั้งค่าระบบงานสารบรรณ — เลขทะเบียน (admin)
        Route::middleware('role:admin')->prefix('settings')->name('settings.')->group(function () {
            Route::get('numbers', [SarabanSettingController::class, 'index'])->name('numbers.index');
            Route::post('numbers', [SarabanSettingController::class, 'store'])->name('numbers.store');
            Route::get('numbers/{counter}', [SarabanSettingController::class, 'show'])->name('numbers.show');
            Route::put('numbers/{counter}', [SarabanSettingController::class, 'update'])->name('numbers.update');
            Route::delete('numbers/{counter}', [SarabanSettingController::class, 'destroy'])->name('numbers.destroy');
        });
    });
