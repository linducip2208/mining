<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends BaseCrudController
{
    protected string $model = Branch::class;
    protected string $viewPrefix = 'branches';
    protected string $module = 'branch';
    protected array $rules = [
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|max:20',
            'name' => 'required|max:150',
            'city' => 'nullable|max:100',
            'status' => 'boolean',
    ];
}
