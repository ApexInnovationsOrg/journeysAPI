<?php namespace DB;

// require_once '../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;  
use Dotenv\Dotenv as Dotenv;


$dotenv = new Dotenv($_SERVER['DOCUMENT_ROOT'] . '/JourneyAPI/');
$dotenv->load();
 
$capsule = new Capsule; 
 

$capsule->addConnection(array(
    'driver'    => 'mysql',
    'host'      => getenv('DB_HOST'),
    'database'  => getenv('DB_DATABASE'),
    'username'  => getenv('DB_USERNAME'),
    'password'  => getenv('DB_PASSWORD'),
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => ''
));

$capsule->setAsGlobal();
$capsule->bootEloquent();
