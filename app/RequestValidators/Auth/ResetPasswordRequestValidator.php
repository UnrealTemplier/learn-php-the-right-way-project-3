<?php

declare(strict_types=1);

namespace App\RequestValidators\Auth;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use Valitron\Validator;

class ResetPasswordRequestValidator implements RequestValidatorInterface
{
    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', ['password', 'confirmPassword']);
        $v->rule('equals', 'confirmPassword', 'password')->label('Confirm password');

        if (!$v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}