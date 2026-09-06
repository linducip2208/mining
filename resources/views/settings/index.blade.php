@extends('layouts.app')
@section('title', ' - Pengaturan Sistem')
@section('content')
@php
    $groups = $settings->keys()->values();
    $firstGroup = $activeGroup ?: $groups->first();
    $inputId = fn ($key) => 'setting-'.md5($key);
@endphp
<div class="page-frame" x-data="settingsCenter(@js($firstGroup))" x-init="boot()">
    <section class="flex flex-col xl:flex-row xl:items-end justify-between gap-4 mb-6">
        <div>
            <div class="section-kicker mb-2">Enterprise configuration center</div>
            <h1 class="text-[28px] font-bold tracking-[-.03em] text-slate-900">Pengaturan Sistem</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola identitas, perilaku bisnis, keamanan, dan integrasi Mining ERP dari satu pusat konfigurasi.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('printer.view')
                <a href="{{ route('printer.index') }}" class="inline-flex items-center gap-2 h-10 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:border-amber-300"><x-ui.icon name="printer" class="w-4 h-4" /> Printer &amp; Perangkat</a>
            @endcan
            <div class="relative">
                <x-ui.icon name="search" class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                <input x-model="query" type="search" placeholder="Cari pengaturan..." aria-label="Cari pengaturan" class="w-full sm:w-72 h-10 pl-9 pr-3 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
            </div>
            @can('advanced.setting.view')
                <a href="{{ route('setting.export') }}" class="inline-flex items-center gap-2 h-10 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:border-amber-300"><x-ui.icon name="download" class="w-4 h-4" /> Ekspor</a>
            @endcan
        </div>
    </section>

    <div x-show="dirty" x-cloak class="mb-4 flex items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <span class="flex items-center gap-2"><x-ui.icon name="alert" class="w-4 h-4" /> Perubahan belum disimpan.</span>
        <button type="button" class="text-xs font-semibold underline" @click="window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'})">Simpan sekarang</button>
    </div>

    <form method="POST" action="{{ route('setting.update') }}" enctype="multipart/form-data" x-ref="settingsForm" @input="dirty=true" @change="dirty=true" @submit="dirty=false">
        @csrf
        @method('PUT')
        <div class="grid lg:grid-cols-12 gap-5 items-start">
            <aside class="lg:col-span-3 dashboard-card p-2 lg:sticky lg:top-24">
                <div class="px-3 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pusat konfigurasi</div>
                @foreach($settings as $group => $items)
                    <button type="button" @click="active=@js($group)" :class="active===@js($group) ? 'bg-amber-50 text-amber-800 font-semibold ring-1 ring-amber-100' : 'text-slate-600 hover:bg-slate-50'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-left text-sm mb-1">
                        <span>{{ $group }}</span><span class="text-[11px] text-slate-400">{{ $items->count() }}</span>
                    </button>
                @endforeach
                @can('advanced.setting.update')
                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="resetAll()" class="w-full px-3 py-2.5 rounded-lg text-left text-xs font-semibold text-red-600 hover:bg-red-50">Reset semua pengaturan</button>
                    </div>
                @endcan
            </aside>
            <div class="lg:col-span-9 space-y-4">
                @foreach($settings as $group => $items)
                    <section x-show="active===@js($group)" x-cloak class="dashboard-card overflow-hidden">
                        <header class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div><div class="section-kicker">{{ $group === 'Branding' ? 'White label' : 'Configuration group' }}</div><h2 class="mt-1 text-base font-semibold text-slate-900">{{ $group }}</h2><p class="mt-1 text-xs text-slate-400">{{ $items->count() }} pengaturan tersedia</p></div>
                            <button type="button" data-group="{{ $group }}" @click="resetGroup($event.currentTarget.dataset.group)" class="text-xs font-semibold text-slate-500 hover:text-amber-700">Reset grup</button>
                        </header>
                        <div class="p-5 {{ $group === 'Branding' ? 'grid xl:grid-cols-5 gap-6' : '' }}">
                            <div class="{{ $group === 'Branding' ? 'xl:col-span-3' : '' }} grid md:grid-cols-2 gap-x-5 gap-y-1">
                                @foreach($items as $row)
                                    @php
                                        $meta = $row['meta'];
                                        $id = $inputId($row['key']);
                                    @endphp
                                    <div class="setting-row py-3 border-b border-slate-100 last:border-0" data-search="{{ strtolower($meta['label'].' '.$meta['description'].' '.$row['key']) }}" x-show="matches($el.dataset.search)">
                                        <div class="flex items-start justify-between gap-3">
                                            <div><label for="{{ $id }}" class="text-sm font-semibold text-slate-800">{{ $meta['label'] }}</label><p class="mt-1 text-xs leading-5 text-slate-500">{{ $meta['description'] }}</p>@if($meta['help_text'])<p class="mt-1 text-[11px] text-amber-700">{{ $meta['help_text'] }}</p>@endif @if($developerLabels)<p class="mt-1 text-[10px] font-mono text-slate-400">{{ $row['key'] }}</p>@endif</div>
                                            @if($meta['unit'])<span class="shrink-0 text-[11px] text-slate-400">{{ $meta['unit'] }}</span>@endif
                                        </div>
                                        <div class="mt-2 flex items-center gap-2">
                                            @if($meta['type'] === 'boolean')
                                                <input type="hidden" name="settings[{{ $row['key'] }}]" value="0"><label class="relative inline-flex items-center cursor-pointer"><input id="{{ $id }}" type="checkbox" name="settings[{{ $row['key'] }}]" value="1" data-default="{{ $meta['default'] ? '1' : '0' }}" @checked(filter_var($row['value'], FILTER_VALIDATE_BOOLEAN)) class="sr-only peer"><span class="w-10 h-6 bg-slate-200 peer-checked:bg-amber-500 rounded-full transition"></span><span class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow transition peer-checked:translate-x-4"></span><span class="ml-2 text-xs text-slate-500">{{ filter_var($row['value'], FILTER_VALIDATE_BOOLEAN) ? 'Aktif' : 'Tidak aktif' }}</span></label>
                                            @elseif($meta['type'] === 'select')
                                                <select id="{{ $id }}" name="settings[{{ $row['key'] }}]" data-default="{{ $meta['default'] }}" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">@foreach($meta['options'] as $value=>$label)<option value="{{ $value }}" @selected((string)$row['value']===(string)$value)>{{ $label }}</option>@endforeach</select>
                                            @elseif($meta['type'] === 'model_select')
                                                <select id="{{ $id }}" name="settings[{{ $row['key'] }}]" data-default="{{ $meta['default'] ?? '' }}" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none"><option value="">— Tidak dipilih —</option>@foreach($referenceOptions[$row['key']] ?? [] as $value=>$label)<option value="{{ $value }}" @selected((string)$row['value']===(string)$value)>{{ $label }}</option>@endforeach</select>
                                            @elseif($meta['type'] === 'multiselect')
                                                @php
                                                    $selected = is_array($row['value']) ? $row['value'] : (json_decode((string) $row['value'], true) ?: $meta['default']);
                                                @endphp
                                                <input type="hidden" name="settings[{{ $row['key'] }}][]" value="">
                                                <select id="{{ $id }}" name="settings[{{ $row['key'] }}][]" multiple data-default="{{ json_encode($meta['default']) }}" class="w-full min-h-20 px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">@foreach($meta['options'] as $value=>$label)<option value="{{ $value }}" @selected(in_array($value, $selected, true))>{{ $label }}</option>@endforeach</select>
                                            @elseif($meta['type'] === 'image')
                                                <input id="{{ $id }}" type="file" name="settings[{{ $row['key'] }}]" accept=".png,.jpg,.jpeg,.webp,.ico,image/png,image/jpeg,image/webp,image/x-icon" class="w-full text-xs file:mr-2 file:rounded-md file:border-0 file:bg-amber-50 file:px-3 file:py-2 file:text-amber-800"><span class="text-[11px] text-slate-400">{{ $row['exists'] ? 'File tersimpan' : 'PNG/JPG/WEBP/ICO' }}</span>
                                            @else
                                                <input id="{{ $id }}" type="{{ $meta['type'] === 'secret' ? 'password' : ($meta['type'] === 'color' ? 'color' : ($meta['type'] === 'time' ? 'time' : ($meta['type'] === 'date' ? 'date' : (in_array($meta['type'],['percentage','currency','integer','decimal']) ? 'number' : 'text')))) }}" name="settings[{{ $row['key'] }}]" value="{{ $meta['sensitive'] ? '' : $row['value'] }}" placeholder="{{ $meta['sensitive'] && $row['exists'] ? '•••••••• (tersimpan)' : '' }}" data-default="{{ $meta['default'] }}" autocomplete="{{ $meta['sensitive'] ? 'new-password' : 'off' }}" step="{{ in_array($meta['type'],['percentage','decimal','currency']) ? '0.01' : '1' }}" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                                            @endif
                                            <button type="button" class="shrink-0 text-[11px] text-slate-400 hover:text-amber-700" @click="resetSetting($event.currentTarget)">Default</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if($group === 'Branding')
                                <div class="xl:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 p-4" x-data="brandingPreview()" x-init="watchForm($root.closest('form'))">
                                    <div class="flex items-center justify-between mb-3"><div><div class="section-kicker">Live preview</div><h3 class="font-semibold text-slate-900">Tampilan white-label</h3></div><span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600">Realtime</span></div>
                                    <div class="overflow-hidden rounded-xl bg-white border border-slate-200 shadow-sm">
                                        <div class="h-8 flex items-center gap-2 px-3" :style="{background: topbar}"><span class="w-2 h-2 rounded-full bg-red-400"></span><span class="w-2 h-2 rounded-full bg-amber-400"></span><span class="w-2 h-2 rounded-full bg-emerald-400"></span><span class="ml-2 text-[10px] text-slate-400 truncate" x-text="appName"></span></div>
                                        <div class="flex min-h-48"><div class="w-16 p-2 space-y-2" :style="{background: sidebar}"><div class="h-7 rounded-lg flex items-center justify-center font-bold text-white" :style="{background: primary}" x-text="shortName.substring(0,1)"></div><div class="h-2 rounded bg-white/20"></div><div class="h-2 rounded bg-white/20"></div><div class="h-2 rounded bg-white/20"></div></div><div class="flex-1 p-4"><div class="text-[10px] text-slate-400" x-text="company"></div><div class="mt-1 text-sm font-bold text-slate-900" x-text="tagline"></div><div class="mt-4 grid grid-cols-2 gap-2"><div class="rounded-lg border border-slate-200 p-2"><div class="text-[9px] text-slate-400">Produksi Hari Ini</div><div class="mt-1 text-base font-bold" :style="{color: primary}">12.842 t</div></div><div class="rounded-lg border border-slate-200 p-2"><div class="text-[9px] text-slate-400">Margin</div><div class="mt-1 text-base font-bold text-emerald-600">18,4%</div></div></div><button class="mt-3 px-3 py-1.5 rounded-md text-[10px] text-white" :style="{background: primary}">Simpan</button></div></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <footer class="px-5 py-3 border-t border-slate-100 flex justify-end"><button type="submit" class="inline-flex items-center gap-2 min-h-[42px] px-5 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700"><x-ui.icon name="check" class="w-4 h-4" /> Simpan Perubahan</button></footer>
                    </section>
                @endforeach
            </div>
        </div>
    </form>
    @can('advanced.setting.update')
        <section class="mt-5 dashboard-card p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div><h2 class="font-semibold text-slate-900">Migrasi konfigurasi</h2><p class="text-xs text-slate-500 mt-1">Impor JSON hanya menerima key resmi dan selalu mengecualikan secret.</p></div>
            <form method="POST" action="{{ route('setting.import') }}" enctype="multipart/form-data" class="flex items-center gap-2">@csrf<input type="file" name="configuration" required accept=".json,application/json" class="max-w-56 text-xs"><button class="h-9 px-3 rounded-lg border border-slate-200 text-xs font-semibold hover:border-amber-300">Impor JSON</button></form>
        </section>
    @endcan
