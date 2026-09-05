<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends BaseCrudController
{
    protected string $model = Customer::class;
    protected string $viewPrefix = 'customers';
    protected string $module = 'sales';
    protected array $rules = [
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|max:30',
            'name' => 'required|max:150',
            'npwp' => 'nullable',
            'address' => 'nullable',
            'city' => 'nullable',
            'phone' => 'nullable',
            'email' => 'nullable|email',
            'payment_term_id' => 'nullable|exists:payment_terms,id',
            'credit_limit' => 'nullable|numeric',
            'group' => 'nullable',
            'status' => 'boolean',
    ];
}
