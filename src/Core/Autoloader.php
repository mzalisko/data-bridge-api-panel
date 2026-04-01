<?php

declare(strict_types=1);

namespace App\Core;

/**
 * PSR-4 style autoloader.
 *
 * Namespace prefix : App\
 * Base directory   : <ROOT>/src/
 *
 * Usage:
 *   $autoloader = new \App\Core\Autoloader();
 *   $autoloader->register();
 */
class Autoloader
{
    private string $prefix = 'App\\';
    private string $baseDir;

    public function __construct()
    {
        $this->baseDir = defined('SRC') ? SRC . '/' : dirname(__DIR__) . '/';
    }

    /**
     * Register this autoloader with the SPL autoload stack.
     */
    public function register(): void
    {
        spl_autoload_register([$this, 'load']);
    }

    /**
     * Attempt to load the class file for a given fully-qualified class name.
     */
    public function load(string $class): void
    {
        // Verify the namespace prefix matches
        $len = strlen($this->prefix);
        if (strncmp($this->prefix, $class, $len) !== 0) {
            return;
        }

        // Strip prefix and map remainder to a file path
        $relativeClass = substr($class, $len);
        $file = $this->baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_file($file)) {
            require $file;
        }
    }
}
