{{-- Timeline: items = [['title'=>, 'body'=>?, 'time'=>?, 'done'=>bool]] --}}
@props(['items' => []])
<ol {{ $attributes->merge(['class' => 'relative space-y-4 before:absolute before:left-[7px] before:top-2 before:bottom-2 before:w-px before:bg-slate-200 dark:before:bg-slate-700']) }}>
    @foreach ($items as $it)
    <li class="relative pl-7">
        <span class="absolute left-0 top-1 w-[15px] h-[15px] rounded-full border-2 {{ ($it['done'] ?? true) ? 'border-green-500 bg-green-100 dark:bg-green-500/20' : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-navy-800' }}" aria-hidden="true"></span>
        <div class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $it['title'] }}</div>
        @if (!empty($it['body']))<div class="text-[13px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $it['body'] }}</div>@endif
        @if (!empty($it['time']))<div class="text-[11px] text-slate-400 mt-0.5">{{ $it['time'] }}</div>@endif
    </li>
    @endforeach
</ol>
