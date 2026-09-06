<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PrinterDevice;
use App\Models\PrintJob;
use App\Models\Site;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\LocalPrintAgentService;
use App\Services\PrintJobService;
use App\Support\HumanLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrinterDeviceController extends Controller
{
    public function index()
    {
        return view('printers.index', [
            'devices' => PrinterDevice::with(['company', 'site'])->latest()->get(),
            'jobs' => PrintJob::with(['printer', 'requester'])->latest('requested_at')->limit(12)->get(),
            'companies' => Company::orderBy('name')->get(),
            'sites' => Site::orderBy('name')->get(),
            'agent' => app(LocalPrintAgentService::class)->health(),
            'humanStatus' => fn (string $status): string => HumanLabel::label($status),
            'connectionTypes' => ['SYSTEM' => 'System / Browser', 'LOCAL_AGENT' => 'Local Agent', 'USB' => 'USB', 'BLUETOOTH' => 'Bluetooth', 'NETWORK' => 'Network / LAN', 'ESC_POS' => 'ESC/POS'],
            'printerTypes' => ['A4' => 'Printer A4', 'THERMAL_58' => 'Thermal 58 mm', 'THERMAL_80' => 'Thermal 80 mm', 'WEIGHBRIDGE' => 'Printer Timbangan', 'ESC_POS' => 'ESC/POS'],
            'paperSizes' => ['A4' => 'A4', 'A5' => 'A5', 'F4' => 'F4', '58mm' => 'Thermal 58 mm', '80mm' => 'Thermal 80 mm', 'Continuous' => 'Continuous'],
            'documentTypes' => ['WEIGHBRIDGE_TICKET' => 'Tiket Timbangan', 'FUEL_RECEIPT' => 'Struk BBM', 'INVOICE' => 'Invoice', 'PURCHASE_ORDER' => 'Purchase Order', 'DELIVERY_ORDER' => 'Delivery Order', 'REPORT' => 'Laporan', 'PAYSLIP' => 'Payslip'],
        ]);
    }

    public function store(Request $request)
    {
        $device = PrinterDevice::create($this->validated($request));
        AuditService::created('PRINTER', $device);

        return back()->with('success', 'Printer berhasil ditambahkan.');
    }

    public function update(Request $request, PrinterDevice $printer)
    {
        $old = $printer->toArray();
        $printer->update($this->validated($request));
        AuditService::updated('PRINTER', $printer, $old);

        return back()->with('success', 'Konfigurasi printer diperbarui.');
    }

    public function destroy(PrinterDevice $printer)
    {
        AuditService::deleted('PRINTER', $printer);
        $printer->delete();

        return back()->with('success', 'Printer dihapus dari registry.');
    }

    public function makeDefault(PrinterDevice $printer)
    {
        PrinterDevice::where('printer_type', $printer->printer_type)->update(['is_default' => false]);
        $printer->update(['is_default' => true]);
        $settingKey = in_array($printer->printer_type, ['THERMAL_58', 'THERMAL_80', 'WEIGHBRIDGE', 'ESC_POS'], true)
            ? 'printer.default_thermal_printer_id'
            : 'printer.default_a4_printer_id';
        if ($printer->printer_type === 'WEIGHBRIDGE') $settingKey = 'printer.default_weighbridge_printer_id';
        Setting::set($settingKey, (string) $printer->id, 'model_select');
        AuditService::log('DEFAULT', 'PRINTER', $printer->id, PrinterDevice::class, null, ['printer' => $printer->name]);

        return back()->with('success', 'Printer default ditetapkan.');
    }

    public function agentConfig()
    {
        return response()->json(app(LocalPrintAgentService::class)->discoveryPackage());
    }

    public function test(Request $request, PrinterDevice $printer)
    {
        $job = app(PrintJobService::class)->queue('PRINTER_TEST', $printer->id, $printer, [
            'format' => 'html',
            'content_base64' => base64_encode(view('print.thermal.test', ['printer' => $printer])->render()),
        ], auth()->id(), 'PRINTER_TEST:'.$printer->id.':'.Str::uuid(), 1);
        AuditService::log('TEST_PRINT', 'PRINTER', $printer->id, PrinterDevice::class, null, ['job_uuid' => $job->uuid]);

        return response()->json(['job' => $job->uuid, 'package' => app(LocalPrintAgentService::class)->package($job)]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'printer_type' => 'required|in:A4,THERMAL_58,THERMAL_80,WEIGHBRIDGE,ESC_POS',
            'connection_type' => 'required|in:SYSTEM,LOCAL_AGENT,USB,BLUETOOTH,NETWORK,ESC_POS',
            'device_identifier' => 'nullable|string|max:255',
            'paper_size' => 'required|in:A4,A5,F4,58mm,80mm,Continuous',
            'document_types' => 'nullable|array',
            'document_types.*' => 'string|max:50',
            'workstation' => 'nullable|string|max:150',
            'company_id' => 'nullable|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'auto_print' => 'nullable|boolean',
            'copies' => 'nullable|integer|min:1|max:3',
        ]);
        $data['document_types'] = array_values($data['document_types'] ?? []);
        foreach (['is_default', 'is_active', 'auto_print'] as $field) {
            $data[$field] = (bool) ($data[$field] ?? false);
        }
        $data['is_active'] = $request->has('is_active');
        $data['copies'] = (int) ($data['copies'] ?? 1);

        return $data;
    }
}
