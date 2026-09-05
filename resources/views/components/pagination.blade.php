@php
    $query = array_merge(request()->query(), $params ?? []);
@endphp
<nav class="flex items-center justify-between mt-4">
    <div class="text-sm text-slate-500">
        Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
    </div>
    <div class="flex gap-1">
        @if ($paginator->onFirstPage())
            <span class="px-3 py-1.5 text-sm rounded-md bg-white border border-slate-200 text-slate-300">&laquo;</span>
        @else
            <a href="{{ $paginator->url($paginator->currentPage() - 1) }}" class="px-3 py-1.5 text-sm rounded-md bg-white border border-slate-200 hover:bg-slate-50">&laquo;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-3 py-1.5 text-sm rounded-md text-slate-400">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="px-3 py-1.5 text-sm rounded-md bg-amber-500 text-white font-semibold">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="px-3 py-1.5 text-sm rounded-md bg-white border border-slate-200 hover:bg-slate-50">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->url($paginator->currentPage() + 1) }}" class="px-3 py-1.5 text-sm rounded-md bg-white border border-slate-200 hover:bg-slate-50">&raquo;</a>
        @else
            <span class="px-3 py-1.5 text-sm rounded-md bg-white border border-slate-200 text-slate-300">&raquo;</span>
        @endif
    </div>
</nav>
