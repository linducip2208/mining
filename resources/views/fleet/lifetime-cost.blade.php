@extends('layouts.app')

@section('title', ' - Biaya Sepanjang Masa Unit')

@section('content')
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold text-slate-800 dark:text-white">{{ $title }}</h1>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Unit</label>
                <select name="equipment_id"
                    class="mt-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" @selected($equipment?->id === $unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">Tampilkan</button>
        </form>
    </div>

    @if($equipment && $summary)
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach([
            'BBM' => $summary['fuel_cost'],
            'Sparepart' => $summary['sparepart_cost'],
            'Tenaga Kerja' => $summary['labor_cost'],
            'Servis Luar' => $summary['external_cost'],
            'Lain-lain' => $summary['other_cost'],
            'Penyusutan' => $summary['depreciation'],
        ] as $label => $value)
        <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $label }}</p>
            <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-white">Rp {{ number_format($value, 0, ',', '.') }}</p>
        </div>
        @endforeach
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-800 dark:text-white">Total Biaya Sepanjang Masa</h2>
            <p class="text-lg font-bold text-primary-600 dark:text-primary-400">Rp {{ number_format($summary['total_cost'], 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach([
            'Biaya / HM' => $summary['cost_per_hm'] !== null ? 'Rp '.number_format($summary['cost_per_hm'], 0, ',', '.') : 'N/A',
            'Biaya / KM' => $summary['cost_per_km'] !== null ? 'Rp '.number_format($summary['cost_per_km'], 0, ',', '.') : 'N/A',
            'Biaya / Ton' => $summary['cost_per_ton'] !== null ? 'Rp '.number_format($summary['cost_per_ton'], 0, ',', '.') : 'N/A',
        ] as $label => $value)
        <div class="rounded-xl border border-slate-200 bg-white p-4 text-center dark:border-slate-700 dark:bg-slate-900">
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $label }}</p>
            <p class="mt-1 text-xl font-bold text-slate-800 dark:text-white">{{ $value }}</p>
        </div>
        @endforeach
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
        Denominator: HM sepanjang masa {{ number_format($summary['lifetime_hm'], 1) }} ·
        KM sepanjang masa {{ number_format($summary['lifetime_km'], 1) }} ·
        Tonase terkait unit {{ number_format($summary['lifetime_ton'], 2) }}.
        N/A berarti denominator tidak tersedia (belum ada data HM/KM/tonase), bukan nol.
    </div>
    @else
    <div class="rounded-xl border border-slate-200 bg-white p-10 text-center text-slate-400 dark:border-slate-700 dark:bg-slate-900">Pilih unit untuk melihat biaya sepanjang masa.</div>
    @endif
</div>
@endsection
