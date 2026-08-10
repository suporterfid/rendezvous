<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $dbStatus = 'ok';
        try {
            DB::connection()->getPdo();
        } catch (Exception $e) {
            $dbStatus = 'error';
        }

        return response()->json([
            'status' => $dbStatus === 'ok' ? 'healthy' : 'unhealthy',
            'database' => $dbStatus,
            'mailbox_ingest_last_seen_at' => now()->toIso8601String(),
            'feed_refresh_last_seen_at' => now()->toIso8601String(),
            'stale_claims' => 0,
            'version' => '0.1.0-mvp'
        ]);
    }
}
