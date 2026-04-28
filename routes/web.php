<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth; // Ensure Auth facade is used
use Illuminate\Support\Str;
use App\Models\User;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\UserDashboardController; // Changed from UserHomeController

// Halaman utama → redirect ke user home
Route::get('/', function () {
    return redirect()->route('user.home');
});

// Home page dapat diakses publik
Route::get('/home', [UserDashboardController::class , 'index'])->name('user.home');
Route::get('/about', [UserDashboardController::class, 'about'])->name('user.about');
Route::get('/contact', [UserDashboardController::class, 'contact'])->name('user.contact');

// Route registrasi chat
Route::post('/chat/register', [ChatController::class , 'register'])->name('chat.register');
Route::get('/chat/register/whatsapp/{token}', [ChatController::class, 'showWhatsappRegister'])->name('chat.register.whatsapp');
Route::post('/chat/register/whatsapp', [ChatController::class, 'submitWhatsappRegister'])->name('chat.register.whatsapp.submit');
Route::post('/chat/register-anonymous', [ChatController::class , 'registerAnonymous'])->name('chat.registerAnonymous');
Route::post('/chat/update-profile', [ChatController::class , 'updateProfile'])->name('chat.updateProfile');
Route::match(['get', 'post'], '/chat/logout', [ChatController::class , 'logout'])->name('chat.logout');

// Routes Chat
Route::get('/chat', [ChatController::class, 'showChat'])->name('chat.index');
Route::get('/chat-widget', [ChatController::class, 'showWidget'])->name('chat.widget');
Route::get('/chat/init', [ChatController::class , 'initChat'])->name('chat.init');
Route::post('/chat/send', [ChatController::class , 'sendMessage'])->name('chat.send');
Route::patch('/chat/message/{message}', [ChatController::class, 'updateMessage'])->name('chat.message.update');
Route::delete('/chat/message/{message}', [ChatController::class, 'deleteMessage'])->name('chat.message.destroy');
Route::post('/chat/typing', [ChatController::class , 'typing'])->name('chat.typing');

// Routes yang butuh login user (jika ada fitur user biasa)
Route::middleware(['auth'])->group(function () {
    //
});

