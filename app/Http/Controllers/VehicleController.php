<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends BaseCrudController
{
    protected string $model = Vehicle::class;
    protected string $viewPrefix = 'vehicles';
    protected string $module = 'fleet';
    protected array $rules = [
        'company_id' => 'required|exists:companies,id',
        'code' => 'required|max:30',
        'plate_no' => 'required|max:30',
        'name' => 'required|max:150',
        'type' => 'required|max:30',
        'capacity_ton' => 'nullable|numeric|min:0',
        'status' => 'required|max:20',
    ];
}
