<?php

namespace App\Http\Controllers;

use App\Models\LetterType;
use App\Services\AuditService;
use App\Services\LetterService;
use Illuminate\Http\Request;

class LetterTypeController extends Controller
{
    public function index()
    {
        return view('letters.types', ['types' => LetterType::orderBy('code')->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|max:20|unique:letter_types,code',
            'name' => 'required|max:150',
            'numbering_format' => 'required|max:200',
            'reset_period' => 'required|in:YEARLY,MONTHLY,NEVER',
        ]);
        $type = LetterType::create($validated + ['is_active' => true, 'created_by' => auth()->id()]);
        LetterService::ensureNumbering($type);
        AuditService::created('LETTER', $type);

        return back()->with('success', 'Jenis surat dibuat.');
    }

    public function update(Request $request, LetterType $letter_type)
    {
        $validated = $request->validate([
            'name' => 'required|max:150',
            'numbering_format' => 'required|max:200',
            'reset_period' => 'required|in:YEARLY,MONTHLY,NEVER',
            'is_active' => 'boolean',
        ]);
        $letter_type->update($validated);
        LetterService::ensureNumbering($letter_type->fresh());
        AuditService::updated('LETTER', $letter_type);

        return back()->with('success', 'Jenis surat diperbarui.');
    }
}
