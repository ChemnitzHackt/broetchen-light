<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

define('ROOT_DIR', __DIR__.'/..');

// start mit "php -S localhost:8083"

require ROOT_DIR.'/vendor/autoload.php';

$app = new \Slim\App;
include ROOT_DIR.'/src/cors.php';
include ROOT_DIR.'/src/routes.php';

$app->run();