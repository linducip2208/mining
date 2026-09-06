<?php

namespace App\Support;

final class PermissionLabel
{
    private const MODULES = [
        'user' => 'Pengguna', 'role' => 'Peran & Izin', 'setting' => 'Pengaturan Sistem', 'audit' => 'Audit Trail',
        'company' => 'Perusahaan', 'branch' => 'Cabang', 'site' => 'Site Tambang', 'division' => 'Divisi',
        'employee' => 'Karyawan', 'attendance' => 'Absensi', 'leave' => 'Cuti', 'overtime' => 'Lembur', 'payroll' => 'Payroll',
        'mining' => 'Aktivitas Tambang', 'weighbridge' => 'Timbangan', 'production' => 'Produksi Crusher',
        'dispatch' => 'Dispatch', 'fleet' => 'Armada', 'fuel' => 'BBM', 'tire' => 'Ban', 'stockpile' => 'Stockpile',
        'inventory' => 'Inventory', 'stock' => 'Stok', 'stock_adjustment' => 'Penyesuaian Stok', 'stock_transfer' => 'Pemindahan Stok',
        'purchase_request' => 'Permintaan Pembelian', 'purchase_order' => 'Purchase Order', 'goods_receipt' => 'Penerimaan Barang', 'vendor_bill' => 'Tagihan Vendor',
        'fuel_issue' => 'Pengeluaran BBM', 'fuel_receipt' => 'Penerimaan BBM',
        'sales' => 'Penjualan', 'sales_order' => 'Sales Order', 'delivery_order' => 'Surat Jalan', 'invoice' => 'Faktur',
        'payment' => 'Pembayaran', 'deposit' => 'Deposit Customer', 'contract' => 'Kontrak', 'asset' => 'Aset',
        'equipment' => 'Peralatan', 'work_order' => 'Work Order', 'maintenance' => 'Maintenance', 'finance' => 'Keuangan',
        'journal' => 'Jurnal', 'ledger' => 'Buku Besar', 'budget' => 'Budget', 'tax' => 'Pajak', 'hse' => 'HSE',
        'compliance' => 'Compliance', 'document' => 'Dokumen', 'csr' => 'CSR', 'report' => 'Laporan', 'approval' => 'Persetujuan',
    ];

    private const ACTIONS = [
        'view' => 'Lihat', 'create' => 'Tambah', 'update' => 'Perbarui', 'delete' => 'Hapus', 'approve' => 'Setujui',
        'reject' => 'Tolak', 'post' => 'Posting', 'print' => 'Cetak', 'export' => 'Ekspor', 'void' => 'Batalkan',
        'override' => 'Override', 'activate' => 'Aktifkan', 'deactivate' => 'Nonaktifkan', 'suspend' => 'Tangguhkan',
        'reset_password' => 'Reset Password', 'assign_role' => 'Tetapkan Peran', 'view_login_history' => 'Lihat Riwayat Login',
        'logout_session' => 'Akhiri Sesi', 'close' => 'Tutup', 'reopen' => 'Buka Kembali', 'unpost' => 'Batalkan Posting',
    ];

    public static function label(?string $code): string
    {
        $parts = explode('.', (string) $code, 2);
        if (count($parts) !== 2) return HumanLabel::label($code);
        $module = self::MODULES[$parts[0]] ?? HumanLabel::label($parts[0]);
        $action = self::ACTIONS[$parts[1]] ?? HumanLabel::label($parts[1]);
        return $action . ' ' . $module;
    }

    public static function moduleLabel(?string $module): string
    {
        return self::MODULES[$module] ?? HumanLabel::label($module);
    }

    public static function actionLabel(?string $action): string
    {
        return self::ACTIONS[$action] ?? HumanLabel::label($action);
    }
}
