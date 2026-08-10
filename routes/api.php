<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthCheckController;
use App\Http\Controllers\Api\TaskConnectWebhookController;
use App\Http\Controllers\Api\CaldavServerController;

Route::prefix('v1')->group(function () {
    Route::get('/platform/health', HealthCheckController::class);

    // TaskConnect Integration Webhooks
    Route::post('/taskconnect/ingest-mailboxes', [TaskConnectWebhookController::class, 'triggerIngest']);
    Route::post('/taskconnect/refresh-feeds', [TaskConnectWebhookController::class, 'triggerRefresh']);

    // CalDAV Server Endpoints (PUT, DELETE, PROPFIND, REPORT, GET)
    Route::any('/caldav/calendars/{calendarPublicId}/events/{eventUid?}', [CaldavServerController::class, 'handle']);
});
