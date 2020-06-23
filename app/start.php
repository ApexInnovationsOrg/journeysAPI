<?php namespace DB;

// require_once '../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;  
use Dotenv\Dotenv as Dotenv;
use app\Utils\RedisSessionHandler;

$whoops = new \Whoops\Run;


if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
	/* special ajax here */
    $whoops->prependHandler(new \Whoops\Handler\JsonResponseHandler);
}
else
{
    $whoops->prependHandler(new \Whoops\Handler\PrettyPageHandler);
}
$whoops->register();


if(!isset($_COOKIE['ApexInnovations']) && isset($_COOKIE['ApexAdmin']))
{
	$session = new RedisSessionHandler(['gc_maxlifetime'=>10080*60]);
	$session->register();
	session_name('ApexAdmin');
	session_start();
}
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
 
$capsule = new Capsule; 
 


$capsule->addConnection(array(
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST'],
    'database'  => $_ENV['DB_DATABASE'],
    'username'  => $_ENV['DB_USERNAME'],
    'password'  => $_ENV['DB_PASSWORD'],
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => ''
));

$capsule->setAsGlobal();
$capsule->bootEloquent();
