<?php

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Evaluation;
use Modules\Core\Models\PaAgreement;
use Modules\Core\Models\Supervision;
use Modules\Leave\Models\LeaveRequest;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Models\InterUnitMail;

class NotificationController extends Controller
{
    /**
     * รายการแจ้งเตือนทั้งหมด
     */
    public function index(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20)
            ->through(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? '',
                'message' => $n->data['message'] ?? '',
                'type' => $n->data['type'] ?? 'info',
                'key' => $n->data['key'] ?? null,
                'url' => $n->data['url'] ?? null,
                'is_read' => $n->read_at !== null,
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        return Inertia::render('Notification::Index', [
            'notificationPage' => $notifications,
            // Keep the legacy key for compatibility with any stale frontend bundle.
            'notifications' => $notifications,
        ]);
    }

    /**
     * เปิดการแจ้งเตือน: mark as read แล้วพาไปยังหน้าที่เกี่ยวข้อง
     */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $this->resolveTargetUrl($notification->data);

        return redirect()->to($url);
    }

    /**
     * อ่านทั้งหมด
     */
    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    public function purgeBroken(Request $request): RedirectResponse
    {
        $notifications = $request->user()->notifications()->get();

        $brokenIds = $notifications
            ->filter(fn ($notification) => $this->isBrokenNotification($notification->data))
            ->pluck('id');

        if ($brokenIds->isNotEmpty()) {
            DatabaseNotification::whereIn('id', $brokenIds)->delete();
        }

        return back()->with('success', 'ล้างแจ้งเตือนที่ลิงก์เสียแล้ว '.number_format($brokenIds->count()).' รายการ');
    }

    private function resolveTargetUrl(array $data): string
    {
        $key = $data['key'] ?? null;
        if (is_string($key) && $key !== '') {
            if ($url = $this->resolveByKey($key)) {
                return $url;
            }
        }

        $url = $data['url'] ?? null;
        if (is_string($url) && $url !== '') {
            if ($legacy = $this->resolveLegacyUrl($url)) {
                return $legacy;
            }

            if ($resolved = $this->resolveExistingInternalUrl($url)) {
                return $resolved;
            }

            if ($this->isSafeInternalUrl($url)) {
                return $url;
            }
        }

        return route('notifications.index');
    }

    private function resolveByKey(string $key): ?string
    {
        if (Str::startsWith($key, 'leave-handover-ack:')) {
            $id = (int) Str::after($key, 'leave-handover-ack:');
            return $id > 0 ? route('leave.requests.show', $id) : null;
        }

        if (Str::startsWith($key, 'leave-handover:')) {
            return route('leave.handover.inbox');
        }

        if (Str::startsWith($key, 'leave:')) {
            $id = (int) Str::after($key, 'leave:');
            return $id > 0 ? route('leave.requests.proposal', $id) : null;
        }

        if (Str::startsWith($key, 'pa:')) {
            $id = (int) Str::after($key, 'pa:');
            return $id > 0 ? route('pa.show', $id) : null;
        }

        if (Str::startsWith($key, 'evaluation-')) {
            $id = (int) Str::after($key, 'evaluation-');
            return $id > 0 ? route('evaluations.show', $id) : null;
        }

        if (Str::startsWith($key, 'supervision-')) {
            $id = (int) Str::after($key, 'supervision-');
            return $id > 0 ? route('supervisions.show', $id) : null;
        }

        if (Str::startsWith($key, 'doc-hand:') || Str::startsWith($key, 'document:')) {
            $id = (int) Str::after($key, Str::startsWith($key, 'doc-hand:') ? 'doc-hand:' : 'document:');
            return $id > 0 ? route('saraban.documents.show', $id) : null;
        }

        if (Str::startsWith($key, 'iumail:')) {
            $id = (int) explode(':', $key, 3)[1];
            return $id > 0 ? route('saraban.area-mail.show', $id) : null;
        }

        if (Str::startsWith($key, 'personal:')) {
            return route('saraban.personal-documents.index');
        }

        return null;
    }

    private function resolveLegacyUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        if (Str::contains($path, 'leave.index')) {
            return route('leave.requests.index');
        }

        return null;
    }

    private function resolveExistingInternalUrl(string $url): ?string
    {
        $path = trim((string) (parse_url($url, PHP_URL_PATH) ?: $url));
        $path = $path === '' ? '/' : $path;

        if (preg_match('#^/saraban/documents/(\d+)$#', $path, $m)) {
            return Document::withTrashed()->whereKey((int) $m[1])->exists()
                ? route('saraban.documents.show', (int) $m[1])
                : route('saraban.documents.index');
        }

        if (preg_match('#^/leave/requests/(\d+)$#', $path, $m)) {
            return LeaveRequest::find((int) $m[1])
                ? route('leave.requests.show', (int) $m[1])
                : route('leave.requests.folder');
        }

        if (preg_match('#^/leave/requests/(\d+)/proposal$#', $path, $m)) {
            return LeaveRequest::find((int) $m[1])
                ? route('leave.requests.proposal', (int) $m[1])
                : route('leave.requests.folder');
        }

        if (preg_match('#^/leave/handover$#', $path)) {
            return route('leave.handover.inbox');
        }

        if (preg_match('#^/pa/(\d+)$#', $path, $m)) {
            return PaAgreement::find((int) $m[1])
                ? route('pa.show', (int) $m[1])
                : route('pa.index');
        }

        if (preg_match('#^/supervisions/(\d+)$#', $path, $m)) {
            return Supervision::find((int) $m[1])
                ? route('supervisions.show', (int) $m[1])
                : route('supervisions.index');
        }

        if (preg_match('#^/evaluations/(\d+)$#', $path, $m)) {
            return Evaluation::find((int) $m[1])
                ? route('evaluations.show', (int) $m[1])
                : route('evaluations.index');
        }

        if (preg_match('#^/saraban/area-mail/(\d+)$#', $path, $m)) {
            return InterUnitMail::find((int) $m[1])
                ? route('saraban.area-mail.show', (int) $m[1])
                : route('saraban.area-mail.inbox');
        }

        if (preg_match('#^/saraban/personal-documents$#', $path)) {
            return route('saraban.personal-documents.index');
        }

        if (preg_match('#^/notifications$#', $path)) {
            return route('notifications.index');
        }

        return null;
    }

    private function isBrokenNotification(array $data): bool
    {
        $key = $data['key'] ?? null;
        if (is_string($key) && $key !== '' && $this->isBrokenKey($key)) {
            return true;
        }

        $url = $data['url'] ?? null;
        if (is_string($url) && $url !== '' && $this->isBrokenUrl($url)) {
            return true;
        }

        return false;
    }

    private function isBrokenKey(string $key): bool
    {
        if (Str::startsWith($key, 'leave-handover-ack:')) {
            return ! LeaveRequest::find((int) Str::after($key, 'leave-handover-ack:'));
        }

        if (Str::startsWith($key, 'leave-handover:')) {
            return false;
        }

        if (Str::startsWith($key, 'leave:')) {
            return ! LeaveRequest::find((int) Str::after($key, 'leave:'));
        }

        if (Str::startsWith($key, 'pa:')) {
            return ! PaAgreement::find((int) Str::after($key, 'pa:'));
        }

        if (Str::startsWith($key, 'evaluation-')) {
            return ! Evaluation::find((int) Str::after($key, 'evaluation-'));
        }

        if (Str::startsWith($key, 'supervision-')) {
            return ! Supervision::find((int) Str::after($key, 'supervision-'));
        }

        if (Str::startsWith($key, 'doc-hand:') || Str::startsWith($key, 'document:')) {
            $id = (int) Str::after($key, Str::startsWith($key, 'doc-hand:') ? 'doc-hand:' : 'document:');

            return ! Document::withTrashed()->whereKey($id)->exists();
        }

        if (Str::startsWith($key, 'iumail:')) {
            $parts = explode(':', $key, 3);
            $id = isset($parts[1]) ? (int) $parts[1] : 0;

            return $id > 0 ? ! InterUnitMail::find($id) : true;
        }

        return false;
    }

    private function isBrokenUrl(string $url): bool
    {
        $path = trim((string) (parse_url($url, PHP_URL_PATH) ?: $url));
        $path = $path === '' ? '/' : $path;

        if (preg_match('#^/saraban/documents/(\d+)$#', $path, $m)) {
            return ! Document::withTrashed()->whereKey((int) $m[1])->exists();
        }

        if (preg_match('#^/leave/requests/(\d+)$#', $path, $m)) {
            return ! LeaveRequest::find((int) $m[1]);
        }

        if (preg_match('#^/leave/requests/(\d+)/proposal$#', $path, $m)) {
            return ! LeaveRequest::find((int) $m[1]);
        }

        if (preg_match('#^/pa/(\d+)$#', $path, $m)) {
            return ! PaAgreement::find((int) $m[1]);
        }

        if (preg_match('#^/supervisions/(\d+)$#', $path, $m)) {
            return ! Supervision::find((int) $m[1]);
        }

        if (preg_match('#^/evaluations/(\d+)$#', $path, $m)) {
            return ! Evaluation::find((int) $m[1]);
        }

        if (preg_match('#^/saraban/area-mail/(\d+)$#', $path, $m)) {
            return ! InterUnitMail::find((int) $m[1]);
        }

        return false;
    }

    private function isSafeInternalUrl(string $url): bool
    {
        if (Str::startsWith($url, ['/'])) {
            return true;
        }

        $baseUrl = rtrim((string) config('app.url'), '/');

        return $baseUrl !== '' && Str::startsWith($url, $baseUrl.'/');
    }
}
