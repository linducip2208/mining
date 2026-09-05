<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\WeighbridgeTicket;
use App\Models\WorkOrder;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->q);
        $results = [];

        if (strlen($q) >= 2) {
            $like = "%{$q}%";
            $results = collect([
                ['module' => 'Faktur Penjualan', 'icon' => '🧾', 'items' => Invoice::where('number', 'like', $like)->limit(5)->get(['id', 'number', 'status'])],
                ['module' => 'Surat Jalan', 'icon' => '🚚', 'items' => DeliveryOrder::where('number', 'like', $like)->limit(5)->get(['id', 'number', 'status'])],
                ['module' => 'Order Penjualan', 'icon' => '📦', 'items' => SalesOrder::where('number', 'like', $like)->limit(5)->get(['id', 'number', 'status'])],
                ['module' => 'Order Pembelian', 'icon' => '🛒', 'items' => PurchaseOrder::where('number', 'like', $like)->limit(5)->get(['id', 'number', 'status'])],
                ['module' => 'Permintaan Beli', 'icon' => '📝', 'items' => PurchaseRequest::where('number', 'like', $like)->limit(5)->get(['id', 'number', 'status'])],
                ['module' => 'Tiket Timbangan', 'icon' => '⚖️', 'items' => WeighbridgeTicket::where('ticket_no', 'like', $like)->limit(5)->get(['id', 'ticket_no as number', 'status'])],
                ['module' => 'Work Order', 'icon' => '🔧', 'items' => WorkOrder::where('number', 'like', $like)->limit(5)->get(['id', 'number', 'status'])],
                ['module' => 'Customer', 'icon' => '🤝', 'items' => Customer::where('name', 'like', $like)->orWhere('code', 'like', $like)->limit(5)->get(['id', 'name as number', 'status'])],
                ['module' => 'Supplier', 'icon' => '🏭', 'items' => Supplier::where('name', 'like', $like)->orWhere('code', 'like', $like)->limit(5)->get(['id', 'name as number', 'status'])],
                ['module' => 'Karyawan', 'icon' => '👷', 'items' => Employee::where('name', 'like', $like)->orWhere('code', 'like', $like)->limit(5)->get(['id', 'name as number', 'status'])],
                ['module' => 'Item', 'icon' => '📋', 'items' => Item::where('name', 'like', $like)->orWhere('code', 'like', $like)->limit(5)->get(['id', 'name as number', 'status'])],
                ['module' => 'Aset', 'icon' => '🏗️', 'items' => Asset::where('name', 'like', $like)->orWhere('code', 'like', $like)->limit(5)->get(['id', 'name as number', 'status'])],
                ['module' => 'Dokumen', 'icon' => '📁', 'items' => Document::where('subject', 'like', $like)->orWhere('number', 'like', $like)->limit(5)->get(['id', 'number', 'status'])],
            ])->filter(fn ($group) => $group['items']->isNotEmpty())->values();
        }

        return view('search.results', compact('q', 'results'));
    }
}
