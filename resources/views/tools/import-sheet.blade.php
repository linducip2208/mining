@extends('layouts.app')

@section('title', 'Pilih Sheet')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
        <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Pilih Sheet — {{ $batch->type }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Workbook memiliki beberapa sheet. Pilih sheet yang akan diimport.
        </p>

        <form method="post" action="{{ route('imports.sheet.select', $batch) }}" class="mt-4 space-y-3">
            @csrf
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Sheet</label>
            <select name="sheet" required
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                @foreach($sheets as $sheet)
                    <option value="{{ $sheet }}">{{ $sheet }}</option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <button type="submit"
                    class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                    Lanjut ke Pemetaan
                </button>
                <a href="{{ route('imports.index') }}"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
