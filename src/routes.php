<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\User;
use App\Order;
use App\Mailer as OrderMailer;
use App\Log;
use App\Config;

$app->get('/', function (Request $request, Response $response) {
    $content = file_get_contents('frontend.html');
    $content = str_replace('#project_name#', Config::get('project_name'), $content);
    $response->getBody()->write($content);
    return $response;
});

$app->group('/api', function () use ($app) {

    // curl -i -H "Accept: application/json" -H "Content-Type: application/json" -X POST -d "{\"email_address\":\"bla@foo.bar\", \"password\":\"xxx\"}" http://localhost:3000/api/login
    $app->post('/login', function (Request $request, Response $response) {
        $input = file_get_contents('php://input');
        $params = json_decode($input, true);
        //print json_last_error_msg();
        //print_r($input); print "\n"; print_r($params); die();
        if (empty($params['email_address']) || empty($params['password'])) {
            return $response->withJson(['error' => 'Email or Pwd empty'], 400);
        }
        $user = new User;
        $myuser = $user->isValid($params['email_address'], $params['password']);
        if ($myuser) {
            Log::login($params['email_address'], true);
            return $response->withJson(['sessionid' => md5($myuser['email'])]);
        } else {
            Log::login($params['email_address'], false);
            return $response->withJson(['error' => 'User not found'], 401);
        }
    });

    // curl http://localhost:3000/api/services
    $app->get('/services', function (Request $request, Response $response) {
        $file = ROOT_DIR.'/db/services.json';
        if (!file_exists($file)) {
            copy($file.'.dist', $file);
        }
        $services = json_decode(file_get_contents(ROOT_DIR.'/db/services.json'), true);
        return $response->withJson($services);
    });

    // curl http://localhost:3000/api/orders/4406a33260d8956e2d95fae136a5ea74
    $app->get('/orders/{sessionid}', function (Request $request, Response $response, $params) {
        if (empty($params['sessionid'])) {
            return $response->withJson(['error' => 'Session needed'], 400);
        }
        $user = new User;
        $myuser = $user->getBySession($params['sessionid']);
        if (!$myuser) {
            return $response->withJson(['error' => 'User not found'], 401);
        }

        $json = ROOT_DIR.'/db/orders-'.$myuser['email'].'.json';
        if (!file_exists($json)) {
            return $response->withJson([]);
        }
        $orders = json_decode(file_get_contents($json), true);
        return $response->withJson($orders);

    });

    // curl -i -H "Accept: application/json" -H "Content-Type: application/json" -X POST -d "{\"sessionid\":\"4406a33260d8956e2d95fae136a5ea74\", \"orders\": {\"broetchen\":{\"schrippe\":3,\"vollkornbrot\":1}}}" http://localhost:3000/api/orders
    $app->post('/orders', function (Request $request, Response $response) {
        $params = json_decode(file_get_contents('php://input'), true);
        if (empty($params['sessionid'])) {
            return $response->withJson(['error' => 'Session needed'], 400);
        }
        if (empty($params['orders'])) {
            return $response->withJson(['error' => 'Orders needed'], 400);
        }
        $user = new User;
        $myuser = $user->getBySession($params['sessionid']);
        if (!$myuser) {
            return $response->withJson(['error' => 'User not found'], 401);
        }
        $order = new Order;
        $order->saveOrder($myuser['email'], $params['orders']);

        $mailer = new OrderMailer;
        $mailer->sendOrder($myuser['email'], $params['orders']);
    });
});
