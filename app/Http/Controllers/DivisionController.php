<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;

class DivisionController extends BaseCrudController
{
    protected string $model = Division::class;
    protected string $viewPrefix = 'divisions';
    protected string $module = 'division';
    protected array $rules = [
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|max:20',
            'name' => 'required|max:150',
            'status' => 'boolean',
    ];
}
