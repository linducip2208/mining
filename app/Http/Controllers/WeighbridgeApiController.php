<?php

namespace App\Http\Controllers;

use App\Services\Weighbridge\RestProvider;
use Illuminate\Http\Request;

/**
 * External bridge-device ingest (token auth, idempotent).
 */
class WeighbridgeApiController extends Controller
{
    public function reading(Request $request)
    {
        $token = $request->bearerToken() ?: (string) $request->input('api_token', '');
        if ($token === '') {
            return response()->json(['message' => 'Token wajib diisi.'], 401);
        }
        try {
            $reading = RestProvider::ingest($token, $request->only([
                'raw_weight', 'stable_weight', 'read_at', 'is_stable', 'notes',
            ]));
        } catch (\DomainException $e) {
            $code = $e->getMessage() === 'Token device tidak valid.' ? 401 : 422;
            return response()->json(['message' => $e->getMessage()], $code);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json([
            'id' => $reading->id,
            'stable_weight' => (float) $reading->stable_weight,
            'read_at' => $reading->read_at,
        ], 201);
    }
}
