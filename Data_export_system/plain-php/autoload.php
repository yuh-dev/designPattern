<?php
// Autoloader PSR-4 đơn giản: App\Export\X\Y  ->  src/X/Y.php
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\Export\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
