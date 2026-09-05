<?php

namespace App\Http\Controllers;

use App\Models\PaymentTerm;
use Illuminate\Http\Request;

class PaymentTermController extends BaseCrudController
{
    protected string $model = PaymentTerm::class;
    protected string $viewPrefix = 'payment-terms';
    protected string $module = 'finance';
    protected array $rules = [
            'code' => 'required|max:20',
            'name' => 'required|max:100',
            'days' => 'required|integer|min:0',
    ];
}
