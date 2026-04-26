<?php

return [
    'complaint_categories' => [
        'Pendaftaran & Aktivasi',
        'Dukungan Teknis',
        'Masalah Pembayaran',
        'Komplain / Keluhan',
        'Lain-lain',
    ],
    'whatsapp_conversation_reuse_minutes' => (int) env('OPENCLAW_WHATSAPP_CONVERSATION_REUSE_MINUTES', 30),
    'whatsapp_reset_commands' => [
        'menu',
        'reset',
        'mulai',
        'start',
        'restart',
    ],
    'whatsapp_reset_greetings' => [
        'halo',
        'haloo',
        'halooo',
        'hai',
        'haii',
        'hi',
        'hii',
        'hiii',
        'hello',
        'p',
        'permisi',
        'assalamualaikum',
        'assalamu\'alaikum',
        'asalamualaikum',
        'asalamu\'alaikum',
    ],
];
