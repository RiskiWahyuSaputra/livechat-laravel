<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\QuickReply;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('Creating dummy data...');

        // ============================================
        // ADMINS (Agents) - Table: admins
        // Columns: id, username, email, password, role, is_superadmin, permissions, status, max_active_chats, remember_token, created_at, updated_at
        // ============================================
        
        // Check if agents already exist
        $existingAgents = Admin::where('role', 'agent')->count();
        
        if ($existingAgents === 0) {
            // Create agents if none exist
            $agents = collect([
                Admin::create([
                    'username' => 'agent1',
                    'email' => 'agent1@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'agent',
                    'is_superadmin' => false,
                    'permissions' => null,
                    'status' => 'online',
                    'max_active_chats' => 5,
                ]),
                Admin::create([
                    'username' => 'agent2',
                    'email' => 'agent2@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'agent',
                    'is_superadmin' => false,
                    'permissions' => null,
                    'status' => 'busy',
                    'max_active_chats' => 4,
                ]),
                Admin::create([
                    'username' => 'agent3',
                    'email' => 'agent3@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'agent',
                    'is_superadmin' => false,
                    'permissions' => null,
                    'status' => 'offline',
                    'max_active_chats' => 6,
                ]),
                Admin::create([
                    'username' => 'agent4',
                    'email' => 'agent4@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'agent',
                    'is_superadmin' => false,
                    'permissions' => null,
                    'status' => 'online',
                    'max_active_chats' => 3,
                ]),
                Admin::create([
                    'username' => 'agent5',
                    'email' => 'agent5@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'agent',
                    'is_superadmin' => false,
                    'permissions' => null,
                    'status' => 'offline',
                    'max_active_chats' => 5,
                ]),
            ]);
            $this->command->info('Created ' . $agents->count() . ' agents');
        } else {
            $agents = Admin::where('role', 'agent')->get();
            $this->command->info('Agents already exist (' . $agents->count() . ' found), using existing data');
        }

        // ============================================
        // CUSTOMERS (Users) - Table: users
        // Columns: id, name, email, email_verified_at, password, is_online, is_blocked, remember_token, created_at, updated_at, contact, origin
        // ============================================
        
        $origins = [
            'Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Semarang', 
            'Makassar', 'Tangerang', 'Bekasi', 'Depok', 'Yogyakarta', 
            'Bali', 'Lampung', 'Kalimantan', 'Sulawesi', 'Papua', 'Unknown'
        ];
        
        $firstNames = [
            'Budi', 'Ani', 'Dedi', 'Siti', 'Joko', 'Rina', 'Ahmad', 'Dewi', 
            'Hendra', 'Lina', 'Wahyu', 'Putri', 'Rizal', 'Yuni', 'Fajar', 
            'Nisa', 'Bayu', 'Sari', 'Doni', 'Mega', 'Tono', 'Maya', 'Ivan', 
            'Grace', 'Dimas', 'Annisa', 'Reza', 'Cantika', 'Feri', 'Sindy'
        ];

        $customers = [];
        for ($i = 1; $i <= 50; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $customer = User::create([
                'name' => $firstName . ' ' . chr(65 + ($i % 26)) . chr(65 + (($i + 5) % 26)),
                'email' => 'customer' . $i . '@example.com',
                'email_verified_at' => Carbon::now()->subDays(rand(1, 30)),
                'password' => Hash::make('password'),
                'is_online' => $i <= 15, // 15 online users
                'is_blocked' => rand(0, 10) > 9, // ~10% blocked
                'contact' => '+6281' . rand(10000000, 99999999),
                'origin' => $origins[array_rand($origins)],
            ]);
            $customers[] = $customer;
        }

        $this->command->info('Created ' . count($customers) . ' customers (users)');

        // ============================================
        // CONVERSATIONS - Table: conversations
        // Columns: id, user_id (FK), admin_id (FK nullable), status, queue_position, problem_category, bot_phase, last_message_at, created_at, updated_at, deleted_at
        // ============================================
        
        $statuses = [
            'closed' => 60,   // 60% closed
            'active' => 20,   // 20% active
            'pending' => 10,   // 10% pending
            'queued' => 10    // 10% queued
        ];
        
        $categories = [
            'General Inquiry', 'Technical Support', 'Billing', 'Sales', 
            'Complaint', 'Feedback', 'Account', 'Product', 'Service', 'Other'
        ];
        
        $botPhases = ['init', 'category_selection', 'contact_info', 'waiting', 'completed', 'cancelled'];

        $conversations = [];
        $conversationCount = 60;

        for ($i = 1; $i <= $conversationCount; $i++) {
            // Determine status based on weighted random
            $rand = rand(1, 100);
            if ($rand <= 60) $status = 'closed';
            elseif ($rand <= 80) $status = 'active';
            elseif ($rand <= 90) $status = 'pending';
            else $status = 'queued';

            // Random date within last 30 days
            $daysAgo = rand(0, 30);
            $hoursAgo = rand(0, 23);
            $minutesAgo = rand(0, 59);
            $createdAt = Carbon::now()->subDays($daysAgo)->subHours($hoursAgo)->subMinutes($minutesAgo);

            // 85% have admin assigned
            $hasAdmin = rand(1, 100) <= 85;
            $admin = $hasAdmin ? $agents->random() : null;

            // For closed conversations, admin must be assigned
            if ($status === 'closed' && !$admin) {
                $admin = $agents->random();
            }

            $conversation = Conversation::create([
                'user_id' => $customers[array_rand($customers)]->id,
                'admin_id' => $admin ? $admin->id : null,
                'status' => $status,
                'queue_position' => $status === 'queued' ? rand(1, 5) : null,
                'problem_category' => $status === 'closed' ? $categories[array_rand($categories)] : null,
                'bot_phase' => $botPhases[array_rand($botPhases)],
                'created_at' => $createdAt,
                'updated_at' => $status === 'closed' 
                    ? $createdAt->copy()->addMinutes(rand(5, 120)) 
                    : $createdAt->copy()->addMinutes(rand(1, 30)),
                'last_message_at' => $createdAt->copy()->addMinutes(rand(1, 60)),
            ]);

            $conversations[] = $conversation;
        }

        $this->command->info('Created ' . count($conversations) . ' conversations');

        // ============================================
        // MESSAGES - Table: messages
        // Columns: id, conversation_id (FK), sender_id, sender_type (user/admin/system), message_type, content, is_read, created_at, updated_at
        // ============================================
        
        $messageId = 1;
        $totalMessages = 0;

        foreach ($conversations as $conversation) {
            // Random number of messages per conversation (2-12)
            $messageCount = rand(2, 12);
            $conversationTime = $conversation->created_at;
            
            // If admin exists, they reply after first user message
            $adminReplied = false;
            
            for ($j = 1; $j <= $messageCount; $j++) {
                // First message always from user
                if ($j === 1) {
                    $senderType = 'user';
                    $senderId = $conversation->user_id;
                } elseif (!$adminReplied && $conversation->admin_id) {
                    // Second message from admin if exists
                    $senderType = 'admin';
                    $senderId = $conversation->admin_id;
                    $adminReplied = true;
                } else {
                    // Alternate between user and admin
                    $senderType = $j % 2 == 0 ? 'admin' : 'user';
                    $senderId = $senderType === 'admin' ? $conversation->admin_id : $conversation->user_id;
                }

                // Skip if no admin for admin messages
                if ($senderType === 'admin' && !$conversation->admin_id) {
                    $senderType = 'user';
                    $senderId = $conversation->user_id;
                }

                $messageTypes = ['text', 'text', 'text', 'text', 'image', 'file'];
                $content = $this->generateMessageContent($senderType, $j, $messageCount);

                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => (int) $senderId,
                    'sender_type' => $senderType,
                    'message_type' => $messageTypes[array_rand($messageTypes)],
                    'content' => $content,
                    'is_read' => $j < $messageCount || $conversation->status === 'closed',
                    'created_at' => $conversationTime,
                    'updated_at' => $conversationTime,
                ]);

                // Add 1-8 minutes between messages
                $conversationTime = $conversationTime->copy()->addMinutes(rand(1, 8));
                $totalMessages++;
            }

            // Update last_message_at
            $conversation->update(['last_message_at' => $conversationTime]);
        }

        $this->command->info('Created ' . $totalMessages . ' messages');

        // ============================================
        // QUICK REPLIES - Table: quick_replies
        // Columns: id, title, content, created_at, updated_at
        // ============================================
        
        $quickReplies = [
            ['title' => 'Greeting', 'content' => 'Hello! Thank you for contacting us. How can I help you today?'],
            ['title' => 'Closing', 'content' => 'Is there anything else I can help you with? If not, thank you for chatting with us!'],
            ['title' => 'Escalation', 'content' => 'I understand your concern. Let me escalate this to our specialist team who can better assist you.'],
            ['title' => 'Wait', 'content' => 'Please wait a moment while I check this for you.'],
            ['title' => 'Follow Up', 'content' => 'We will follow up with you via email within 24 hours.'],
            ['title' => 'Technical Issue', 'content' => "I see you're experiencing a technical issue. Could you provide more details about the error message you're seeing?"],
            ['title' => 'Billing Question', 'content' => 'For billing inquiries, I can help you review your account. Could you confirm your account email?'],
            ['title' => 'Thank You', 'content' => 'Thank you for your patience. We appreciate your understanding.'],
            ['title' => 'Account Help', 'content' => 'I can help you with your account. What specific issue are you facing?'],
            ['title' => 'Product Info', 'content' => 'Our team will provide you with detailed product information shortly.'],
        ];

        foreach ($quickReplies as $reply) {
            QuickReply::create([
                'title' => $reply['title'],
                'content' => $reply['content'],
            ]);
        }

        $this->command->info('Created ' . QuickReply::count() . ' quick replies');

        // ============================================
        // SUMMARY
        // ============================================
        
        $this->command->info('');
        $this->command->info('=== Dummy Data Summary ===');
        $this->command->info('Admins/Agents: ' . Admin::count());
        $this->command->info('  - Super Admin: 1');
        $this->command->info('  - Agents: ' . ($agents->count()));
        $this->command->info('Customers (Users): ' . User::count());
        $this->command->info('  - Online: ' . User::where('is_online', true)->count());
        $this->command->info('  - Blocked: ' . User::where('is_blocked', true)->count());
        $this->command->info('Conversations: ' . Conversation::count());
        $this->command->info('  - Closed: ' . Conversation::where('status', 'closed')->count());
        $this->command->info('  - Active: ' . Conversation::where('status', 'active')->count());
        $this->command->info('  - Pending: ' . Conversation::where('status', 'pending')->count());
        $this->command->info('  - Queued: ' . Conversation::where('status', 'queued')->count());
        $this->command->info('Messages: ' . Message::count());
        $this->command->info('Quick Replies: ' . QuickReply::count());
        $this->command->info('=========================');
        $this->command->info('Dummy data creation completed!');
    }

    private function generateMessageContent($senderType, $index, $total)
    {
        $userMessages = [
            "Hello, I need help with my account",
            "Hi, I have a question about your service",
            "Is anyone there?",
            "I need technical support please",
            "Can you help me?",
            "I want to know more about your products",
            "I have a problem with my order",
            "How do I reset my password?",
            "What are your business hours?",
            "I want to file a complaint about my billing",
            "Thank you for your help!",
            "That's great, thanks a lot!",
            "I'll try that solution",
            "One more question please",
            "Is there a way to upgrade my account?",
            "Can I get a refund?",
            "When will my order be shipped?",
            "I need help with installation",
            "The app is not working properly",
            "I want to cancel my subscription"
        ];

        $adminMessages = [
            "Hello! Welcome to our live chat. How can I assist you today?",
            "Thank you for contacting us. I'll be happy to help you.",
            "I understand your concern. Let me look into this for you.",
            "Sure, I can help you with that right away.",
            "One moment please, I'm checking the details.",
            "Is there anything else I can help you with?",
            "Your issue has been resolved. Please let me know if you need further assistance.",
            "I've made the changes to your account as requested.",
            "Could you please provide more details?",
            "I see. Let me investigate this further for you.",
            "Great! Is there anything else you need help with?",
            "You're welcome! Have a great day!",
            "I've forwarded your request to our technical team.",
            "Please allow me a few minutes to process this.",
            "Your request is being processed. We'll notify you via email."
        ];

        if ($senderType === 'user') {
            return $userMessages[array_rand($userMessages)];
        } elseif ($senderType === 'admin') {
            // First admin message should be greeting
            if ($index === 2 || $index === 1) {
                return "Hello! Welcome. How can I help you today?";
            }
            return $adminMessages[array_rand($adminMessages)];
        } else {
            // System message
            $systemMessages = [
                "Conversation started",
                "Agent joined the conversation",
                "Conversation transferred",
                "Chat closed by agent"
            ];
            return $systemMessages[array_rand($systemMessages)];
        }
    }
}
