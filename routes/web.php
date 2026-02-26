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

// Route registrasi chat
Route::post('/chat/register', [ChatController::class , 'register'])->name('chat.register');

// Routes yang butuh login user (untuk menggunakan websocket chat)
Route::middleware(['auth'])->group(function () {
    Route::get('/chat', function () {
            return redirect()->route('user.home');
        }
        )->name('chat.index'); // Redirect to home instead of full page
        Route::get('/chat/init', [ChatController::class , 'initChat'])->name('chat.init');
        Route::post('/chat/send', [ChatController::class , 'sendMessage'])->name('chat.send');
        Route::post('/chat/typing', [ChatController::class , 'typing'])->name('chat.typing');    });

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
            Route::get('/conversation/{conversation}', [DashboardController::class , 'showConversation'])->name('conversation.show');
            Route::post('/chat/send', [DashboardController::class , 'sendMessage'])->name('chat.send');
            Route::post('/chat/typing', [DashboardController::class , 'typing'])->name('chat.typing');
            Route::post('/conversation/{conversation}/claim', [DashboardController::class , 'claimConversation'])->name('conversation.claim');
            Route::post('/conversation/{conversation}/handover', [DashboardController::class , 'handoverConversation'])->name('conversation.handover');
            Route::post('/conversation/{conversation}/close', [DashboardController::class , 'closeConversation'])->name('conversation.close');
            Route::post('/conversation/{conversation}/block', [DashboardController::class , 'blockUser'])->name('conversation.block');
            Route::post('/status', [DashboardController::class , 'updateStatus'])->name('status.update');
        }
        );
    });
