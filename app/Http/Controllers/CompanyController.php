<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends BaseCrudController
{
    protected string $model = Company::class;
    protected string $viewPrefix = 'companies';
    protected string $module = 'company';
    protected array $rules = [
            'code' => 'required|max:20',
            'name' => 'required|max:150',
            'npwp' => 'nullable',
            'address' => 'nullable',
            'city' => 'nullable|max:100',
            'phone' => 'nullable|max:30',
            'email' => 'nullable|email',
            'status' => 'boolean',
    ];
}
