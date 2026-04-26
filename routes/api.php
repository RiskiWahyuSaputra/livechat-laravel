<?php

use App\Http\Controllers\OpenClawWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/openclaw/whatsapp', [OpenClawWebhookController::class, 'handleWhatsapp'])
    ->name('api.openclaw.whatsapp.webhook');
