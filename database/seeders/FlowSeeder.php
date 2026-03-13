<?php

namespace Database\Seeders;

use App\Models\ConversationFlow;
use App\Models\FlowEdge;
use App\Models\FlowNode;
use Illuminate\Database\Seeder;

class FlowSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedChooseCustomerService();
        $this->seedCsGeneralBot();
        $this->seedCsVoucherBot();
        $this->seedCsUndercuttingBot();
    }

    // -------------------------------------------------------------------------
    // Flow 1: choose_customer_service
    // -------------------------------------------------------------------------
    private function seedChooseCustomerService(): void
    {
        $flow = ConversationFlow::updateOrCreate(
            ['code' => 'choose_customer_service'],
            [
                'name'        => 'Pilih Customer Service',
                'description' => 'Menu utama pemilihan layanan CS',
                'status'      => 'published',
            ]
        );

        $scheduleInfo = implode("\n", [
            '',
            '📋 *Jadwal Customer Service:*',
            '- Senin–Jumat: 08:00–22:00 WIB',
            '- Sabtu & Minggu: 09:00–21:00 WIB',
            '',
            '🎟️ *Jadwal CS Voucher:*',
            '- Senin–Jumat: 08:30–17:00 WIB',
            '- Sabtu: 09:00–15:00 WIB',
            '- Minggu & Libur Nasional: Tidak ada layanan',
            '',
            '⚡ *Jadwal CS Undercutting Price:*',
            '- Senin–Jumat: 09:00–17:30 WIB',
            '- Sabtu: 09:00–15:00 WIB',
            '- Minggu & Libur Nasional: Tidak ada layanan',
        ]);

        $nodes = [
            'start' => [
                'type'    => 'START',
                'content' => null,
            ],
            'message_intro' => [
                'type'    => 'MESSAGE',
                'content' => [
                    'text' => "👋 Selamat datang di *BEST CORP*!\n\nAnda akan terhubung dengan Customer Service. Pilih Customer Service yang Anda inginkan:" . $scheduleInfo,
                ],
            ],
            'menu_choose' => [
                'type'    => 'MENU',
                'content' => [
                    'prompt'  => 'Silakan pilih layanan dengan mengetik angka:',
                    'options' => [
                        ['key' => '1', 'label' => 'Customer Service'],
                        ['key' => '2', 'label' => 'CS Voucher'],
                        ['key' => '3', 'label' => 'CS Undercutting Price'],
                    ],
                ],
            ],
            'switch_cs_general' => [
                'type'    => 'SWITCH_FLOW',
                'content' => ['to_flow_code' => 'cs_general_bot'],
            ],
            'switch_cs_voucher' => [
                'type'    => 'SWITCH_FLOW',
                'content' => ['to_flow_code' => 'cs_voucher_bot'],
            ],
            'switch_cs_undercutting' => [
                'type'    => 'SWITCH_FLOW',
                'content' => ['to_flow_code' => 'cs_undercutting_bot'],
            ],
            'fallback' => [
                'type'    => 'FALLBACK',
                'content' => [
                    'text'              => '⚠️ Mohon pilih layanan yang tersedia dengan mengetik angka *1*, *2*, atau *3*.',
                    'go_to_node_code'   => 'menu_choose',
                ],
            ],
        ];

        $createdNodes = $this->upsertNodes($flow, $nodes);

        $edges = [
            ['from' => 'start',         'to' => 'message_intro',         'type' => 'always',      'value' => null, 'priority' => 1],
            ['from' => 'message_intro', 'to' => 'menu_choose',           'type' => 'always',      'value' => null, 'priority' => 1],
            ['from' => 'menu_choose',   'to' => 'switch_cs_general',     'type' => 'user_choice', 'value' => ['choice' => '1'], 'priority' => 1],
            ['from' => 'menu_choose',   'to' => 'switch_cs_voucher',     'type' => 'user_choice', 'value' => ['choice' => '2'], 'priority' => 2],
            ['from' => 'menu_choose',   'to' => 'switch_cs_undercutting','type' => 'user_choice', 'value' => ['choice' => '3'], 'priority' => 3],
            ['from' => 'menu_choose',   'to' => 'fallback',              'type' => 'always',      'value' => null, 'priority' => 99],
        ];

        $this->upsertEdges($flow, $createdNodes, $edges);
    }

    // -------------------------------------------------------------------------
    // Flow 2: cs_general_bot
    // -------------------------------------------------------------------------
    private function seedCsGeneralBot(): void
    {
        $flow = ConversationFlow::updateOrCreate(
            ['code' => 'cs_general_bot'],
            [
                'name'        => 'CS General Bot',
                'description' => 'Alur Customer Service umum (jam operasional + chatbot)',
                'status'      => 'published',
            ]
        );

        $closedText = implode("\n", [
            '⏰ Maaf, saat ini *Customer Service* sedang tidak tersedia.',
            '',
            '📋 Jadwal Customer Service:',
            '- Senin–Jumat: 08:00–22:00 WIB',
            '- Sabtu & Minggu: 09:00–21:00 WIB',
            '',
            'Silakan tinggalkan pesan Anda dan kami akan menghubungi Anda saat jadwal aktif.',
        ]);

        $nodes = [
            'start'           => ['type' => 'START',   'content' => null],
            'open_greeting'   => [
                'type'    => 'MESSAGE',
                'content' => ['text' => "✅ *CS kami sedang online!*\n\nSilakan tulis pesan atau pertanyaan Anda, dan tim kami akan segera membantu."],
            ],
            'closed_greeting' => [
                'type'    => 'MESSAGE',
                'content' => ['text' => $closedText],
            ],
            'input_collect'   => [
                'type'    => 'INPUT',
                'content' => [
                    'prompt'              => 'Silakan tulis pesan Anda:',
                    'save_to_context_key' => 'user_message',
                ],
            ],
            'message_confirm' => [
                'type'    => 'MESSAGE',
                'content' => ['text' => "✅ Pesan Anda telah kami terima!\n\nTim Customer Service akan menghubungi Anda secepatnya. Terima kasih telah menghubungi *BEST CORP*. 😊"],
            ],
            'end'             => ['type' => 'END', 'content' => null],
        ];

        $createdNodes = $this->upsertNodes($flow, $nodes);

        $edges = [
            ['from' => 'start',           'to' => 'open_greeting',   'type' => 'within_schedule',  'value' => ['service' => 'cs_general'], 'priority' => 1],
            ['from' => 'start',           'to' => 'closed_greeting', 'type' => 'outside_schedule', 'value' => ['service' => 'cs_general'], 'priority' => 2],
            ['from' => 'open_greeting',   'to' => 'input_collect',   'type' => 'always',            'value' => null, 'priority' => 1],
            ['from' => 'closed_greeting', 'to' => 'input_collect',   'type' => 'always',            'value' => null, 'priority' => 1],
            ['from' => 'input_collect',   'to' => 'message_confirm', 'type' => 'always',            'value' => null, 'priority' => 1],
            ['from' => 'message_confirm', 'to' => 'end',             'type' => 'always',            'value' => null, 'priority' => 1],
        ];

        $this->upsertEdges($flow, $createdNodes, $edges);
    }

    // -------------------------------------------------------------------------
    // Flow 3: cs_voucher_bot
    // -------------------------------------------------------------------------
    private function seedCsVoucherBot(): void
    {
        $flow = ConversationFlow::updateOrCreate(
            ['code' => 'cs_voucher_bot'],
            [
                'name'        => 'CS Voucher Bot',
                'description' => 'Alur Admin Pembelian Voucher (jam operasional + chatbot)',
                'status'      => 'published',
            ]
        );

        $closedText = implode("\n", [
            '⏰ Maaf, saat ini *CS Voucher* sedang tidak tersedia.',
            '',
            '📋 Jadwal CS Voucher:',
            '- Senin–Jumat: 08:30–17:00 WIB',
            '- Sabtu: 09:00–15:00 WIB',
            '- Minggu & Libur Nasional: Tidak ada layanan',
            '',
            'Silakan tinggalkan pesan Anda dan kami akan menghubungi Anda saat jadwal aktif.',
        ]);

        $nodes = [
            'start'           => ['type' => 'START',   'content' => null],
            'open_greeting'   => [
                'type'    => 'MESSAGE',
                'content' => ['text' => "✅ *CS Voucher kami sedang online!*\n\nSilakan tulis pertanyaan Anda mengenai pembelian voucher."],
            ],
            'closed_greeting' => [
                'type'    => 'MESSAGE',
                'content' => ['text' => $closedText],
            ],
            'input_collect'   => [
                'type'    => 'INPUT',
                'content' => [
                    'prompt'              => 'Silakan tulis pesan Anda:',
                    'save_to_context_key' => 'user_message',
                ],
            ],
            'message_confirm' => [
                'type'    => 'MESSAGE',
                'content' => ['text' => "✅ Pesan Anda untuk *CS Voucher* telah kami terima!\n\nTim kami akan menghubungi Anda secepatnya. Terima kasih! 😊"],
            ],
            'end'             => ['type' => 'END', 'content' => null],
        ];

        $createdNodes = $this->upsertNodes($flow, $nodes);

        $edges = [
            ['from' => 'start',           'to' => 'open_greeting',   'type' => 'within_schedule',  'value' => ['service' => 'cs_voucher'], 'priority' => 1],
            ['from' => 'start',           'to' => 'closed_greeting', 'type' => 'outside_schedule', 'value' => ['service' => 'cs_voucher'], 'priority' => 2],
            ['from' => 'open_greeting',   'to' => 'input_collect',   'type' => 'always',            'value' => null, 'priority' => 1],
            ['from' => 'closed_greeting', 'to' => 'input_collect',   'type' => 'always',            'value' => null, 'priority' => 1],
            ['from' => 'input_collect',   'to' => 'message_confirm', 'type' => 'always',            'value' => null, 'priority' => 1],
            ['from' => 'message_confirm', 'to' => 'end',             'type' => 'always',            'value' => null, 'priority' => 1],
        ];

        $this->upsertEdges($flow, $createdNodes, $edges);
    }

    // -------------------------------------------------------------------------
    // Flow 4: cs_undercutting_bot
    // -------------------------------------------------------------------------
    private function seedCsUndercuttingBot(): void
    {
        $flow = ConversationFlow::updateOrCreate(
            ['code' => 'cs_undercutting_bot'],
            [
                'name'        => 'CS Undercutting Price Bot',
                'description' => 'Alur CS Undercutting Price (jam operasional + chatbot)',
                'status'      => 'published',
            ]
        );

        $closedText = implode("\n", [
            '⏰ Maaf, saat ini *CS Undercutting Price* sedang tidak tersedia.',
            '',
            '📋 Jadwal CS Undercutting Price:',
            '- Senin–Jumat: 09:00–17:30 WIB',
            '- Sabtu: 09:00–15:00 WIB',
            '- Minggu & Libur Nasional: Tidak ada layanan',
            '',
            'Silakan tinggalkan pesan Anda dan kami akan menghubungi Anda saat jadwal aktif.',
        ]);

        $nodes = [
            'start'           => ['type' => 'START',   'content' => null],
            'open_greeting'   => [
                'type'    => 'MESSAGE',
                'content' => ['text' => "✅ *CS Undercutting Price kami sedang online!*\n\nSilakan tulis pertanyaan Anda mengenai harga undercutting."],
            ],
            'closed_greeting' => [
                'type'    => 'MESSAGE',
                'content' => ['text' => $closedText],
            ],
            'input_collect'   => [
                'type'    => 'INPUT',
                'content' => [
                    'prompt'              => 'Silakan tulis pesan Anda:',
                    'save_to_context_key' => 'user_message',
                ],
            ],
            'message_confirm' => [
                'type'    => 'MESSAGE',
                'content' => ['text' => "✅ Pesan Anda untuk *CS Undercutting Price* telah kami terima!\n\nTim kami akan menghubungi Anda secepatnya. Terima kasih! 😊"],
            ],
            'end'             => ['type' => 'END', 'content' => null],
        ];

        $createdNodes = $this->upsertNodes($flow, $nodes);

        $edges = [
            ['from' => 'start',           'to' => 'open_greeting',   'type' => 'within_schedule',  'value' => ['service' => 'cs_undercutting'], 'priority' => 1],
            ['from' => 'start',           'to' => 'closed_greeting', 'type' => 'outside_schedule', 'value' => ['service' => 'cs_undercutting'], 'priority' => 2],
            ['from' => 'open_greeting',   'to' => 'input_collect',   'type' => 'always',            'value' => null, 'priority' => 1],
            ['from' => 'closed_greeting', 'to' => 'input_collect',   'type' => 'always',            'value' => null, 'priority' => 1],
            ['from' => 'input_collect',   'to' => 'message_confirm', 'type' => 'always',            'value' => null, 'priority' => 1],
            ['from' => 'message_confirm', 'to' => 'end',             'type' => 'always',            'value' => null, 'priority' => 1],
        ];

        $this->upsertEdges($flow, $createdNodes, $edges);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @return array<string, FlowNode> Map of node_code => FlowNode */
    private function upsertNodes(ConversationFlow $flow, array $definitions): array
    {
        $result = [];
        foreach ($definitions as $code => $def) {
            $node = FlowNode::updateOrCreate(
                ['flow_id' => $flow->id, 'code' => $code],
                [
                    'type'    => $def['type'],
                    'content' => $def['content'],
                ]
            );
            $result[$code] = $node;
        }

        return $result;
    }

    private function upsertEdges(ConversationFlow $flow, array $nodeMap, array $edges): void
    {
        // Remove existing edges for this flow to allow clean re-seed
        FlowEdge::where('flow_id', $flow->id)->delete();

        foreach ($edges as $e) {
            FlowEdge::create([
                'flow_id'         => $flow->id,
                'from_node_id'    => $nodeMap[$e['from']]->id,
                'to_node_id'      => $nodeMap[$e['to']]->id,
                'condition_type'  => $e['type'],
                'condition_value' => $e['value'],
                'priority'        => $e['priority'],
            ]);
        }
    }
}
