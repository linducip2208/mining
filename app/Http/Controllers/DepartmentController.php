<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends BaseCrudController
{
    protected string $model = Department::class;
    protected string $viewPrefix = 'departments';
    protected string $module = 'department';
    protected array $rules = [
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|max:20',
            'name' => 'required|max:150',
            'status' => 'boolean',
    ];
}
