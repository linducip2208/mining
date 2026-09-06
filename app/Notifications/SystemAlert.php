<?php

namespace App\Notifications;

use App\Models\Setting;
use App\Services\BrandingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SystemAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $eventType,
        public string $title,
        public $items
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $lines = $this->items->take(5)->map(function ($item) {
            if (isset($item->number)) {
                return $item->number;
            }
            if (isset($item->name)) {
                return $item->name;
            }

            return 'Item tanpa referensi';
        })->implode(', ');

        return [
            'type' => $this->eventType,
            'title' => $this->title,
            'body' => "{$this->items->count()} item: {$lines}".($this->items->count() > 5 ? ', dll.' : ''),
            'count' => $this->items->count(),
            'url' => '/dashboard',
        ];
    }

    /**
     * WhatsApp integration-ready: jika webhook dikonfigurasi di Settings
     * (key: notification.whatsapp_webhook_url), alert juga dikirim ke sana.
     */
    public function toWhatsApp($notifiable): void
    {
        $url = Setting::get('notification.whatsapp_webhook_url');
        if (empty($url)) {
            return;
        }
        try {
            Http::timeout(5)->post($url, [
                'phone' => $notifiable->phone,
                'event' => $this->eventType,
                'message' => '['.BrandingService::appName().'] '.$this->title.' — '.$this->items->count().' item',
            ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp alert gagal: '.$e->getMessage());
        }
    }
}
