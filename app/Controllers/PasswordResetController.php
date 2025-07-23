<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\RequestValidatorFactoryInterface;
use App\Contracts\UserProviderServiceInterface;
use App\DataObjects\LoginData;
use App\Exception\ValidationException;
use App\Mail\ForgotPasswordEmail;
use App\RequestValidators\Auth\ForgotPasswordRequestValidator;
use App\RequestValidators\Auth\ResetPasswordRequestValidator;
use App\Services\PasswordResetService;
use Clockwork\Request\Log;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class PasswordResetController
{
    public function __construct(
        private readonly Twig                             $twig,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly UserProviderServiceInterface     $userProviderService,
        private readonly PasswordResetService             $passwordResetService,
        private readonly ForgotPasswordEmail              $forgotPasswordEmail,
    ) {}

    public function showForgotPasswordForm(Response $response): Response
    {
        return $this->twig->render($response, 'auth/forgot_password.twig');
    }

    public function handleForgotPasswordRequest(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory->make(ForgotPasswordRequestValidator::class)->validate(
            $request->getParsedBody(),
        );

        $email = $data['email'];

        $user = $this->userProviderService->getByCredentials($email);

        if ($user) {
            $this->passwordResetService->deactivateAllPasswordResets($email);
            $passwordReset = $this->passwordResetService->generate($email);
            $this->forgotPasswordEmail->send($passwordReset);
        }

        return $response;
    }

    public function showResetPasswordForm(Response $response, array $args): Response
    {
        $token         = $args['token'];
        $passwordReset = $this->passwordResetService->findByToken($token);

        if (!$passwordReset) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        return $this->twig->render($response, 'auth/reset_password.twig', ['token' => $token]);
    }

    public function resetPassword(Request $request, Response $response, array $args): Response
    {
        $data = $this->requestValidatorFactory->make(ResetPasswordRequestValidator::class)->validate(
            $request->getParsedBody(),
        );

        $token         = $args['token'];
        $passwordReset = $this->passwordResetService->findByToken($token);

        if (!$passwordReset) {
            throw new ValidationException(['confirmPassword' => ['Invalid token']]);
        }

        $user = $this->userProviderService->getByCredentials($passwordReset->getEmail());

        if (!$user) {
            throw new ValidationException(['confirmPassword' => ['Invalid token']]);
        }

        $this->passwordResetService->updatePassword($user, $data['password']);

        return $response;
    }
}
