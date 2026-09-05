<?php

namespace App\Notifications;

use App\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class ApprovalPending extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ApprovalRequest $request)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'APPROVAL_PENDING',
            'title' => 'Persetujuan diperlukan',
            'body' => sprintf('%s %s menunggu persetujuan Anda (Rp %s).', $this->request->module, $this->request->transaction_number, number_format((float) $this->request->amount, 0, ',', '.')),
            'approval_request_id' => $this->request->id,
            'url' => '/approvals/' . $this->request->id,
        ];
    }
}
