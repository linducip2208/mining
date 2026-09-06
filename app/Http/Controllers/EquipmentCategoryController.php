<?php

namespace App\Http\Controllers;

use App\Models\EquipmentCategory;
use Illuminate\Http\Request;

class EquipmentCategoryController extends BaseCrudController
{
    protected string $model = EquipmentCategory::class;
    protected string $viewPrefix = 'equipment-categories';
    protected string $module = 'fleet';
    protected array $rules = [
        'code' => 'required|max:20',
        'name' => 'required|max:100',
        'type' => 'required|in:HEAVY,VEHICLE,SUPPORT',
        'standard_fuel_lph' => 'nullable|numeric|min:0',
        'fuel_warning_pct' => 'nullable|numeric|min:0|max:500',
        'status' => 'boolean',
    ];
}
