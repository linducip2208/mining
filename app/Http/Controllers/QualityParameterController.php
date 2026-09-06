<?php

namespace App\Http\Controllers;

use App\Models\QualityParameter;
use Illuminate\Http\Request;

class QualityParameterController extends BaseCrudController
{
    protected string $model = QualityParameter::class;
    protected string $viewPrefix = 'quality.parameters';
    protected string $module = 'quality';
    protected array $rules = [
        'code' => 'required|max:30',
        'name' => 'required|max:100',
        'unit' => 'nullable|max:20',
        'status' => 'boolean',
    ];
}
