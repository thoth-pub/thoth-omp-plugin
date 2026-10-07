<?php

/** Test-only PHP server router. Never loaded by the plugin's production entrypoint. */

use APP\core\Application;
use ThothApi\GraphQL\Client;

if (PHP_SAPI !== 'cli-server' || getenv('THOTH_DISPOSABLE') !== '1') {
    http_response_code(404);
    exit;
}

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
// Serve application assets, but never expose the test harness or credentials.
if (strpos($path, '/tests/') !== false || strpos($path, '/.') !== false) {
    http_response_code(404);
    exit;
}
if (is_file(getcwd() . $path) && pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
    return false;
}

define('INDEX_FILE_LOCATION', getcwd() . '/index.php');
require './lib/pkp/includes/bootstrap.php';
require __DIR__ . '/cypress-client.php';
import('plugins.generic.thoth.classes.container.ThothContainer');
thothCypressCredentials();
ThothContainer::getInstance()->singleton('client', fn ($container) =>
    (new Client(['base_uri' => 'http://api:8000', 'allow_redirects' => false]))
        ->setToken($container->get('config')['token']));
Application::get()->execute();
