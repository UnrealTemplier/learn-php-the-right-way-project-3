<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\UserProviderServiceInterface;
use App\Entity\User;
use App\Mail\SignupEmail;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class VerifyController
{
    public function __construct(
        private readonly Twig                         $twig,
        private readonly UserProviderServiceInterface $userProviderService,
        private readonly SignupEmail                  $signupEmail,
    ) {}

    public function index(Request $request, Response $response): Response
    {
        if ($request->getAttribute('user')->getVerifiedAt()) {
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        return $this->twig->render($response, 'auth/verify.twig');
    }

    public function verify(Request $request, Response $response, array $args): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        if (!hash_equals((string)$user->getId(), $args['id'])
            || !hash_equals(sha1($user->getEmail()), $args['hash'])) {
            throw new \RuntimeException('Verification failed');
        }

        if (!$user->getVerifiedAt()) {
            $this->userProviderService->verifyUser($user);
        }

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    public function resend(Request $request, Response $response): Response
    {
        $this->signupEmail->send($request->getAttribute('user'));

        return $response;
    }
}