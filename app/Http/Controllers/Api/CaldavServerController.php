<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Services\CaldavServerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Exception;

class CaldavServerController extends Controller
{
    public function __construct(private CaldavServerService $caldavService) {}

    public function handle(Request $request, string $calendarPublicId, string $eventUid = ''): Response
    {
        $calendar = Calendar::where('public_id', $calendarPublicId)->first();

        if (!$calendar) {
            return response('Calendar Not Found', 404);
        }

        $method = strtoupper($request->method());

        try {
            switch ($method) {
                case 'PUT':
                    $this->caldavService->handlePut($calendar, $eventUid, $request->getContent());
                    return response('', 201);

                case 'DELETE':
                    $deleted = $this->caldavService->handleDelete($calendar, $eventUid);
                    return response('', $deleted ? 204 : 404);

                case 'PROPFIND':
                case 'REPORT':
                case 'GET':
                    return response('CalDAV Endpoint Active', 200, ['Content-Type' => 'text/calendar']);

                default:
                    return response('Method Not Allowed', 405);
            }
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }
}
