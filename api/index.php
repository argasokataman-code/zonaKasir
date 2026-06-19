<?php

declare(strict_types=1);

/**
 * Vercel Laravel Entry Point
 *
 * Bridges Vercel serverless PHP runtime to Laravel's public/index.php.
 * Sets up environment, handles subdirectory, and passes request to Laravel.
 */

// Resolve the project root (one level up from api/)
$projectRoot = dirname(__DIR__);

// Change working directory to Laravel public folder
chdir($projectRoot . '/public');

// Load Laravel's public entry point
require $projectRoot . '/public/index.php';
