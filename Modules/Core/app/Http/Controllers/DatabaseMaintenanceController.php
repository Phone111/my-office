<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ปรับปรุงดูแลฐานข้อมูล — สำรองข้อมูล (Backup) และล้าง/สร้าง Cache
 */
class DatabaseMaintenanceController extends Controller
{
    private function backupDir(): string
    {
        return storage_path('app/backups');
    }

    public function index(): Response
    {
        $backups = collect(File::isDirectory($this->backupDir()) ? File::files($this->backupDir()) : [])
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->map(fn ($f) => [
                'name' => $f->getFilename(),
                'size_kb' => round($f->getSize() / 1024, 1),
                'created_at' => date('Y-m-d H:i', $f->getMTime()),
            ])
            ->values();

        return Inertia::render('Core::Admin/Maintenance', [
            'backups' => $backups,
        ]);
    }

    /**
     * ล้าง cache ทั้งหมด
     */
    public function clearCache(): RedirectResponse
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return back()->with('success', 'ล้าง cache ทั้งหมดเรียบร้อยแล้ว');
    }

    /**
     * สร้าง cache เพื่อเพิ่มประสิทธิภาพ (เลี่ยง route:cache เพราะมี closure route)
     */
    public function optimize(): RedirectResponse
    {
        Artisan::call('config:cache');
        Artisan::call('view:cache');

        return back()->with('success', 'สร้าง cache (config/view) เรียบร้อยแล้ว');
    }

    /**
     * สำรองฐานข้อมูลด้วย mysqldump
     */
    public function backup(): RedirectResponse
    {
        $db = config('database.connections.'.config('database.default'));
        File::ensureDirectoryExists($this->backupDir());

        $file = $this->backupDir().'/'.now()->format('Y-m-d_His').'.sql';

        $command = sprintf(
            'mysqldump -h%s -P%s -u%s %s %s > %s',
            escapeshellarg((string) $db['host']),
            escapeshellarg((string) $db['port']),
            escapeshellarg((string) $db['username']),
            $db['password'] ? '-p'.escapeshellarg((string) $db['password']) : '',
            escapeshellarg((string) $db['database']),
            escapeshellarg($file),
        );

        $result = Process::timeout(300)->run($command);

        if ($result->failed()) {
            return back()->with('error', 'สำรองข้อมูลไม่สำเร็จ (ต้องมี mysqldump): '.trim($result->errorOutput()));
        }

        return back()->with('success', 'สำรองฐานข้อมูลเรียบร้อยแล้ว: '.basename($file));
    }

    /**
     * ลบไฟล์สำรอง
     */
    public function destroyBackup(string $name): RedirectResponse
    {
        $path = $this->backupDir().'/'.basename($name);

        if (File::exists($path)) {
            File::delete($path);
        }

        return back()->with('success', 'ลบไฟล์สำรองเรียบร้อยแล้ว');
    }
}
