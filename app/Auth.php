<?php

declare(strict_types=1);

namespace App;

use App\Contracts\SessionInterface;
use App\Contracts\UserInterface;
use App\Contracts\UserProviderServiceInterface;
use App\DataObjects\LoginData;
use App\DataObjects\RegisterUserData;
use App\DataObjects\TwoFactorLoginData;
use App\Enum\AuthAttemptStatus;
use App\Mail\SignupEmail;
use App\Mail\TwoFactorAuthEmail;
use App\Services\UserLoginCodeService;

class Auth implements Contracts\AuthInterface
{
    private const SESSION_KEY_2FA = '2fa';

    private ?UserInterface $user = null;

    public function __construct(
        private readonly UserProviderServiceInterface $userProvider,
        private readonly SessionInterface             $session,
        private readonly SignupEmail                  $signupEmail,
        private readonly TwoFactorAuthEmail           $twoFactorAuthEmail,
        private readonly UserLoginCodeService         $userLoginCodeService,
    ) {}

    public function user(): ?UserInterface
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $userId = $this->session->get('user');

        if (!$userId) {
            return null;
        }

        $user = $this->userProvider->getById($userId);

        if (!$user) {
            return null;
        }

        $this->user = $user;

        return $this->user;
    }

    public function register(RegisterUserData $data): UserInterface
    {
        $user = $this->userProvider->create($data);
        if (!$user) {
            throw new \RuntimeException('Failed to create a user');
        }

        $this->login($user);

        $this->signupEmail->send($user);

        return $user;
    }

    public function attemptLogin(LoginData $credentials): AuthAttemptStatus
    {
        $user = $this->userProvider->getByCredentials($credentials);

        if (!$user || !$this->check_credentials($user, $credentials)) {
            return AuthAttemptStatus::FAILED;
        }

        if ($user->hasTwoFactorAuthEnabled()) {
            $this->startLoginWith2FA($user);

            return AuthAttemptStatus::TWO_FACTOR_AUTH;
        }

        $this->login($user);

        return AuthAttemptStatus::SUCCESS;
    }

    public function startLoginWith2FA(UserInterface $user): void
    {
        $this->session->regenerate();
        $this->session->put($this::SESSION_KEY_2FA, $user->getId());

        $this->userLoginCodeService->deactivateAllActiveCodes($user);

        $this->twoFactorAuthEmail->send($this->userLoginCodeService->generate($user));
    }

    public function attemptTwoFactorLogin(TwoFactorLoginData $data): bool
    {
        $userId = $this->session->get($this::SESSION_KEY_2FA);
        if (!$userId) {
            return false;
        }

        $user = $this->userProvider->getById($userId);

        if (!$user || $user->getEmail() !== $data->email) {
            return false;
        }

        if (!$this->userLoginCodeService->verify($user, $data->code)) {
            return false;
        }

        $this->userLoginCodeService->deactivateAllActiveCodes($user);

        $this->session->forget($this::SESSION_KEY_2FA);

        $this->login($user);

        return true;
    }

    private function login(UserInterface $user): void
    {
        $this->user = $user;

        $this->session->regenerate();
        $this->session->put('user', $this->user->getId());
    }

    public function logout(): void
    {
        $this->session->forget('user');
        $this->session->regenerate();

        $this->user = null;
    }

    private function check_credentials(UserInterface $user, LoginData $credentials): bool
    {
        return password_verify($credentials->password, $user->getPassword());
    }
}