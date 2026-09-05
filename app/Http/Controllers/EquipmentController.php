<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentController extends BaseCrudController
{
    protected string $model = Equipment::class;
    protected string $viewPrefix = 'equipment';
    protected string $module = 'asset';
    protected array $rules = [
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'code' => 'required|max:30',
            'name' => 'required|max:150',
            'type' => 'required',
            'brand' => 'nullable',
            'model' => 'nullable',
            'plate_no' => 'nullable',
            'ownership' => 'required',
            'status' => 'required',
    ];
}
