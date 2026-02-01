<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use Hexlet\Code\Url;
use Hexlet\Code\UrlCheck;
use Hexlet\Code\UrlChecksRepository;
use Hexlet\Code\UrlsRepository;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;
use Symfony\Component\DomCrawler\Crawler;

session_start();

$container = new Container();
$container->set('renderer', fn() => new PhpRenderer(__DIR__ . '/../templates'));
$renderer = $container->get('renderer');
$renderer->setLayout('layout.phtml');

$container->set('flash', fn() => new Messages());

$container->set(\PDO::class, function () {
    $databaseUrl = parse_url(getenv('DATABASE_URL') ?: '');

    if ($databaseUrl === false) {
        throw new RuntimeException('Failed to parse DATABASE_URL or variable not set');
    }
    $username = $databaseUrl['user'] ?? '';
    $password = $databaseUrl['pass'] ?? '';
    $host = $databaseUrl['host'] ?? 'localhost';
    $dbName = isset($databaseUrl['path']) ? ltrim($databaseUrl['path'], '/') : '';

    $conn = new \PDO("pgsql:dbname=$dbName;host=$host", $username, $password);
    $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    return $conn;
});

$app = AppFactory::createFromContainer($container);

$router = $app->getRouteCollector()->getRouteParser();
$container->get('renderer')->addAttribute('router', $router);
$container->get('renderer')->addAttribute('flash', $container->get('flash')->getMessages());

$customErrorHandler = function ($request, $exception) use ($app) {
    $response = $app->getResponseFactory()->createResponse();

    if ($exception instanceof HttpNotFoundException) {
        return $this->get('renderer')->render($response->withStatus(404), 'errors/404.phtml');
    }

    return $this->get('renderer')->render($response->withStatus(500), 'errors/500.phtml');
};

$errorMiddleware = $app->addErrorMiddleware(
    displayErrorDetails: true,
    logErrors: true,
    logErrorDetails: true
);
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('home');

$app->post('/urls', function ($request, $response) use ($router) {
    $formData = (array)$request->getParsedBody();
    $validator = new Valitron\Validator($formData);

    $validator->rule('required', ['url.name'])->message('URL не должен быть пустым');
    $validator->rule('lengthMax', ['url.name'], 255)->message('URL превышает 255 символов');
    $validator->rule('url', ['url.name'])->message('Некорректный URL');
    $validator->labels(['url.name' => 'Url']);


    if (!$validator->validate()) {
        return $this->get('renderer')->render($response->withStatus(422), 'index.phtml', [
            'old' => $formData,
            'errors' => $validator->errors(),
        ]);
    }

    $parseUrl = parse_url($formData['url']['name']);
    $normalizedUrl = mb_strtolower("{$parseUrl['scheme']}://{$parseUrl['host']}");

    $urlRepository = $this->get(UrlsRepository::class);
    $url = $urlRepository->findByName($normalizedUrl);

    if (isset($url)) {
        $this->get('flash')->addMessage('success', 'Страница уже существует');
    } else {
        $url = new Url($normalizedUrl);
        $urlRepository->save($url);
        $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
    }
    return $response->withRedirect($router->urlFor('urls.show', ['id' => $url->getId()]), 302);
})->setName('urls.create');

$app->get('/urls', function ($request, $response) {
    $urlRepository = $this->get(UrlsRepository::class);
    $urls = $urlRepository->all();

    $checksRepository = $this->get(UrlChecksRepository::class);
    $latestChecks = $checksRepository->findLatestChecks();

    return $this->get('renderer')->render($response, 'urls/index.phtml', [
        'urls' => $urls,
        'checks' => $latestChecks
    ]);
})->setName('urls.index');

$app->get('/urls/{id:[0-9]+}', function ($request, $response, array $args) {
    $id = $args['id'];
    $urlRepository = $this->get(UrlsRepository::class);
    $url = $urlRepository->find($id);

    if (!$url) {
        throw new HttpNotFoundException($request);
    }

    $checksRepository = $this->get(UrlChecksRepository::class);
    $checks = $checksRepository->findByUrlId($id);

    return $this->get('renderer')->render($response, 'urls/show.phtml', [
        'url' => $url,
        'checks' => $checks
    ]);
})->setName('urls.show');

$app->post('/urls/{url_id:[0-9]+}/checks', function ($request, $response, array $args) use ($router) {
    $id = $args['url_id'];
    $urlRepository = $this->get(UrlsRepository::class);
    $url = $urlRepository->find($id);

    if (!$url) {
        throw new HttpNotFoundException($request);
    }

    try {
        $client = new Client(['connect_timeout' => 2.0]);

        $clientResponse = $client->get($url->getName());
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    } catch (GuzzleHttp\Exception\RequestException $e) {
        $clientResponse = $e->getResponse();
        $this->get('flash')->addMessage('warning', "Проверка была выполнена успешно, но сервер ответил с ошибкой");
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $url->getId()]), 302);
    } catch (GuzzleHttp\Exception\ConnectException) {
        $this->get('flash')->addMessage('error', "Произошла ошибка при проверке, не удалось подключиться");
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $url->getId()]), 302);
    }

    $statusCode = $clientResponse->getStatusCode();

    $html = $clientResponse->getBody()->getContents();
    $crawler = new Crawler($html);

    $h1 = $crawler->filter('h1')->count() ? $crawler->filter('h1')->first()->text() : '';
    $title = $crawler->filter('title')->count() ? $crawler->filter('title')->first()->text() : '';
    $description = $crawler->filter('meta[name="description"]')->count()
        ? (string)$crawler->filter('meta[name="description"]')->first()->attr('content') : '';

    $check = new UrlCheck($statusCode, $title, $h1, $description);

    $check->setUrlId($id);
    $checksRepository = $this->get(UrlChecksRepository::class);
    $checksRepository->save($check);

    return $response->withRedirect($router->urlFor('urls.show', ['id' => $url->getId()]), 302);
})->setName('urls.checks.store');

$app->run();
