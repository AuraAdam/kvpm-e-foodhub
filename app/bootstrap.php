<?php
/**
 * Loads configuration, the autoloader, helpers, the database and the session.
 * Used by public/index.php and by the command-line scripts in database/.
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// Autoload classes in the App\ namespace from the app/ folder (PSR-4 style).
spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Helpers/functions.php';

App\Core\Env::load(BASE_PATH . '/.env');
$GLOBALS['__config'] = require BASE_PATH . '/config/config.php';

date_default_timezone_set(config('app.timezone'));
error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');

App\Core\Database::connect(config('db'));
