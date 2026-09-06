<?php

namespace App\Http\Controllers;

use App\Models\FuelTank;
use Illuminate\Http\Request;

class FuelTankController extends BaseCrudController
{
    protected string $model = FuelTank::class;
    protected string $viewPrefix = 'fuel.tanks';
    protected string $module = 'fuel';
    protected array $rules = [
        'company_id' => 'required|exists:companies,id',
        'site_id' => 'nullable|exists:sites,id',
        'code' => 'required|max:30',
        'name' => 'required|max:150',
        'capacity_liter' => 'nullable|numeric|min:0',
        'fuel_type' => 'required|in:SOLAR,BENSIN,LISTRIK',
        'status' => 'boolean',
    ];

    public function destroy($id)
    {
        $tank = FuelTank::findOrFail($id);
        if (\App\Models\FuelLedger::where('fuel_tank_id', $tank->id)->exists()) {
            return back()->with('error', 'Tangki memiliki riwayat ledger — nonaktifkan saja, jangan hapus.');
        }
        return parent::destroy($id);
    }
}
