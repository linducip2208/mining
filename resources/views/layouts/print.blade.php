<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentTitle ?? $appName }}</title>
    <style>
        @page { size: {{ $paperSize }} {{ $orientation }}; margin: {{ $margins['top'] }}mm {{ $margins['right'] }}mm {{ $margins['bottom'] }}mm {{ $margins['left'] }}mm; }
    </style>
    <style>{!! file_get_contents(resource_path('css/print.css')) !!}</style>
    @stack('print-styles')
</head>
<body>
    @if ($watermarkEnabled && !empty($watermark)) <x-print.watermark :text="$watermark" :opacity="$watermarkOpacity" /> @endif
    <main class="print-document">@yield('document')</main>
</body>
</html>
