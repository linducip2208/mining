<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends BaseCrudController
{
    protected string $model = Item::class;
    protected string $viewPrefix = 'items';
    protected string $module = 'inventory';
    protected array $rules = [
            'code' => 'required|max:30',
            'name' => 'required|max:150',
            'item_category_id' => 'required|exists:item_categories,id',
            'type' => 'required',
            'unit_id' => 'required|exists:units,id',
            'min_stock' => 'nullable|numeric',
            'reorder_point' => 'nullable|numeric',
            'standard_cost' => 'nullable|numeric',
            'status' => 'boolean',
    ];
}
