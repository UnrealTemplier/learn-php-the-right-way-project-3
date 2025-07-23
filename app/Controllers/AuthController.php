<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\AuthInterface;
use App\Contracts\RequestValidatorFactoryInterface;
use App\DataObjects\LoginData;
use App\DataObjects\RegisterUserData;
use App\DataObjects\TwoFactorLoginData;
use App\Enum\AuthAttemptStatus;
use App\Exception\ValidationException;
use App\RequestValidators\Auth\LoginRequestValidator;
use App\RequestValidators\Auth\RegisterUserRequestValidator;
use App\RequestValidators\Auth\TwoFactorLoginRequestValidator;
use App\ResponseFormatter;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AuthController
{
    public function __construct(
        private readonly Twig                             $twig,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly AuthInterface                    $auth,
        private readonly ResponseFormatter                $responseFormatter,
    ) {}

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

    public function loginView(Response $response): Response
    {
        return $this->twig->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory->make(LoginRequestValidator::class)->validate($request->getParsedBody());

        $status = $this->auth->attemptLogin(new LoginData($data['email'], $data['password']));

        if ($status === AuthAttemptStatus::FAILED) {
            throw new ValidationException(['password' => ['You have entered an invalid username or password']]);
        }

        if ($status === AuthAttemptStatus::TWO_FACTOR_AUTH) {
            return $this->responseFormatter->asJson($response, ['two_factor' => true]);
        }

        return $this->responseFormatter->asJson($response, []);
    }

    public function twoFactorLogin(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory->make(TwoFactorLoginRequestValidator::class)->validate(
            $request->getParsedBody(),
        );

        if (!$this->auth->attemptTwoFactorLogin(new TwoFactorLoginData($data['email'], $data['code']))) {
            throw new ValidationException(['code' => ['Invalid code']]);
        }

        return $response;
    }

    public function logout(Response $response): Response
    {
        $this->auth->logout();

        return $response->withStatus(302)->withHeader('Location', '/login');
    }
}