<?php
/**
 * Minimal PSR-4 autoloader for PHPMailer.
 *
 * Lets the project run without Composer. If you run `composer install`,
 * Composer replaces this file with its own autoloader.
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'PHPMailer\\PHPMailer\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/phpmailer/phpmailer/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
