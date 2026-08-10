<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domain\TaskConnect\Services\TaskConnectService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class TaskConnectWebhookController extends Controller
{
    public function __construct(private TaskConnectService $taskConnectService) {}

    public function triggerIngest(Request $request): JsonResponse
    {
        if (!$this->taskConnectService->isEnabled()) {
            return response()->json(['error' => 'TaskConnect seam is disabled'], 403);
        }

        $signature = $request->header('X-TaskConnect-Signature', '');
        if (!$this->taskConnectService->verifySignature($request->getContent(), $signature)) {
            return response()->json(['error' => 'Invalid HMAC signature'], 401);
        }

        Artisan::call('calendar:ingest-mailboxes');

        return response()->json([
            'success' => true,
            'action' => 'calendar:ingest-mailboxes',
            'executed_at' => now()->toIso8601String()
        ]);
    }

    public function triggerRefresh(Request $request): JsonResponse
    {
        if (!$this->taskConnectService->isEnabled()) {
            return response()->json(['error' => 'TaskConnect seam is disabled'], 403);
        }

        $signature = $request->header('X-TaskConnect-Signature', '');
        if (!$this->taskConnectService->verifySignature($request->getContent(), $signature)) {
            return response()->json(['error' => 'Invalid HMAC signature'], 401);
        }

        Artisan::call('calendar:refresh-feeds');

        return response()->json([
            'success' => true,
            'action' => 'calendar:refresh-feeds',
            'executed_at' => now()->toIso8601String()
        ]);
    }
}
