<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class ChatSettingSeeder extends Seeder
{
    public function run()
    {
        Setting::set('chat_greeting', 'anda berapa di layanan whatsapp BRILLIAN.BIS kami terus melayani', 'chat');
        Setting::set('chat_main_menu', json_encode([
            ['id' => 'youtube', 'label' => 'Youtube BRILLIAN.BIZ'],
            ['id' => 'hubungi_cs', 'label' => 'Hubungi CS'],
            ['id' => 'jadwal_seminar', 'label' => 'Jadwal seminar'],
        ]), 'chat');

        Setting::set('chat_seminar_schedule', "Berikut jadwal seminar BRILLIAN.BIZ:\n1. Seminar Bisnis - Senin, 10:00\n2. Webinar Marketing - Rabu, 14:00\n3. Workshop AI - Sabtu, 09:00", 'chat');
    }
}
