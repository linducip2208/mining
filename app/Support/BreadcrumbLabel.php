<?php

namespace App\Support;

use Illuminate\Support\Str;

final class BreadcrumbLabel
{
    private const LABELS = [
        'dashboard' => 'Command Center', 'users' => 'Pengguna', 'user' => 'Pengguna', 'roles' => 'Peran & Izin', 'role' => 'Peran & Izin',
        'settings' => 'Pengaturan Sistem', 'setting' => 'Pengaturan Sistem', 'finance' => 'Keuangan', 'fleet' => 'Armada', 'fuel' => 'BBM',
        'mining' => 'Operasi Tambang', 'production' => 'Produksi', 'dispatch' => 'Dispatch', 'stockpiles' => 'Stockpile', 'stockpile' => 'Stockpile',
        'weighbridge' => 'Timbangan', 'approval' => 'Persetujuan', 'approvals' => 'Persetujuan', 'reports' => 'Laporan', 'report' => 'Laporan',
        'purchase-orders' => 'Purchase Order', 'purchase-requests' => 'Permintaan Pembelian', 'sales-orders' => 'Sales Order',
        'delivery-orders' => 'Surat Jalan', 'fuel-issues' => 'Pengeluaran BBM', 'stock-adjustments' => 'Penyesuaian Stok',
        'stock-transfers' => 'Pemindahan Stok', 'goods-receipts' => 'Penerimaan Barang',
        'index' => 'Daftar', 'create' => 'Tambah', 'store' => 'Simpan', 'edit' => 'Ubah', 'update' => 'Perbarui', 'show' => 'Detail',
    ];

    public static function label(?string $segment): string
    {
        $key = Str::lower((string) $segment);
        return self::LABELS[$key] ?? HumanLabel::label($key);
    }
}
