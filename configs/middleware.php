<?php

declare(strict_types=1);

use App\Clockwork\Support\Vanilla\Clockwork;
use App\Clockwork\Support\Vanilla\ClockworkMiddleware;
use App\Config;
use App\Enum\AppEnvironment;
use App\Middleware\CsrfFieldsMiddleware;
use App\Middleware\OldFormDataMiddleware;
use App\Middleware\StartSessionMiddleware;
use App\Middleware\ValidationErrorsMiddleware;
use App\Middleware\ValidationExceptionMiddleware;
use Slim\App;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return function (App $app) {
    $container = $app->getContainer();
    $config    = $container->get(Config::class);

    $app->add(MethodOverrideMiddleware::class);
    $app->add(CsrfFieldsMiddleware::class);
    $app->add('csrf');
    $app->add(TwigMiddleware::create($app, $container->get(Twig::class)));
    $app->add(OldFormDataMiddleware::class);
    $app->add(ValidationErrorsMiddleware::class);
    $app->add(ValidationExceptionMiddleware::class);
    $app->add(StartSessionMiddleware::class);

    if (AppEnvironment::isDevelopment($config->get('app_environment'))) {
        $app->add(
            new ClockworkMiddleware($container->get(Clockwork::class))
                ->withResponseFactory($app->getResponseFactory())
        );
    }

    $app->add(BodyParsingMiddleware::class);

    $app->addErrorMiddleware(
        (bool)$config->get('display_error_details'),
        (bool)$config->get('log_errors'),
        (bool)$config->get('log_error_details'),
    );
};
