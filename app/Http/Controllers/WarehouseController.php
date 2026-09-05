<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends BaseCrudController
{
    protected string $model = Warehouse::class;
    protected string $viewPrefix = 'warehouses';
    protected string $module = 'inventory';
    protected array $rules = [
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'code' => 'required|max:30',
            'name' => 'required|max:150',
            'type' => 'required',
            'status' => 'boolean',
    ];
}
