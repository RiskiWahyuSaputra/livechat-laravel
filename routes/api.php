<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsappWebhookController;


Route::post('/webhook/whatsapp', [WhatsappWebhookController::class, 'handle']);
