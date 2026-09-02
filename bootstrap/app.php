<?php

use App\Exceptions\WeatherProviderException;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (WeatherProviderException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'code' => $exception->errorCode,
                'message' => match ($exception->errorCode) {
                    WeatherProviderException::NOT_FOUND => 'Cidade não encontrada.',
                    WeatherProviderException::RATE_LIMITED => 'O serviço de clima está ocupado. Tente novamente em instantes.',
                    WeatherProviderException::TIMEOUT => 'A consulta do clima demorou mais que o esperado.',
                    WeatherProviderException::NETWORK_ERROR => 'Não foi possível conectar ao serviço de clima.',
                    default => 'Não foi possível atualizar o clima agora.',
                },
            ], match ($exception->errorCode) {
                WeatherProviderException::NOT_FOUND => 404,
                WeatherProviderException::RATE_LIMITED => 429,
                WeatherProviderException::TIMEOUT => 504,
                default => 503,
            });
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
