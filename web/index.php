<?php
require_once __DIR__.'/../vendor/autoload.php';

const DB_HOST     = 'localhost';
const DB_DATABASE = 'labyrinthe';
const DB_USERNAME = 'root';
const DB_PASSWORD = '';
                        
use Silex\Application;
use Silex\Provider\TwigServiceProvider as TwigSP;
$app = new Application();

//mode debug activer
$app['debug'] = true;

$app->register(new Silex\Provider\ServiceControllerServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());

//test flash message
$app['session']->getFlashBag()->add('message', 'Super cool');

$app['database.config'] = [
    'dsn'      => 'mysql:host=' . DB_HOST . ';dbname=' . DB_DATABASE,
    'username' => DB_USERNAME,
    'password' => '',
    'options'  => [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8", // flux en utf8
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // mysql erreurs remontées sous forme d'exception
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, // tous les fetch en objets
    ]
];

$app['front.controller'] = function () use ($app) {
    return new \Controllers\FrontController($app);
};

$app->get('/', "front.controller:index") ;
$app->get('/labyrinthe', "front.controller:generate") ;
//redirection du form vers la methode du front.controleler
$app->post('create', "front.controller:create") ;

$app['pdo'] = function( $app ){    
  	
  	$options = $app['database.config'];
  
  	return new \PDO($options['dsn'], $options['username'], $options['password'], $options['options']);
};  
$app->register(new TwigSP(), [
    'twig.path' => __DIR__ . '/../views',
]);

//G2RER les forms
//$app->match('parameter', function() use ($app) {}) ;


$app->run();