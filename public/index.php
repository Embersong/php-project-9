<?php

require __DIR__ . '/../vendor/autoload.php';

use Hexlet\Code\Url;
use Hexlet\Code\UrlCheck;
use Hexlet\Code\UrlChecksRepository;
use Hexlet\Code\UrlsRepository;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;

session_start();

$container = new Container();
$container->set('renderer', fn() => new PhpRenderer(__DIR__ . '/../templates'));
$renderer = $container->get('renderer');
$renderer->setLayout('layout.phtml');

$container->set('flash', fn() => new Messages());

$container->set(\PDO::class, function () {
    $databaseUrl = parse_url(getenv('DATABASE_URL'));
    $username = $databaseUrl['user'];
    $password = $databaseUrl['pass'];
    $host = $databaseUrl['host'];
    $dbName = ltrim($databaseUrl['path'], '/');

    $conn = new \PDO("pgsql:dbname=$dbName;host=$host", $username, $password);
    $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    return $conn;
});

/*$initFilePath = implode('/', [dirname(__DIR__), 'database.sql']);
$initSql = file_get_contents($initFilePath);
$container->get(\PDO::class)->exec($initSql);*/

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();
$container->get('renderer')->addAttribute('router', $router);
$container->get('renderer')->addAttribute('flash', $container->get('flash')->getMessages());

$app->get('/', function ($request, $response) use ($router) {

    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('home');

$app->post('/', function ($request, $response) use ($router) {
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

    return $this->get('renderer')->render($response, 'urls/index.phtml', ['urls' => $urls]);
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

$app->post('/urls/{url_id:[0-9]+}/checks', function ($request, $response, array $args) use ($app, $router) {
    $id = $args['url_id'];
    $urlRepository = $this->get(UrlsRepository::class);
    $url = $urlRepository->find($id);

    if (!$url) {
        throw new HttpNotFoundException($request);
    }

    $check = new UrlCheck();
    $check->setUrlId($id);
    $checksRepository = $this->get(UrlChecksRepository::class);
    $checksRepository->save($check);

    return $response->withRedirect($router->urlFor('urls.show', ['id' => $url->getId()]), 302);
})->setName('urls.checks.store');

$app->run();
