<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ContactReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactReportController extends Controller
{
    protected ContactReportService $service;

    public function __construct(ContactReportService $service)
    {
        $this->service = $service;
    }

    /**
     * Display the Contact Report page with initial data.
     *
     * Reads filters from query string with defaults:
     * - timezone: UTC
     * - channel: null (all channels)
     * - start_date: 30 days ago (Y-m-d)
     * - end_date: today (Y-m-d)
     *
     * Requirements: 1.1, 1.2, 7.1
     */
    public function index(Request $request): View
    {
        $filters = [
            'timezone'   => $request->get('timezone', 'UTC'),
            'channel'    => null,
            'start_date' => $request->get('start_date', Carbon::now()->subDays(29)->format('Y-m-d')),
            'end_date'   => $request->get('end_date', Carbon::now()->format('Y-m-d')),
        ];

        $reportData = $this->service->getReportData($filters);

        return view('admin.contact-report', compact('reportData', 'filters'));
    }

    /**
     * Return JSON data for dynamic filter updates (AJAX endpoint).
     *
     * Validates:
     * - timezone: required, valid PHP DateTimeZone identifier
     * - start_date: required, date
     * - end_date: required, date, after_or_equal:start_date
     * - channel: nullable, string
     *
     * Requirements: 1.2, 7.3, 7.4
     */
    public function data(Request $request): JsonResponse
    {
        $request->validate([
            'timezone' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    try {
                        new \DateTimeZone($value);
                    } catch (\Exception $e) {
                        $fail('Timezone tidak valid.');
                    }
                },
            ],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $filters = [
            'timezone'   => $request->get('timezone'),
            'channel'    => null,
            'start_date' => $request->get('start_date'),
            'end_date'   => $request->get('end_date'),
        ];

        return response()->json($this->service->getReportData($filters));
    }
}
