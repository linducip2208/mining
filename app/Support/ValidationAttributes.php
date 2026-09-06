<?php

namespace App\Support;

final class ValidationAttributes
{
    private const LABELS = [
        'site_id' => 'Site', 'pit_id' => 'Pit', 'company_id' => 'Perusahaan', 'branch_id' => 'Cabang',
        'division_id' => 'Divisi', 'department_id' => 'Departemen', 'employee_id' => 'Karyawan',
        'customer_id' => 'Customer', 'supplier_id' => 'Supplier', 'equipment_id' => 'Unit / Peralatan',
        'vehicle_id' => 'Kendaraan', 'warehouse_id' => 'Gudang', 'item_id' => 'Item', 'date_from' => 'Tanggal Mulai',
        'date_to' => 'Tanggal Akhir', 'approval_status' => 'Status Persetujuan', 'posting_status' => 'Status Posting',
        'email' => 'Email', 'password' => 'Kata Sandi', 'password_confirmation' => 'Konfirmasi Kata Sandi',
    ];

    public static function all(): array
    {
        return self::LABELS;
    }

    public static function label(string $attribute): string
    {
        return self::LABELS[$attribute] ?? HumanLabel::label($attribute);
    }
}
