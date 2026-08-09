<?php

namespace Modules\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * แจ้งเตือนทั่วไปสำหรับเส้นทางอนุมัติ (เก็บลงฐานข้อมูล)
 */
class ApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $url,
        public string $type = 'info',
        public ?string $key = null,
    ) {
    }

    /**
     * ส่งผ่านช่องทางฐานข้อมูล
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * ข้อมูลที่บันทึกลงตาราง notifications
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'type' => $this->type,
            'key' => $this->key,
        ];
    }
}