</div>
<script>
function settingsCenter(initial) {
    return {
        active: initial, query: '', dirty: false,
        boot() { window.addEventListener('beforeunload', e => { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } }); },
        matches(text) { return !this.query || text.includes(this.query.toLowerCase()); },
        resetSetting(button) { const row=button.closest('.setting-row'), input=row?.querySelector('[data-default]'); if (!input || !confirm('Reset pengaturan ini ke default?')) return; if(input.type==='checkbox') input.checked=['1','true'].includes(String(input.dataset.default).toLowerCase()); else if(input.multiple) Array.from(input.options).forEach(o=>o.selected=JSON.parse(input.dataset.default||'[]').includes(o.value)); else input.value=input.dataset.default; this.dirty=true; },
        resetGroup(group) { if (!confirm('Reset semua pengaturan dalam grup ini ke default?')) return; const form=document.createElement('form'); form.method='POST'; form.action='{{ route('setting.reset-group') }}'; form.innerHTML='<input name="_token" value="{{ csrf_token() }}"><input name="group" value="'+group+'">'; document.body.append(form); form.submit(); },
        resetAll() { if (!confirm('Reset SEMUA pengaturan? Tindakan ini hanya dapat dipulihkan dengan impor konfigurasi.')) return; const form=document.createElement('form'); form.method='POST'; form.action='{{ route('setting.reset-all') }}'; form.innerHTML='<input name="_token" value="{{ csrf_token() }}">'; document.body.append(form); form.submit(); }
    };
}
function brandingPreview() {
    return { appName:'Mining ERP Pro', shortName:'Mining ERP', tagline:'Integrated Mining ERP', company:'PT Tambang Sejahtera', primary:'#d97706', sidebar:'#101923', topbar:'#ffffff',
        watchForm(form) { const sync=()=>{ const v=k=>form.querySelector('[name="settings['+k+']"]')?.value; this.appName=v('branding.app_name')||this.appName; this.shortName=v('branding.app_short_name')||this.shortName; this.tagline=v('branding.tagline')||this.tagline; this.company=v('system.company_name')||this.company; this.primary=v('branding.primary_color')||this.primary; this.sidebar=v('branding.sidebar_color')||this.sidebar; this.topbar=v('branding.topbar_color')||this.topbar; }; sync(); form.addEventListener('input',sync); form.addEventListener('change',sync); }
    };
}
</script>
@endsection
