<?php

namespace App\Http\Controllers;

use App\Models\FuelTank;
use Illuminate\Http\Request;

class FuelTankController extends BaseCrudController
{
    protected string $model = FuelTank::class;
    protected string $viewPrefix = 'fuel.tanks';
    protected string $module = 'fuel';
    protected array $rules = [
        'company_id' => 'required|exists:companies,id',
        'site_id' => 'nullable|exists:sites,id',
        'code' => 'required|max:30',
        'name' => 'required|max:150',
        'capacity_liter' => 'nullable|numeric|min:0',
        'fuel_type' => 'required|in:SOLAR,BENSIN,LISTRIK',
        'status' => 'boolean',
    ];
}
