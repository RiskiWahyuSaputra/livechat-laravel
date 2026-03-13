<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BotMenu;

class BotMenuSeeder extends Seeder
{
    public function run()
    {
        // 1. YouTube Menu (Direct Link)
        BotMenu::create([
            'label' => 'Youtube BRILLIAN.BIZ',
            'message_response' => "Anda dapat menonton channel youtube BRILLIAN.BIZ di sini:\nhttps://www.youtube.com/channel/UCrYL75CKdg0s4RonBqu-tQg",
            'action_type' => 'link',
            'action_value' => 'https://www.youtube.com/channel/UCrYL75CKdg0s4RonBqu-tQg',
            'order_index' => 1
        ]);

        // 2. Hubungi CS Menu (Submenu)
        $csMenu = BotMenu::create([
            'label' => 'Hubungi CS',
            'message_response' => 'Pilih customer service yang anda inginkan:',
            'action_type' => 'submenu',
            'order_index' => 2
        ]);

            // 2a. Submenu: Customer service
            BotMenu::create([
                'parent_id' => $csMenu->id,
                'label' => 'Customer service',
                'message_response' => null, // Will use custom AI opt-in logic in controller for now
                'action_type' => 'connect_cs',
                'action_value' => 'General Support',
                'order_index' => 1
            ]);

            // 2b. Submenu: CS Voucher
            BotMenu::create([
                'parent_id' => $csMenu->id,
                'label' => 'CS Voucher',
                'message_response' => 'Anda akan terhubung dengan CS Voucher, isi kebutuhan anda.',
                'action_type' => 'connect_cs',
                'action_value' => 'Voucher Support',
                'order_index' => 2
            ]);

        // 3. Jadwal Seminar Menu (Direct Link)
        BotMenu::create([
            'label' => 'Jadwal seminar',
            'message_response' => "Berikut jadwal seminar BRILLIAN.BIZ:\nhttps://seminar.mybrilian.com/jadwal-seminar",
            'action_type' => 'link',
            'action_value' => 'https://seminar.mybrilian.com/jadwal-seminar',
            'order_index' => 3
        ]);
    }
}
