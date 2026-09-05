<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends BaseCrudController
{
    protected string $model = Asset::class;
    protected string $viewPrefix = 'assets';
    protected string $module = 'asset';
    protected array $rules = [
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'code' => 'required|max:30',
            'name' => 'required|max:150',
            'type' => 'required',
            'acquisition_date' => 'nullable|date',
            'acquisition_cost' => 'nullable|numeric',
            'useful_life_years' => 'nullable|integer',
            'status' => 'required',
    ];
}
