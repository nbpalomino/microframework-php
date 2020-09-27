<?php

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Pimple\Container as PimpleContainer;
use Pimple\Psr11\Container;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

use Core42\Services\Provider as Core42ServiceProvider;

/*
 * Request instance (use this instead of $_GET, $_POST, etc).
 */
$request = Request::createFromGlobals();

/*
 * Dotenv initialization
 */
if (file_exists(__DIR__ . '/.env') !== true) {
    Response::create('Missing .env file (please copy .env.example).', Response::HTTP_INTERNAL_SERVER_ERROR)
        ->prepare($request)
        ->send();
    return;
}
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/*
 * Error handler
 */
$whoops = new Run;

if (getenv('MODE') === 'dev') {
    $whoops->pushHandler(
        new PrettyPageHandler()
    );
} else {
    $whoops->pushHandler(
        // Using the pretty error handler in production is likely a bad idea.
        // Instead respond with a generic error message.
        function () use ($request) {
            Response::create('An internal server error has occurred.', Response::HTTP_INTERNAL_SERVER_ERROR)
                ->prepare($request)
                ->send();
        }
    );
}
$whoops->register();

/**
 * Container setup
 */
$pimple = new PimpleContainer();

/**
 * Settings DB connection
 */
$pimple['db_conn'] = getenv('DB_CONN');
$pimple['db_user'] = getenv('DB_USER');
$pimple['db_pass'] = getenv('DB_PASS');
$pimple['pdo'] = function($c) {
    return new PDO($c['db_conn'], $c['db_user'], $c['db_pass']);
};

/*
 * Routes
 */
$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) use ($pimple) {
    $routes = require __DIR__ . '/routes.php';
    foreach ($routes as $route) {
        $r->addRoute($route[0], $route[1], $route[2]);
        // Register controllers in container
        $fqcn = $route[2][0];
        $pimple[$fqcn] = function($c) use ($fqcn) {
            return new $fqcn($c);
        };
    }
});

/**
 * View handler
 */
if(!function_exists('views')) {
    function views($view, array $params) {
        $path = __DIR__ . '/views/'.$view;
        if (!file_exists($path)) {
            return Response::create("View {$path} not found.", Response::HTTP_INTERNAL_SERVER_ERROR)
                ->send();
        }

        $v = require $path;
        return new Response($v);
    }
}


/**
 * Providers
 */
$pimple->register(new Core42ServiceProvider);

/*
 * Dispatch
 */
$routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());
switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        // No matching route was found.
        Response::create("404 Not Found", Response::HTTP_NOT_FOUND)
            ->prepare($request)
            ->send();
        break;
    case Dispatcher::METHOD_NOT_ALLOWED:
        // A matching route was found, but the wrong HTTP method was used.
        Response::create("405 Method Not Allowed", Response::HTTP_METHOD_NOT_ALLOWED)
            ->prepare($request)
            ->send();
        break;
    case Dispatcher::FOUND:
        // Fully qualified class name of the controller
        $fqcn = $routeInfo[1][0];
        // Controller method responsible for handling the request
        $routeMethod = $routeInfo[1][1];
        // Route parameters (ex. /products/{category}/{id})
        $routeParams = $routeInfo[2];

        // Obtain an instance of route's controller
        // Resolves constructor dependencies using the container
        $controller = $pimple[$fqcn];

        // Generate a response by invoking the appropriate route method in the controller
        $response = $controller->$routeMethod($request, $routeParams);
        if ($response instanceof Response) {
            // Send the generated response back to the user
            $response
                ->prepare($request)
                ->send();
        }
        break;
    default:
        // According to the dispatch(..) method's documentation this shouldn't happen.
        // But it's here anyways just to cover all of our bases.
        Response::create('Received unexpected response from dispatcher.', Response::HTTP_INTERNAL_SERVER_ERROR)
            ->prepare($request)
            ->send();
        return;
}