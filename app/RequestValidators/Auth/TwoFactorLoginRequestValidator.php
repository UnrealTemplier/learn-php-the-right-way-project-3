<?php

declare(strict_types=1);

namespace App\RequestValidators\Auth;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use Valitron\Validator;

class TwoFactorLoginRequestValidator implements RequestValidatorInterface
{
    public function validate(array $data): array
    {
        $v = new Validator($data);

        $v->rule('required', ['email', 'code']);
        $v->rule('email', 'email');

        if (!$v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}