<?php

use App\Http\Controllers\PrintJobController;
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

// Callback dari Local Print Agent. HMAC signed; tidak memakai session/cookie.
Route::post('/print-jobs/{print_job}/agent-status', [PrintJobController::class, 'agentStatus'])
    ->name('print-jobs.agent-status');
