<?php
/**
 * Router script for PHP built-in development server.
 * Serves static assets directly; routes everything else through index.php
 * so CodeIgniter's routing works as expected.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files directly if they exist
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    // Don't serve directory indexes
    if (is_dir(__DIR__ . $uri)) {
        // Redirect to index.php for directories
        return false;
    }
    return false; // Let the built-in server handle the file
}

// Everything else goes to index.php (CodeIgniter front controller)
return false;
