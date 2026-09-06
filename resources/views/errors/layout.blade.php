@php
    $map = [
        403 => ['Akses Ditolak', 'Anda tidak memiliki izin untuk mengakses halaman ini.', 'shield'],
        404 => ['Halaman Tidak Ditemukan', 'URL yang Anda tuju tidak ada atau sudah dipindahkan.', 'search'],
        419 => ['Sesi Kedaluwarsa', 'Sesi Anda berakhir. Silakan muat ulang dan coba lagi.', 'clock'],
        500 => ['Kesalahan Server', 'Terjadi gangguan internal. Tim teknis telah diberi tahu via log.', 'alert'],
        503 => ['Perawatan Sistem', 'Aplikasi sedang dalam pemeliharaan. Coba lagi beberapa saat.', 'wrench'],
    ];
    $code = $code ?? 500;
    [$title, $desc, $icon] = $map[$code] ?? $map[500];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} · {{ $title }} · Mining ERP</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100 dark:bg-navy-950">
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="bg-white dark:bg-navy-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-card p-10 max-w-md w-full text-center">
        <div class="mx-auto w-14 h-14 rounded-2xl bg-navy-900 dark:bg-white/5 text-amber-400 flex items-center justify-center font-black text-2xl" aria-hidden="true">{{ $code }}</div>
        <h1 class="mt-4 text-xl font-bold text-slate-800 dark:text-slate-100">{{ $title }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">{{ $exception?->getMessage() && app()->hasDebugModeEnabled() ? $exception->getMessage() : $desc }}</p>
        <div class="mt-6 flex justify-center gap-2">
            <a href="{{ url('/dashboard') }}" class="inline-block px-5 py-2.5 rounded-ctl bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Kembali ke Dashboard</a>
            <a href="/docs/troubleshooting/common" class="inline-block px-5 py-2.5 rounded-ctl bg-slate-100 dark:bg-white/10 text-sm">Bantuan</a>
        </div>
    </div>
</div>
</body>
</html>
