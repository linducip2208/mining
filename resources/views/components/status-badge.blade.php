@props(['status'])
@php
    $colors = [
        'DRAFT' => 'bg-slate-100 text-slate-600',
        'SUBMITTED' => 'bg-blue-50 text-blue-600',
        'PENDING' => 'bg-amber-50 text-amber-700',
        'PENDING_APPROVAL' => 'bg-amber-50 text-amber-700',
        'APPROVED' => 'bg-green-50 text-green-700',
        'POSTED' => 'bg-indigo-50 text-indigo-700',
        'COMPLETED' => 'bg-green-100 text-green-800',
        'PAID' => 'bg-green-100 text-green-800',
        'PARTIALLY_PAID' => 'bg-yellow-50 text-yellow-700',
        'PARTIALLY_DELIVERED' => 'bg-yellow-50 text-yellow-700',
        'REJECTED' => 'bg-red-50 text-red-600',
        'CANCELLED' => 'bg-red-50 text-red-500',
        'VOID' => 'bg-red-100 text-red-700',
        'IN_PROGRESS' => 'bg-blue-50 text-blue-600',
        'CLOSED' => 'bg-slate-200 text-slate-700',
        'CALCULATED' => 'bg-blue-50 text-blue-600',
        'RETURNED' => 'bg-orange-50 text-orange-600',
        'FAVORABLE' => 'bg-green-50 text-green-700',
        'UNFAVORABLE' => 'bg-red-50 text-red-600',
        'ACTIVE' => 'bg-green-50 text-green-700',
        'INACTIVE' => 'bg-slate-100 text-slate-500',
        'LOCKED' => 'bg-red-50 text-red-600',
        'SUSPENDED' => 'bg-orange-50 text-orange-600',
        'FIRST_WEIGH' => 'bg-blue-50 text-blue-600',
        'COMPLETE' => 'bg-amber-50 text-amber-700',
        'VALIDATED' => 'bg-green-50 text-green-700',
        'OPEN' => 'bg-blue-50 text-blue-600',
        'BREAKDOWN' => 'bg-red-50 text-red-600',
        'MAINTENANCE' => 'bg-amber-50 text-amber-700',
        'IN_USE' => 'bg-blue-50 text-blue-600',
        'AVAILABLE' => 'bg-green-50 text-green-700',
        'DEPOSIT_IN' => 'bg-green-50 text-green-700',
        'DEPOSIT_USED' => 'bg-blue-50 text-blue-600',
        'DEPOSIT_REFUND' => 'bg-orange-50 text-orange-600',
    ];
    $color = $colors[$status] ?? 'bg-slate-100 text-slate-600';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset ring-black/5 dark:ring-white/10 $color"]) }}>
    {{ str_replace('_', ' ', $status) }}
</span>
