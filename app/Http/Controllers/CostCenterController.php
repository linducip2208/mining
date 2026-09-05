<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use Illuminate\Http\Request;

class CostCenterController extends BaseCrudController
{
    protected string $model = CostCenter::class;
    protected string $viewPrefix = 'cost-centers';
    protected string $module = 'cost_center';
    protected array $rules = [
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|max:20',
            'name' => 'required|max:150',
            'type' => 'in:COST,PROFIT',
            'status' => 'boolean',
    ];
}
