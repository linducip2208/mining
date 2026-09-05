<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends BaseCrudController
{
    protected string $model = Supplier::class;
    protected string $viewPrefix = 'suppliers';
    protected string $module = 'procurement';
    protected array $rules = [
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|max:30',
            'name' => 'required|max:150',
            'payment_term_id' => 'nullable|exists:payment_terms,id',
            'type' => 'required',
            'status' => 'boolean',
    ];
}
