<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => config('app.name'),
            'version' => config('app.version'),
            'time' => now()->toIso8601String(),
        ]);
    }

    public function ready(): JsonResponse
    {
        try {
            DB::select('SELECT 1');

            if (!is_writable(storage_path('framework'))) {
                throw new \RuntimeException('Application storage is not writable.');
            }

            return response()->json([
                'status' => 'ready',
                'checks' => [
                    'database' => 'ok',
                    'storage' => 'ok',
                ],
                'time' => now()->toIso8601String(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'not_ready',
                'time' => now()->toIso8601String(),
            ], 503);
        }
    }
}
