<?php

namespace App\Http\Controllers;

use App\Models\DumpingPoint;
use Illuminate\Http\Request;

class DumpingPointController extends BaseCrudController
{
    protected string $model = DumpingPoint::class;
    protected string $viewPrefix = 'dispatch.dumping-points';
    protected string $module = 'dispatch';
    protected array $rules = [
        'site_id' => 'required|exists:sites,id',
        'code' => 'required|max:30',
        'name' => 'required|max:150',
        'type' => 'required|in:STOCKPILE,CRUSHER,PORT,WASTE_DUMP',
        'warehouse_id' => 'nullable|exists:warehouses,id',
        'crusher_id' => 'nullable|exists:crushers,id',
        'status' => 'boolean',
    ];
}
