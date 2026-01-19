<?php
// Custom autoloader for Bloompy WooCommerce Bridge Addon
spl_autoload_register(function ($class) {
    if (strpos($class, 'BloompyAddon\\WooCommerceBridge\\') !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen('BloompyAddon\\WooCommerceBridge\\'));
    $file = __DIR__ . '/App/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}); 