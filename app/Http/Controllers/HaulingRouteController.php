<?php

namespace App\Http\Controllers;

use App\Models\HaulingRoute;
use Illuminate\Http\Request;

class HaulingRouteController extends BaseCrudController
{
    protected string $model = HaulingRoute::class;
    protected string $viewPrefix = 'dispatch.routes';
    protected string $module = 'dispatch';
    protected array $rules = [
        'site_id' => 'required|exists:sites,id',
        'code' => 'required|max:30',
        'name' => 'required|max:150',
        'loading_point_id' => 'nullable|exists:loading_points,id',
        'dumping_point_id' => 'nullable|exists:dumping_points,id',
        'distance_km' => 'nullable|numeric|min:0',
        'status' => 'boolean',
    ];
}
