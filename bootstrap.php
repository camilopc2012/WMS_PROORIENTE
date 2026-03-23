<?php
/**
 * Bootstrap - Initializes Eloquent ORM and loads configuration
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Load config
$dbConfig = require __DIR__ . '/config/database.php';
$appConfig = require __DIR__ . '/config/app.php';

// Boot Eloquent
$capsule = new Capsule;
$capsule->addConnection($dbConfig);
$capsule->setEventDispatcher(new Dispatcher(new Container));
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Set default timezone
date_default_timezone_set('America/Bogota');
