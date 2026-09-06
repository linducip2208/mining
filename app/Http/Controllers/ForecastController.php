<?php

namespace App\Http\Controllers;

use App\Services\ForecastService;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    public function index()
    {
        return view('forecast.index', [
            'production' => ForecastService::productionForecast(),
            'fuel' => ForecastService::fuelAnomalies(),
            'weighbridge' => ForecastService::weighbridgeAnomalies(),
            'purchase' => ForecastService::purchasePriceAnomalies(),
            'overtime' => ForecastService::overtimeAnomalies(),
            'downtime' => ForecastService::downtimeAnomalies(),
            'adjustments' => ForecastService::stockAdjustmentAnomalies(),
            'cash' => ForecastService::cashFlowForecast(),
        ]);
    }
}
