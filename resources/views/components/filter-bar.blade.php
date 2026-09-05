@props(['route', 'label' => 'Filter'])
<form method="GET" action="{{ $route }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4 flex flex-wrap gap-3 items-end">
    {{ $slot }}
    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium">{{ $label }}</button>
    @if (count(array_filter(request()->query())))
        <a href="{{ $route }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm">Reset</a>
    @endif
</form>
