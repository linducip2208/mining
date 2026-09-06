@php
    $query = array_merge(request()->query(), $params ?? []);
@endphp
<nav class="flex flex-wrap items-center justify-between gap-2 mt-4" aria-label="Paginasi">
    <div class="text-sm text-slate-500 dark:text-slate-400">
        Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
    </div>
    <div class="flex gap-1">
        @if ($paginator->onFirstPage())
            <span class="px-3 py-1.5 text-sm rounded-ctl bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700 text-slate-300 dark:text-slate-600">&laquo;</span>
        @else
            <a href="{{ $paginator->url($paginator->currentPage() - 1) }}" rel="prev" aria-label="Halaman sebelumnya" class="px-3 py-1.5 text-sm rounded-ctl bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-white/5">&laquo;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-3 py-1.5 text-sm rounded-ctl text-slate-400">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="px-3 py-1.5 text-sm rounded-ctl bg-amber-500 text-white font-semibold">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="px-3 py-1.5 text-sm rounded-ctl bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-white/5">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->url($paginator->currentPage() + 1) }}" rel="next" aria-label="Halaman berikutnya" class="px-3 py-1.5 text-sm rounded-ctl bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-white/5">&raquo;</a>
        @else
            <span class="px-3 py-1.5 text-sm rounded-ctl bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700 text-slate-300 dark:text-slate-600">&raquo;</span>
        @endif
    </div>
</nav>
