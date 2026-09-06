@extends('layouts.app')

@section('title', ' - AI Copilot')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">AI Copilot</h1>
    <p class="text-sm text-slate-500">Tanya jawab operasional berbasis data real — read-only & tercatat di audit. Provider aktif: <span class="font-mono font-bold">{{ $active }}</span></p>
</div>

<div class="grid md:grid-cols-2 gap-4">
    <div>
        <form method="POST" action="{{ route('ai.ask') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
            @csrf
            <label class="text-xs font-semibold text-slate-600 uppercase">Pertanyaan</label>
            <textarea name="question" rows="4" required maxlength="1000" placeholder="cth: Berapa produksi 7 hari terakhir? / Stok kritis apa saja? / BBM boros di unit mana?" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">{{ old('question') }}</textarea>
            <button class="mt-2 px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Tanya AI</button>
        </form>

        @if (session('ai_answer'))
        @php $ans = session('ai_answer'); @endphp
        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 text-sm">
            <div class="text-[11px] uppercase text-indigo-500 font-semibold mb-1">Jawaban · {{ $ans['provider'] ?? '' }} ({{ $ans['model'] ?? '' }})</div>
            <div class="whitespace-pre-line text-slate-800">{{ $ans['answer'] ?? '' }}</div>
            @if (!empty($ans['sources']))
            <div class="mt-2 text-[11px] text-slate-500">Sumber: {{ implode(', ', (array) $ans['sources']) }}</div>
            @endif
        </div>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-3">Riwayat Pertanyaan Saya</div>
        <div class="space-y-2 max-h-[560px] overflow-y-auto">
            @forelse ($history ?? [] as $h)
            <div class="rounded-lg bg-slate-50 border border-slate-100 p-3 text-sm">
                <div class="font-semibold text-slate-700">Q: {{ $h->question }}</div>
                <div class="text-slate-600 mt-1 whitespace-pre-line">{{ \Illuminate\Support\Str::limit($h->answer, 400) }}</div>
                <div class="text-[11px] text-slate-400 mt-1">{{ $h->created_at }} · {{ $h->provider }}</div>
            </div>
            @empty
            <div class="text-sm text-slate-400 text-center py-8">Belum ada riwayat.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
