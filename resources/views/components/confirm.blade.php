@props(['title' => 'Konfirmasi'])
<div x-data="{ open: false }" @click.outside="open = false" {{ $attributes }}>
    <button @click="open = true" {{ $attributes->except('x-data') }} type="button">
        {{ $slot }}
    </button>
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" style="display:none">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-5" @click.outside="open=false">
            <div class="font-semibold text-slate-800">{{ $title }}</div>
            <div class="text-sm text-slate-500 mt-1">{{ $message ?? 'Lanjutkan tindakan ini?' }}</div>
            <div class="flex gap-2 justify-end mt-4">
                <button @click="open=false" type="button" class="px-4 py-2 rounded-lg bg-slate-100 text-sm">Batal</button>
                {{ $action }}
            </div>
        </div>
    </div>
</div>
