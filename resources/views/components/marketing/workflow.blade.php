{{-- Workflow strip: supported flow for this page intent. --}}
@props(['steps'])
@if($steps !== [])
<section aria-labelledby="alur" class="max-w-6xl mx-auto px-4 mt-12">
    <h2 id="alur" class="text-2xl font-bold tracking-tight">Alur yang Didukung</h2>
    <div class="mt-4 overflow-x-auto">
        <ol class="flex items-stretch gap-0 min-w-max">
            @foreach($steps as $i => $step)
            <li class="flex items-stretch">
                <span class="px-4 py-3 rounded-lg bg-[#0f172a] text-white text-sm font-semibold whitespace-nowrap">{{ $step }}</span>
                @if($i < count($steps) - 1)
                <span class="self-center px-1.5 text-amber-500 font-bold" aria-hidden="true">→</span>
                @endif
            </li>
            @endforeach
        </ol>
    </div>
</section>
@endif
