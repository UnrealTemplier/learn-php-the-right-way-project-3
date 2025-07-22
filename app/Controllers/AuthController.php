<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\AuthInterface;
use App\Contracts\RequestValidatorFactoryInterface;
use App\DataObjects\LoginData;
use App\DataObjects\RegisterUserData;
use App\Exception\ValidationException;
use App\RequestValidators\Auth\LoginRequestValidator;
use App\RequestValidators\Auth\RegisterUserRequestValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AuthController
{
    public function __construct(
        private readonly Twig                             $twig,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly AuthInterface                    $auth,
    ) {}

    public function loginView(Response $response): Response
    {
        return $this->twig->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory
            ->make(LoginRequestValidator::class)
            ->validate($request->getParsedBody());

        if (!$this->auth->attemptLogin(
            new LoginData(
                $data['email'],
                $data['password'],
            ),
        )) {
            $message = 'Email or password are incorrect';
            throw new ValidationException(['email' => [$message], 'password' => [$message]]);
        }

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    public function logout(Response $response): Response
    {
        $this->auth->logout();

        return $response->withStatus(302)->withHeader('Location', '/login');
    }

    public function registerView(Response $response): Response
    {
        return $this->twig->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory
            ->make(RegisterUserRequestValidator::class)
            ->validate($request->getParsedBody());

        $this->auth->register(
            new RegisterUserData(
                $data['name'],
                $data['email'],
                $data['password'],
            ),
        );

        return $response->withStatus(302)->withHeader('Location', '/');
    }
}