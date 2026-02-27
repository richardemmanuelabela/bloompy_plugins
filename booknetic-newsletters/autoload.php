<?php

// Custom autoloader for Booknetic Newsletters Addon
spl_autoload_register(function ($class) {
    // Only handle classes in our namespace
    if (strpos($class, 'BookneticAddon\\Newsletters\\') !== 0) {
        return;
    }
    
    // Remove the namespace prefix
    $relativeClass = substr($class, strlen('BookneticAddon\\Newsletters\\'));
    
    // Convert namespace separators to directory separators
    $file = __DIR__ . '/App/' . str_replace('\\', '/', $relativeClass) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
}); 