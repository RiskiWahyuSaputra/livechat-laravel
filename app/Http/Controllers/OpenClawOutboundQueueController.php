<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\OpenClawWhatsappOutboundQueue;
use Illuminate\Http\Request;

class OpenClawOutboundQueueController extends Controller
{
    public function __construct(
        protected OpenClawWhatsappOutboundQueue $queue
    ) {
    }

    public function pull(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $limit = min(50, max(1, (int) $request->input('limit', 10)));
        $leaseSeconds = min(300, max(15, (int) $request->input('lease_seconds', 60)));

        return response()->json([
            'status' => 'ok',
            'items' => $this->queue->claim($limit, $leaseSeconds),
        ]);
    }

    public function acknowledge(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $acks = $request->input('acks');
        if (!is_array($acks)) {
            $singleId = trim((string) $request->input('id'));
            if ($singleId !== '') {
                $acks = [[
                    'id' => $singleId,
                    'success' => filter_var($request->input('success', false), FILTER_VALIDATE_BOOL),
                    'error' => (string) $request->input('error', ''),
                ]];
            } else {
                $acks = [];
            }
        }

        foreach ($acks as $ack) {
            $id = trim((string) ($ack['id'] ?? ''));
            if ($id === '') {
                continue;
            }

            $this->queue->acknowledge(
                $id,
                filter_var($ack['success'] ?? false, FILTER_VALIDATE_BOOL),
                isset($ack['error']) ? (string) $ack['error'] : null,
            );
        }

        return response()->json(['status' => 'ok']);
    }

    private function isAuthorized(Request $request): bool
    {
        $expected = trim((string) Setting::get('openclaw_bridge_token', env('OPENCLAW_BRIDGE_TOKEN', '')));
        if ($expected === '') {
            return true;
        }

        $provided = $request->bearerToken()
            ?? $request->header('X-OpenClaw-Bridge-Token')
            ?? $request->input('token');

        return is_string($provided) && $provided !== '' && hash_equals($expected, $provided);
    }
}
