<?php

// Manually load .env file before Laravel boots
$envPath = dirname(__DIR__).'/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove surrounding quotes if present
            if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = substr($value, 1, -1);
            } elseif (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                $value = substr($value, 1, -1);
            }
            // Only set if not already set (safe load)
            if (! isset($_ENV[$key]) && ! isset($_SERVER[$key]) && getenv($key) === false) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

use App\Http\Middleware\EnsureActiveCustomer;
use App\Http\Middleware\EnsureHttps;
use App\Http\Middleware\SecurityHeaders;
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
        $middleware->trustProxies(at: '*');
        $middleware->append([EnsureHttps::class, SecurityHeaders::class]);
        $middleware->web(append: [EnsureActiveCustomer::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Keep register failures readable when the runtime truncates long traces.
        $exceptions->report(function (\Throwable $exception) {
            if (app()->runningInConsole() || ! request()->is('register')) {
                return;
            }

            $cause = $exception;
            for ($depth = 0; $depth < 8 && $cause->getPrevious(); $depth++) {
                $cause = $cause->getPrevious();
            }

            $message = preg_replace('/\\s+/', ' ', $cause->getMessage());
            $message = preg_replace('/(password|passwd|pwd|token|secret)\\s*[=:]\\s*[^\\s;,]+/i', '$1=[redacted]', $message);
            $message = preg_replace('#(://[^:/\\s]+:)[^@\\s]+@#', '$1[redacted]@', $message);

            error_log('REGISTER_FAILURE '.json_encode([
                'exception' => get_class($cause),
                'message' => mb_substr($message, 0, 600),
                'file' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $cause->getFile()),
                'line' => $cause->getLine(),
            ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));

            // The serverless runtime retains only the tail of oversized logs.
            // This failure is already reported above; keep its concise cause.
            return false;
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
