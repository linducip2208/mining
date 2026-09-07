<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LetterRegister;
use App\Models\LetterType;
use App\Models\Site;
use App\Models\Supplier;
use App\Services\ApprovalService;
use App\Services\AuditService;
use App\Services\LetterService;
use App\Services\PrintDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LetterRegisterController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = LetterRegister::with(['type', 'company', 'site', 'department'])
            ->when($request->q, fn ($q) => $q->where(fn ($w) => $w->where('number', 'like', "%{$request->q}%")->orWhere('subject', 'like', "%{$request->q}%")->orWhere('recipient_name', 'like', "%{$request->q}%")))
            ->when($request->type_id, fn ($q) => $q->where('letter_type_id', $request->type_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->month, fn ($q) => $q->whereMonth('letter_date', $request->month))
            ->when($request->year, fn ($q) => $q->whereYear('letter_date', $request->year))
            ->when($request->from, fn ($q) => $q->whereDate('letter_date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('letter_date', '<=', $request->to));
        $this->applyCompanyScope($items);
        $this->applySiteScope($items);
        $items = $items->orderByDesc('id')->paginate(20)->withQueryString();

        return view('letters.index', [
            'items' => $items, 'letter' => null,
            'types' => LetterType::where('is_active', true)->orderBy('name')->get(),
            'statuses' => LetterRegister::STATUSES,
        ]);
    }

    public function create()
    {
        return view('letters.form', [
            'letter' => null,
            'types' => LetterType::where('is_active', true)->orderBy('name')->get(),
            'customers' => Customer::orderBy('name')->limit(200)->get(),
            'suppliers' => Supplier::orderBy('name')->limit(200)->get(),
            'employees' => Employee::where('status', 'ACTIVE')->orderBy('name')->limit(200)->get(),
            'sites' => Site::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateInput($request);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);

        $letter = LetterRegister::create($validated + ['status' => 'DRAFT', 'created_by' => auth()->id()]);
        AuditService::created('LETTER', $letter);

        return redirect()->route('letters.show', $letter)->with('success', 'Surat dicatat sebagai draft.');
    }

    public function show(LetterRegister $letter)
    {
        $this->ensureInScope($letter);

        return view('letters.index', [
            'letter' => $letter->load(['type', 'company', 'site', 'department', 'customer', 'supplier', 'employee', 'creator', 'approver', 'reservation', 'related']),
            'items' => LetterRegister::orderByDesc('id')->paginate(20),
            'types' => LetterType::where('is_active', true)->orderBy('name')->get(),
            'statuses' => LetterRegister::STATUSES,
        ]);
    }

    public function update(Request $request, LetterRegister $letter)
    {
        $this->ensureInScope($letter);
        if (! in_array($letter->status, ['DRAFT', 'NUMBER_RESERVED'], true)) {
            return back()->with('error', 'Hanya DRAFT/NUMBER_RESERVED yang dapat diubah.');
        }
        $validated = $this->validateInput($request);
        $letter->update($validated + ['updated_by' => auth()->id()]);
        AuditService::updated('LETTER', $letter);

        return back()->with('success', 'Surat diperbarui.');
    }

    /**
     * Reserve a number before the document is finished.
     */
    public function reserve(LetterRegister $letter)
    {
        $this->ensureInScope($letter);
        if ($letter->status !== 'DRAFT') {
            return back()->with('error', 'Hanya DRAFT yang dapat dipesan nomornya.');
        }
        $letter->loadMissing(['type', 'company', 'site', 'department']);
        try {
            $reservation = DB::transaction(function () use ($letter) {
                $res = LetterService::reserveNumber($letter->type, [
                    'TYPE' => $letter->type->code,
                    'DEPARTMENT' => $letter->department?->code ?? $letter->department?->name ?? '',
                    'COMPANY' => $letter->company?->code ?? '',
                    'SITE' => $letter->site?->code ?? '',
                ]);
                LetterService::commitReservation($res, $letter);
                $letter->update(['status' => 'NUMBER_RESERVED']);

                return $res;
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memesan nomor: '.$e->getMessage());
        }
        AuditService::log('UPDATE', 'LETTER', $letter->id, LetterRegister::class, null, ['event' => 'number_reserved', 'number' => $reservation->number]);

        return back()->with('success', 'Nomor dipesan: '.$reservation->number);
    }

    public function review(LetterRegister $letter)
    {
        $this->ensureInScope($letter);
        if (! in_array($letter->status, ['DRAFT', 'NUMBER_RESERVED'], true)) {
            return back()->with('error', 'Status tidak valid.');
        }
        ApprovalService::submit('ADMINISTRATION', 'LETTER', $letter);
        if ($letter->fresh()->status === 'APPROVED') {
            return back()->with('success', 'Surat disetujui.');
        }
        $letter->update(['status' => 'REVIEW']);

        return back()->with('success', 'Surat diajukan untuk review.');
    }

    public function approve(LetterRegister $letter)
    {
        $this->ensureInScope($letter);
        if ($letter->status !== 'REVIEW') {
            return back()->with('error', 'Hanya REVIEW yang dapat disetujui langsung.');
        }
        $letter->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'LETTER', $letter->id, LetterRegister::class);

        return back()->with('success', 'Surat disetujui.');
    }

    public function publish(LetterRegister $letter)
    {
        $this->ensureInScope($letter);
        if ($letter->status !== 'DRAFT') {
            return back()->with('error', 'Hanya DRAFT yang dapat dipublish (alur sederhana).');
        }
        $letter->update(['status' => 'PUBLISHED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'LETTER', $letter->id, LetterRegister::class, null, ['event' => 'published_simple']);

        return back()->with('success', 'Surat dipublish.');
    }

    public function sign(LetterRegister $letter)
    {
        $this->ensureInScope($letter);
        if ($letter->status !== 'APPROVED') {
            return back()->with('error', 'Hanya APPROVED yang dapat ditandatangani.');
        }
        $letter->update(['status' => 'SIGNED', 'signed_by' => auth()->id(), 'signed_at' => now()]);
        AuditService::log('UPDATE', 'LETTER', $letter->id, LetterRegister::class, null, ['event' => 'signed']);

        return back()->with('success', 'Surat ditandatangani.');
    }

    public function send(LetterRegister $letter)
    {
        $this->ensureInScope($letter);
        if (! in_array($letter->status, ['SIGNED', 'APPROVED', 'PUBLISHED'], true)) {
            return back()->with('error', 'Surat belum siap dikirim.');
        }
        $letter->update(['status' => 'SENT', 'sent_at' => now()]);
        AuditService::log('UPDATE', 'LETTER', $letter->id, LetterRegister::class, null, ['event' => 'sent']);

        return back()->with('success', 'Surat ditandai terkirim.');
    }

    public function archive(LetterRegister $letter)
    {
        $this->ensureInScope($letter);
        if (! in_array($letter->status, ['SENT', 'SIGNED', 'PUBLISHED'], true)) {
            return back()->with('error', 'Hanya SENT/SIGNED/PUBLISHED yang dapat diarsipkan.');
        }
        $letter->update(['status' => 'ARCHIVED']);
        AuditService::log('UPDATE', 'LETTER', $letter->id, LetterRegister::class, null, ['event' => 'archived']);

        return back()->with('success', 'Surat diarsipkan.');
    }

    public function void(LetterRegister $letter, Request $request)
    {
        $this->ensureInScope($letter);
        if (in_array($letter->status, ['VOID', 'ARCHIVED'], true)) {
            return back()->with('error', 'Status tidak dapat divoid.');
        }
        DB::transaction(function () use ($letter, $request) {
            $letter->update(['status' => 'VOID']);
            if ($letter->reservation && $letter->reservation->status === 'USED') {
                LetterService::releaseReservation($letter->reservation, 'VOID', $request->input('reason'));
            }
            AuditService::log('VOID', 'LETTER', $letter->id, LetterRegister::class, null, ['reason' => $request->input('reason')]);
        });

        return back()->with('success', 'Surat divoid. Nomor tidak dipakai ulang.');
    }

    public function cancel(LetterRegister $letter)
    {
        $this->ensureInScope($letter);
        if (! in_array($letter->status, ['DRAFT', 'NUMBER_RESERVED', 'REVIEW'], true)) {
            return back()->with('error', 'Status tidak dapat dibatalkan.');
        }
        DB::transaction(function () use ($letter) {
            if ($letter->reservation && $letter->reservation->status === 'USED') {
                LetterService::releaseReservation($letter->reservation, 'CANCELLED');
            }
            $letter->update(['status' => 'CANCELLED']);
            AuditService::log('CANCEL', 'LETTER', $letter->id, LetterRegister::class);
        });

        return back()->with('success', 'Surat dibatalkan.');
    }

    public function attach(Request $request, LetterRegister $letter)
    {
        $this->ensureInScope($letter);
        $validated = $request->validate([
            'attachment' => 'required|file|mimes:pdf,docx,xlsx,png,jpg,jpeg|max:10240',
        ]);
        $file = $validated['attachment'];
        $path = $file->store('letters', 'private');
        $letter->update([
            'attachment_path' => $path,
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_mime' => $file->getClientMimeType(),
            'attachment_size' => $file->getSize(),
        ]);
        AuditService::log('UPDATE', 'LETTER', $letter->id, LetterRegister::class, null, ['event' => 'attached']);

        return back()->with('success', 'Lampiran diunggah.');
    }

    public function download(LetterRegister $letter)
    {
        $this->ensureInScope($letter);
        abort_unless($letter->attachment_path && Storage::disk('private')->exists($letter->attachment_path), 404);
        AuditService::log('EXPORT', 'LETTER', $letter->id, LetterRegister::class, null, ['event' => 'download']);

        return Storage::disk('private')->download($letter->attachment_path, $letter->attachment_name);
    }

    public function print(LetterRegister $letter)
    {
        $this->ensureInScope($letter);
        AuditService::log('PRINT', 'LETTER', $letter->id, LetterRegister::class, null, ['number' => $letter->number]);

        return view('print.letter', PrintDocumentService::context(['letter' => $letter->load(['type', 'company', 'site', 'department', 'customer', 'supplier', 'employee'])]));
    }

    protected function validateInput(Request $request): array
    {
        return $request->validate([
            'letter_type_id' => 'required|exists:letter_types,id',
            'letter_date' => 'required|date',
            'subject' => 'required|max:255',
            'body' => 'nullable',
            'recipient_type' => 'required|in:'.implode(',', LetterRegister::RECIPIENT_TYPES),
            'recipient_name' => 'nullable|max:200',
            'recipient_company' => 'nullable|max:200',
            'recipient_address' => 'nullable',
            'recipient_phone' => 'nullable|max:50',
            'recipient_email' => 'nullable|email|max:150',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'employee_id' => 'nullable|exists:employees,id',
            'notes' => 'nullable',
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);
    }
}
