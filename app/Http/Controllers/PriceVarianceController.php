<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Site;
use App\Models\PriceList;
use App\Models\PriceVariance;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceVarianceController extends Controller
{
    public function index(Request $request)
    {
        $items = PriceVariance::with(['item', 'approvedBy'])
            ->when($request->approval_status, fn ($q) => $q->where('approval_status', $request->approval_status))
            ->when($request->variance_type, fn ($q) => $q->where('variance_type', $request->variance_type))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('sales.variance.index', ['items' => $items, 'statuses' => ['PENDING', 'APPROVED', 'REJECTED']]);
    }

    public function approve(PriceVariance $price_variance)
    {
        $price_variance->update(['approval_status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'PRICE_VARIANCE', $price_variance->id, PriceVariance::class);
        return back()->with('success', 'Selisih harga disetujui untuk diposting sesuai konfigurasi akuntansi.');
    }
}
