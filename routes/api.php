<?php

use App\Http\Controllers\OpenClawOutboundQueueController;
use App\Http\Controllers\OpenClawWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/openclaw/whatsapp', [OpenClawWebhookController::class, 'handleWhatsapp'])
    ->name('api.openclaw.whatsapp.webhook');

Route::post('/openclaw/whatsapp/outbound/pull', [OpenClawOutboundQueueController::class, 'pull'])
    ->name('api.openclaw.whatsapp.outbound.pull');

Route::post('/openclaw/whatsapp/outbound/ack', [OpenClawOutboundQueueController::class, 'acknowledge'])
    ->name('api.openclaw.whatsapp.outbound.ack');
