<?php

declare(strict_types=1);

namespace App\ErrorHandler;

class CompatibilityErrorHandler
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        // Set a custom error handler for PHP 8.4 compatibility
        if (PHP_VERSION_ID >= 80400) {
            set_error_handler([self::class, 'handleError'], E_ALL);
        }
    }

    public static function handleError(int $severity, string $message, string $file = '', int $line = 0): bool
    {
        // Suppress E_STRICT and E_DEPRECATED warnings from Symfony 6.1
        if ($severity === E_STRICT || $severity === E_DEPRECATED) {
            // Check if it's a Symfony-related deprecation we want to suppress
            if (
                str_contains($file, 'symfony') &&
                (str_contains($message, 'E_STRICT') || str_contains($message, 'assert.warning'))
            ) {
                return true; // Suppress the error
            }
        }

        // For other errors, let PHP handle them normally
        return false;
    }

    public static function filterOutput(string $buffer): string
    {
        // Remove specific deprecation warnings from output
        $patterns = [
            '/Deprecated: Constant E_STRICT is deprecated in.*?ErrorHandler\.php.*?\n/',
            '/PHP Deprecated: Constant E_STRICT is deprecated in.*?ErrorHandler\.php.*?\n/',
            '/<br \/>.*?Deprecated.*?Constant E_STRICT.*?<br \/>/',
            '/<b>Deprecated<\/b>:.*?Constant E_STRICT.*?<br \/>/',
        ];

        foreach ($patterns as $pattern) {
            $buffer = preg_replace($pattern, '', $buffer);
        }

        return $buffer;
    }
}