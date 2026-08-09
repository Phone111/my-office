<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * หน้ารวมระบบการเงินและบัญชี (hub) — จัดลิงก์เป็นกลุ่มตามคู่มือ AMSS
 * แทนการแสดงเมนูย่อยทั้งหมดใน sidebar
 */
class FinanceHubController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Finance::Hub');
    }
}
