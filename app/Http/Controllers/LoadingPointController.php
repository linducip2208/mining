<?php

namespace App\Http\Controllers;

use App\Models\LoadingPoint;
use Illuminate\Http\Request;

class LoadingPointController extends BaseCrudController
{
    protected string $model = LoadingPoint::class;
    protected string $viewPrefix = 'dispatch.loading-points';
    protected string $module = 'dispatch';
    protected array $rules = [
        'site_id' => 'required|exists:sites,id',
        'pit_id' => 'nullable|exists:pits,id',
        'code' => 'required|max:30',
        'name' => 'required|max:150',
        'status' => 'boolean',
    ];
}
