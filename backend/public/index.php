<?php

use AmoCRM\Client\AmoCRMApiClient;
use App\Services\AmoCRMService;
use DI\Container;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__.'/../vendor/autoload.php';
date_default_timezone_set('europe/moscow');

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$clientId = $_ENV['CLIENT_ID'];
$clientSecret = $_ENV['CLIENT_SECRET'];
$redirectUri = $_ENV['CLIENT_REDIRECT_URI'];

$container->set('amoService', function () {
    $clientId = $_ENV['CLIENT_ID'];
    $clientSecret = $_ENV['CLIENT_SECRET'];
    $redirectUri = $_ENV['CLIENT_REDIRECT_URI'];
    $client = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);

    return new AmoCRMService($client);
});

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("index page");
    return $response;
});

$app->post('/webhook', function (Request $request, Response $response, $args) {
    $amoService = null;
    //проверка на существование сервиса и получение его
    if ($this->has('amoService')) {
        $amoService = $this->get('amoService');
    }
    if ($request->getParsedBody() != null && $amoService instanceof AmoCRMService) {
        $pathToFile = dirname(__DIR__)."/test.txt";
        $json = (array)$request->getParsedBody();
        // проверки на тип события вебхука
        if (array_key_exists("leads", $json)) {
            $leads = $json["leads"];
            $method = array_keys($leads)[0];
            if($method === "add"){// при добавлении сделки
                $data = $leads[$method][0];
                $amoService->createNoteAfterAddLead($data);
            }elseif ($method === "update"){// при изменении сделки
                $data = $leads[$method][0];
                $amoService->createNoteAfterUpdateLead($data);
            }
        }
        if (array_key_exists("contacts", $json)) {
            $contact = $json["contacts"];
            $method = array_keys($contact)[0];
            if($method === "add"){//при добавлении карточки
                $data = $contact[$method][0];
                $amoService->createNoteAfterAddContact($data);
            }elseif ($method === "update"){// при изменении карточки
                $data = $contact[$method][0];
                $amoService->createNoteAfterUpdateContact($data);
                file_put_contents($pathToFile, json_encode($data));
            }
        }

    }

    return $response;
});

$app->run();