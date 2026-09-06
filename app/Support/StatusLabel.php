<?php

namespace App\Support;

final class StatusLabel
{
    private const LABELS = [
        'DRAFT' => 'Draf', 'SUBMITTED' => 'Diajukan', 'PENDING' => 'Menunggu', 'PENDING_APPROVAL' => 'Menunggu Persetujuan',
        'APPROVED' => 'Disetujui', 'POSTED' => 'Diposting', 'COMPLETED' => 'Selesai', 'COMPLETE' => 'Selesai',
        'PAID' => 'Lunas', 'PARTIALLY_PAID' => 'Dibayar Sebagian', 'PARTIALLY_DELIVERED' => 'Dikirim Sebagian',
        'REJECTED' => 'Ditolak', 'CANCELLED' => 'Dibatalkan', 'VOID' => 'Dibatalkan', 'IN_PROGRESS' => 'Sedang Diproses',
        'WAITING_REVIEW' => 'Menunggu Review', 'QUALITY_HOLD' => 'Ditahan Quality Control', 'CLOSED' => 'Ditutup',
        'CALCULATED' => 'Dihitung', 'RETURNED' => 'Dikembalikan', 'FAVORABLE' => 'Menguntungkan', 'UNFAVORABLE' => 'Tidak Menguntungkan',
        'ACTIVE' => 'Aktif', 'INACTIVE' => 'Tidak Aktif', 'LOCKED' => 'Terkunci', 'SUSPENDED' => 'Ditangguhkan',
        'FIRST_WEIGH' => 'Timbang Pertama', 'VALIDATED' => 'Tervalidasi', 'OPEN' => 'Terbuka', 'BREAKDOWN' => 'Rusak',
        'MAINTENANCE' => 'Dalam Pemeliharaan', 'UNDER_MAINTENANCE' => 'Dalam Pemeliharaan', 'OUT_OF_SERVICE' => 'Tidak Beroperasi',
        'IN_USE' => 'Sedang Dipakai', 'AVAILABLE' => 'Tersedia', 'DEPOSIT_IN' => 'Deposit Masuk', 'DEPOSIT_USED' => 'Deposit Terpakai',
        'DEPOSIT_REFUND' => 'Pengembalian Deposit', 'PARTIALLY_RECEIVED' => 'Diterima Sebagian', 'REVISED' => 'Direvisi',
    ];

    public static function label(?string $status): string
    {
        if ($status === null || trim($status) === '') return "\u{2014}";
        $key = strtoupper((string) $status);
        return self::LABELS[$key] ?? HumanLabel::label($key);
    }

    public static function color(?string $status): string
    {
        $key = strtoupper((string) $status);
        return match (true) {
            in_array($key, ['APPROVED', 'COMPLETED', 'COMPLETE', 'PAID', 'VALIDATED', 'ACTIVE', 'AVAILABLE', 'FAVORABLE']) => 'bg-green-50 text-green-700',
            in_array($key, ['REJECTED', 'CANCELLED', 'VOID', 'BREAKDOWN', 'LOCKED', 'UNFAVORABLE', 'OUT_OF_SERVICE']) => 'bg-red-50 text-red-600',
            in_array($key, ['PENDING', 'PENDING_APPROVAL', 'MAINTENANCE', 'UNDER_MAINTENANCE', 'RETURNED', 'SUSPENDED']) => 'bg-amber-50 text-amber-700',
            in_array($key, ['SUBMITTED', 'IN_PROGRESS', 'OPEN', 'IN_USE', 'FIRST_WEIGH']) => 'bg-blue-50 text-blue-600',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public static function icon(?string $status): string
    {
        return match (strtoupper((string) $status)) {
            'APPROVED', 'COMPLETED', 'COMPLETE', 'PAID', 'VALIDATED', 'ACTIVE', 'AVAILABLE' => 'check-circle',
            'REJECTED', 'CANCELLED', 'VOID', 'BREAKDOWN', 'LOCKED' => 'alert',
            'PENDING', 'PENDING_APPROVAL', 'MAINTENANCE', 'UNDER_MAINTENANCE' => 'clock',
            default => 'file',
        };
    }
}
