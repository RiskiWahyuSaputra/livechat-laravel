<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\UserHomeController; // Added

// Halaman utama → redirect ke login user
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('user.home'); // Redirect to user home if authenticated
    }
    return redirect()->route('user.login');
});

// Auth User (hanya saat belum login)
Route::middleware('guest')->group(function () {
    Route::get('/login',     [UserAuthController::class, 'showLogin'])->name('user.login');
    Route::get('/register',  [UserAuthController::class, 'showRegister'])->name('user.register');
    Route::post('/login',    [UserAuthController::class, 'login']);
    Route::post('/register', [UserAuthController::class, 'register']);
});

// Routes yang butuh login user
Route::middleware('auth')->group(function () {
    Route::get('/home',             [UserHomeController::class, 'index'])->name('user.home'); // Added
    Route::post('/logout',          [UserAuthController::class, 'logout'])->name('user.logout');
    Route::get('/chat',             [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/send',       [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('/chat/typing',     [ChatController::class, 'typing'])->name('chat.typing');
});

// Routes Admin
Route::prefix('admin')->name('admin.')->group(function () {

    // Auth Admin (hanya saat belum login sebagai admin)
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login',  [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login']);
    });

    // Routes yang butuh login admin
    Route::middleware('admin.auth')->group(function () {
        Route::post('/logout',   [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/dashboard',                              [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/conversation/{conversation}',            [DashboardController::class, 'showConversation'])->name('conversation.show');
        Route::post('/chat/send',                             [DashboardController::class, 'sendMessage'])->name('chat.send');
        Route::post('/chat/typing',                           [DashboardController::class, 'typing'])->name('chat.typing');
        Route::post('/conversation/{conversation}/claim',     [DashboardController::class, 'claimConversation'])->name('conversation.claim');
        Route::post('/conversation/{conversation}/handover',  [DashboardController::class, 'handoverConversation'])->name('conversation.handover');
        Route::post('/conversation/{conversation}/close',     [DashboardController::class, 'closeConversation'])->name('conversation.close');
        Route::post('/conversation/{conversation}/block',     [DashboardController::class, 'blockUser'])->name('conversation.block');
        Route::post('/status',                                [DashboardController::class, 'updateStatus'])->name('status.update');
    });
});
