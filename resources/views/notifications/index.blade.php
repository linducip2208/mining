@extends('layouts.app')
@section('title', ' - Notifikasi')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Notifikasi</h1>
    <form action="{{ route('notification.read-all') }}" method="POST">@csrf
        <button class="px-4 py-2 rounded-lg bg-slate-100 text-sm">Tandai Semua Terbaca</button>
    </form>
</div>
<div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100">
    @forelse ($notifications as $notif)
    <div class="px-5 py-3 {{ $notif->read_at ? '' : 'bg-amber-50/50' }}">
        <div class="text-sm font-medium">{{ $notif->data['title'] ?? 'Notifikasi' }}</div>
        <div class="text-sm text-slate-500">{{ $notif->data['body'] ?? '' }}</div>
        <div class="text-[11px] text-slate-400 mt-0.5">{{ $notif->created_at->diffForHumans() }}</div>
    </div>
    @empty
    <div class="px-5 py-10 text-center text-slate-400 text-sm">Tidak ada notifikasi</div>
    @endforelse
</div>
@endsection