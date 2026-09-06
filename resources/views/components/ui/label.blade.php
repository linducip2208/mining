{{-- Form label with required indicator. --}}
@props(['for' => null, 'required' => false])
<label @if($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => 'block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300']) }}>{{ $slot }}@if($required) <span class="text-red-500" aria-hidden="true">*</span>@endif</label>
