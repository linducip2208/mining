<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;

class SiteController extends BaseCrudController
{
    protected string $model = Site::class;
    protected string $viewPrefix = 'sites';
    protected string $module = 'site';
    protected array $rules = [
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|max:20',
            'name' => 'required|max:150',
            'type' => 'required|in:MINE,PLANT,PORT,OFFICE',
            'status' => 'boolean',
    ];
}
