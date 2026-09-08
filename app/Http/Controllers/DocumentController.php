<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentDownload;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = Document::with(['division', 'creator'])
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('subject', 'like', "%{$request->q}%")->orWhere('number', 'like', "%{$request->q}%"))
            ->when($request->expiring, fn ($q) => $q->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', now()->addDays(30)))
            ->when(! is_null($companies = auth()->user()?->accessibleCompanyIds()), fn ($q) => $q->whereIn('company_id', $companies))
            ->when(! is_null($divisions = auth()->user()?->accessibleDivisionIds()), fn ($q) => $q->where(function ($w) use ($divisions) {
                $w->whereIn('division_id', $divisions)->orWhereNull('division_id');
            }))
            ->orderByDesc('date')->paginate(20)->withQueryString();

        return view('documents.index', [
            'items' => $items,
            'document' => null,
            'categories' => ['IN' => 'Surat Masuk', 'OUT' => 'Surat Keluar', 'INTERNAL' => 'Dokumen Internal', 'EMPLOYEE' => 'Dokumen Karyawan', 'VENDOR' => 'Dokumen Vendor', 'LEGAL' => 'Legal', 'PERMIT' => 'Perizinan'],
            'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'ARCHIVED', 'CANCELLED'],
        ]);
    }

    public function create()
    {
        return view('documents.form', [
            'document' => null,
            'categories' => ['IN' => 'Surat Masuk', 'OUT' => 'Surat Keluar', 'INTERNAL' => 'Dokumen Internal', 'EMPLOYEE' => 'Dokumen Karyawan', 'VENDOR' => 'Dokumen Vendor', 'LEGAL' => 'Legal', 'PERMIT' => 'Perizinan'],
            'divisions' => Division::pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateInput($request);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);

        $document = DB::transaction(function () use ($validated, $request) {
            $division = isset($validated['division_id']) ? Division::find($validated['division_id']) : null;
            $divisionCode = strtoupper(substr($division?->code ?? 'GEN', 0, 4));
            $seq = $this->nextLetterSeq($divisionCode);
            $romanMonth = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
            $validated['number'] = sprintf('%03d/%s/%s/%d', $seq, $divisionCode, $romanMonth, now()->year);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $validated['file_path'] = $file->store('documents', 'private');
                $validated['file_name'] = $file->getClientOriginalName();
                $validated['mime_type'] = $file->getMimeType();
                $validated['file_size'] = $file->getSize();
            }
            $validated['created_by'] = auth()->id();

            return Document::create($validated);
        });

        AuditService::created('DOCUMENT', $document);

        return redirect()->route('documents.index')->with('success', 'Dokumen tersimpan: '.$document->number);
    }

    public function show(Document $document)
    {
        return view('documents.index', [
            'document' => $document->load('division'),
            'items' => Document::orderByDesc('id')->paginate(20),
            'categories' => ['IN' => 'Surat Masuk', 'OUT' => 'Surat Keluar', 'INTERNAL' => 'Dokumen Internal', 'EMPLOYEE' => 'Dokumen Karyawan', 'VENDOR' => 'Dokumen Vendor', 'LEGAL' => 'Legal', 'PERMIT' => 'Perizinan'],
            'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'ARCHIVED', 'CANCELLED'],
        ]);
    }

    public function approve(Document $document)
    {
        $document->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'DOCUMENT', $document->id, Document::class);

        return back()->with('success', 'Dokumen disetujui.');
    }

    public function download(Document $document)
    {
        DocumentDownload::create(['document_id' => $document->id, 'user_id' => auth()->id()]);
        AuditService::log('EXPORT', 'DOCUMENT', $document->id, Document::class, null, ['download' => $document->file_name]);

        return Storage::disk('private')->download($document->file_path, $document->file_name);
    }

    protected function nextLetterSeq(string $divisionCode): int
    {
        $seq = DB::transaction(function () use ($divisionCode) {
            $row = DB::table('letter_sequences')
                ->where('division_code', $divisionCode)
                ->where('year', now()->year)
                ->lockForUpdate()
                ->first();
            if (! $row) {
                DB::table('letter_sequences')->insert(['division_code' => $divisionCode, 'year' => now()->year, 'last_seq' => 1, 'created_at' => now(), 'updated_at' => now()]);

                return 1;
            }
            DB::table('letter_sequences')->where('id', $row->id)->update(['last_seq' => $row->last_seq + 1, 'updated_at' => now()]);

            return $row->last_seq + 1;
        });

        return $seq;
    }

    protected function validateInput(Request $request): array
    {
        $rules = [
            'company_id' => 'nullable|exists:companies,id',
            'category' => 'required|in:IN,OUT,INTERNAL,EMPLOYEE,VENDOR,LEGAL,PERMIT',
            'subject' => 'required|max:255',
            'body' => 'nullable',
            'date' => 'required|date',
            'division_id' => 'nullable|exists:divisions,id',
            'expiry_date' => 'nullable|date',
            'reminder_date' => 'nullable|date',
            'status' => 'nullable|in:DRAFT,SUBMITTED',
        ];
        if ($request->hasFile('file')) {
            $rules['file'] = 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png';
        }

        return $request->validate($rules);
    }
}
