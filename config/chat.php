<?php

return [
    'complaint_categories' => [
        'Pendaftaran & Aktivasi',
        'Dukungan Teknis',
        'Masalah Pembayaran',
        'Komplain / Keluhan',
        'Lain-lain',
    ],

    /*
     |--------------------------------------------------------------------------
     | Flow Engine Feature Flag
     |--------------------------------------------------------------------------
     | When enabled (default: true) the chatbot uses the dynamic FlowEngine
     | instead of the legacy hardcoded complaint-category state machine.
     | Set FLOW_ENGINE_ENABLED=false in .env to revert to legacy behaviour.
     */
    'use_flow_engine' => env('FLOW_ENGINE_ENABLED', true),
];
