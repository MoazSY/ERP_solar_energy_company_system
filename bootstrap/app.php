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
    ->withMiddleware(function (Middleware $middleware): void {
      $middleware->alias(['check_auth' => App\Http\Middleware\Check_auth::class,
        'check_admin' => App\Http\Middleware\Check_admin::class,
        'check_employee' => App\Http\Middleware\Check_employee::class,
        'check_customer' => App\Http\Middleware\Check_customer::class,
        'check_Agency_manager' => App\Http\Middleware\Check_Agency_manager::class,
        'check_company_manager' => App\Http\Middleware\Check_company_manager::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
