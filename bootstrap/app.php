<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth:web,admin']]
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        
        $middleware->validateCsrfTokens(except: [
            'api/webhook/whatsapp',
            'chat/send',
            'chat/register',
            'chat/typing'
        ]);

        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthenticated::class,
            'admin.permission' => \App\Http\Middleware\CheckAdminPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('chat:check-inactivity')->everyMinute();
    })->create();
