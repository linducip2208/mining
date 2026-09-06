<?php

use App\Http\Controllers\WeighbridgeApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — MINING ERP
|--------------------------------------------------------------------------
| Device-facing endpoints (token auth di controller, BUKAN Sanctum session).
| Dipakai bridge timbangan driver REST untuk push hasil pembacaan.
*/

// Hasil timbangan dari device (idempotent, token via Bearer / api_token)
Route::post('/weighbridge/reading', [WeighbridgeApiController::class, 'reading'])
    ->name('api.weighbridge.reading');