// Routes Admin
Route::prefix('admin')->name('admin.')->group(function () {

    // Auth Admin (hanya saat belum login sebagai admin)
    Route::middleware('guest:admin')->group(function () {
            Route::get('/login', [AdminAuthController::class , 'showLogin'])->name('login');
            Route::post('/login', [AdminAuthController::class , 'login']);
        }
        );

        // Routes yang butuh login admin
        Route::middleware('admin.auth')->group(function () {
            Route::post('/logout', [AdminAuthController::class , 'logout'])->name('logout');

            Route::get('/dashboard', [DashboardController::class , 'index'])->name('dashboard');
            Route::get('/test-cookie', [DashboardController::class, 'checkCookie'])->name('test-cookie');
            Route::delete('/user/{user}', [DashboardController::class, 'destroyUser'])->name('user.destroy');
            Route::post('/status', [DashboardController::class , 'updateStatus'])->name('status.update');
            // --- Menu 2: Live Chat Workspace ---
            Route::middleware('admin.permission:view_chat')->group(function () {
                Route::get('/chat', [DashboardController::class, 'chatWorkspace'])->name('chat');
                Route::get('/conversation/{conversation}', [DashboardController::class , 'showConversation'])->name('conversation.show');
                Route::post('/chat/send', [DashboardController::class , 'sendMessage'])->name('chat.send');
                Route::patch('/chat/message/{message}', [DashboardController::class, 'updateMessage'])->name('chat.message.update');
                Route::delete('/chat/message/{message}', [DashboardController::class, 'deleteMessage'])->name('chat.message.destroy');
                Route::post('/chat/typing', [DashboardController::class , 'typing'])->name('chat.typing');
                Route::post('/conversation/{conversation}/claim', [DashboardController::class , 'claimConversation'])->name('conversation.claim');
                Route::post('/conversation/{conversation}/handover', [DashboardController::class , 'handoverConversation'])->name('conversation.handover');
                Route::post('/conversation/{conversation}/escalate', [DashboardController::class , 'escalateConversation'])->name('conversation.escalate');
                Route::post('/conversation/{conversation}/close', [DashboardController::class , 'closeConversation'])->name('conversation.close');
                Route::post('/conversation/{conversation}/block', [DashboardController::class , 'blockUser'])->name('conversation.block');
            });

            // --- Menu: Agent Chat ---
            Route::middleware('admin.permission:view_chat')->prefix('agent-chat')->name('agent-chat.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\AgentChatController::class, 'index'])->name('index');
                Route::post('/start', [App\Http\Controllers\Admin\AgentChatController::class, 'startConversation'])->name('start');
                Route::get('/conversation/{id}', [App\Http\Controllers\Admin\AgentChatController::class, 'showConversation'])->name('show');
                Route::post('/send', [App\Http\Controllers\Admin\AgentChatController::class, 'sendMessage'])->name('send');
            });

            // --- Menu 3: Chat History / Archive ---
            Route::middleware('admin.permission:view_history')->group(function () {
                Route::get('/history', [App\Http\Controllers\Admin\ChatHistoryController::class, 'index'])->name('history.index');
                Route::get('/history/{id}', [App\Http\Controllers\Admin\ChatHistoryController::class, 'show'])->name('history.show');
            });

            // --- Menu 4: Quick Replies Management ---
            Route::middleware('admin.permission:manage_quick_replies')->group(function () {
                Route::resource('/quick-replies', App\Http\Controllers\Admin\QuickReplyController::class)->except(['show', 'create', 'edit']);
            });

            // --- Menu 5: Customer Management ---
            Route::middleware('admin.permission:manage_customers')->group(function () {
                Route::resource('/customers', App\Http\Controllers\Admin\CustomerController::class)->only(['index', 'update', 'destroy']);
            });

            // --- Menu 6: Admins Management ---
            Route::middleware('admin.permission:manage_roles')->group(function () {
                Route::resource('/admins', App\Http\Controllers\Admin\RoleController::class)->names([
                    'index' => 'admins.index',
                    'store' => 'admins.store',
                    'update' => 'admins.update',
                    'destroy' => 'admins.destroy',
                ]);

                // --- Menu 8: Role CRUD ---
Route::resource('/roles', \App\Http\Controllers\RoleController::class)->names([
                    'index' => 'roles.index',
                    'create' => 'roles.create',
                    'store' => 'roles.store',
                    'edit' => 'roles.edit',
                    'update' => 'roles.update',
                    'destroy' => 'roles.destroy',
                ]);
            });

            // --- Map Data API ---
            Route::get('/map/data', [App\Http\Controllers\Admin\MapController::class, 'getMapData'])->name('map.data');

            // --- Menu 7: Analytics & Analysis ---
            Route::get('/analytics', [App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');
            Route::get('/analytics/filter', [App\Http\Controllers\Admin\AnalyticsController::class, 'filter'])->name('analytics.filter');
            Route::get('/analytics/realtime', [App\Http\Controllers\Admin\AnalyticsController::class, 'realtime'])->name('analytics.realtime');
            Route::get('/analytics/export', [App\Http\Controllers\Admin\AnalyticsController::class, 'export'])->name('analytics.export');

            // --- Menu: Reports / Laporan ---
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\ReportController::class, 'index'])->name('index');
                Route::get('/export/excel', [App\Http\Controllers\Admin\ReportController::class, 'exportExcel'])->name('export.excel');
                Route::get('/export/pdf', [App\Http\Controllers\Admin\ReportController::class, 'exportPdf'])->name('export.pdf');
                Route::get('/api-data', [App\Http\Controllers\Admin\ReportController::class, 'apiData'])->name('api-data');
            });

            // --- Menu 9: Settings ---
            Route::get('/settings', [App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
            Route::put('/settings', [App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
            Route::post('/settings/cleanup', [App\Http\Controllers\Admin\SettingController::class, 'runCleanup'])->name('settings.cleanup');

            // --- Menu 10: Bot Menus Management ---
            Route::post('/bot-menus/greeting', [App\Http\Controllers\Admin\BotMenuController::class, 'updateGreeting'])->name('bot-menus.greeting');
            Route::resource('/bot-menus', App\Http\Controllers\Admin\BotMenuController::class)->except(['show', 'create', 'edit']);
        }
        );
    });